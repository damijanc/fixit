<?php

declare(strict_types=1);

namespace App\Services\AI;

use Symfony\AI\Platform\Exception\AuthenticationException;
use Symfony\AI\Platform\Exception\BadRequestException;
use Symfony\AI\Platform\Exception\ExceedContextSizeException;
use Symfony\AI\Platform\Exception\ModelNotFoundException;
use Symfony\AI\Platform\Exception\RateLimitExceededException;
use Symfony\AI\Platform\Exception\RuntimeException;
use Symfony\AI\Platform\Exception\ServerException;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

final class AiRequestFailedException extends AiException
{
    public static function unreachable(\Throwable $previous): self
    {
        return new self(
            'The AI provider could not be reached. Check the internet connection and API key, then try again.',
            previous: $previous,
        );
    }

    public static function emptyResponse(): self
    {
        return new self('The AI provider returned an empty or unsupported response.');
    }

    /**
     * The model's safety system stopped the reply (Anthropic stop_reason
     * "refusal"). Any partial text is discarded.
     */
    public static function refused(string $providerName): self
    {
        return new self(
            "{$providerName} declined this request. Describe the appliance problem differently, or ask a qualified professional.",
        );
    }

    /**
     * Translate a Symfony AI / HttpClient failure into a user-safe message.
     *
     * Symfony AI reports HTTP failures as typed exceptions without an HTTP
     * status code, so the status is derived from the exception type first.
     */
    public static function fromProvider(\Throwable $previous, string $providerName = 'AI provider'): self
    {
        if ($previous instanceof ExceedContextSizeException) {
            return new self(
                "{$providerName} reports the conversation is too long for this model. Start a new repair session.",
                previous: $previous,
            );
        }

        // The bridges throw the bare platform RuntimeException (subclasses are
        // HTTP failures) for a reply without usable content, e.g. a refusal
        // before any text. The request arrived, so "could not be reached" is wrong.
        if ($previous::class === RuntimeException::class) {
            return new self(
                "{$providerName} returned no answer for this request. Rephrase it or try again.",
                previous: $previous,
            );
        }

        $status = self::httpStatus($previous);

        $message = match (true) {
            $status === 401 => "{$providerName} rejected the API key (401). Check that the key is active and belongs to {$providerName}.",
            $status === 402 => "{$providerName} reports insufficient balance or quota (402). Add credit or use another key.",
            $status === 403 => "{$providerName} denied access to this model (403). Check the account permissions and model access.",
            $status === 404 => "{$providerName} could not find the endpoint or model (404). Choose a different model in Settings.",
            $status === 429 => "{$providerName} rate limit reached (429). ".self::retryHint($previous),
            $status >= 500 => "{$providerName} is unavailable (HTTP {$status}). Try again later.",
            $status >= 400 => "{$providerName} rejected the request (HTTP {$status}). Check the selected model and API key.",
            default => 'The AI provider could not be reached. Check the internet connection and try again.',
        };

        if (in_array($status, [400, 413, 415, 422], true)
            && str_contains(strtolower($previous->getMessage()), 'image')) {
            $message .= ' The selected model may not accept images. Choose a vision-capable model in Settings.';
        }

        return new self($message, previous: $previous);
    }

    public static function noModel(): self
    {
        return new self('No AI model is configured. Open Settings and choose a model.');
    }

    public static function invalidKey(string $providerName, \Throwable $previous): self
    {
        return new self(
            "{$providerName} rejected the key format before sending. Check that you pasted the complete API key.",
            previous: $previous,
        );
    }

    public static function imageUnreadable(): self
    {
        return new self('The captured photo could not be read. Take the photo again.');
    }

    public static function imageTooLarge(): self
    {
        return new self('The captured photo is empty or larger than the 32 MB upload limit. Take a smaller photo.');
    }

    public static function imageUnsupported(): self
    {
        return new self('The captured photo format is not supported. Use a JPEG, PNG, GIF or WebP image.');
    }

    private static function httpStatus(\Throwable $exception): int
    {
        return match (true) {
            $exception instanceof AuthenticationException => 401,
            $exception instanceof BadRequestException => 400,
            $exception instanceof ModelNotFoundException => 404,
            $exception instanceof RateLimitExceededException => 429,
            $exception instanceof ServerException => $exception->getStatusCode() ?? 500,
            $exception instanceof HttpExceptionInterface => $exception->getResponse()->getStatusCode(),
            default => (int) $exception->getCode(),
        };
    }

    private static function retryHint(\Throwable $exception): string
    {
        $seconds = $exception instanceof RateLimitExceededException ? $exception->getRetryAfter() : null;

        return $seconds !== null && $seconds > 0
            ? "Wait {$seconds} seconds and try again."
            : 'Wait a moment and try again.';
    }
}
