<?php

declare(strict_types=1);

namespace App\Services\AI\Provider;

final readonly class ProviderSettings
{
    public function __construct(
        public string $providerId,
        public string $modelId,
        #[\SensitiveParameter] public ?string $apiKey = null,
    ) {}

    public function withApiKey(?string $apiKey): self
    {
        $apiKey = $apiKey === null ? null : trim($apiKey);

        return new self($this->providerId, $this->modelId, $apiKey === '' ? null : $apiKey);
    }

    public function withoutApiKey(): self
    {
        return new self($this->providerId, $this->modelId, null);
    }

    public function hasApiKey(): bool
    {
        return $this->apiKey !== null && trim($this->apiKey) !== '';
    }
}
