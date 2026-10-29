<?php

declare(strict_types=1);

use App\Services\Safety\SafetyFilter;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

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

/*
 * Phone-charging advice was blocked on the ZTE (2026-10-29): rules written
 * for opening mains appliances matched accessories, plugging a charger into
 * another socket and a battery-charging setting. Each safe sentence below is
 * paired with a dangerous one that must stay blocked.
 */

it('allows safe charging advice', function (string $sentence) {
    $result = (new SafetyFilter)->assess($sentence);

    expect($result->blocked)->toBeFalse()
        ->and($result->escalated)->toBeFalse()
        ->and($result->text)->toBe($sentence);
})->with([
    'protective case' => "Remove the phone's protective case and check that the plug sits fully in the port.",
    'any case or cover' => 'Take off any case or cover, then reconnect the cable.',
    'silicone case' => 'Take the silicone case off so the cable reaches the port.',
    'screen protector' => 'Remove the screen protector if it covers the sensor.',
    'charger to another socket' => 'Connect the charger to a different wall socket.',
    'plug into another socket' => 'Plug the charger into another wall socket or a computer USB port.',
    'unplug from socket' => 'Unplug the charger from the wall socket, wait ten seconds and plug it back in.',
    'battery charging setting' => 'Disable optimised battery charging protection temporarily.',
    // The verb must act on the casing itself, not on something nearby.
    'open settings on device' => 'Open Settings on your device and check Battery Health.',
    'open device settings' => "Open your device's Settings and look for charging alerts.",
    'remove cable, look at back' => 'Remove the cable and look at the back of the phone for damage.',
    'remove lint from device' => 'Remove lint from the charging port of the device with a wooden toothpick.',
    'remove any case' => 'Remove any case so the plug can seat fully.',
    // Matched on the ZTE (2026-10-29): Claude means the protective case.
    'remove the case' => 'Remove the case and make sure the cable clicks fully into the port.',
    'take off your case' => 'Take off your case, then check Battery Health in Settings.',
    'open app on machine' => 'Open the companion app on the machine and check for updates.',
]);

it('still blocks opening, mains and interlock work', function (string $sentence, string $reason) {
    $result = (new SafetyFilter)->assess($sentence);

    expect($result->blocked)->toBeTrue()
        ->and($result->reasons)->toContain($reason);
})->with([
    'back cover' => ['Remove the back cover and replace the battery.', 'opening_appliance'],
    'device casing' => ['Remove the case screws and open the device.', 'opening_appliance'],
    'appliance housing' => ['Take off the housing of the kettle.', 'opening_appliance'],
    'open the device' => ['Open the device and check the connector.', 'opening_appliance'],
    'open up the back panel' => ['Open up the back panel.', 'opening_appliance'],
    'remove screws' => ['Remove the four screws on the bottom.', 'opening_appliance'],
    'unscrew cover' => ['Unscrew the bottom cover.', 'opening_appliance'],
    'disassemble alone' => ['Disassemble the kettle to clean the element.', 'opening_appliance'],
    'take apart alone' => ['Take apart the charger to check the board.', 'opening_appliance'],
    'pry open' => ['Pry open the phone with a guitar pick.', 'opening_appliance'],
    'remove the case' => ['Remove the case to reach the board.', 'opening_appliance'],
    'remove the case, check wires' => ['Remove the case and check the wires inside.', 'opening_appliance'],
    'remove the case, replace battery' => ['Remove the case and replace the battery.', 'opening_appliance'],
    'remove the case screws' => ['Remove the case screws.', 'opening_appliance'],
    'rewire socket' => ['Rewire the wall socket so the earth is connected.', 'mains_work'],
    'touch mains' => ['Touch the mains terminals to feel for heat.', 'mains_work'],
    'open socket' => ['Open the wall socket and check the wires.', 'mains_work'],
    'live wire' => ['Connect the live wire to the brown terminal.', 'mains_power'],
    'bypass fuse' => ['Bypass the fuse with a piece of wire.', 'bypass_interlock'],
    'disable surge protection' => ['Disable the surge protection on the power strip.', 'bypass_interlock'],
    'disable thermal protection' => ['Disable the thermal protection so the motor keeps running.', 'bypass_interlock'],
    'disable safety switch' => ['Disable the safety switch on the door.', 'bypass_interlock'],
]);

it('still escalates mains-powered appliances', function () {
    $result = (new SafetyFilter)->assess('The heater is mains-powered. Check that the plug is fully inserted into the wall socket.');

    expect($result->blocked)->toBeFalse()
        ->and($result->escalated)->toBeTrue();
});

it('logs which rules blocked a reply without the reply text', function () {
    config(['app.debug' => false]);
    Log::spy();

    (new SafetyFilter)->assess('Remove the back cover and replace the battery.');

    Log::shouldHaveReceived('warning')->once()->withArgs(
        fn (string $message, array $context): bool => $message === 'Safety filter blocked model output'
            && $context === ['reasons' => ['opening_appliance']],
    );
});

it('logs the matched fragment in debug builds to diagnose false blocks', function () {
    config(['app.debug' => true]);
    Log::spy();

    (new SafetyFilter)->assess('Charge it overnight. Remove the back cover and replace the battery.');

    Log::shouldHaveReceived('warning')->once()->withArgs(
        fn (string $message, array $context): bool => $context['reasons'] === ['opening_appliance']
            && $context['matches'] === ['opening_appliance' => 'Remove the back'],
    );
});
