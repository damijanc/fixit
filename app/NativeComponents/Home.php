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

    public function render(): View
    {
        return view('native.home');
    }
}
