<?php

declare(strict_types=1);

use App\Models\RepairSession;
use App\Services\AI\ModelGateway;
use App\Services\Repair\RepairSessionService;
use App\Support\DisplayText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Native\Mobile\AsyncTask;
use Native\Mobile\Testing\Native;
use Native\Mobile\Testing\TestableComponent;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\AI\Platform\Exception\ServerException;
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

it('downscales the captured photo before it is saved', function () {
    $page = Native::visit('/diagnosis');
    $page->bridge()->respondTo('PhotoResize.Downscale', fn (array $params): array => [
        'path' => $params['path'], 'width' => 1568, 'height' => 1176, 'bytes' => 245_000, 'resized' => true,
    ]);

    $page->call('handlePhotoTaken', '/data/user/0/com.fixit.app/no_backup/Camera/fixit-test.jpg', 'image/jpeg')
        ->assertCalled('PhotoResize.Downscale', fn (array $params): bool => $params['path'] === '/data/user/0/com.fixit.app/no_backup/Camera/fixit-test.jpg')
        ->assertSee('Photo captured: image/jpeg, fixit-test.jpg');

    expect(RepairSession::query()->sole()->photo_path)->toBe('/data/user/0/com.fixit.app/no_backup/Camera/fixit-test.jpg');
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

it('shows a failed photo request next to the send button', function () {
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform(static function (): never {
            throw new ServerException(503, 'internal server error');
        }),
    ));

    // The message used to land in the status line below the fold, so a
    // failed send looked like the button did nothing.
    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->tap('analyze-photo-button')
        ->assertElement('row', fn (array $node): bool => ($node['ref'] ?? null) === 'vision-error')
        ->assertSee('unavailable (HTTP 503)')
        ->assertMissingElement('text', fn (array $node): bool => ($node['ref'] ?? null) === 'vision-status');
});

it('clears the photo error when the photo is sent again', function () {
    $fail = true;
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform(static function () use (&$fail): string {
            if ($fail) {
                throw new ServerException(503, 'internal server error');
            }

            return 'The image shows a blue test object.';
        }),
    ));

    $screen = Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->tap('analyze-photo-button')
        ->assertSee('unavailable (HTTP 503)');

    $fail = false;

    $screen->tap('analyze-photo-button')
        ->assertDontSee('unavailable (HTTP 503)')
        ->assertMissingElement('row', fn (array $node): bool => ($node['ref'] ?? null) === 'vision-error');
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

/**
 * The rendered text-input node for a ref, so tests can compare the pushed
 * value and the node id across frames.
 *
 * @return array<string, mixed>
 */
function textInputNode(TestableComponent $screen, string $ref): array
{
    $found = [];

    $screen->assertElement('outlined_text_input', function (array $node) use ($ref, &$found): bool {
        if (($node['ref'] ?? null) !== $ref) {
            return false;
        }

        $found = $node;

        return true;
    });

    return $found;
}

it('does not push typed symptoms back into the field', function () {
    // Live native:model echoed every keystroke; a stale echo overwrote newer
    // native text and dropped characters.
    $screen = Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->input('symptoms-input', 'no water comes out')
        ->assertSet('symptoms', 'no water comes out');

    expect(textInputNode($screen, 'symptoms-input')['props']['value'] ?? '')->toBe('');
});

it('does not push a typed follow-up answer back into the field', function () {
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform('Vision result: I see a test object.'),
    ));

    $screen = Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->tap('analyze-photo-button')
        ->input('follow-up-input', 'the light is on')
        ->assertSet('followUpAnswer', 'the light is on');

    expect(textInputNode($screen, 'follow-up-input')['props']['value'] ?? '')->toBe('');
});

