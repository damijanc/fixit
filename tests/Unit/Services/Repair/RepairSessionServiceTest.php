<?php

declare(strict_types=1);

use App\Models\RepairSession;
use App\Services\Repair\RepairSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('persists a repair session and its observations', function () {
    $service = new RepairSessionService;

    $session = $service->startWithPhoto('/tmp/photo.jpg', 'no water flow');
    $service->recordVisionResult($session, 'prompt', 'possible pump fault');

    expect($session->id)->toBeString();
    expect(RepairSession::query()->count())->toBe(1);
    expect($session->observations()->count())->toBe(2);

    $reloaded = (new RepairSessionService)->latest();

    expect($reloaded)->not->toBeNull();
    expect($reloaded->id)->toBe($session->id);
    expect($reloaded->symptoms)->toBe('no water flow');
    expect($reloaded->summary)->toBe('possible pump fault');
    expect($reloaded->status)->toBe('awaiting_follow_up');
});

it('deletes a discarded session', function () {
    $service = new RepairSessionService;
    $session = $service->startWithPhoto('/tmp/photo.jpg');

    $service->discard($session);

    expect(RepairSession::query()->count())->toBe(0);
});

it('returns follow-up turns in order for the conversation thread', function () {
    $service = new RepairSessionService;

    $session = $service->startWithPhoto('/tmp/photo.jpg', 'no water flow');
    $service->recordVisionResult($session, 'prompt', 'possible pump fault');
    $service->recordFollowUp($session, 'the light is on', 'check the tank valve');
    $service->recordFollowUp($session, 'the valve clicks', 'the pump may be blocked');

    expect($service->followUpTurns($session))->toBe([
        ['role' => 'user', 'text' => 'the light is on'],
        ['role' => 'assistant', 'text' => 'check the tank valve'],
        ['role' => 'user', 'text' => 'the valve clicks'],
        ['role' => 'assistant', 'text' => 'the pump may be blocked'],
    ]);
});
