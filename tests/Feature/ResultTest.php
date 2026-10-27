<?php

declare(strict_types=1);

use App\Services\Repair\RepairSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Native\Mobile\Testing\Native;

uses(RefreshDatabase::class);

it('shows a saved result and its follow-up', function () {
    $session = (new RepairSessionService)->startWithPhoto('/tmp/photo.jpg', 'no water');
    (new RepairSessionService)->recordVisionResult($session, 'prompt', 'check the water tank');
    (new RepairSessionService)->recordFollowUp($session, 'pump runs', 'check the filter');

    Native::visit('/result/'.$session->id)
        ->assertSee('Result')
        ->assertSee('check the water tank')
        ->assertSee('check the filter')
        ->assertSee('no water')
        ->tap('result-continue-button')
        ->assertNavigatedTo('/diagnosis/'.$session->id);
});

it('shows a safety notice for escalated sessions', function () {
    $session = (new RepairSessionService)->startWithPhoto('/tmp/photo.jpg');
    (new RepairSessionService)->recordVisionResult($session, 'prompt', 'Contact a professional.', 'escalated');

    Native::visit('/result/'.$session->id)
        ->assertSee('escalated for safety')
        ->assertSee('qualified professional');
});

it('handles a missing session', function () {
    Native::visit('/result/does-not-exist')
        ->assertSee('Repair session not found.')
        ->tap('result-history-button')
        ->assertNavigatedTo('/history');
});
