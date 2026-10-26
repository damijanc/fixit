<?php

declare(strict_types=1);

use App\Services\AI\AiRequestFailedException;
use App\Services\AI\MissingApiKeyException;
use App\Services\AI\ModelGateway;
use Symfony\AI\Platform\ModelCatalog\FallbackModelCatalog;
use Symfony\AI\Platform\Platform;
use Symfony\AI\Platform\Provider;
use Symfony\AI\Platform\Test\MockModelClient;
use Symfony\AI\Platform\Test\MockPlatformFactory;
use Symfony\AI\Platform\Test\MockResultConverter;
use Tests\TestCase;

uses(TestCase::class);

it('returns model text through the gateway', function () {
    $platform = MockPlatformFactory::createPlatform('FixIt AI connectivity OK');

    $gateway = new ModelGateway($platform, 'test-model');

    expect($gateway->ask('ping'))->toBe('FixIt AI connectivity OK');
});

it('sends the prompt through Symfony AI', function () {
    $client = new MockModelClient('pong');
    $platform = new Platform([
        new Provider('mock', [$client], [new MockResultConverter], new FallbackModelCatalog),
    ]);

    $gateway = new ModelGateway($platform, 'test-model');
    $gateway->ask('ping');

    expect($client->getCalls())->toHaveCount(1);
    expect($client->getCalls()[0]['model']->getName())->toBe('test-model');
});

it('fails safely when no api key is configured', function () {
    config(['ai.api_key' => null, 'ai.base_url' => 'https://example.test']);

    $gateway = new ModelGateway;

    expect(fn () => $gateway->ask('ping'))->toThrow(MissingApiKeyException::class);
});

it('fails safely when no model is configured', function () {
    config(['ai.model' => '', 'ai.api_key' => 'test-key']);

    $platform = MockPlatformFactory::createPlatform('unused');
    $gateway = new ModelGateway($platform);

    expect(fn () => $gateway->ask('ping'))->toThrow(AiRequestFailedException::class);
});

it('wraps provider failures without leaking provider details', function () {
    $platform = MockPlatformFactory::createPlatform(static function (): never {
        throw new RuntimeException('secret provider response body');
    });

    $gateway = new ModelGateway($platform, 'test-model');

    try {
        $gateway->ask('ping');
    } catch (AiRequestFailedException $exception) {
        expect($exception->getMessage())
            ->not->toContain('secret provider response body')
            ->toContain('could not be reached');

        return;
    }

    $this->fail('Expected an AiRequestFailedException.');
});
