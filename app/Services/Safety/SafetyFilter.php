<?php

declare(strict_types=1);

namespace App\Services\Safety;

/**
 * Deterministic safety gate for model output.
 *
 * Prompt instructions are not enough: a model can still return a hazardous
 * instruction. This filter scans each sentence, ignores explicitly negated
 * ones, blocks dangerous actions, and escalates mains/gas topics.
 */
final class SafetyFilter
{
    /** @var array<string, string> */
    private const BLOCK_PATTERNS = [
        'mains_power' => '/\b(live wire|high voltage|230\s?v|240\s?v|110\s?v|power supply unit|psu|capacitor)\b/iu',
        'mains_work' => '/\b(work on|touch|connect|disconnect|rewire|repair)\b[^.!?]{0,50}\b(mains|wall socket|live wire)\b/iu',
        'opening_appliance' => '/\b(open|remove|take off|disassemble|take apart|unscrew)\b[^.!?]{0,60}\b(back|case|casing|housing|panel|cover|appliance|device|boiler|machine)\b/iu',
        'bypass_interlock' => '/\b(bypass|defeat|disable|short out|jumper)\b[^.!?]{0,60}\b(interlock|safety switch|fuse|breaker|protection)\b/iu',
        'gas' => '/\b(gas valve|gas line|gas leak|gas supply|gas appliance|boiler)\b/iu',
        'hazardous_test' => '/\b(short circuit|test with (a )?multimeter|probe (the )?(live|mains)|water and electricity)\b/iu',
        'microwave' => '/\bmicrowave\b/iu',
    ];

    /** @var array<string, string> */
    private const ESCALATION_PATTERNS = [
        'mains_power' => '/\b(mains(?:-powered)?|wall socket|live wire|high voltage|230\s?v|240\s?v|110\s?v)\b/iu',
        'gas' => '/\b(gas|boiler)\b/iu',
        'professional' => '/\b(qualified|licensed)\s+(electrician|technician|professional|engineer)\b/iu',
    ];

    private const NEGATION_PATTERN = '/\b(do not|don\'t|never|avoid|without|no need to|must not|should not)\b/iu';

    public function assess(string $text): SafetyResult
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($text)) ?: [trim($text)];
        $reasons = [];
        $blocked = false;

        foreach ($sentences as $sentence) {
            if ($sentence === '' || preg_match(self::NEGATION_PATTERN, $sentence) === 1) {
                continue;
            }

            foreach (self::BLOCK_PATTERNS as $reason => $pattern) {
                if (preg_match($pattern, $sentence) === 1) {
                    $reasons[] = $reason;
                    $blocked = true;
                }
            }
        }

        $escalated = false;
        foreach (self::ESCALATION_PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                $escalated = true;
                break;
            }
        }

        if ($blocked) {
            return new SafetyResult(
                safe: false,
                blocked: true,
                escalated: true,
                text: 'I can\'t provide that instruction. It may involve mains power, gas, or defeating a safety interlock. Stop and contact a qualified repair professional. I can help with safe, external checks only.',
                reasons: array_values(array_unique($reasons)),
            );
        }

        if ($escalated) {
            return new SafetyResult(
                safe: true,
                blocked: false,
                escalated: true,
                text: $text."\n\nSafety: this involves mains power or gas. Do not open or test it yourself; use a qualified professional.",
            );
        }

        return new SafetyResult(
            safe: true,
            blocked: false,
            escalated: false,
            text: $text,
        );
    }
}
