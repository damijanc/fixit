<?php

declare(strict_types=1);

use App\Services\AI\ApiKeyStore;
use App\Services\AI\ModelGateway;
use Native\Mobile\Testing\Native;
use Symfony\AI\Platform\Test\MockPlatformFactory;
use Tests\Support\InMemoryApiKeyStore;

it('shows that no key is saved initially', function () {
    app()->instance(ApiKeyStore::class, new InMemoryApiKeyStore);

    Native::visit('/settings')
        ->assertSee('Settings')
        ->assertSee('No key saved yet.')
        ->assertSee('deepseek-flash');
});

it('saves the provider key in the key store and hides it from the UI', function () {
    $store = new InMemoryApiKeyStore;
    app()->instance(ApiKeyStore::class, $store);

    Native::visit('/settings')
        ->set('apiKey', 'sk-fixit-secure-test-123')
        ->tap('save-key-button')
        ->assertSee('API key saved securely on this device.')
        ->assertSee('A key is saved on this device.')
        ->assertDontSee('sk-fixit-secure-test-123');

    expect($store->get())->toBe('sk-fixit-secure-test-123');
});

it('saves a key typed into the secure field', function () {
    $store = new InMemoryApiKeyStore;
    app()->instance(ApiKeyStore::class, $store);

    Native::visit('/settings')
        ->input('api-key-input', 'sk-typed-in-the-ui-123')
        ->tap('save-key-button')
        ->assertSee('API key saved securely on this device.');

    expect($store->get())->toBe('sk-typed-in-the-ui-123');
});

it('tests the connection with the saved key', function () {
    $store = new InMemoryApiKeyStore('sk-fixit-secure-test-123');
    app()->instance(ApiKeyStore::class, $store);
    app()->instance(ModelGateway::class, new ModelGateway(
        $store,
        MockPlatformFactory::createPlatform('FixIt AI connectivity OK'),
        'test-model',
    ));

    Native::visit('/settings')
        ->tap('test-connection-button')
        ->assertSee('Connected: FixIt AI connectivity OK');
});

it('asks for a key before testing the connection', function () {
    app()->instance(ApiKeyStore::class, new InMemoryApiKeyStore);

    Native::visit('/settings')
        ->tap('test-connection-button')
        ->assertSee('Save a provider API key before testing the connection.');
});

it('removes the saved key', function () {
    $store = new InMemoryApiKeyStore('sk-fixit-secure-test-123');
    app()->instance(ApiKeyStore::class, $store);

    Native::visit('/settings')
        ->assertSee('Remove saved key')
        ->tap('remove-key-button')
        ->assertSee('Saved API key removed from this device.')
        ->assertSee('No key saved yet.');

    expect($store->get())->toBeNull();
});
