<?php

declare(strict_types=1);

namespace App\Services\AI\Provider;

/**
 * Persistence boundary for the one provider/model/key selection.
 *
 * Implementations must never fall back to a bundled .env key: release APKs
 * include .env, so a key placed there would ship inside the app.
 */
interface ProviderSettingsStore
{
    public function get(): ProviderSettings;

    public function save(ProviderSettings $settings): bool;

    public function forgetKey(): bool;
}
