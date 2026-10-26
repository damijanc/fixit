<?php

declare(strict_types=1);

namespace App\Services\AI;

use RuntimeException;

/**
 * Base class for user-safe AI integration failures.
 *
 * Messages on subclasses are safe to display in the app and never contain
 * provider credentials or raw response bodies.
 */
class AiException extends RuntimeException {}
