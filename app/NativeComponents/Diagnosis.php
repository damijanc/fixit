<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Models\RepairSession;
use App\Services\AI\AiException;
use App\Services\AI\ModelGateway;
use App\Services\Diagnosis\ConversationBuilder;
use App\Services\Diagnosis\DiagnosisPrompts;
use App\Services\Repair\RepairSessionService;
use App\Services\Safety\SafetyFilter;
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

    public bool $visionBusy = false;

    public string $symptoms = '';

    public string $sessionId = '';

    public string $sessionNotice = '';

    public string $followUpAnswer = '';

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
        $this->photoPath = $path;
        $this->cameraStatus = "Photo captured: {$mimeType}, ".basename($path);
        $this->visionStatus = '';
        $this->visionResult = '';
        $this->followUpAnswer = '';
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
        $this->visionResult = '';
        $this->symptoms = '';
        $this->sessionId = '';
        $this->sessionNotice = '';
        $this->followUpAnswer = '';
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
        $this->visionStatus = "Uploading the photo to {$this->providerName} for analysis…";
        $this->visionResult = '';
        $this->safetyNotice = '';

        try {
            $prompts = app(DiagnosisPrompts::class);
            $prompt = $prompts->photoRequest($this->symptoms);
            $result = app(ModelGateway::class)->askWithImage($prompt, $this->photoPath, $prompts->photoSystem());

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

            $assessment = app(SafetyFilter::class)->assess($result);
            $status = $assessment->blocked || $assessment->escalated ? 'escalated' : 'awaiting_follow_up';

            app(RepairSessionService::class)->recordVisionResult($session, $prompt, $assessment->text, $status);

            $this->visionResult = $assessment->text;
            $this->safetyNotice = $assessment->blocked
                ? 'Safety filter blocked unsafe guidance. This needs a qualified professional.'
                : ($assessment->escalated ? 'Safety note: mains or gas risk. Use a qualified professional.' : '');
            $this->visionStatus = "{$this->providerName} response received. Check every suggestion before acting on it.";
            $this->followUpAnswer = '';
            $this->followUpStatus = '';
            $this->followUpResult = '';
            $this->turns = app(RepairSessionService::class)->followUpTurns($session);
            $this->sessionNotice = 'Repair session saved locally.';
        } catch (AiException $exception) {
            $this->visionStatus = $exception->getMessage();
        } catch (\Throwable $exception) {
            report($exception);
            $this->visionStatus = 'Unexpected error while analyzing the photo.';
        } finally {
            $this->visionBusy = false;
        }
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
        $this->followUpStatus = 'Thinking about your answer…';

        try {
            $result = app(ModelGateway::class)->continueConversation(
                app(ConversationBuilder::class)->forFollowUp($session, $answer),
                app(DiagnosisPrompts::class)->followUpSystem(),
            );

            $assessment = app(SafetyFilter::class)->assess($result);
            $status = $assessment->blocked || $assessment->escalated ? 'escalated' : 'awaiting_follow_up';

            app(RepairSessionService::class)->recordFollowUp($session, $answer, $assessment->text, $status);

            $this->followUpAnswer = '';
            $this->followUpResult = $assessment->text;
            $this->turns = app(RepairSessionService::class)->followUpTurns($session);
            $this->safetyNotice = $assessment->blocked
                ? 'Safety filter blocked unsafe guidance. This needs a qualified professional.'
                : ($assessment->escalated ? 'Safety note: mains or gas risk. Use a qualified professional.' : '');
            $this->followUpStatus = 'Follow-up saved locally.';
        } catch (AiException $exception) {
            $this->followUpStatus = $exception->getMessage();
        } catch (\Throwable $exception) {
            report($exception);
            $this->followUpStatus = 'Unexpected error while continuing the diagnosis.';
        } finally {
            $this->followUpBusy = false;
        }
    }

    public function render(): View
    {
        return view('native.diagnosis');
    }
}
