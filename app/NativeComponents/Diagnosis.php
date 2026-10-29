<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Models\RepairSession;
use App\Services\AI\AiException;
use App\Services\AI\ModelGateway;
use App\Services\Diagnosis\ConversationBuilder;
use App\Services\Diagnosis\DiagnosisPrompts;
use App\Services\Diagnosis\DiagnosisReply;
use App\Services\Repair\RepairSessionService;
use App\Services\Safety\SafetyFilter;
use App\Services\Safety\SafetyResult;
use Fixit\PhotoResize\PhotoResize;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Events\Camera\PermissionDenied;
use Native\Mobile\Events\Camera\PhotoCancelled;
use Native\Mobile\Events\Camera\PhotoTaken;
use Native\Mobile\Facades\Camera;

class Diagnosis extends NativeComponent
{
    public string $cameraStatus = '';

    public string $photoPath = '';

    public string $visionStatus = '';

    public string $visionResult = '';

    /** Failed photo request, shown next to the send button. */
    public string $visionError = '';

    public bool $visionBusy = false;

    public string $symptoms = '';

    /**
     * Text the symptoms field shows when it is mounted (a resumed session).
     * Typing updates $symptoms only, so PHP never echoes keystrokes back into
     * the native field: a stale echo overwrote newer text and dropped
     * characters. Discarding the photo unmounts the field, which clears it.
     */
    public string $symptomsInitial = '';

    public string $sessionId = '';

    public string $sessionNotice = '';

    public string $followUpAnswer = '';

    /** Text the follow-up field shows when it mounts: a failed answer to retry. */
    public string $followUpInitial = '';

    /** The answer being sent, shown in the thread until the reply arrives. */
    public string $pendingAnswer = '';

    public string $followUpStatus = '';

    public string $followUpResult = '';

    public bool $followUpBusy = false;

    /** @var list<array{role: string, text: string}> */
    public array $turns = [];

    public string $safetyNotice = '';

    public string $providerName = 'your AI provider';

    public function mount(): void
    {
        $this->providerName = app(ModelGateway::class)->providerName();

        $sessionId = $this->param('session');

        if (! is_string($sessionId) || $sessionId === '') {
            return;
        }

        $session = RepairSession::query()->find($sessionId);

        if (! $session instanceof RepairSession) {
            $this->sessionNotice = 'Repair session not found.';

            return;
        }

        $this->sessionId = $session->id;
        $this->symptoms = (string) ($session->symptoms ?? '');
        $this->symptomsInitial = $this->symptoms;
        $this->visionResult = (string) ($session->summary ?? '');

        if (is_string($session->photo_path) && is_file($session->photo_path)) {
            $this->photoPath = $session->photo_path;
        }

        if ($this->visionResult !== '') {
            $this->visionStatus = 'Resumed the previous repair session.';
        }

        if ($session->status === 'escalated') {
            $this->safetyNotice = 'This session was escalated for safety. Use a qualified professional for mains or gas work.';
        }

        $this->turns = app(RepairSessionService::class)->followUpTurns($session);

        $followUp = app(RepairSessionService::class)->latestFollowUp($session);
        if ($followUp !== null) {
            $this->followUpResult = $followUp;
            $this->followUpStatus = 'Resumed the previous follow-up answer.';
        }

        $this->sessionNotice = 'Previous repair session loaded from local storage.';
    }

    public function openSettings(): void
    {
        $this->navigate('/settings');
    }

    public function openResult(): void
    {
        if ($this->sessionId === '') {
            $this->visionStatus = 'Start a diagnosis and send a photo before opening the result.';

            return;
        }

        $this->navigate('/result/'.$this->sessionId);
    }

    public function goHome(): void
    {
        $this->navigate('/');
    }

    public function takePhoto(): void
    {
        $this->cameraStatus = 'Opening the camera… After the shutter, tap ✓ to confirm the photo.';

        $started = Camera::getPhoto()
            ->id('fixit-camera-check')
            ->start();

        if (! $started) {
            $this->cameraStatus = 'The camera could not be opened. Check the permission and try again.';
        }
    }

