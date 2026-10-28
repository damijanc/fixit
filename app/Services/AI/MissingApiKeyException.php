<?php

declare(strict_types=1);

namespace App\Services\AI;

final class MissingApiKeyException extends AiException
{
    public static function forProvider(string $providerName = 'AI provider'): self
    {
        return new self("No API key saved for {$providerName}. Add your key in Settings.");
    }
}
