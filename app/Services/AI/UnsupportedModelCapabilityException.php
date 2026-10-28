<?php

declare(strict_types=1);

namespace App\Services\AI;

final class UnsupportedModelCapabilityException extends AiException
{
    public static function noVision(string $modelId, string $providerName): self
    {
        return new self(
            "{$modelId} from {$providerName} cannot analyse photos. Open Settings and choose a vision-capable model.",
        );
    }
}
