<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Services\AI\AiException;
use App\Services\AI\ApiKeyStore;
use App\Services\AI\ModelGateway;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Settings extends NativeComponent
{
    public string $apiKey = '';

    public string $status = '';

    public bool $hasKey = false;

    public function mount(): void
    {
        $this->hasKey = app(ApiKeyStore::class)->isConfigured();
    }

    public function saveKey(): void
    {
        $key = trim($this->apiKey);

        if (mb_strlen($key) < 8) {
            $this->status = 'Enter a valid provider API key first.';

            return;
        }

        if (! app(ApiKeyStore::class)->put($key)) {
            $this->status = 'Could not save the key in secure storage. Try again.';

            return;
        }

        $this->apiKey = '';
        $this->hasKey = true;
        $this->status = 'API key saved securely on this device.';
    }

    public function removeKey(): void
    {
        if (! app(ApiKeyStore::class)->forget()) {
            $this->status = 'Could not remove the saved key. Try again.';

            return;
        }

        $this->apiKey = '';
        $this->hasKey = false;
        $this->status = 'Saved API key removed from this device.';
    }

    public function testConnection(): void
    {
        if (! app(ApiKeyStore::class)->isConfigured()) {
            $this->status = 'Save a provider API key before testing the connection.';

            return;
        }

        $this->status = 'Contacting the model…';

        try {
            $reply = app(ModelGateway::class)->ask('Reply with exactly: FixIt AI connectivity OK');
            $this->status = 'Connected: '.$reply;
        } catch (AiException $exception) {
            $this->status = $exception->getMessage();
        } catch (\Throwable $exception) {
            report($exception);
            $this->status = 'Unexpected error while contacting the model.';
        }
    }

    public function goBack(): void
    {
        $this->back();
    }

    public function render(): View
    {
        return view('native.settings');
    }
}
