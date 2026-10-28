<?php

declare(strict_types=1);

use App\Services\AI\Provider\EncryptedFileProviderSettingsStore;
use App\Services\AI\Provider\ProviderCatalog;
use App\Services\AI\Provider\ProviderSettings;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->path = storage_path('framework/testing/ai-settings-test.enc');
    $this->legacyPath = storage_path('framework/testing/ai-key-test.enc');
    @unlink($this->path);
    @unlink($this->path.'.tmp');
    @unlink($this->legacyPath);
    $this->catalog = ProviderCatalog::fromConfig((array) config('ai'));
    $this->store = new EncryptedFileProviderSettingsStore(
        $this->path,
        $this->legacyPath,
        $this->catalog,
    );
});

afterEach(function () {
    @unlink($this->path);
    @unlink($this->path.'.tmp');
    @unlink($this->legacyPath);
});

it('encrypts and reads back provider, model and key', function () {
    $settings = new ProviderSettings('openrouter', 'anthropic/claude-sonnet-4.5', 'sk-or-v1-secret-123');

    expect($this->store->save($settings))->toBeTrue();
    expect($this->store->get()->providerId)->toBe('openrouter');
    expect($this->store->get()->modelId)->toBe('anthropic/claude-sonnet-4.5');
    expect($this->store->get()->apiKey)->toBe('sk-or-v1-secret-123');
});

it('never writes the plaintext provider key to disk', function () {
    $this->store->save(new ProviderSettings('deepseek', 'deepseek-flash', 'sk-fixit-secure-test-123'));

    $ciphertext = file_get_contents($this->path);

    expect($ciphertext)
        ->toBeString()
        ->not->toContain('sk-fixit-secure-test-123')
        ->not->toContain('openrouter');

    expect(fileperms($this->path) & 0777)->toBe(0600);
});

it('returns the configured DeepSeek default when nothing is saved', function () {
    $settings = $this->store->get();

    expect($settings->providerId)->toBe('deepseek');
    expect($settings->modelId)->toBe('deepseek-flash');
    expect($settings->apiKey)->toBeNull();
});

it('migrates the legacy DeepSeek key into the new encrypted settings file', function () {
    file_put_contents($this->legacyPath, Crypt::encryptString('sk-legacy-deepseek-key'));

    $settings = $this->store->get();

    expect($settings->providerId)->toBe('deepseek');
    expect($settings->modelId)->toBe('deepseek-flash');
    expect($settings->apiKey)->toBe('sk-legacy-deepseek-key');
    expect(is_file($this->legacyPath))->toBeFalse();
    expect(is_file($this->path))->toBeTrue();
    expect(file_get_contents($this->path))->not->toContain('sk-legacy-deepseek-key');

    // A second read uses the migrated file, not the deleted legacy file.
    expect($this->store->get()->apiKey)->toBe('sk-legacy-deepseek-key');
});

it('keeps the key when only the model changes for the same provider', function () {
    $this->store->save(new ProviderSettings('openrouter', 'openai/gpt-4.1', 'sk-or-v1-existing'));

    $this->store->save(new ProviderSettings('openrouter', 'anthropic/claude-sonnet-4.5'));

    expect($this->store->get()->providerId)->toBe('openrouter');
    expect($this->store->get()->modelId)->toBe('anthropic/claude-sonnet-4.5');
    expect($this->store->get()->apiKey)->toBe('sk-or-v1-existing');
});

it('drops the key when the provider changes without a new key', function () {
    $this->store->save(new ProviderSettings('deepseek', 'deepseek-flash', 'sk-deepseek-only'));

    $this->store->save(new ProviderSettings('openrouter', 'openai/gpt-4.1'));

    expect($this->store->get()->providerId)->toBe('openrouter');
    expect($this->store->get()->apiKey)->toBeNull();
});

it('forgets only the key and keeps the provider selection', function () {
    $this->store->save(new ProviderSettings('openrouter', 'openai/gpt-4.1', 'sk-or-v1-existing'));

    expect($this->store->forgetKey())->toBeTrue();
    expect($this->store->get()->providerId)->toBe('openrouter');
    expect($this->store->get()->modelId)->toBe('openai/gpt-4.1');
    expect($this->store->get()->apiKey)->toBeNull();
});

it('returns defaults for an unreadable ciphertext file', function () {
    file_put_contents($this->path, 'not-a-valid-ciphertext');

    $settings = $this->store->get();

    expect($settings->providerId)->toBe('deepseek');
    expect($settings->apiKey)->toBeNull();
});

it('ignores an unreadable legacy key file', function () {
    file_put_contents($this->legacyPath, 'not-a-valid-ciphertext');

    $settings = $this->store->get();

    expect($settings->providerId)->toBe('deepseek');
    expect($settings->apiKey)->toBeNull();
});
