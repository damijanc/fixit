<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Models\RepairSession;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class History extends NativeComponent
{
    /** @var list<array{id: string, label: string, summary: string}> */
    public array $sessions = [];

    public function mount(): void
    {
        $this->sessions = RepairSession::query()
            ->latest('updated_at')
            ->limit(30)
            ->get()
            ->map(fn (RepairSession $session): array => [
                'id' => $session->id,
                'label' => ucfirst(str_replace('_', ' ', $session->status)).' · '.($session->updated_at?->diffForHumans() ?? ''),
                'summary' => Str::limit((string) ($session->summary ?? 'No result yet'), 120),
            ])
            ->values()
            ->all();
    }

    public function openSession(string $id): void
    {
        $this->navigate('/result/'.$id);
    }

    public function goHome(): void
    {
        $this->navigate('/');
    }

    public function render(): View
    {
        return view('native.history');
    }
}
