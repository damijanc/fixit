<?php

declare(strict_types=1);

use App\Services\AI\EncryptedFileApiKeyStore;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->path = storage_path('framework/testing/ai-key-test.enc');
    @unlink($this->path);
});

afterEach(function () {
    @unlink($this->path);
    @unlink($this->path.'.tmp');
});

it('encrypts and reads back the provider key', function () {
    $store = new EncryptedFileApiKeyStore($this->path);

    expect($store->put('sk-fixit-secure-test-123'))->toBeTrue();
    expect($store->isConfigured())->toBeTrue();
    expect($store->get())->toBe('sk-fixit-secure-test-123');
});

it('never writes the plaintext key to disk', function () {
    $store = new EncryptedFileApiKeyStore($this->path);
    $store->put('sk-fixit-secure-test-123');

    $ciphertext = file_get_contents($this->path);

    expect($ciphertext)
        ->toBeString()
        ->not->toContain('sk-fixit-secure-test-123');

    expect(fileperms($this->path) & 0777)->toBe(0600);
});

it('forgets the saved key', function () {
    $store = new EncryptedFileApiKeyStore($this->path);
    $store->put('sk-fixit-secure-test-123');

    expect($store->forget())->toBeTrue();
    expect($store->get())->toBeNull();
    expect($store->isConfigured())->toBeFalse();
});

it('returns null for an unreadable ciphertext file', function () {
    file_put_contents($this->path, 'not-a-valid-ciphertext');

    $store = new EncryptedFileApiKeyStore($this->path);

    expect($store->get())->toBeNull();
    expect($store->isConfigured())->toBeFalse();
});
