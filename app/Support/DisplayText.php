<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Presentation helpers for native views. Native text elements render raw
 * strings, so Markdown emitted by models would show up as literal symbols.
 */
final class DisplayText
{
    public static function plain(string $text): string
    {
        $text = preg_replace('/^\s{0,3}#{1,6}\s+/m', '', $text) ?? $text;
        $text = preg_replace('/^\s*[-*+]\s+/m', '• ', $text) ?? $text;
        $text = preg_replace('/(\*\*|__)(.+?)\1/s', '$2', $text) ?? $text;
        $text = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/s', '$1', $text) ?? $text;
        $text = str_replace('`', '', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    public static function status(string $status): string
    {
        return match ($status) {
            'investigating' => 'Photo taken',
            'awaiting_follow_up' => 'Waiting for your answer',
            'escalated' => 'Needs a professional',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Short title for a repair: the user's own symptom words when present.
     */
    public static function repairTitle(?string $symptoms, ?string $summary): string
    {
        $source = trim((string) $symptoms) !== '' ? (string) $symptoms : self::plain((string) $summary);
        $source = trim(preg_replace('/\s+/', ' ', $source) ?? $source);

        if ($source === '') {
            return 'Repair';
        }

        return mb_strlen($source) > 48 ? rtrim(mb_substr($source, 0, 47)).'…' : $source;
    }
}
