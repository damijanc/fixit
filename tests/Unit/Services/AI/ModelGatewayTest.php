<?php

declare(strict_types=1);

use App\Services\AI\AiRequestFailedException;
use App\Services\AI\MissingApiKeyException;
use App\Services\AI\Provider\ProviderCatalog;
use App\Services\AI\UnsupportedModelCapabilityException;
use App\Services\Diagnosis\DiagnosisReply;
use Illuminate\Support\Facades\Log;
use Symfony\AI\Platform\Exception\AuthenticationException;
use Symfony\AI\Platform\Exception\BadRequestException;
use Symfony\AI\Platform\Message\AssistantMessage;
use Symfony\AI\Platform\Message\Content\Text;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\SystemMessage;
use Symfony\AI\Platform\Message\UserMessage;
use Symfony\AI\Platform\ModelCatalog\FallbackModelCatalog;
use Symfony\AI\Platform\Platform;
use Symfony\AI\Platform\Provider;
use Symfony\AI\Platform\Test\MockModelClient;
use Symfony\AI\Platform\Test\MockPlatformFactory;
use Symfony\AI\Platform\Test\MockResultConverter;
use Tests\Support\FakePlatformFactory;
use Tests\Support\TestGateway;
use Tests\TestCase;

uses(TestCase::class);

function recordingPlatform(MockModelClient $client): Platform
{
    return new Platform([
        new Provider('mock', [$client], [new MockResultConverter], new FallbackModelCatalog),
    ]);
}

it('returns model text through the gateway', function () {
    $gateway = TestGateway::make(MockPlatformFactory::createPlatform('FixIt AI connectivity OK'));

    expect($gateway->ask('ping'))->toBe('FixIt AI connectivity OK');
});

it('sends the selected model through Symfony AI', function () {
    $client = new MockModelClient('pong');

    TestGateway::make(recordingPlatform($client))->ask('ping');

    expect($client->getCalls())->toHaveCount(1);
    expect($client->getCalls()[0]['model']->getName())->toBe('deepseek-flash');
});

it('fails safely when the secure settings store has no key', function () {
    $gateway = TestGateway::make(MockPlatformFactory::createPlatform('unused'), key: null);

    expect(fn () => $gateway->ask('ping'))
        ->toThrow(MissingApiKeyException::class, 'No API key saved for DeepSeek');
});

it('labels a missing key with the selected provider', function () {
    $gateway = TestGateway::make(
        MockPlatformFactory::createPlatform('unused'),
        provider: 'openrouter',
        model: 'openai/gpt-4.1',
        key: null,
    );

    expect(fn () => $gateway->ask('ping'))
        ->toThrow(MissingApiKeyException::class, 'No API key saved for OpenRouter');
});

it('wraps provider failures without leaking provider details', function () {
    $platform = MockPlatformFactory::createPlatform(static function (): never {
        throw new RuntimeException('secret provider response body');
    });

    $gateway = TestGateway::make($platform);

    try {
        $gateway->ask('ping');
    } catch (AiRequestFailedException $exception) {
        expect($exception->getMessage())
            ->not->toContain('secret provider response body')
            ->not->toContain('test-key')
            ->toContain('could not be reached');

        return;
    }

    $this->fail('Expected an AiRequestFailedException.');
});

it('surfaces a rejected key without leaking the response body or key', function () {
    $platform = MockPlatformFactory::createPlatform(static function (): never {
        throw new AuthenticationException('Unauthorized: secret upstream body');
    });

    $gateway = TestGateway::make($platform, key: 'sk-secret-provider-key');

    try {
        $gateway->ask('ping');
    } catch (AiRequestFailedException $exception) {
        expect($exception->getMessage())
            ->toContain('DeepSeek rejected the API key (401)')
            ->not->toContain('secret upstream body')
            ->not->toContain('sk-secret-provider-key');

        return;
    }

    $this->fail('Expected an AiRequestFailedException.');
});

it('does not write the provider key into logs', function () {
    Log::spy();

    $platform = MockPlatformFactory::createPlatform(static function (): never {
        throw new AuthenticationException('Unauthorized upstream response');
    });

    $gateway = TestGateway::make($platform, key: 'sk-secret-provider-key-98765');

    try {
        $gateway->ask('ping');
    } catch (AiRequestFailedException) {
        // Expected.
    }

    Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context): bool {
        return $message === 'AI request failed'
            && ! str_contains(json_encode($context), 'sk-secret-provider-key-98765');
    })->once();
});

