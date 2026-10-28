<?php

declare(strict_types=1);

use App\Services\AI\AiRequestFailedException;
use App\Services\AI\ModelGateway;
use App\Services\AI\Provider\ProviderCatalog;
use App\Services\AI\Provider\ProviderRegistry;
use App\Services\AI\Provider\ProviderSettings;
use App\Services\AI\Provider\SymfonyAiPlatformFactory;
use App\Services\AI\SymfonyAgentFactory;
use Psr\Log\NullLogger;
use Symfony\AI\Platform\Exception\AuthenticationException;
use Symfony\AI\Platform\Exception\BadRequestException;
use Symfony\AI\Platform\Exception\ExceedContextSizeException;
use Symfony\AI\Platform\Exception\ModelNotFoundException;
use Symfony\AI\Platform\Exception\RateLimitExceededException;
use Symfony\AI\Platform\Exception\ServerException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\Support\InMemoryProviderSettingsStore;
use Tests\TestCase;

uses(TestCase::class);

it('maps Symfony AI exception types to user-safe messages', function (Throwable $previous, string $expected) {
    $exception = AiRequestFailedException::fromProvider($previous, 'DeepSeek');

    expect($exception->getMessage())->toContain($expected)
        ->and($exception->getPrevious())->toBe($previous);
})->with([
    'authentication' => [new AuthenticationException('Invalid key'), 'DeepSeek rejected the API key (401)'],
    'bad request' => [new BadRequestException('Bad Request'), 'DeepSeek rejected the request (HTTP 400)'],
    'model not found' => [new ModelNotFoundException('Not Found'), 'could not find the endpoint or model (404)'],
    'rate limit' => [new RateLimitExceededException(null), 'DeepSeek rate limit reached (429). Wait a moment'],
    'rate limit with retry-after' => [new RateLimitExceededException(30), 'Wait 30 seconds and try again.'],
    'server error' => [new ServerException(503), 'DeepSeek is unavailable (HTTP 503)'],
    'server error without status' => [new ServerException, 'DeepSeek is unavailable (HTTP 500)'],
    'context too long' => [new ExceedContextSizeException('context_length_exceeded'), 'conversation is too long'],
    'transport failure' => [new RuntimeException('Could not resolve host'), 'could not be reached'],
]);

it('adds the vision hint when a bad request mentions images', function () {
    $exception = AiRequestFailedException::fromProvider(
        new BadRequestException('Unsupported image input for this model'),
        'DeepSeek',
    );

    expect($exception->getMessage())->toContain('may not accept images');
});

it('never includes the provider error body in the message', function () {
    $exception = AiRequestFailedException::fromProvider(
        new AuthenticationException('secret upstream body sk-leaked'),
        'DeepSeek',
    );

    expect($exception->getMessage())
        ->not->toContain('secret upstream body')
        ->not->toContain('sk-leaked');
});

it('maps real provider HTTP errors from the Symfony AI bridge', function (int $status, string $expected) {
    $client = new MockHttpClient(new MockResponse(
        json_encode(['error' => ['message' => 'secret upstream body']]),
        ['http_code' => $status, 'response_headers' => ['content-type' => 'application/json']],
    ));

    $registry = new ProviderRegistry(
        ProviderCatalog::fromConfig((array) config('ai')),
        new InMemoryProviderSettingsStore(new ProviderSettings('deepseek', 'deepseek-flash', 'sk-test-key-123')),
        new SymfonyAiPlatformFactory($client),
    );
    $gateway = new ModelGateway(new SymfonyAgentFactory($registry), $registry, new NullLogger);

    expect(fn () => $gateway->ask('ping'))->toThrow(AiRequestFailedException::class, $expected);
})->with([
    'unauthorized' => [401, 'DeepSeek rejected the API key (401)'],
    'rate limited' => [429, 'DeepSeek rate limit reached (429)'],
    'server error' => [502, 'DeepSeek is unavailable (HTTP 502)'],
]);
