<?php

declare(strict_types=1);

use Fixit\PhotoResize\PhotoResize;
use Native\Mobile\Testing\FakeBridge;
use Tests\TestCase;

uses(TestCase::class);

it('asks the native plugin to downscale the photo in place', function () {
    $bridge = FakeBridge::enable()->respondTo('PhotoResize.Downscale', [
        'path' => '/data/photo.jpg', 'width' => 1568, 'height' => 1176, 'bytes' => 245_000, 'resized' => true,
    ]);

    $result = app(PhotoResize::class)->downscale('/data/photo.jpg');

    expect($result)->toBe([
        'path' => '/data/photo.jpg', 'width' => 1568, 'height' => 1176, 'bytes' => 245_000, 'resized' => true,
    ]);
    $bridge->assertCalled('PhotoResize.Downscale', fn (array $params): bool => $params === [
        'path' => '/data/photo.jpg', 'maxEdge' => 1568, 'quality' => 85,
    ]);
});

it('returns null when the native resize fails or is missing', function (array|string|null $response) {
    FakeBridge::enable()->respondTo('PhotoResize.Downscale', $response);

    expect(app(PhotoResize::class)->downscale('/data/photo.jpg'))->toBeNull();
})->with([
    'bridge error' => [['status' => 'error', 'code' => 'RESIZE_FAILED', 'message' => 'out of memory']],
    'function not registered' => [null],
    'not json' => ['oops'],
]);
