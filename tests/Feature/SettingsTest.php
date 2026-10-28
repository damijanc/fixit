<?php

declare(strict_types=1);

use App\Services\AI\ModelGateway;
use App\Services\AI\Provider\ProviderSettings;
use App\Services\AI\Provider\ProviderSettingsStore;
use Native\Mobile\Testing\Native;
use Symfony\AI\Platform\Test\MockPlatformFactory;
use Tests\Support\InMemoryProviderSettingsStore;
use Tests\Support\TestGateway;

function useProviderSettings(?ProviderSettings $settings = null): InMemoryProviderSettingsStore
{
    $store = new InMemoryProviderSettingsStore($settings ?? new ProviderSettings('deepseek', 'deepseek-flash'));
    app()->instance(ProviderSettingsStore::class, $store);

    return $store;
}

it('shows the default provider, model and no key initially', function () {
    useProviderSettings();

    Native::visit('/settings')
        ->assertSee('Settings')
        ->assertSee('No key saved for this provider yet.')
        ->assertSee('DeepSeek')
        ->assertSee('deepseek-flash')
        ->assertSee('openrouter')
        ->assertSee('huggingface')
        ->assertSee('anthropic')
        ->assertSee('openai');
});

it('saves the provider key in secure storage and hides it from the UI', function () {
    $store = useProviderSettings();

    Native::visit('/settings')
        ->set('apiKey', 'sk-fixit-secure-test-123')
        ->tap('save-settings-button')
        ->assertSee('Provider settings and API key saved securely on this device.')
        ->assertSee('A key is saved for this provider.')
        ->assertDontSee('sk-fixit-secure-test-123');

    expect($store->get()->apiKey)->toBe('sk-fixit-secure-test-123');
});

it('saves a key typed into the secure field without requiring a DeepSeek prefix', function () {
    $store = useProviderSettings();

    Native::visit('/settings')
        ->select('provider-select', 'huggingface')
        ->select('model-select', 'Qwen/Qwen3-VL-30B-A3B-Instruct')
        ->input('api-key-input', 'hf_typed_in_the_ui_123')
        ->tap('save-settings-button')
        ->assertSee('Provider settings and API key saved securely on this device.');

    expect($store->get()->providerId)->toBe('huggingface');
    expect($store->get()->modelId)->toBe('Qwen/Qwen3-VL-30B-A3B-Instruct');
    expect($store->get()->apiKey)->toBe('hf_typed_in_the_ui_123');
});

it('rejects an incomplete key without provider-specific prefix rules', function () {
    $store = useProviderSettings();

    Native::visit('/settings')
        ->set('apiKey', 'short')
        ->tap('save-settings-button')
        ->assertSee('That API key does not look complete.');

    expect($store->get()->apiKey)->toBeNull();
});

it('does not reuse the DeepSeek key when switching to another provider', function () {    $store = useProviderSettings(new ProviderSettings('deepseek', 'deepseek-flash', 'sk-deepseek-only-123'));

    Native::visit('/settings')
        ->select('provider-select', 'openrouter')
        ->select('model-select', 'openai/gpt-4.1')
        ->tap('save-settings-button')
        ->assertSee('Enter an API key for OpenRouter before saving.')
        ->assertDontSee('sk-deepseek-only-123');

    expect($store->get()->providerId)->toBe('deepseek');
    expect($store->get()->apiKey)->toBe('sk-deepseek-only-123');
});

it('clears a typed key when the provider changes', function () {
    $store = useProviderSettings(new ProviderSettings('deepseek', 'deepseek-flash', 'sk-deepseek-only-123'));

    Native::visit('/settings')
        ->set('apiKey', 'sk-typed-but-not-saved')
        ->select('provider-select', 'openrouter')
        ->assertSet('apiKey', '')
        ->select('model-select', 'openai/gpt-4.1')
        ->tap('save-settings-button')
        ->assertSee('Enter an API key for OpenRouter before saving.');

    expect($store->get()->providerId)->toBe('deepseek');
    expect($store->get()->apiKey)->toBe('sk-deepseek-only-123');
});

it('switches provider and replaces the key when a new key is entered', function () {
    $store = useProviderSettings(new ProviderSettings('deepseek', 'deepseek-flash', 'sk-deepseek-only-123'));

    Native::visit('/settings')
        ->select('provider-select', 'openrouter')
        ->select('model-select', 'openai/gpt-4.1')
        ->set('apiKey', 'sk-or-v1-openrouter-123')
        ->tap('save-settings-button')
        ->assertSee('Provider settings and API key saved securely on this device.');

    expect($store->get()->providerId)->toBe('openrouter');
    expect($store->get()->modelId)->toBe('openai/gpt-4.1');
    expect($store->get()->apiKey)->toBe('sk-or-v1-openrouter-123');
});

it('changes the model while keeping the key for the same provider', function () {
    $store = useProviderSettings(new ProviderSettings('openrouter', 'openai/gpt-4.1', 'sk-or-v1-existing'));

    Native::visit('/settings')
        ->select('model-select', 'anthropic/claude-sonnet-4.5')
        ->tap('save-settings-button')
        ->assertSee('Provider settings and API key saved securely on this device.');

    expect($store->get()->modelId)->toBe('anthropic/claude-sonnet-4.5');
    expect($store->get()->apiKey)->toBe('sk-or-v1-existing');
});

it('tests the connection with the saved provider and key', function () {
    useProviderSettings(new ProviderSettings('deepseek', 'deepseek-flash', 'sk-fixit-secure-test-123'));
    app()->instance(ModelGateway::class, TestGateway::make(MockPlatformFactory::createPlatform('FixIt AI connectivity OK')));

    Native::visit('/settings')
        ->tap('test-connection-button')
        ->assertSee('Connected: FixIt AI connectivity OK');
});

it('asks for a key before testing the connection', function () {
    useProviderSettings();

    Native::visit('/settings')
        ->tap('test-connection-button')
        ->assertSee('Save an API key for DeepSeek before testing the connection.');
});

it('requires saving changes before testing the connection', function () {
    useProviderSettings(new ProviderSettings('deepseek', 'deepseek-flash', 'sk-fixit-secure-test-123'));

    Native::visit('/settings')
        ->select('provider-select', 'openai')
        ->tap('test-connection-button')
        ->assertSee('Save provider settings before testing the connection.');
});

it('removes the saved key but keeps the provider selection', function () {
    $store = useProviderSettings(new ProviderSettings('openrouter', 'openai/gpt-4.1', 'sk-or-v1-existing'));

    Native::visit('/settings')
        ->assertSee('Remove saved key')
        ->tap('remove-key-button')
        ->assertSee('Saved API key removed from this device.')
        ->assertSee('No key saved for this provider yet.');

    expect($store->get()->apiKey)->toBeNull();
    expect($store->get()->providerId)->toBe('openrouter');
    expect($store->get()->modelId)->toBe('openai/gpt-4.1');
});
