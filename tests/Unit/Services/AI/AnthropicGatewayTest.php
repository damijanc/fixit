<?php

declare(strict_types=1);

use App\Services\AI\AiRequestFailedException;
use App\Services\AI\ModelGateway;
use App\Services\AI\Provider\ProviderCatalog;
use App\Services\AI\Provider\SymfonyAiPlatformFactory;
use App\Services\Diagnosis\DiagnosisReply;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\Support\TestGateway;
use Tests\TestCase;

uses(TestCase::class);

/**
 * A gateway running the real Symfony AI Anthropic bridge against a mocked
 * Messages API. $requests collects every decoded request body.
 *
 * @param  array<string, mixed>  $response
 * @param  list<array<string, mixed>>  $requests
 */
function anthropicGateway(array $response, array &$requests, string $model = 'claude-opus-5-5'): ModelGateway
{
    $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests, $response): MockResponse {
        $requests[] = json_decode((string) $options['body'], true);

        return new MockResponse((string) json_encode($response), [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json'],
        ]);
    });

    $catalog = ProviderCatalog::fromConfig((array) config('ai'));
    $platform = (new SymfonyAiPlatformFactory($client))->create($catalog->get('anthropic'), 'sk-ant-test-key');

    return TestGateway::make($platform, provider: 'anthropic', model: $model, key: 'sk-ant-test-key');
}

/**
 * @param  list<array<string, mixed>>  $content
 * @return array<string, mixed>
 */
function claudeMessage(array $content, string $stopReason = 'end_turn', string $model = 'claude-opus-5-5'): array
{
    return [
        'id' => 'msg_test',
        'type' => 'message',
        'role' => 'assistant',
        'model' => $model,
        'content' => $content,
        'stop_reason' => $stopReason,
        'stop_details' => null,
        'usage' => ['input_tokens' => 42, 'output_tokens' => 7],
    ];
}

it('offers the current Claude models with Opus 5.5 first', function () {
    $models = array_keys(ProviderCatalog::fromConfig((array) config('ai'))->get('anthropic')->models);

    expect($models)->toBe(['claude-opus-5-5', 'claude-sonnet-5-5', 'claude-haiku-5-5']);
});

it('reads the answer when Claude returns a thinking block before the text', function () {
    // Current Claude models always think; with display "omitted" the thinking
    // block is empty but still precedes the text, so the bridge returns a
    // multi-part result instead of a string.
    $requests = [];
    $gateway = anthropicGateway(claudeMessage([
        ['type' => 'thinking', 'thinking' => '', 'signature' => 'sig-test'],
        ['type' => 'text', 'text' => 'FixIt AI connectivity OK'],
    ]), $requests);

    expect($gateway->ask('Reply with exactly: FixIt AI connectivity OK'))->toBe('FixIt AI connectivity OK');
});

it('gives Claude room to think and sets the effort explicitly', function () {
    $requests = [];
    anthropicGateway(claudeMessage([['type' => 'text', 'text' => 'ok']]), $requests)
        ->ask('ping', 'You are FixIt.');

    // The bridge default of 1000 tokens is shared with thinking and truncates answers.
    expect($requests[0]['model'])->toBe('claude-opus-5-5')
        ->and($requests[0]['max_tokens'])->toBe(16000)
        ->and($requests[0]['output_config']['effort'])->toBe('medium')
        ->and($requests[0])->not->toHaveKeys(['temperature', 'top_p', 'top_k', 'thinking']);
});

it('sends the photo to Claude as a base64 image block', function () {
    $requests = [];
    anthropicGateway(claudeMessage([['type' => 'text', 'text' => 'A kettle.']]), $requests)
        ->askWithImage('What is in this photo?', base_path('tests/Fixtures/vision-photo.jpg'));

    $content = $requests[0]['messages'][0]['content'];

    expect(collect($content)->pluck('type')->all())->toContain('image', 'text')
        ->and(collect($content)->firstWhere('type', 'image')['source']['type'])->toBe('base64');
});

it('explains a Claude refusal instead of a connection error', function () {
    $requests = [];
    $gateway = anthropicGateway(claudeMessage([
        ['type' => 'text', 'text' => 'I can'],
    ], stopReason: 'refusal'), $requests);

    expect(fn () => $gateway->ask('ping'))
        ->toThrow(AiRequestFailedException::class, 'Anthropic declined this request');
});

it('explains a refusal that carries no text', function () {
    $requests = [];
    $gateway = anthropicGateway(claudeMessage([], stopReason: 'refusal'), $requests);

    expect(fn () => $gateway->ask('ping'))
        ->toThrow(AiRequestFailedException::class, 'Anthropic returned no answer');
});

it('asks Claude for a structured diagnosis when a schema is given', function () {
    $requests = [];
    $json = (string) json_encode(['object' => 'Kettle']);

    $reply = anthropicGateway(claudeMessage([['type' => 'text', 'text' => $json]]), $requests)
        ->askWithImage('What is in this photo?', base_path('tests/Fixtures/vision-photo.jpg'), 'You are FixIt.', DiagnosisReply::schema());

    expect($reply)->toBe($json)
        ->and($requests[0]['output_config']['format']['type'])->toBe('json_schema')
        ->and($requests[0]['output_config']['format']['schema']['required'])->toContain('safe_checks', 'hazard')
        ->and($requests[0]['output_config']['effort'])->toBe('medium');
});

it('logs token usage and cache reads for every call', function () {
    Log::spy();
    $requests = [];
    $response = claudeMessage([['type' => 'text', 'text' => 'ok']]);
    $response['usage'] = ['input_tokens' => 1800, 'output_tokens' => 240, 'cache_read_input_tokens' => 1500, 'cache_creation_input_tokens' => 0];

    anthropicGateway($response, $requests)->ask('ping');

    Log::shouldHaveReceived('info')->withArgs(
        fn (string $message, array $context): bool => $message === 'AI usage'
            && $context === [
                'provider' => 'Anthropic',
                'model' => 'claude-opus-5-5',
                'input_tokens' => 1800,
                'output_tokens' => 240,
                'cache_read_tokens' => 1500,
                'cache_creation_tokens' => 0,
            ],
    );
});
