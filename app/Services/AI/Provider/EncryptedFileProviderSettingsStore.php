<?php

declare(strict_types=1);

namespace App\Services\AI\Provider;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Encrypts the provider/model/key selection with Laravel's APP_KEY and stores
 * only ciphertext in app-private storage.
 *
 * NativePHP keeps APP_KEY in the Android Keystore / iOS Keychain on device
 * (it is stripped from the bundled .env), so the ciphertext cannot be
 * decrypted from a pulled APK or a copied storage file. The plaintext key is
 * never written to .env, config, logs, or the APK.
 *
 * The old `ai-key.enc` file from before T13 is migrated into the new file on
 * first read, keeping existing DeepSeek users signed in.
 */
final class EncryptedFileProviderSettingsStore implements ProviderSettingsStore
{
    public function __construct(
        private readonly string $path,
        private readonly ?string $legacyKeyPath,
        private readonly ProviderCatalog $catalog,
    ) {}

    public static function defaultPath(): string
    {
        return storage_path('app/private/ai-settings.enc');
    }

    public static function legacyPath(): string
    {
        return storage_path('app/private/ai-key.enc');
    }

    public function get(): ProviderSettings
    {
        $data = $this->read();

        if ($data === null) {
            return $this->defaults();
        }

        return $data;
    }

    public function save(ProviderSettings $settings): bool
    {
        $existing = $this->get();
        $apiKey = $settings->hasApiKey() ? trim((string) $settings->apiKey) : null;

        // Keep the existing key only while the provider stays the same. A key
        // is never carried over to a different provider.
        if ($apiKey === null && $existing->providerId === $settings->providerId) {
            $apiKey = $existing->apiKey;
        }

        return $this->write(new ProviderSettings(
            providerId: $settings->providerId,
            modelId: $settings->modelId,
            apiKey: $apiKey,
        ));
    }

    public function forgetKey(): bool
    {
        $current = $this->get();

        return $this->write($current->withoutApiKey());
    }

    private function defaults(): ProviderSettings
    {
        return new ProviderSettings(
            providerId: $this->catalog->defaultProviderId(),
            modelId: $this->catalog->defaultModel()->id,
        );
    }

    private function read(): ?ProviderSettings
    {
        if (is_file($this->path)) {
            $settings = $this->readFile($this->path);

            if ($settings !== null) {
                return $settings;
            }
        }

        return $this->migrateLegacyKey();
    }

    private function readFile(string $path): ?ProviderSettings
    {
        $ciphertext = file_get_contents($path);

        if (! is_string($ciphertext) || trim($ciphertext) === '') {
            return null;
        }

        try {
            $json = Crypt::decryptString($ciphertext);
        } catch (DecryptException) {
            return null;
        }

        $data = json_decode($json, true);

        if (! is_array($data)) {
            return null;
        }

        $providerId = $data['provider'] ?? null;
        $modelId = $data['model'] ?? null;
        $apiKey = $data['api_key'] ?? null;

        if (! is_string($providerId) || $providerId === '' || ! is_string($modelId) || $modelId === '') {
            return null;
        }

        return new ProviderSettings(
            providerId: $providerId,
            modelId: $modelId,
            apiKey: is_string($apiKey) && trim($apiKey) !== '' ? trim($apiKey) : null,
        );
    }

    private function migrateLegacyKey(): ?ProviderSettings
    {
        if ($this->legacyKeyPath === null || ! is_file($this->legacyKeyPath)) {
            return null;
        }

        $ciphertext = file_get_contents($this->legacyKeyPath);

        if (! is_string($ciphertext) || trim($ciphertext) === '') {
            return null;
        }

        try {
            $apiKey = Crypt::decryptString($ciphertext);
        } catch (DecryptException) {
            return null;
        }

        if (! is_string($apiKey) || trim($apiKey) === '') {
            return null;
        }

        $settings = $this->defaults()->withApiKey($apiKey);

        if ($this->write($settings)) {
            @unlink($this->legacyKeyPath);
        }

        return $settings;
    }

    private function write(ProviderSettings $settings): bool
    {
        $directory = dirname($this->path);

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            return false;
        }

        $json = json_encode([
            'provider' => $settings->providerId,
            'model' => $settings->modelId,
            'api_key' => $settings->apiKey,
        ]);

        if (! is_string($json)) {
            return false;
        }

        $temporaryPath = $this->path.'.tmp';

        if (file_put_contents($temporaryPath, Crypt::encryptString($json)) === false) {
            return false;
        }

        @chmod($temporaryPath, 0600);

        if (! rename($temporaryPath, $this->path)) {
            @unlink($temporaryPath);

            return false;
        }

        if ($this->legacyKeyPath !== null) {
            @unlink($this->legacyKeyPath);
        }

        return true;
    }
}
