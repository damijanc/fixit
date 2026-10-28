<?php

declare(strict_types=1);

use App\Services\AI\ModelGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Native\Mobile\Testing\Native;
use Symfony\AI\Platform\Test\MockPlatformFactory;
use Tests\Support\TestGateway;

uses(RefreshDatabase::class);

it('sends the safety system prompt with the photo and the follow-up', function () {
    $systemPrompts = [];

    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform(static function ($model, array $payload) use (&$systemPrompts): string {
            foreach ($payload['messages'] ?? [] as $message) {
                if (($message['role'] ?? null) === 'system') {
                    $systemPrompts[] = (string) $message['content'];
                }
            }

            return 'Check that the plug is fully inserted.';
        }),
    ));

    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->set('symptoms', 'no power')
        ->call('analyzePhoto')
        ->set('followUpAnswer', 'the plug is in')
        ->call('sendFollowUp');

    expect($systemPrompts)->toHaveCount(2);

    foreach ($systemPrompts as $prompt) {
        expect($prompt)->toContain('Never tell the user to open mains-powered devices');
    }

    expect($systemPrompts[0])->toContain('under 120 words')
        ->and($systemPrompts[1])->toContain('under 100 words');
});
