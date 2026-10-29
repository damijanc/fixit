<?php

declare(strict_types=1);

namespace App\Services\Diagnosis;

/**
 * A diagnosis in the structured shape models with structured outputs (the
 * Claude models) must answer in. Stored as JSON wherever the model's text is
 * stored; DisplayText renders it as plain text, the thread as a card.
 */
final readonly class DiagnosisReply
{
    public const HAZARDS = ['none', 'mains', 'gas', 'battery', 'other'];

    public const CONFIDENCE = ['low', 'medium', 'high'];

    /**
     * @param  list<string>  $safeChecks
     */
    public function __construct(
        public string $object,
        public string $likelyCause,
        public string $confidence,
        public array $safeChecks,
        public string $hazard,
        public bool $needsProfessional,
        public string $question,
    ) {}

    /**
     * JSON schema sent as output_config.format. Every property is required
     * and no other is allowed, as structured outputs expect.
     *
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'object' => ['type' => 'string', 'description' => 'What the photo shows, in a few words.'],
                'likely_cause' => ['type' => 'string', 'description' => 'The most likely fault, stating uncertainty.'],
                'confidence' => ['type' => 'string', 'enum' => self::CONFIDENCE],
                'safe_checks' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Up to five low-risk, external checks the user can do now, in order.',
                ],
                'hazard' => [
                    'type' => 'string',
                    'enum' => self::HAZARDS,
                    'description' => 'mains: mains-powered internals or wiring; gas: gas appliances; battery: swollen or damaged battery.',
                ],
                'needs_professional' => [
                    'type' => 'boolean',
                    'description' => 'True when the fix needs opening the device, mains or gas work, or a damaged battery.',
                ],
                'question' => ['type' => 'string', 'description' => 'One diagnostic question, or an empty string.'],
            ],
            'required' => ['object', 'likely_cause', 'confidence', 'safe_checks', 'hazard', 'needs_professional', 'question'],
            'additionalProperties' => false,
        ];
    }

    public static function fromJson(string $json): ?self
    {
        $data = json_decode(trim($json), true);

        if (! is_array($data)) {
            return null;
        }

        $checks = $data['safe_checks'] ?? null;

        if (! is_string($data['object'] ?? null)
            || ! is_string($data['likely_cause'] ?? null)
            || ! in_array($data['confidence'] ?? null, self::CONFIDENCE, true)
            || ! is_array($checks) || ! array_is_list($checks)
            || array_filter($checks, fn (mixed $check): bool => ! is_string($check)) !== []
            || ! in_array($data['hazard'] ?? null, self::HAZARDS, true)
            || ! is_bool($data['needs_professional'] ?? null)
            || ! is_string($data['question'] ?? null)) {
            return null;
        }

        return new self(
            object: trim($data['object']),
            likelyCause: trim($data['likely_cause']),
            confidence: $data['confidence'],
            safeChecks: array_values(array_filter(array_map('trim', $checks), fn (string $check): bool => $check !== '')),
            hazard: $data['hazard'],
            needsProfessional: $data['needs_professional'],
            question: trim($data['question']),
        );
    }

    public function withProfessional(): self
    {
        return new self($this->object, $this->likelyCause, $this->confidence, $this->safeChecks, $this->hazard, true, $this->question);
    }

    public function toJson(): string
    {
        return (string) json_encode([
            'object' => $this->object,
            'likely_cause' => $this->likelyCause,
            'confidence' => $this->confidence,
            'safe_checks' => $this->safeChecks,
            'hazard' => $this->hazard,
            'needs_professional' => $this->needsProfessional,
            'question' => $this->question,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Readable text: the safety filter's input, and the History/Home summary.
     */
    public function plainText(): string
    {
        $lines = array_filter([
            $this->object,
            $this->likelyCause,
            ...array_map(fn (string $check): string => '• '.$check, $this->safeChecks),
            $this->needsProfessional ? 'Use a qualified professional for anything beyond these checks.' : '',
            $this->question,
        ], fn (string $line): bool => $line !== '');

        return implode("\n", $lines);
    }
}
