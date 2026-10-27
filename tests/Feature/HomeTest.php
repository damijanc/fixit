<?php

declare(strict_types=1);

use App\Services\AI\ModelGateway;
use Native\Mobile\Testing\Native;
use Symfony\AI\Platform\Test\MockPlatformFactory;
use Tests\Support\InMemoryApiKeyStore;

it('renders the FixIt home screen', function () {
    Native::visit('/')
        ->assertSee('FixIt')
        ->assertSee('AI connectivity')
        ->assertSee('Test AI connection')
        ->assertSee('Provider settings')
        ->assertSee('tap the ✓ checkmark to confirm')
        ->assertSee('Android-only · Local sessions · No account required');
});

it('shows the model reply after a successful connectivity check', function () {
    app()->instance(ModelGateway::class, new ModelGateway(
        new InMemoryApiKeyStore('test-key'),
        MockPlatformFactory::createPlatform('FixIt AI connectivity OK'),
        'test-model',
    ));

    Native::visit('/')
        ->tap('test-ai-button')
        ->assertSee('OK: FixIt AI connectivity OK')
        ->assertElement('text', fn (array $node): bool => ($node['ref'] ?? null) === 'ai-status');
});

it('shows a safe message when no api key is saved', function () {
    app()->instance(ModelGateway::class, new ModelGateway(new InMemoryApiKeyStore));

    Native::visit('/')
        ->tap('test-ai-button')
        ->assertSee('No provider API key saved');
});

it('opens the native camera when the photo button is tapped', function () {
    Native::visit('/')
        ->tap('take-photo-button')
        ->assertPhotoRequested();
});

it('shows the captured photo path and preview', function () {
    Native::visit('/')
        ->call('handlePhotoTaken', '/data/user/0/com.fixit.app/no_backup/Camera/fixit-test.jpg', 'image/jpeg')
        ->assertSee('Photo captured: image/jpeg, fixit-test.jpg')
        ->assertElement('image', fn (array $node): bool => ($node['ref'] ?? null) === 'captured-photo'
            && ($node['props']['src'] ?? null) === '/data/user/0/com.fixit.app/no_backup/Camera/fixit-test.jpg');
});

it('explains when camera permission is denied', function () {
    Native::visit('/')
        ->call('handleCameraPermissionDenied', 'photo')
        ->assertSee('Camera permission denied for photo');
});

it('explains the confirmation step when no photo is returned', function () {
    Native::visit('/')
        ->call('handlePhotoCancelled')
        ->assertSee('No photo received')
        ->assertSee('tap the ✓ checkmark on the camera review screen');
});

it('requires an explicit action before uploading the photo', function () {
    app()->instance(ModelGateway::class, new ModelGateway(
        new InMemoryApiKeyStore('test-key'),
        MockPlatformFactory::createPlatform('I can see a blue test image.'),
        'deepseek-flash',
    ));

    Native::visit('/')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->assertSee('Sending uploads this photo to DeepSeek')
        ->assertSee('Send photo to DeepSeek')
        ->tap('analyze-photo-button')
        ->assertSee('DeepSeek response received')
        ->assertSee('I can see a blue test image.')
        ->assertElement('image', fn (array $node): bool => ($node['ref'] ?? null) === 'captured-photo');
});

it('keeps the photo when the vision request fails', function () {
    app()->instance(ModelGateway::class, new ModelGateway(
        new InMemoryApiKeyStore('test-key'),
        MockPlatformFactory::createPlatform(static function (): never {
            throw new RuntimeException('provider failure', 402);
        }),
        'deepseek-flash',
    ));

    Native::visit('/')
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

    Native::visit('/')
        ->call('handlePhotoTaken', $temporaryPhoto, 'image/jpeg')
        ->tap('discard-photo-button')
        ->assertSee('Photo discarded.')
        ->assertMissingElement('image', fn (array $node): bool => ($node['ref'] ?? null) === 'captured-photo');

    expect(is_file($temporaryPhoto))->toBeFalse();
});
