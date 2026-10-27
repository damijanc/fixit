<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Services\AI\AiException;
use App\Services\AI\ModelGateway;
use Illuminate\View\View;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Events\Camera\PermissionDenied;
use Native\Mobile\Events\Camera\PhotoCancelled;
use Native\Mobile\Events\Camera\PhotoTaken;
use Native\Mobile\Facades\Camera;

class Home extends NativeComponent
{
    public string $aiStatus = '';

    public bool $aiBusy = false;

    public string $cameraStatus = '';

    public string $photoPath = '';

    public string $visionStatus = '';

    public string $visionResult = '';

    public bool $visionBusy = false;

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
        if ($this->photoPath !== '' && is_file($this->photoPath)) {
            @unlink($this->photoPath);
        }

        $this->photoPath = '';
        $this->visionStatus = '';
        $this->visionResult = '';
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
            $this->visionResult = app(ModelGateway::class)->askWithImage(
                $this->visionPrompt(),
                $this->photoPath,
            );
            $this->visionStatus = 'DeepSeek response received. Check every suggestion before acting on it.';
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
        return <<<'PROMPT'
You are FixIt, a cautious repair assistant. Look at the photo and identify the object and the most likely fault. Ask at most one diagnostic question. Suggest only low-risk, non-invasive checks. Never tell the user to open mains-powered devices, service gas equipment, bypass interlocks, or perform hazardous tests. Escalate to a qualified professional when appropriate. Keep the response under 120 words and state uncertainty.
PROMPT;
    }

    public function render(): View
    {
        return view('native.home');
    }
}
