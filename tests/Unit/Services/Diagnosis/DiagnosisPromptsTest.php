<?php

declare(strict_types=1);

use App\Services\Diagnosis\DiagnosisPrompts;

it('puts the safety rules in every system prompt', function (string $method) {
    $prompt = (new DiagnosisPrompts)->{$method}();

    expect($prompt)
        ->toContain('low-risk, non-invasive checks')
        ->toContain('Never tell the user to open mains-powered devices, service gas equipment, bypass interlocks, or perform hazardous tests')
        ->toContain('qualified professional');
})->with(['photoSystem', 'followUpSystem']);

it('includes the described symptoms in the photo request', function () {
    expect((new DiagnosisPrompts)->photoRequest('  no water flow  '))
        ->toContain('The user describes these symptoms: no water flow')
        ->toContain('at most one diagnostic question');
});

it('says when no symptoms were described', function () {
    expect((new DiagnosisPrompts)->photoRequest(''))
        ->toContain('has not described symptoms yet');
});
