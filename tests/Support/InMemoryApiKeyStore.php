<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\AI\ApiKeyStore;

final class InMemoryApiKeyStore implements ApiKeyStore
{
    public function __construct(private ?string $key = null) {}

    public function get(): ?string
    {
        return $this->key;
    }

    public function put(string $key): bool
    {
        $this->key = $key;

        return true;
    }

    public function forget(): bool
    {
        $this->key = null;

        return true;
    }

    public function isConfigured(): bool
    {
        return $this->key !== null;
    }
}
