<?php

declare(strict_types=1);

namespace App\Services\AI;

final class MissingApiKeyException extends AiException
{
    public static function forProvider(): self
    {
        return new self('No API key configured. Add a provider key before contacting the model.');
    }
}