    #[On(PhotoTaken::class)]
    public function handlePhotoTaken(string $path, string $mimeType = 'image/jpeg'): void
    {
        self::shrinkPhoto($path);

        $this->photoPath = $path;
        $this->cameraStatus = "Photo captured: {$mimeType}, ".basename($path);
        $this->visionStatus = '';
        $this->visionError = '';
        $this->visionResult = '';
        $this->followUpAnswer = '';
        $this->followUpInitial = '';
        $this->followUpStatus = '';
        $this->followUpResult = '';
        $this->turns = [];
        $this->safetyNotice = '';

        $session = app(RepairSessionService::class)->startWithPhoto($path, trim($this->symptoms));
        $this->sessionId = $session->id;
        $this->sessionNotice = 'Repair session saved locally.';
    }

    #[On(PhotoCancelled::class)]
    public function handlePhotoCancelled(): void
    {
        $this->cameraStatus = 'No photo received. Take the photo, then tap the ✓ checkmark on the camera review screen.';
    }

    #[On(PermissionDenied::class)]
    public function handleCameraPermissionDenied(string $action): void
    {
        $this->cameraStatus = "Camera permission denied for {$action}. Enable it in Android settings.";
    }

    public function discardPhoto(): void
    {
        if ($this->sessionId !== '') {
            $session = RepairSession::query()->find($this->sessionId);
            if ($session instanceof RepairSession) {
                app(RepairSessionService::class)->discard($session);
            }
        }

        if ($this->photoPath !== '' && is_file($this->photoPath)) {
            @unlink($this->photoPath);
        }

        $this->photoPath = '';
        $this->visionStatus = '';
        $this->visionError = '';
        $this->visionResult = '';
        $this->symptoms = '';
        $this->symptomsInitial = '';
        $this->sessionId = '';
        $this->sessionNotice = '';
        $this->followUpAnswer = '';
        $this->followUpInitial = '';
        $this->followUpStatus = '';
        $this->followUpResult = '';
        $this->turns = [];
        $this->safetyNotice = '';
        $this->cameraStatus = 'Photo discarded.';
    }

    public function analyzePhoto(): void
    {
        if ($this->visionBusy) {
            return;
        }

        if ($this->photoPath === '' || ! is_file($this->photoPath)) {
            $this->visionStatus = "Take a photo before asking {$this->providerName} to analyze it.";

            return;
        }

        $this->visionBusy = true;
        $this->visionStatus = '';
        $this->visionError = '';
        $this->visionResult = '';
        $this->safetyNotice = '';

        $prompts = app(DiagnosisPrompts::class);
        $structured = app(ModelGateway::class)->supportsStructuredOutput();
        $prompt = $prompts->photoRequest($this->symptoms);
        $system = $prompts->photoSystem($structured);
        $schema = $structured ? DiagnosisReply::schema() : null;
        $photoPath = $this->photoPath;

        // The model call runs on a background PHP thread so the busy state
        // renders immediately; the UI thread would otherwise block until the
        // reply arrives and the tap would look like it did nothing.
        $this->async(static fn (): string => app(ModelGateway::class)->askWithImage($prompt, $photoPath, $system, $schema))
            ->timeout(self::aiTaskTimeout())
            ->finished(fn (string $result) => $this->photoAnalyzed($prompt, $result))
            ->failed(function (\Throwable $exception): void {
                $this->visionBusy = false;
                $this->visionError = AiException::displayMessage($exception, 'Unexpected error while analyzing the photo.');
            });
    }

    private function photoAnalyzed(string $prompt, string $result): void
    {
        $this->visionBusy = false;

        $session = $this->sessionId !== ''
            ? RepairSession::query()->find($this->sessionId)
            : null;

        if (! $session instanceof RepairSession) {
            $session = app(RepairSessionService::class)->startWithPhoto($this->photoPath, trim($this->symptoms));
            $this->sessionId = $session->id;
        }

        if (trim($this->symptoms) !== '' && $session->symptoms !== trim($this->symptoms)) {
            $session->forceFill(['symptoms' => trim($this->symptoms)])->save();
        }

        $assessment = self::assessReply($result);
        $status = $assessment->blocked || $assessment->escalated ? 'escalated' : 'awaiting_follow_up';

        app(RepairSessionService::class)->recordVisionResult($session, $prompt, $assessment->text, $status);

        $this->visionResult = $assessment->text;
        $this->safetyNotice = $assessment->blocked
            ? 'Safety filter blocked unsafe guidance. This needs a qualified professional.'
            : ($assessment->escalated ? 'Safety note: mains, gas or battery risk. Use a qualified professional.' : '');
        $this->visionStatus = "{$this->providerName} response received. Check every suggestion before acting on it.";
        $this->followUpAnswer = '';
        $this->followUpInitial = '';
        $this->followUpStatus = '';
        $this->followUpResult = '';
        $this->turns = app(RepairSessionService::class)->followUpTurns($session);
        $this->sessionNotice = 'Repair session saved locally.';
    }

