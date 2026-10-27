<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Models\RepairSession;
use App\Services\AI\AiException;
use App\Services\AI\ModelGateway;
use App\Services\Repair\RepairSessionService;
use Illuminate\View\View;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Events\Camera\PermissionDenied;
use Native\Mobile\Events\Camera\PhotoCancelled;
use Native\Mobile\Events\Camera\PhotoTaken;
use Native\Mobile\Facades\Camera;
use Symfony\AI\Platform\Message\AssistantMessage;
use Symfony\AI\Platform\Message\Content\Text;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\SystemMessage;
use Symfony\AI\Platform\Message\UserMessage;

class Home extends NativeComponent
{
    public string $aiStatus = '';

    public bool $aiBusy = false;

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

    public function mount(): void
    {
        $session = app(RepairSessionService::class)->latest();

        if (! $session instanceof RepairSession) {
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

        $followUp = app(RepairSessionService::class)->latestFollowUp($session);
        if ($followUp !== null) {
            $this->followUpResult = $followUp;
            $this->followUpStatus = 'Resumed the previous follow-up answer.';
        }

        $this->sessionNotice = 'Previous repair session loaded from local storage.';
    }

    public function testAiConnection(): void
    {
        if ($this->aiBusy) {
            return;
        }

        $this->aiBusy = true;
        $this->aiStatus = 'Contacting the model…';

        try {
            $reply = app(ModelGateway::class)->ask('Reply with exactly: FixIt AI connectivity OK');
            $this->aiStatus = 'OK: '.$reply;
        } catch (AiException $exception) {
            $this->aiStatus = $exception->getMessage();
        } catch (\Throwable $exception) {
            report($exception);
            $this->aiStatus = 'Unexpected error while contacting the model.';
        } finally {
            $this->aiBusy = false;
        }
    }

    public function openSettings(): void
    {
        $this->navigate('/settings');
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
        $this->cameraStatus = 'Photo discarded.';
    }

    public function analyzePhoto(): void
    {
        if ($this->visionBusy) {
            return;
        }

        if ($this->photoPath === '' || ! is_file($this->photoPath)) {
            $this->visionStatus = 'Take a photo before asking DeepSeek to analyze it.';

            return;
        }

        $this->visionBusy = true;
        $this->visionStatus = 'Uploading the photo to DeepSeek for analysis…';
        $this->visionResult = '';

        try {
            $prompt = $this->visionPrompt();
            $result = app(ModelGateway::class)->askWithImage($prompt, $this->photoPath);

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

            app(RepairSessionService::class)->recordVisionResult($session, $prompt, $result);

            $this->visionResult = $result;
            $this->visionStatus = 'DeepSeek response received. Check every suggestion before acting on it.';
            $this->followUpAnswer = '';
            $this->followUpStatus = '';
            $this->followUpResult = '';
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

    private function visionPrompt(): string
    {
        $symptoms = trim($this->symptoms);
        $symptomContext = $symptoms === ''
            ? 'The user has not described symptoms yet.'
            : "The user describes these symptoms: {$symptoms}";

        return <<<PROMPT
You are FixIt, a cautious repair assistant. Look at the photo and identify the object and the most likely fault. Ask at most one diagnostic question. Suggest only low-risk, non-invasive checks. Never tell the user to open mains-powered devices, service gas equipment, bypass interlocks, or perform hazardous tests. Escalate to a qualified professional when appropriate. Keep the response under 120 words and state uncertainty. {$symptomContext}
PROMPT;
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
                $this->followUpConversation($session, $answer),
            );

            app(RepairSessionService::class)->recordFollowUp($session, $answer, $result);

            $this->followUpAnswer = '';
            $this->followUpResult = $result;
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

    private function followUpConversation(RepairSession $session, string $answer): MessageBag
    {
        $messages = new MessageBag(new SystemMessage($this->followUpPrompt()));

        $prompt = $session->observations()
            ->where('kind', 'photo_prompt')
            ->latest('id')
            ->value('content');

        if (is_string($prompt) && $prompt !== '') {
            $messages->add(new UserMessage(new Text($prompt)));
        }

        if (is_string($session->summary) && $session->summary !== '') {
            $messages->add(new AssistantMessage(new Text($session->summary)));
        }

        $history = $session->observations()
            ->whereIn('kind', ['follow_up_answer', 'follow_up_result'])
            ->orderBy('id')
            ->get();

        foreach ($history as $observation) {
            $content = new Text((string) $observation->content);

            $messages->add(
                $observation->role === 'assistant'
                    ? new AssistantMessage($content)
                    : new UserMessage($content),
            );
        }

        $messages->add(new UserMessage(new Text($answer)));

        return $messages;
    }

    private function followUpPrompt(): string
    {
        return 'You are FixIt, a cautious repair assistant. Use the earlier photo analysis, symptoms and answers. Respond with a short cautious assessment and at most one new diagnostic question. Suggest only low-risk, non-invasive checks. Never tell the user to open mains-powered devices, service gas equipment, bypass interlocks, or perform hazardous tests. Escalate to a qualified professional when appropriate. Keep the response under 100 words and state uncertainty.';
    }

    public function render(): View
    {
        return view('native.home');
    }
}
