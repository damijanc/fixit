<?php

declare(strict_types=1);

use App\Services\AI\MissingApiKeyException;
use App\Services\AI\Provider\ProviderCatalog;
use App\Services\AI\Provider\ProviderRegistry;
use App\Services\AI\Provider\ProviderSettings;
use App\Services\AI\ProviderConfigurationException;
use App\Services\AI\UnsupportedModelCapabilityException;
use Symfony\AI\Platform\Test\MockPlatformFactory;
use Tests\Support\FakePlatformFactory;
use Tests\Support\InMemoryProviderSettingsStore;
use Tests\TestCase;

uses(TestCase::class);

function registryWith(?ProviderSettings $settings = null, ?FakePlatformFactory $factory = null): ProviderRegistry
{
    $catalog = ProviderCatalog::fromConfig((array) config('ai'));
    $factory ??= new FakePlatformFactory(MockPlatformFactory::createPlatform('unused'));

    return new ProviderRegistry(
        $catalog,
        new InMemoryProviderSettingsStore($settings ?? new ProviderSettings('deepseek', 'deepseek-flash', 'test-key')),
        $factory,
    );
}

it('resolves the selected provider and model', function () {
    $registry = registryWith(new ProviderSettings('openrouter', 'anthropic/claude-sonnet-4.5', 'or-key'));

    expect($registry->providerId())->toBe('openrouter');
    expect($registry->providerName())->toBe('OpenRouter');
    expect($registry->modelId())->toBe('anthropic/claude-sonnet-4.5');
    expect($registry->model()->supportsVision)->toBeTrue();
});

it('reports an unknown provider instead of silently switching', function () {
    $registry = registryWith(new ProviderSettings('removed-provider', 'deepseek-flash', 'test-key'));

    expect(fn () => $registry->provider())
        ->toThrow(ProviderConfigurationException::class, 'saved AI provider [removed-provider] is no longer available');
});

it('reports an unknown model instead of silently switching', function () {
    $registry = registryWith(new ProviderSettings('deepseek', 'removed-model', 'test-key'));

    expect(fn () => $registry->model())
        ->toThrow(ProviderConfigurationException::class, 'saved model [removed-model] is not available for DeepSeek');
});

it('asks for the selected provider key', function () {
    $registry = registryWith(new ProviderSettings('huggingface', 'Qwen/Qwen3-VL-30B-A3B-Instruct'));

    expect(fn () => $registry->platform())
        ->toThrow(MissingApiKeyException::class, 'No API key saved for Hugging Face');
});

it('builds the platform lazily with the selected provider key only', function () {
    $factory = new FakePlatformFactory(MockPlatformFactory::createPlatform('unused'));
    $registry = registryWith(
        new ProviderSettings('openrouter', 'openai/gpt-4.1', 'sk-or-v1-selected'),
        $factory,
    );

    expect($factory->calls)->toBe([]);
    $registry->platform();

    expect($factory->calls)->toBe([
        ['provider' => 'openrouter', 'key' => 'sk-or-v1-selected'],
    ]);
});

it('rejects a text-only model before an image request', function () {
    $catalog = ProviderCatalog::fromConfig([
        'default_provider' => 'test',
        'providers' => [
            'test' => [
                'name' => 'Test Provider',
                'driver' => 'generic',
                'base_url' => 'https://example.test',
                'key_hint' => '',
                'models' => [
                    'text-only' => ['label' => 'Text only', 'vision' => false],
                ],
            ],
        ],
    ]);
    $registry = new ProviderRegistry(
        $catalog,
        new InMemoryProviderSettingsStore(new ProviderSettings('test', 'text-only', 'test-key')),
        new FakePlatformFactory(MockPlatformFactory::createPlatform('unused')),
    );

    expect(fn () => $registry->assertVisionSupported())
        ->toThrow(UnsupportedModelCapabilityException::class, 'text-only from Test Provider cannot analyse photos');
});

it('returns a safe display name when the saved provider is invalid', function () {
    $registry = registryWith(new ProviderSettings('removed-provider', 'deepseek-flash', 'test-key'));

    expect($registry->providerName())->toBe('your AI provider');
});
