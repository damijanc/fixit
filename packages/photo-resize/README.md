# fixit/photo-resize

Local NativePHP Mobile plugin (Android only). The embedded PHP has no GD or
Imagick, so photos are downscaled natively before upload.

```php
use Fixit\PhotoResize\PhotoResize;

// Long edge at most 1568 px, JPEG quality 85, EXIF rotation applied, EXIF dropped.
$result = app(PhotoResize::class)->downscale($path);
// ['path' => ..., 'width' => ..., 'height' => ..., 'bytes' => ..., 'resized' => true] or null
```

The bridge function is `PhotoResize.Downscale`
(`resources/android/PhotoResizeFunctions.kt`).
