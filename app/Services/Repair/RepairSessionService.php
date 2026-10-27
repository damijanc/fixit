<?php

declare(strict_types=1);

namespace App\Services\Repair;

use App\Models\RepairSession;

final class RepairSessionService
{
    public function latest(): ?RepairSession
    {
        return RepairSession::query()->latest('updated_at')->first();
    }

    public function startWithPhoto(string $photoPath, string $symptoms = ''): RepairSession
    {
        return RepairSession::query()->create([
            'photo_path' => $photoPath,
            'symptoms' => $symptoms !== '' ? $symptoms : null,
            'status' => 'investigating',
        ]);
    }

    public function recordVisionResult(RepairSession $session, string $prompt, string $result): RepairSession
    {
        $session->addObservation('user', $prompt, 'photo_prompt');
        $session->addObservation('assistant', $result, 'vision_result');

        $session->forceFill([
            'summary' => $result,
            'status' => 'awaiting_follow_up',
        ])->save();

        return $session;
    }

    public function discard(RepairSession $session): void
    {
        $session->delete();
    }
}
