<?php

declare(strict_types=1);

namespace App\Services\AI\Provider;

final readonly class ModelDefinition
{
    public function __construct(
        public string $id,
        public string $label,
        public bool $supportsVision = true,
        /** @var array<string, mixed> Request options sent with every call to this model. */
        public array $options = [],
        /** The model honours a JSON schema (structured outputs). */
        public bool $supportsStructuredOutput = false,
    ) {}

    public function supportsImage(): bool
    {
        return $this->supportsVision;
    }
}
