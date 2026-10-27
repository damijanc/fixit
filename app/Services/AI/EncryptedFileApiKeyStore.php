<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Encrypts the provider key with Laravel's APP_KEY and stores only the
 * ciphertext in app-private storage.
 *
 * NativePHP keeps APP_KEY in the Android Keystore / iOS Keychain on device
 * (it is stripped from the bundled .env), so the ciphertext cannot be
 * decrypted from a pulled APK or a copied storage file. The plaintext key is
 * never written to .env, config, logs, or the APK.
 */
final class EncryptedFileApiKeyStore implements ApiKeyStore
{
    public function __construct(private readonly string $path) {}

    public static function defaultPath(): string
    {
        return storage_path('app/private/ai-key.enc');
    }

    public function get(): ?string
    {
        if (! is_file($this->path)) {
            return null;
        }

        $ciphertext = file_get_contents($this->path);
        if (! is_string($ciphertext) || trim($ciphertext) === '') {
            return null;
        }

        try {
            $key = Crypt::decryptString($ciphertext);
        } catch (DecryptException) {
            return null;
        }

        return is_string($key) && trim($key) !== '' ? $key : null;
    }

    public function put(string $key): bool
    {
        $directory = dirname($this->path);
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            return false;
        }

        $temporaryPath = $this->path.'.tmp';

        if (file_put_contents($temporaryPath, Crypt::encryptString($key)) === false) {
            return false;
        }

        @chmod($temporaryPath, 0600);

        return rename($temporaryPath, $this->path);
    }

    public function forget(): bool
    {
        foreach ([$this->path, $this->path.'.tmp'] as $path) {
            if (is_file($path) && ! @unlink($path)) {
                return false;
            }
        }

        return true;
    }

    public function isConfigured(): bool
    {
        return $this->get() !== null;
    }
}
