<?php

declare(strict_types=1);

namespace App\Services\Safety;

use Illuminate\Support\Facades\Log;

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
        // Work on the installation. Plugging a charger into a socket is not.
        'mains_work' => '/\b(work on|touch|rewire|repair|open)\b[^.!?]{0,50}\b(mains|wall socket|live wire)\b/iu',
        // Taking a device apart is always blocked. Otherwise the casing must be
        // the verb's own object (at most three words between, never across
        // "and", "on", "from"...): "open Settings on your device" or "remove
        // the cable and look at the back" are not opening anything.
        'opening_appliance' => '/\b(?:disassemble|take\s+apart|pry\s+(?:open|off)|prise\s+(?:open|off))\b'
            .'|\b(?:open(?:\s+up)?|remove|take\s+off|unscrew)\s+'
            .'(?:(?!(?:and|then|or|to|on|in|from|with|for|of|at|so|if)\b)[\w\'’-]+\s+){0,3}?'
            .'(?:back|case|casing|housing|panel|cover|appliance|device|boiler|machine|screws?)\b(?![\'’]s)/iu',
        // Electrical protection only; "battery charging protection" is a setting.
        'bypass_interlock' => '/\b(bypass|defeat|disable|short out|jumper)\b[^.!?]{0,60}\b(interlock|safety switch|fuse|breaker|(?:surge|thermal|overload|overcurrent|circuit) protection)\b/iu',
        'gas' => '/\b(gas valve|gas line|gas leak|gas supply|gas appliance|boiler)\b/iu',
        'hazardous_test' => '/\b(short circuit|test with (a )?multimeter|probe (the )?(live|mains)|water and electricity)\b/iu',
        'microwave' => '/\bmicrowave\b/iu',
    ];

    /** @var array<string, string> */
    private const ESCALATION_PATTERNS = [
        // A wall socket alone is plugging in a charger, not mains work.
        'mains_power' => '/\b(mains(?:-powered)?|live wire|high voltage|230\s?v|240\s?v|110\s?v)\b/iu',
        'gas' => '/\b(gas|boiler)\b/iu',
        'professional' => '/\b(qualified|licensed)\s+(electrician|technician|professional|engineer)\b/iu',
    ];

    private const NEGATION_PATTERN = '/\b(do not|don\'t|never|avoid|without|no need to|must not|should not)\b/iu';

    /**
     * Removable accessories, not the device's own casing. Replaced before the
     * block patterns run, so "remove the protective case" is not "remove the
     * case"; "back cover", "casing" and "housing" are never accessories.
     */
    private const ACCESSORY_PATTERN = '/\b(?:(?:protective|phone|silicone|rubber|leather|clear|wallet|bumper)\s+(?:case|cover)|(?:any\s+)?case\s+or\s+cover|any\s+case|screen\s+protector)\b/iu';

    /**
     * "Remove the case" on a phone means its protective case, on a kettle its
     * casing. It counts as the accessory only when the sentence does not reach
     * for anything inside.
     */
    private const BARE_CASE_PATTERN = '/\b(?:the|your)\s+case\b(?!\s+screws?\b)/iu';

    private const INTERNALS_PATTERN = '/\b(?:board|pcb|circuit|screws?|wires?|wiring|components?|inside|internal|motor|element|fuse|(?:replace|disconnect|unplug)\s+the\s+battery)\b/iu';

    public function assess(string $text): SafetyResult
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($text)) ?: [trim($text)];
        $reasons = [];
        $matches = [];
        $blocked = false;

        foreach ($sentences as $sentence) {
            if ($sentence === '' || preg_match(self::NEGATION_PATTERN, $sentence) === 1) {
                continue;
            }

            $sentence = (string) preg_replace(self::ACCESSORY_PATTERN, 'accessory', $sentence);

            if (preg_match(self::INTERNALS_PATTERN, $sentence) !== 1) {
                $sentence = (string) preg_replace(self::BARE_CASE_PATTERN, 'accessory', $sentence);
            }

            foreach (self::BLOCK_PATTERNS as $reason => $pattern) {
                if (preg_match($pattern, $sentence, $match) === 1) {
                    $reasons[] = $reason;
                    $matches[$reason] ??= $match[0];
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
            $reasons = array_values(array_unique($reasons));

            // The blocked text is never stored. Rule ids make a false block
            // diagnosable on a device; debug builds also log the matched words.
            Log::warning('Safety filter blocked model output', config('app.debug')
                ? ['reasons' => $reasons, 'matches' => $matches]
                : ['reasons' => $reasons]);

            return new SafetyResult(
                safe: false,
                blocked: true,
                escalated: true,
                text: 'I can\'t provide that instruction. It may involve mains power, gas, or defeating a safety interlock. Stop and contact a qualified repair professional. I can help with safe, external checks only.',
                reasons: $reasons,
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
