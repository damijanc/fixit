<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Services\AI\AiException;
use App\Services\AI\ModelGateway;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Home extends NativeComponent
{
    public string $aiStatus = '';

    public bool $aiBusy = false;

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

    public function render(): View
    {
        return view('native.home');
    }
}
