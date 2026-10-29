<?php

declare(strict_types=1);

namespace App\Services\AI;

use Native\Mobile\Exceptions\AsyncTaskException;
use RuntimeException;

/**
 * Base class for user-safe AI integration failures.
 *
 * Messages on subclasses are safe to display in the app and never contain
 * provider credentials or raw response bodies.
 */
class AiException extends RuntimeException
{
    /**
     * The message to show for a failed AI call. AI calls run as NativePHP
     * async tasks, whose failures arrive as AsyncTaskException carrying the
     * original class and message; only AiException messages are user-safe.
     */
    public static function displayMessage(\Throwable $exception, string $fallback): string
    {
        $class = $exception instanceof AsyncTaskException ? $exception->originalClass() : $exception::class;

        if (is_a($class, self::class, true)) {
            return $exception->getMessage();
        }

        report($exception);

        return $fallback;
    }
}
