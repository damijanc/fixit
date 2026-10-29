<?php

declare(strict_types=1);

use App\Services\AI\Provider\ProviderCatalog;
use App\Services\AI\Provider\ProviderDefinition;
use App\Services\AI\Provider\SymfonyAiPlatformFactory;
use Symfony\AI\Agent\Agent;
use Symfony\AI\Platform\Message\Content\Image;
use Symfony\AI\Platform\Message\Content\Text;
use Symfony\AI\Platform\Message\UserMessage;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\TestCase;

uses(TestCase::class);

/**
 * @return array{method: string, url: string, options: array<string, mixed>}
 */
function invokeProvider(string $providerId, string $modelId, string $responseJson): array
{
    $catalog = ProviderCatalog::fromConfig((array) config('ai'));
    $provider = $catalog->get($providerId);

    expect($provider)->toBeInstanceOf(ProviderDefinition::class);

    $captured = [];

    $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured, $responseJson): MockResponse {
        $captured = ['method' => $method, 'url' => $url, 'options' => $options];

        return new MockResponse($responseJson, [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json'],
        ]);
    });

    $platform = (new SymfonyAiPlatformFactory($client))->create($provider, 'sk-provider-test-key-123');
    $content = (new Agent($platform, $modelId))->call('ping')->getContent();

    expect($content)->toBe('ok');

    return $captured;
}

function invokeProviderWithImage(string $providerId, string $modelId, string $responseJson): array
{
    $catalog = ProviderCatalog::fromConfig((array) config('ai'));
    $provider = $catalog->get($providerId);

    expect($provider)->toBeInstanceOf(ProviderDefinition::class);

    $captured = [];

    $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured, $responseJson): MockResponse {
        $captured = ['method' => $method, 'url' => $url, 'options' => $options];

        return new MockResponse($responseJson, [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json'],
        ]);
    });

    $platform = (new SymfonyAiPlatformFactory($client))->create($provider, 'sk-provider-test-key-123');
    $content = (new Agent($platform, $modelId))->call(new UserMessage(
        new Text('What is in this photo?'),
        Image::fromFile(base_path('tests/Fixtures/vision-photo.jpg')),
    ))->getContent();

    expect($content)->toBe('ok');

    return $captured;
}

function chatCompletionResponse(): string
{
    return json_encode([
        'id' => 'chatcmpl-test',
        'object' => 'chat.completion',
        'choices' => [[
            'index' => 0,
            'message' => ['role' => 'assistant', 'content' => 'ok'],
            'finish_reason' => 'stop',
        ]],
        'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1, 'total_tokens' => 2],
    ], JSON_THROW_ON_ERROR);
}

function anthropicResponse(): string
{
    return json_encode([
        'id' => 'msg_test',
        'type' => 'message',
        'role' => 'assistant',
        'model' => 'claude-opus-5-5',
        'content' => [['type' => 'text', 'text' => 'ok']],
        'stop_reason' => 'end_turn',
        'stop_sequence' => null,
        'usage' => ['input_tokens' => 1, 'output_tokens' => 1],
    ], JSON_THROW_ON_ERROR);
}

function openAiResponsesResponse(): string
{
    return json_encode([
        'id' => 'resp_test',
        'object' => 'response',
        'created_at' => 1700000000,
        'status' => 'completed',
        'model' => 'gpt-4.1',
        'output' => [[
            'id' => 'msg_test',
            'type' => 'message',
            'status' => 'completed',
            'role' => 'assistant',
            'content' => [['type' => 'output_text', 'text' => 'ok', 'annotations' => []]],
        ]],
        'usage' => ['input_tokens' => 1, 'output_tokens' => 1, 'total_tokens' => 2],
    ], JSON_THROW_ON_ERROR);
}

function requestBody(array $request): array
{
    return json_decode((string) $request['options']['body'], true, flags: JSON_THROW_ON_ERROR);
}

function requestBearer(array $request): ?string
{
    $header = requestHeader($request, 'authorization');

    return is_string($header) ? preg_replace('/^Bearer\s+/i', '', $header) : null;
}

function requestHeader(array $request, string $name): ?string
{
    foreach (($request['options']['headers'] ?? []) as $header) {
        if (! is_string($header)) {
            continue;
        }

        [$headerName, $value] = array_pad(explode(':', $header, 2), 2, '');

        if (strtolower(trim($headerName)) === strtolower($name)) {
            return trim($value);
        }
    }

    return null;
}