it('explains an image-specific provider rejection', function () {
    $platform = MockPlatformFactory::createPlatform(static function (): never {
        throw new BadRequestException('Unsupported image input for this model');
    });

    $gateway = TestGateway::make($platform);

    try {
        $gateway->askWithImage('What is this?', base_path('tests/Fixtures/vision-photo.jpg'));
    } catch (AiRequestFailedException $exception) {
        expect($exception->getMessage())
            ->toContain('may not accept images')
            ->toContain('vision-capable');

        return;
    }

    $this->fail('Expected an AiRequestFailedException.');
});

it('sends an image as a base64 data URL for vision', function () {
    $client = new MockModelClient('I see a small blue test image.');

    $reply = TestGateway::make(recordingPlatform($client))->askWithImage(
        'What is in this photo?',
        base_path('tests/Fixtures/vision-photo.jpg'),
    );

    expect($reply)->toBe('I see a small blue test image.');
    expect($client->getCalls())->toHaveCount(1);

    $payload = json_encode($client->getCalls()[0]['payload']);

    expect($payload)
        ->toContain('image_url')
        ->toContain('data:image')
        ->toContain('base64,')
        ->toContain('What is in this photo?');
});

it('sends the system prompt through the agent', function () {
    $client = new MockModelClient('ok');

    TestGateway::make(recordingPlatform($client))->ask('ping', 'You are FixIt.');

    $payload = json_encode($client->getCalls()[0]['payload']);

    expect($payload)
        ->toContain('"role":"system"')
        ->toContain('You are FixIt.')
        ->toContain('ping');
});

it('rejects an unreadable image before sending it', function () {
    $gateway = TestGateway::make(MockPlatformFactory::createPlatform('unused'));

    expect(fn () => $gateway->askWithImage('What is this?', '/does/not/exist.jpg'))
        ->toThrow(AiRequestFailedException::class, 'could not be read');
});

it('rejects a photo before calling a model that cannot analyse images', function () {
    $catalog = ProviderCatalog::fromConfig([
        'default_provider' => 'test',
        'providers' => [
            'test' => [
                'name' => 'Test Provider',
                'driver' => 'generic',
                'base_url' => 'https://example.test',
                'key_hint' => '',
                'models' => [
                    'text-only-model' => ['label' => 'Text only', 'vision' => false],
                ],
            ],
        ],
    ]);

    $client = new MockModelClient('should not be called');
    $gateway = TestGateway::make(recordingPlatform($client), provider: 'test', model: 'text-only-model', catalog: $catalog);

    expect(fn () => $gateway->askWithImage('What is this?', base_path('tests/Fixtures/vision-photo.jpg')))
        ->toThrow(UnsupportedModelCapabilityException::class, 'text-only-model from Test Provider cannot analyse photos');
    expect($client->getCalls())->toBe([]);
});

it('sends prior turns when continuing a conversation', function () {
    $client = new MockModelClient('Next step: check the filter.');

    $reply = TestGateway::make(recordingPlatform($client))->continueConversation(new MessageBag(
        new SystemMessage('safety rules'),
        new UserMessage(new Text('earlier photo prompt')),
        new AssistantMessage(new Text('earlier assistant result')),
        new UserMessage(new Text('the light is on but there is no water')),
    ));

    expect($reply)->toBe('Next step: check the filter.');

    $payload = json_encode($client->getCalls()[0]['payload']);

    expect($payload)
        ->toContain('earlier assistant result')
        ->toContain('the light is on but there is no water');
});

it('builds the platform for the selected provider with only that provider key', function () {
    $platform = MockPlatformFactory::createPlatform('pong');
    $factory = new FakePlatformFactory($platform);

    $gateway = TestGateway::make(
        $platform,
        provider: 'openrouter',
        model: 'openai/gpt-4.1',
        key: 'or-key-12345678',
        factory: $factory,
    );

    expect($gateway->ask('ping'))->toBe('pong');
    expect($factory->calls)->toBe([
        ['provider' => 'openrouter', 'key' => 'or-key-12345678'],
    ]);
});

it('does not send a schema to a model without structured outputs', function () {
    $client = new MockModelClient('The pump may be blocked.');

    $reply = TestGateway::make(recordingPlatform($client))->askWithImage(
        'What is in this photo?',
        base_path('tests/Fixtures/vision-photo.jpg'),
        null,
        DiagnosisReply::schema(),
    );

    expect($reply)->toBe('The pump may be blocked.')
        ->and($client->getCalls()[0]['options'])->not->toHaveKey('response_format');
});
