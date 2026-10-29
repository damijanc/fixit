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
     * How to fill DiagnosisReply when the model answers with the schema.
     */
    private const STRUCTURED_RULES = 'Answer in the requested JSON format. safe_checks holds only low-risk, external checks the user can do now (at most five, short, in order); never put opening a device, touching wiring or gas work there. Set hazard and needs_professional honestly: needs_professional is true whenever the fix needs anything beyond those checks. Keep likely_cause in one or two sentences and each safe check under 25 words. Use hazard "battery" for a swollen, hot or damaged battery. Ask at most one question, or leave it empty.';

    /**
     * System prompt for the first photo analysis.
     */
    public function photoSystem(bool $structured = false): string
    {
        return $structured
            ? self::SAFETY_RULES.' '.self::STRUCTURED_RULES
            : self::SAFETY_RULES.' Keep the response under 120 words.';
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
    public function followUpSystem(bool $structured = false): string
    {
        return $structured
            ? self::SAFETY_RULES.' Use the earlier photo analysis, symptoms and answers to update the diagnosis. '.self::STRUCTURED_RULES
            : self::SAFETY_RULES.' Use the earlier photo analysis, symptoms and answers. Respond with a short cautious assessment and at most one new diagnostic question. Keep the response under 100 words.';
    }
}
