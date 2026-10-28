<?php

declare(strict_types=1);

namespace App\Services\Diagnosis;

/**
 * The prompts FixIt sends to the model, kept in one reviewable place.
 *
 * Safety rules live in the system prompt, which Symfony AI's
 * SystemPromptInputProcessor prepends to every request. The prompts are only
 * the first line of defence: SafetyFilter still checks every response.
 */
final class DiagnosisPrompts
{
    private const SAFETY_RULES = 'You are FixIt, a cautious repair assistant. Suggest only low-risk, non-invasive checks. Never tell the user to open mains-powered devices, service gas equipment, bypass interlocks, or perform hazardous tests. Escalate to a qualified professional when appropriate. State uncertainty.';

    /**
     * System prompt for the first photo analysis.
     */
    public function photoSystem(): string
    {
        return self::SAFETY_RULES.' Keep the response under 120 words.';
    }

    /**
     * User request sent together with the photo.
     */
    public function photoRequest(string $symptoms): string
    {
        $symptoms = trim($symptoms);
        $symptomContext = $symptoms === ''
            ? 'The user has not described symptoms yet.'
            : "The user describes these symptoms: {$symptoms}";

        return "Look at the photo and identify the object and the most likely fault. Ask at most one diagnostic question. {$symptomContext}";
    }

    /**
     * System prompt for follow-up turns after the photo analysis.
     */
    public function followUpSystem(): string
    {
        return self::SAFETY_RULES.' Use the earlier photo analysis, symptoms and answers. Respond with a short cautious assessment and at most one new diagnostic question. Keep the response under 100 words.';
    }
}
