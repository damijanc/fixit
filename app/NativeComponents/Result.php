<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Models\RepairSession;
use App\Services\Repair\RepairSessionService;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Result extends NativeComponent
{
    public string $sessionId = '';

    public string $summary = '';

    public string $symptoms = '';

    public string $followUp = '';

    public string $status = '';

    public string $safetyNotice = '';

    public string $photoPath = '';

    public string $message = '';

    /** @var list<array{role: string, text: string}> */
    public array $turns = [];

    public function mount(): void
    {
        $sessionId = $this->param('session');

        if (! is_string($sessionId) || $sessionId === '') {
            $this->message = 'Repair session not found.';

            return;
        }

        $session = RepairSession::query()->find($sessionId);

        if (! $session instanceof RepairSession) {
            $this->message = 'Repair session not found.';

            return;
        }

        $this->sessionId = $session->id;
        $this->summary = (string) ($session->summary ?? 'No result yet.');
        $this->symptoms = (string) ($session->symptoms ?? '');
        $this->status = $session->status;
        $this->followUp = (string) (app(RepairSessionService::class)->latestFollowUp($session) ?? '');
        $this->turns = app(RepairSessionService::class)->followUpTurns($session);

        if (is_string($session->photo_path) && is_file($session->photo_path)) {
            $this->photoPath = $session->photo_path;
        }

        if ($session->status === 'escalated') {
            $this->safetyNotice = 'This diagnosis was escalated for safety. Use a qualified professional for mains or gas work.';
        }
    }

    public function continueDiagnosis(): void
    {
        if ($this->sessionId !== '') {
            $this->navigate('/diagnosis/'.$this->sessionId);
        }
    }

    public function goHistory(): void
    {
        $this->navigate('/history');
    }

    public function goHome(): void
    {
        $this->navigate('/');
    }

    public function render(): View
    {
        return view('native.result');
    }
}