    /**
     * Phone photos are several MB; providers time out or reject them (503 on
     * the Hugging Face router). The native plugin rewrites the file in place;
     * if it is unavailable or fails, the original photo is used.
     */
    private static function shrinkPhoto(string $path): void
    {
        $before = is_file($path) ? (int) filesize($path) : 0;
        $result = app(PhotoResize::class)->downscale($path);

        if ($result === null) {
            Log::warning('Photo not downscaled', ['bytes' => $before]);

            return;
        }

        Log::info('Photo downscaled', [
            'bytes_before' => $before,
            'bytes_after' => $result['bytes'],
            'width' => $result['width'],
            'height' => $result['height'],
            'resized' => $result['resized'],
        ]);
    }

    /**
     * Structured replies (Claude) are checked field by field; free text as a
     * whole. Either way the stored text is what SafetyFilter returns.
     */
    private static function assessReply(string $result): SafetyResult
    {
        $reply = DiagnosisReply::fromJson($result);
        $filter = app(SafetyFilter::class);

        return $reply !== null ? $filter->assessDiagnosis($reply) : $filter->assess($result);
    }

    /**
     * Longer than the HTTP timeout, so a slow provider reports its own error
     * before the task is abandoned.
     */
    private static function aiTaskTimeout(): int
    {
        return (int) config('ai.timeout', 60) + 30;
    }

    public function sendFollowUp(): void
    {
        if ($this->followUpBusy) {
            return;
        }

        $answer = trim($this->followUpAnswer);

        if ($answer === '') {
            $this->followUpStatus = 'Type an answer or observation first.';

            return;
        }

        $session = $this->sessionId !== ''
            ? RepairSession::query()->find($this->sessionId)
            : null;

        if (! $session instanceof RepairSession) {
            $this->followUpStatus = 'Start a photo session before answering a follow-up.';

            return;
        }

        $this->followUpBusy = true;
        $this->pendingAnswer = $answer;
        $this->followUpStatus = '';

        $sessionId = $session->id;
        $structured = app(ModelGateway::class)->supportsStructuredOutput();
        $system = app(DiagnosisPrompts::class)->followUpSystem($structured);
        $schema = $structured ? DiagnosisReply::schema() : null;

        // Background thread, like analyzePhoto(); the session is reloaded there
        // because models do not cross the thread boundary.
        $this->async(static fn (): string => app(ModelGateway::class)->continueConversation(
            app(ConversationBuilder::class)->forFollowUp(RepairSession::query()->findOrFail($sessionId), $answer),
            $system,
            $schema,
        ))
            ->timeout(self::aiTaskTimeout())
            ->finished(fn (string $result) => $this->followUpAnswered($answer, $result))
            ->failed(function (\Throwable $exception) use ($answer): void {
                $this->followUpBusy = false;
                $this->pendingAnswer = '';
                // The field remounted empty while the answer sat in the thread.
                $this->followUpInitial = $answer;
                $this->followUpStatus = AiException::displayMessage($exception, 'Unexpected error while continuing the diagnosis.');
            });
    }

    private function followUpAnswered(string $answer, string $result): void
    {
        $this->followUpBusy = false;
        $this->pendingAnswer = '';

        $session = RepairSession::query()->find($this->sessionId);

        if (! $session instanceof RepairSession) {
            $this->followUpStatus = 'The repair session was removed while waiting for the answer.';

            return;
        }

        $assessment = self::assessReply($result);
        $status = $assessment->blocked || $assessment->escalated ? 'escalated' : 'awaiting_follow_up';

        app(RepairSessionService::class)->recordFollowUp($session, $answer, $assessment->text, $status);

        $this->followUpAnswer = '';
        $this->followUpInitial = '';
        $this->followUpResult = $assessment->text;
        $this->turns = app(RepairSessionService::class)->followUpTurns($session);
        $this->safetyNotice = $assessment->blocked
            ? 'Safety filter blocked unsafe guidance. This needs a qualified professional.'
            : ($assessment->escalated ? 'Safety note: mains, gas or battery risk. Use a qualified professional.' : '');
        $this->followUpStatus = 'Follow-up saved locally.';
    }

    public function render(): View
    {
        return view('native.diagnosis');
    }
}
