<?php

declare(strict_types=1);

namespace App\Services\AI;

final class ProviderConfigurationException extends AiException
{
    public static function unknownProvider(string $providerId): self
    {
        return new self("The saved AI provider [{$providerId}] is no longer available. Open Settings and choose a provider.");
    }

    public static function unknownModel(string $modelId, string $providerName): self
    {
        return new self("The saved model [{$modelId}] is not available for {$providerName}. Open Settings and choose a model.");
    }
}
