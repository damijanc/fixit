<?php

declare(strict_types=1);

namespace App\Services\AI;

final class AiRequestFailedException extends AiException
{
    public static function unreachable(\Throwable $previous): self
    {
        return new self(
            'The AI provider could not be reached. Check the endpoint and API key, then try again.',
            previous: $previous,
        );
    }

    public static function emptyResponse(): self
    {
        return new self('The AI provider returned an empty or unsupported response.');
    }

    public static function noModel(): self
    {
        return new self('No AI model is configured.');
    }
}
