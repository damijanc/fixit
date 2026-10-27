<?php

declare(strict_types=1);

namespace App\Services\Safety;

final readonly class SafetyResult
{
    /**
     * @param  list<string>  $reasons
     */
    public function __construct(
        public bool $safe,
        public bool $blocked,
        public bool $escalated,
        public string $text,
        public array $reasons = [],
    ) {}
}