it('constructs the DeepSeek generic bridge with the DeepSeek endpoint', function () {
    $request = invokeProvider('deepseek', 'deepseek-flash', chatCompletionResponse());

    expect($request['method'])->toBe('POST');
    expect($request['url'])->toBe('https://api.deepseek.com/v1/chat/completions');
    expect(requestBearer($request))->toBe('sk-provider-test-key-123');
    expect(requestBody($request)['model'])->toBe('deepseek-flash');
});

it('constructs the OpenRouter generic bridge with the OpenRouter endpoint', function () {
    $request = invokeProvider('openrouter', 'anthropic/claude-sonnet-4.5', chatCompletionResponse());

    expect($request['url'])->toBe('https://openrouter.ai/api/v1/chat/completions');
    expect(requestBearer($request))->toBe('sk-provider-test-key-123');
    expect(requestBody($request)['model'])->toBe('anthropic/claude-sonnet-4.5');
});

it('constructs the OpenAI model through OpenRouter when OpenRouter is selected', function () {
    $request = invokeProvider('openrouter', 'openai/gpt-4.1', chatCompletionResponse());

    expect($request['url'])->toBe('https://openrouter.ai/api/v1/chat/completions');
    expect(requestBody($request)['model'])->toBe('openai/gpt-4.1');
});

it('constructs the Hugging Face bridge with the router endpoint', function () {
    $request = invokeProvider('huggingface', 'Qwen/Qwen3-VL-30B-A3B-Instruct', chatCompletionResponse());

    expect($request['url'])->toBe('https://router.huggingface.co/v1/chat/completions');
    expect(requestBearer($request))->toBe('sk-provider-test-key-123');
    expect(requestBody($request)['model'])->toBe('Qwen/Qwen3-VL-30B-A3B-Instruct:deepinfra');
});

it('routes Hugging Face photos to an inference provider that accepts full-size images', function () {
    // The router's default pick (novita) answered 503 for 12 MP phone photos;
    // deepinfra serves every curated model and accepted the same photo.
    $request = invokeProviderWithImage('huggingface', 'google/gemma-4-31B-it', chatCompletionResponse());

    expect(requestBody($request)['model'])->toBe('google/gemma-4-31B-it:deepinfra');
});

it('constructs the Anthropic bridge with Anthropic headers', function () {
    $request = invokeProvider('anthropic', 'claude-opus-5-5', anthropicResponse());

    expect($request['url'])->toBe('https://api.anthropic.com/v1/messages');
    expect(requestHeader($request, 'x-api-key'))->toBe('sk-provider-test-key-123');
    expect(requestHeader($request, 'anthropic-version'))->toBe('2023-06-01');
    expect(requestBody($request)['model'])->toBe('claude-opus-5-5');
});

it('constructs the OpenAI Responses bridge for direct OpenAI', function () {
    $request = invokeProvider('openai', 'gpt-4.1', openAiResponsesResponse());

    expect($request['url'])->toBe('https://api.openai.com/v1/responses');
    expect(requestBearer($request))->toBe('sk-provider-test-key-123');
    expect(requestBody($request)['model'])->toBe('gpt-4.1');
});

it('sends an image through the OpenRouter generic bridge as a data URL', function () {
    $request = invokeProviderWithImage('openrouter', 'anthropic/claude-sonnet-4.5', chatCompletionResponse());
    $payload = json_encode(requestBody($request));

    expect($payload)
        ->toContain('image_url')
        ->toContain('data:image')
        ->toContain('base64,');
});

it('sends an image through the Hugging Face bridge as a data URL', function () {
    $request = invokeProviderWithImage('huggingface', 'Qwen/Qwen3-VL-30B-A3B-Instruct', chatCompletionResponse());
    $payload = json_encode(requestBody($request));

    expect($payload)
        ->toContain('image_url')
        ->toContain('data:image')
        ->toContain('base64,');
});

it('sends an image through the Anthropic bridge as a base64 content block', function () {
    $request = invokeProviderWithImage('anthropic', 'claude-opus-5-5', anthropicResponse());
    $payload = json_encode(requestBody($request));

    expect($payload)
        ->toContain('"type":"image"')
        ->toContain('"type":"base64"')
        ->toContain('"media_type":"image\/jpeg"');
});

it('sends an image through the OpenAI Responses bridge as an input image', function () {
    $request = invokeProviderWithImage('openai', 'gpt-4.1', openAiResponsesResponse());
    $payload = json_encode(requestBody($request));

    expect($payload)
        ->toContain('input_image')
        ->toContain('data:image')
        ->toContain('base64,');
});
