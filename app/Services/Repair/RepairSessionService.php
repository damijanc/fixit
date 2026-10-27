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

    public function recordFollowUp(RepairSession $session, string $answer, string $result): RepairSession
    {
        $session->addObservation('user', $answer, 'follow_up_answer');
        $session->addObservation('assistant', $result, 'follow_up_result');
        $session->forceFill(['status' => 'awaiting_follow_up'])->save();

        return $session;
    }

    public function latestFollowUp(RepairSession $session): ?string
    {
        $result = $session->observations()
            ->where('kind', 'follow_up_result')
            ->latest('id')
            ->value('content');

        return is_string($result) && $result !== '' ? $result : null;
    }

    public function discard(RepairSession $session): void
    {
        $session->delete();
    }
}
