<?php

declare(strict_types=1);

namespace App\Services\AI;

final class MissingApiKeyException extends AiException
{
    public static function forProvider(): self
    {
        return new self('No provider API key saved. Add your DeepSeek key in Settings.');
    }
}
