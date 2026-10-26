<?php

declare(strict_types=1);

namespace App\Services\Diagnosis;

/**
 * Framework-independent repair session state.
 * Persistence and model calls are future milestones.
 */
final readonly class DiagnosisState
{
    /**
     * @param list<array{question: string, answer: string}> $observations
     */
    public function __construct(
        public string $sessionId,
        public string $objectDescription,
        public string $symptoms,
        public array $observations = [],
        public string $status = 'investigating',
    ) {}
}
