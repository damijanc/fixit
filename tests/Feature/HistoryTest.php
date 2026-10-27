<?php

declare(strict_types=1);

use App\Services\Repair\RepairSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Native\Mobile\Testing\Native;

uses(RefreshDatabase::class);

it('shows an empty history', function () {
    Native::visit('/history')
        ->assertSee('History')
        ->assertSee('No repair sessions yet.')
        ->tap('history-home-button')
        ->assertNavigatedTo('/');
});

it('lists saved sessions and opens a result', function () {
    $session = (new RepairSessionService)->startWithPhoto('/tmp/photo.jpg', 'no water');
    (new RepairSessionService)->recordVisionResult($session, 'prompt', 'check the water tank');

    Native::visit('/history')
        ->assertSee('check the water tank')
        ->tap('history-session-'.$session->id)
        ->assertNavigatedTo('/result/'.$session->id);
});
