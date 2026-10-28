<?php

declare(strict_types=1);

use App\Models\RepairSession;
use App\Services\AI\ModelGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Native\Mobile\Testing\Native;
use Symfony\AI\Platform\Test\MockPlatformFactory;
use Tests\Support\TestGateway;

uses(RefreshDatabase::class);

it('opens the native camera when the photo button is tapped', function () {
    Native::visit('/diagnosis')
        ->tap('take-photo-button')
        ->assertPhotoRequested();
});

it('shows the captured photo path and preview', function () {
    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', '/data/user/0/com.fixit.app/no_backup/Camera/fixit-test.jpg', 'image/jpeg')
        ->assertSee('Photo captured: image/jpeg, fixit-test.jpg')
        ->assertElement('image', fn (array $node): bool => ($node['ref'] ?? null) === 'captured-photo'
            && ($node['props']['src'] ?? null) === '/data/user/0/com.fixit.app/no_backup/Camera/fixit-test.jpg');
});

it('explains when camera permission is denied', function () {
    Native::visit('/diagnosis')
        ->call('handleCameraPermissionDenied', 'photo')
        ->assertSee('Camera permission denied for photo');
});

it('explains the confirmation step when no photo is returned', function () {
    Native::visit('/diagnosis')
        ->call('handlePhotoCancelled')
        ->assertSee('No photo received')
        ->assertSee('tap the ✓ checkmark on the camera review screen');
});

it('requires an explicit action before uploading the photo', function () {
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform('I can see a blue test image.'),
    ));

    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->assertSee('Sending uploads this photo and symptoms to DeepSeek')
        ->assertSee('Send photo to DeepSeek')
        ->tap('analyze-photo-button')
        ->assertSee('DeepSeek response received')
        ->assertSee('I can see a blue test image.')
        ->assertElement('image', fn (array $node): bool => ($node['ref'] ?? null) === 'captured-photo');
});

it('uses the selected provider name in the photo consent copy', function () {
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform('I can see a blue test image.'),
        provider: 'openrouter',
        model: 'openai/gpt-4.1',
        key: 'or-key-12345678',
    ));

    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->assertSee('Sending uploads this photo and symptoms to OpenRouter')
        ->assertSee('Send photo to OpenRouter')
        ->tap('analyze-photo-button')
        ->assertSee('OpenRouter response received');
});

it('keeps the photo when the vision request fails', function () {
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform(static function (): never {
            throw new RuntimeException('provider failure', 402);
        }),
    ));

    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->tap('analyze-photo-button')
        ->assertSee('insufficient balance')
        ->assertElement('image', fn (array $node): bool => ($node['ref'] ?? null) === 'captured-photo');
});

it('discards the photo from the device', function () {
    $temporaryBase = tempnam(sys_get_temp_dir(), 'fixit-photo-');
    $temporaryPhoto = $temporaryBase.'.jpg';
    copy(base_path('tests/Fixtures/vision-photo.jpg'), $temporaryPhoto);
    @unlink($temporaryBase);

    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', $temporaryPhoto, 'image/jpeg')
        ->tap('discard-photo-button')
        ->assertSee('Photo discarded.')
        ->assertMissingElement('image', fn (array $node): bool => ($node['ref'] ?? null) === 'captured-photo');

    expect(is_file($temporaryPhoto))->toBeFalse();
});

it('restores the latest repair session after a restart', function () {
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform('The image shows a blue test object.'),
    ));

    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->set('symptoms', 'no water flow')
        ->tap('analyze-photo-button')
        ->assertSee('The image shows a blue test object.');

    $session = RepairSession::query()->latest('updated_at')->firstOrFail();

    Native::visit('/diagnosis/'.$session->id)
        ->assertSee('Resumed the previous repair session.')
        ->assertSee('The image shows a blue test object.')
        ->assertElement('image', fn (array $node): bool => ($node['ref'] ?? null) === 'captured-photo');
});

it('continues the diagnosis with a follow-up answer and restores it', function () {
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform(static function ($model, $input, $options): string {
            $payload = json_encode($input);

            return str_contains((string) $payload, 'follow-up answer')
                ? 'Follow-up result: the pump may be blocked.'
                : 'Vision result: I see a test object.';
        }),
    ));

    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->set('symptoms', 'no water flow')
        ->tap('analyze-photo-button')
        ->assertSee('Your answer')
        ->set('followUpAnswer', 'follow-up answer: the light is on')
        ->tap('send-follow-up-button')
        ->assertSee('Follow-up result: the pump may be blocked.')
        ->assertSee('follow-up answer: the light is on');

    $session = RepairSession::query()->latest('updated_at')->firstOrFail();

    Native::visit('/diagnosis/'.$session->id)
        ->assertSee('Follow-up result: the pump may be blocked.')
        ->assertSee('follow-up answer: the light is on')
        ->assertSee('Resumed the previous follow-up answer.');

    expect($session->observations()->where('kind', 'follow_up_answer')->count())->toBe(1);
    expect($session->observations()->where('kind', 'follow_up_result')->count())->toBe(1);
});

it('blocks unsafe model guidance before it is displayed', function () {
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform('Open the back panel and bypass the interlock.'),
    ));

    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->tap('analyze-photo-button')
        ->assertSee('Safety filter blocked unsafe guidance')
        ->assertSee("can't provide that instruction")
        ->assertDontSee('bypass the interlock');

    $session = RepairSession::query()->latest('updated_at')->firstOrFail();

    expect($session->status)->toBe('escalated');
    expect($session->summary)->not->toContain('bypass the interlock');
});

it('blocks unsafe follow-up guidance too', function () {
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform(static function ($model, $input, $options): string {
            $payload = json_encode($input);

            return str_contains((string) $payload, 'follow-up answer')
                ? 'Open the case and touch the mains wiring.'
                : 'Vision result: safe external check only.';
        }),
    ));

    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->tap('analyze-photo-button')
        ->set('followUpAnswer', 'follow-up answer: it still does not work')
        ->tap('send-follow-up-button')
        ->assertSee('Safety filter blocked unsafe guidance')
        ->assertDontSee('touch the mains wiring');
});
