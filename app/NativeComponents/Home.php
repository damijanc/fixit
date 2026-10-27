<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Models\RepairSession;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Home extends NativeComponent
{
    public ?string $latestSessionId = null;

    public string $latestSummary = '';

    public string $latestStatus = '';

    public string $latestUpdated = '';

    public int $sessionCount = 0;

    public function mount(): void
    {
        $this->sessionCount = RepairSession::query()->count();

        $latest = RepairSession::query()->latest('updated_at')->first();

        if (! $latest instanceof RepairSession) {
            return;
        }

        $this->latestSessionId = $latest->id;
        $this->latestStatus = $latest->status;
        $this->latestSummary = Str::limit((string) ($latest->summary ?? ''), 160);
        $this->latestUpdated = $latest->updated_at?->diffForHumans() ?? '';
    }

    public function startDiagnosis(): void
    {
        $this->navigate('/diagnosis');
    }

    public function resumeLast(): void
    {
        if ($this->latestSessionId !== null) {
            $this->navigate('/diagnosis/'.$this->latestSessionId);
        }
    }

    public function openHistory(): void
    {
        $this->navigate('/history');
    }

    public function openSettings(): void
    {
        $this->navigate('/settings');
    }

    public function render(): View
    {
        return view('native.home');
    }
}