it('sends the typed follow-up answer and remounts the field to clear it', function () {
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform(static function ($model, $input, $options): string {
            return str_contains((string) json_encode($input), 'the light is on')
                ? 'Follow-up result: check the pump.'
                : 'Vision result: I see a test object.';
        }),
    ));

    $screen = Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->tap('analyze-photo-button');

    $before = textInputNode($screen, 'follow-up-input')['id'];

    $screen->input('follow-up-input', 'the light is on')
        ->tap('send-follow-up-button')
        ->assertSee('Follow-up result: check the pump.')
        ->assertSet('followUpAnswer', '');

    // A new node id gives the native field fresh, empty state.
    expect(textInputNode($screen, 'follow-up-input')['id'])->not->toBe($before);
});

it('unmounts the symptoms field when the photo is discarded', function () {
    $temporaryBase = tempnam(sys_get_temp_dir(), 'fixit-photo-');
    $temporaryPhoto = $temporaryBase.'.jpg';
    copy(base_path('tests/Fixtures/vision-photo.jpg'), $temporaryPhoto);
    @unlink($temporaryBase);

    // The field is gone for a frame, so the next photo mounts an empty one.
    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', $temporaryPhoto, 'image/jpeg')
        ->input('symptoms-input', 'kettle does not heat')
        ->tap('discard-photo-button')
        ->assertSet('symptoms', '')
        ->assertSet('symptomsInitial', '')
        ->assertMissingElement('outlined_text_input', fn (array $node): bool => ($node['ref'] ?? null) === 'symptoms-input');
});

it('keeps a failed follow-up answer in the field', function () {
    $fail = false;
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform(static function () use (&$fail): string {
            if ($fail) {
                throw new ServerException(503, 'internal server error');
            }

            return 'Vision result: I see a test object.';
        }),
    ));

    $screen = Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->tap('analyze-photo-button');

    $fail = true;

    // While the reply is pending the answer moves into the thread and the
    // field remounts empty, so a failed send puts the text back as its value.
    $screen->input('follow-up-input', 'the light is on')
        ->tap('send-follow-up-button')
        ->assertSee('unavailable (HTTP 503)')
        ->assertSet('followUpAnswer', 'the light is on');

    expect(textInputNode($screen, 'follow-up-input')['props']['value'] ?? '')->toBe('the light is on');
});

/**
 * The rendered node for a ref, or an empty array.
 *
 * @return array<string, mixed>
 */
function nodeByRef(TestableComponent $screen, string $type, string $ref): array
{
    $found = [];

    try {
        $screen->assertElement($type, function (array $node) use ($ref, &$found): bool {
            if (($node['ref'] ?? null) !== $ref) {
                return false;
            }

            $found = $node;

            return true;
        });
    } catch (AssertionFailedError) {
        return [];
    }

    return $found;
}

it('analyzes the photo off the UI thread', function () {
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform('Vision result: I see a test object.'),
    ));

    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->tap('analyze-photo-button')
        ->assertSee('Vision result: I see a test object.')
        ->assertSet('visionBusy', false);

    AsyncTask::fakeInstance()->assertDispatchedTimes(1);
});

it('shows progress and locks the photo card while the photo is analyzed', function () {
    $screen = Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->set('visionBusy', true)
        ->assertSee('is looking at your photo');

    expect(nodeByRef($screen, 'row', 'vision-progress'))->not->toBe([])
        ->and(nodeByRef($screen, 'button', 'analyze-photo-button')['props']['loading'] ?? false)->toBeTrue()
        ->and(nodeByRef($screen, 'button', 'discard-photo-button')['props']['disabled'] ?? false)->toBeTrue()
        ->and(textInputNode($screen, 'symptoms-input')['props']['disabled'] ?? false)->toBeTrue();
});

it('keeps the provider error message from the background task', function () {
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform(static function (): never {
            throw new ServerException(503, 'internal server error');
        }),
    ));

    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->tap('analyze-photo-button')
        ->assertSee('unavailable (HTTP 503)')
        ->assertSet('visionBusy', false)
        ->assertDontSee('is looking at your photo');
});

