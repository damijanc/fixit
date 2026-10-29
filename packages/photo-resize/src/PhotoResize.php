<?php

declare(strict_types=1);

namespace Fixit\PhotoResize;

class PhotoResize
{
    /**
     * Downscale a JPEG in place so its long edge is at most $maxEdge pixels.
     *
     * Returns the native result (path, width, height, bytes, resized) or null
     * when the bridge is unavailable or the resize failed; the photo is then
     * left untouched.
     *
     * @return array{path: string, width: int, height: int, bytes: int, resized: bool}|null
     */
    public function downscale(string $path, int $maxEdge = 1568, int $quality = 85): ?array
    {
        if (! function_exists('nativephp_call')) {
            return null;
        }

        $result = nativephp_call('PhotoResize.Downscale', (string) json_encode([
            'path' => $path,
            'maxEdge' => $maxEdge,
            'quality' => $quality,
        ]));

        $decoded = is_string($result) ? json_decode($result, true) : null;

        if (! is_array($decoded) || ($decoded['status'] ?? null) === 'error' || ! isset($decoded['path'])) {
            return null;
        }

        return [
            'path' => (string) $decoded['path'],
            'width' => (int) ($decoded['width'] ?? 0),
            'height' => (int) ($decoded['height'] ?? 0),
            'bytes' => (int) ($decoded['bytes'] ?? 0),
            'resized' => (bool) ($decoded['resized'] ?? false),
        ];
    }
}
