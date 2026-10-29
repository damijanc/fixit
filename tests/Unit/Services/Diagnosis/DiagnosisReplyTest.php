<?php

declare(strict_types=1);

use App\Services\Diagnosis\DiagnosisPrompts;
use App\Services\Diagnosis\DiagnosisReply;
use App\Services\Safety\SafetyFilter;
use App\Support\DisplayText;
use Tests\TestCase;

uses(TestCase::class);

/**
 * @param  array<string, mixed>  $overrides
 */
function replyJson(array $overrides = []): string
{
    return (string) json_encode(array_merge([
        'object' => 'Phone with a USB-C cable',
        'likely_cause' => 'Lint in the charging port or a faulty cable.',
        'confidence' => 'medium',
        'safe_checks' => ['Try a different cable and charger.', 'Clean the port gently with a wooden toothpick.'],
        'hazard' => 'none',
        'needs_professional' => false,
        'question' => 'Does the charging icon appear at all?',
    ], $overrides));
}

it('defines a strict schema that Claude must follow', function () {
    $schema = DiagnosisReply::schema();

    expect($schema['type'])->toBe('object')
        ->and($schema['additionalProperties'])->toBeFalse()
        ->and($schema['required'])->toEqualCanonicalizing(array_keys($schema['properties']))
        ->and($schema['properties']['hazard']['enum'])->toBe(['none', 'mains', 'gas', 'battery', 'other'])
        ->and($schema['properties']['confidence']['enum'])->toBe(['low', 'medium', 'high']);
});

it('reads a valid structured reply', function () {
    $reply = DiagnosisReply::fromJson(replyJson());

    expect($reply)->not->toBeNull()
        ->and($reply->object)->toBe('Phone with a USB-C cable')
        ->and($reply->safeChecks)->toHaveCount(2)
        ->and($reply->hazard)->toBe('none')
        ->and($reply->needsProfessional)->toBeFalse();
});

it('rejects text that is not a complete reply', function (string $text) {
    expect(DiagnosisReply::fromJson($text))->toBeNull();
})->with([
    'plain text' => 'The phone probably has lint in the port.',
    'missing field' => (string) json_encode(['object' => 'Phone']),
    'wrong type' => replyJson(['safe_checks' => 'Try another cable']),
    'unknown hazard' => replyJson(['hazard' => 'radiation']),
]);

it('renders a reply as plain text for history and titles', function () {
    $text = DisplayText::plain(replyJson());

    expect($text)->toContain('Phone with a USB-C cable')
        ->toContain('Lint in the charging port')
        ->toContain('• Try a different cable and charger.')
        ->toContain('Does the charging icon appear at all?')
        ->not->toContain('{');
});

it('passes a safe structured reply unchanged', function () {
    $result = (new SafetyFilter)->assessDiagnosis(DiagnosisReply::fromJson(replyJson()));

    expect($result->blocked)->toBeFalse()
        ->and($result->escalated)->toBeFalse()
        ->and(DiagnosisReply::fromJson($result->text)?->safeChecks)->toHaveCount(2);
});

it('blocks a structured reply whose checks are dangerous', function () {
    $result = (new SafetyFilter)->assessDiagnosis(DiagnosisReply::fromJson(replyJson([
        'safe_checks' => ['Remove the back cover and replace the battery.'],
    ])));

    expect($result->blocked)->toBeTrue()
        ->and($result->text)->toContain('qualified repair professional')
        ->and($result->text)->not->toContain('back cover')
        ->and(DiagnosisReply::fromJson($result->text))->toBeNull();
});

it('escalates when the model flags a mains or gas hazard', function (array $flags) {
    $result = (new SafetyFilter)->assessDiagnosis(DiagnosisReply::fromJson(replyJson($flags)));

    expect($result->blocked)->toBeFalse()
        ->and($result->escalated)->toBeTrue()
        ->and(DiagnosisReply::fromJson($result->text)?->needsProfessional)->toBeTrue();
})->with([
    'mains hazard' => [['hazard' => 'mains']],
    'gas hazard' => [['hazard' => 'gas']],
    // A swollen or damaged battery is a fire risk (seen on the ZTE, 2026-10-29).
    'battery hazard' => [['hazard' => 'battery']],
    'model asks for a professional' => [['needs_professional' => true]],
    'mains in the text' => [['likely_cause' => 'The mains-powered adapter may be faulty.']],
]);

it('asks for short fields in the structured prompts', function () {
    $prompts = new DiagnosisPrompts;

    expect($prompts->photoSystem(structured: true))
        ->toContain('likely_cause in one or two sentences')
        ->toContain('battery');
});
