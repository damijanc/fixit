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

    public static function fromProvider(\Throwable $previous): self
    {
        $status = (int) $previous->getCode();

        $message = match (true) {
            $status === 401 => 'DeepSeek rejected the API key (401). Check that the full key — including the sk- prefix — is active and was created at platform.deepseek.com.',
            $status === 402 => 'DeepSeek reports insufficient balance (402). Add credit or use another key.',
            $status === 403 => 'DeepSeek denied access to this model (403). Check the account permissions.',
            $status === 404 => 'DeepSeek could not find the endpoint or model (404). Check the model name.',
            $status === 429 => 'DeepSeek rate limit reached (429). Wait a moment and try again.',
            $status >= 500 => "DeepSeek is unavailable (HTTP {$status}). Try again later.",
            $status >= 400 => "DeepSeek rejected the request (HTTP {$status}). Check the model and key.",
            default => 'The AI provider could not be reached. Check the internet connection and try again.',
        };

        return new self($message, previous: $previous);
    }

    public static function noModel(): self
    {
        return new self('No AI model is configured.');
    }
}
