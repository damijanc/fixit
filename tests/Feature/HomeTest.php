<?php

declare(strict_types=1);

use App\Services\Repair\RepairSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Native\Mobile\Testing\Native;

uses(RefreshDatabase::class);

it('renders the home landing screen', function () {
    Native::visit('/')
        ->assertSee('FixIt')
        ->assertSee('Start a new diagnosis')
        ->assertSee('History (0)')
        ->assertSee('Provider settings')
        ->assertSee('Stored only on this phone · No account needed');
});

it('opens the diagnosis screen', function () {
    Native::visit('/')
        ->tap('start-diagnosis-button')
        ->assertNavigatedTo('/diagnosis');
});

it('opens history and settings', function () {
    Native::visit('/')
        ->tap('open-history-button')
        ->assertNavigatedTo('/history');

    Native::visit('/')
        ->tap('home-settings-button')
        ->assertNavigatedTo('/settings');
});

it('shows and resumes the latest saved session', function () {
    $session = (new RepairSessionService)->startWithPhoto('/tmp/photo.jpg', 'no water');
    (new RepairSessionService)->recordVisionResult($session, 'prompt', 'check the water tank');

    Native::visit('/')
        ->assertSee('Last diagnosis')
        ->assertSee('check the water tank')
        ->tap('resume-last-button')
        ->assertNavigatedTo('/diagnosis/'.$session->id);
});
