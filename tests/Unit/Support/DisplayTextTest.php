<?php

declare(strict_types=1);

use App\Support\DisplayText;

it('strips markdown symbols from model output', function () {
    $text = "## Likely cause\n**Water tank** is not *seated*.\n- Check the valve\n* Use `descale` mode";

    expect(DisplayText::plain($text))->toBe(
        "Likely cause\nWater tank is not seated.\n• Check the valve\n• Use descale mode"
    );
});

it('keeps plain text and lone asterisks untouched', function () {
    expect(DisplayText::plain('Rated 5 * 2 amps, no change.'))->toBe('Rated 5 * 2 amps, no change.');
});

it('maps session statuses to readable labels', function () {
    expect(DisplayText::status('awaiting_follow_up'))->toBe('Waiting for your answer');
    expect(DisplayText::status('escalated'))->toBe('Needs a professional');
    expect(DisplayText::status('investigating'))->toBe('Photo taken');
    expect(DisplayText::status('something_else'))->toBe('Something else');
});

it('titles a repair from symptoms before the summary', function () {
    expect(DisplayText::repairTitle('no water flow', 'pump fault'))->toBe('no water flow');
    expect(DisplayText::repairTitle(null, '**Pump** fault'))->toBe('Pump fault');
    expect(DisplayText::repairTitle('', ''))->toBe('Repair');
    expect(mb_strlen(DisplayText::repairTitle(str_repeat('a', 80), null)))->toBe(48);
});
