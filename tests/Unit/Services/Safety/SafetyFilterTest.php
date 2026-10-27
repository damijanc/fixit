<?php

declare(strict_types=1);

use App\Services\Safety\SafetyFilter;

it('allows low-risk external checks', function () {
    $result = (new SafetyFilter)->assess('Check that the wheel spins freely and the brake pads are not rubbing.');

    expect($result->safe)->toBeTrue();
    expect($result->blocked)->toBeFalse();
    expect($result->escalated)->toBeFalse();
});

it('blocks opening a mains-powered appliance and bypassing an interlock', function () {
    $result = (new SafetyFilter)->assess('Open the back panel and bypass the interlock to test the motor.');

    expect($result->safe)->toBeFalse();
    expect($result->blocked)->toBeTrue();
    expect($result->escalated)->toBeTrue();
    expect($result->text)->toContain('qualified repair professional');
    expect($result->text)->not->toContain('bypass the interlock');
    expect($result->reasons)->toContain('opening_appliance');
});

it('blocks gas work', function () {
    $result = (new SafetyFilter)->assess('Remove the gas valve and test for leaks.');

    expect($result->blocked)->toBeTrue();
    expect($result->reasons)->toContain('gas');
});

it('blocks probing live wiring', function () {
    $result = (new SafetyFilter)->assess('Probe the live wire with a multimeter to see whether power arrives.');

    expect($result->blocked)->toBeTrue();
});

it('does not block an explicitly negated warning', function () {
    $result = (new SafetyFilter)->assess('Do not open the device or bypass the interlock.');

    expect($result->blocked)->toBeFalse();
    expect($result->safe)->toBeTrue();
});

it('escalates a mains-powered device without blocking external checks', function () {
    $result = (new SafetyFilter)->assess('The desk lamp is mains-powered. Check the plug and cable for visible damage.');

    expect($result->blocked)->toBeFalse();
    expect($result->escalated)->toBeTrue();
    expect($result->text)->toContain('qualified professional');
});

it('escalates when the model recommends a qualified professional', function () {
    $result = (new SafetyFilter)->assess('Ask a qualified technician to inspect the wiring.');

    expect($result->blocked)->toBeFalse();
    expect($result->escalated)->toBeTrue();
});
