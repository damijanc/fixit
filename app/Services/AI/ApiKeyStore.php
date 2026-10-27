<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * Stores the user-supplied provider key on the device.
 *
 * Implementations must never fall back to a bundled .env value: release APKs
 * include .env, so a key placed there would ship inside the app.
 */
interface ApiKeyStore
{
    public function get(): ?string;

    public function put(string $key): bool;

    public function forget(): bool;

    public function isConfigured(): bool;
}
