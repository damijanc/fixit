<?php

declare(strict_types=1);

use App\Services\AI\AiRequestFailedException;
use App\Services\AI\MissingApiKeyException;
use App\Services\AI\ModelGateway;
use App\Services\AI\Provider\ProviderCatalog;
use App\Services\AI\Provider\ProviderRegistry;
use App\Services\AI\Provider\ProviderSettings;
use App\Services\AI\UnsupportedModelCapabilityException;
use Illuminate\Support\Facades\Log;
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
use Tests\Support\InMemoryProviderSettingsStore;
use Tests\Support\TestGateway;
use Tests\TestCase;

uses(TestCase::class);

it('returns model text through the gateway', function () {
    $gateway = TestGateway::make(MockPlatformFactory::createPlatform('FixIt AI connectivity OK'));

    expect($gateway->ask('ping'))->toBe('FixIt AI connectivity OK');
});

it('sends the selected model through Symfony AI', function () {
    $client = new MockModelClient('pong');
    $platform = new Platform([
        new Provider('mock', [$client], [new MockResultConverter], new FallbackModelCatalog),
    ]);

    $gateway = TestGateway::make($platform);
    $gateway->ask('ping');

    expect($client->getCalls())->toHaveCount(1);
    expect($client->getCalls()[0]['model']->getName())->toBe('deepseek-flash');
});

it('fails safely when the secure settings store has no key', function () {
    $catalog = ProviderCatalog::fromConfig((array) config('ai'));
    $registry = new ProviderRegistry(
        $catalog,
        new InMemoryProviderSettingsStore(new ProviderSettings('deepseek', 'deepseek-flash')),
        new FakePlatformFactory(MockPlatformFactory::createPlatform('unused')),
    );

    $gateway = new ModelGateway($registry);

    expect(fn () => $gateway->ask('ping'))
        ->toThrow(MissingApiKeyException::class, 'No API key saved for DeepSeek');
});

it('labels a missing key with the selected provider', function () {
    $catalog = ProviderCatalog::fromConfig((array) config('ai'));
    $registry = new ProviderRegistry(
        $catalog,
        new InMemoryProviderSettingsStore(new ProviderSettings('openrouter', 'openai/gpt-4.1')),
        new FakePlatformFactory(MockPlatformFactory::createPlatform('unused')),
    );

    $gateway = new ModelGateway($registry);

    expect(fn () => $gateway->ask('ping'))
        ->toThrow(MissingApiKeyException::class, 'No API key saved for OpenRouter');
});

it('fails safely when no model is configured', function () {
    $gateway = new ModelGateway(
        app(ProviderRegistry::class),
        MockPlatformFactory::createPlatform('unused'),
        '',
    );

    expect(fn () => $gateway->ask('ping'))->toThrow(AiRequestFailedException::class, 'No AI model is configured');
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

it('surfaces the provider HTTP status without leaking the response body or key', function () {
    $platform = MockPlatformFactory::createPlatform(static function (): never {
        throw new RuntimeException('Unauthorized: secret upstream body', 401);
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
        throw new RuntimeException('Unauthorized upstream response', 401);
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
        throw new RuntimeException('Unsupported image input for this model', 400);
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
    $platform = new Platform([
        new Provider('mock', [$client], [new MockResultConverter], new FallbackModelCatalog),
    ]);

    $gateway = TestGateway::make($platform);

    $reply = $gateway->askWithImage(
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

    $platform = MockPlatformFactory::createPlatform('should not be called');
    $registry = new ProviderRegistry(
        $catalog,
        new InMemoryProviderSettingsStore(new ProviderSettings('test', 'text-only-model', 'test-key')),
        new FakePlatformFactory($platform),
    );
    $gateway = new ModelGateway($registry, $platform, 'text-only-model');

    expect(fn () => $gateway->askWithImage('What is this?', base_path('tests/Fixtures/vision-photo.jpg')))
        ->toThrow(UnsupportedModelCapabilityException::class, 'text-only-model from Test Provider cannot analyse photos');
});

it('sends prior turns when continuing a conversation', function () {
    $client = new MockModelClient('Next step: check the filter.');
    $platform = new Platform([
        new Provider('mock', [$client], [new MockResultConverter], new FallbackModelCatalog),
    ]);

    $gateway = TestGateway::make($platform);

    $reply = $gateway->continueConversation(new MessageBag(
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
    $catalog = ProviderCatalog::fromConfig((array) config('ai'));
    $registry = new ProviderRegistry(
        $catalog,
        new InMemoryProviderSettingsStore(new ProviderSettings('openrouter', 'openai/gpt-4.1', 'or-key-12345678')),
        $factory,
    );
    $gateway = new ModelGateway($registry);

    expect($gateway->ask('ping'))->toBe('pong');
    expect($factory->calls)->toBe([
        ['provider' => 'openrouter', 'key' => 'or-key-12345678'],
    ]);
});
