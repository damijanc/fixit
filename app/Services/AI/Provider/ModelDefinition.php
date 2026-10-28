<?php

declare(strict_types=1);

namespace App\Services\AI\Provider;

final readonly class ModelDefinition
{
    public function __construct(
        public string $id,
        public string $label,
        public bool $supportsVision = true,
    ) {}

    public function supportsImage(): bool
    {
        return $this->supportsVision;
    }
}