it('sends the follow-up off the UI thread', function () {
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform(static function ($model, $input): string {
            return str_contains((string) json_encode($input), 'the light is on')
                ? 'Follow-up result: check the pump.'
                : 'Vision result: I see a test object.';
        }),
    ));

    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->tap('analyze-photo-button')
        ->input('follow-up-input', 'the light is on')
        ->tap('send-follow-up-button')
        ->assertSee('Follow-up result: check the pump.')
        ->assertSet('followUpBusy', false)
        ->assertSet('pendingAnswer', '');

    AsyncTask::fakeInstance()->assertDispatchedTimes(2);
});

it('shows the answer in the thread and locks the composer while waiting', function () {
    app()->instance(ModelGateway::class, TestGateway::make(
        MockPlatformFactory::createPlatform('Vision result: I see a test object.'),
    ));

    $screen = Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->tap('analyze-photo-button')
        ->set('pendingAnswer', 'the light is on')
        ->set('followUpBusy', true)
        ->assertSee('the light is on')
        ->assertSee('Thinking…');

    expect(textInputNode($screen, 'follow-up-input')['props']['disabled'] ?? false)->toBeTrue()
        ->and(nodeByRef($screen, 'button', 'send-follow-up-button')['props']['loading'] ?? false)->toBeTrue();
});

it('fills the symptoms field from a resumed session', function () {
    $session = app(RepairSessionService::class)
        ->startWithPhoto(base_path('tests/Fixtures/vision-photo.jpg'), 'kettle does not heat');

    $screen = Native::visit('/diagnosis/'.$session->id)
        ->assertSet('symptoms', 'kettle does not heat');

    expect(textInputNode($screen, 'symptoms-input')['props']['value'] ?? '')->toBe('kettle does not heat');
});

function claudeDiagnosisGateway(array $overrides = []): ModelGateway
{
    return TestGateway::make(
        MockPlatformFactory::createPlatform((string) json_encode(array_merge([
            'object' => 'Phone with a USB-C cable',
            'likely_cause' => 'Lint in the charging port or a faulty cable.',
            'confidence' => 'medium',
            'safe_checks' => ['Try a different cable and charger.', 'Clean the port gently with a wooden toothpick.'],
            'hazard' => 'none',
            'needs_professional' => false,
            'question' => 'Does the charging icon appear at all?',
        ], $overrides))),
        provider: 'anthropic',
        model: 'claude-opus-5-5',
        key: 'sk-ant-test-key',
    );
}

it('shows a structured Claude diagnosis as a card', function () {
    app()->instance(ModelGateway::class, claudeDiagnosisGateway());

    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->set('symptoms', 'phone is not charging')
        ->tap('analyze-photo-button')
        ->assertElement('column', fn (array $node): bool => ($node['ref'] ?? null) === 'vision-result-card')
        ->assertSee('Phone with a USB-C cable')
        ->assertSee('Lint in the charging port or a faulty cable.')
        ->assertSee('Medium confidence')
        ->assertSee('Try a different cable and charger.')
        ->assertSee('Clean the port gently with a wooden toothpick.')
        ->assertSee('Does the charging icon appear at all?')
        ->assertDontSee('"likely_cause"');

    expect(DisplayText::plain((string) RepairSession::query()->latest('updated_at')->firstOrFail()->summary))
        ->toContain('Lint in the charging port');
});

it('shows the professional notice when Claude flags a mains hazard', function () {
    app()->instance(ModelGateway::class, claudeDiagnosisGateway(['hazard' => 'mains']));

    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->tap('analyze-photo-button')
        ->assertSee('Use a qualified professional')
        ->assertSee('Safety note: mains, gas or battery risk');

    expect(RepairSession::query()->latest('updated_at')->firstOrFail()->status)->toBe('escalated');
});

it('blocks a structured reply with a dangerous check', function () {
    app()->instance(ModelGateway::class, claudeDiagnosisGateway([
        'safe_checks' => ['Remove the back cover and replace the battery.'],
    ]));

    Native::visit('/diagnosis')
        ->call('handlePhotoTaken', base_path('tests/Fixtures/vision-photo.jpg'), 'image/jpeg')
        ->tap('analyze-photo-button')
        ->assertSee('Safety filter blocked unsafe guidance')
        ->assertDontSee('back cover');
});
