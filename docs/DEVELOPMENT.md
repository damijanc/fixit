# Developer setup

Verified on 2026-10-26 for the host toolchain and test suite. Android emulator/device build steps are marked separately; see [STATUS.md](STATUS.md) for the latest evidence.

## Layout

This repository is a conventional Laravel 13 application at the root, extended with NativePHP Mobile. The Android project and the embedded PHP runtime are generated into `nativephp/` by `php artisan native:install android` and are intentionally not committed. See [decisions/0002-laravel-root-layout.md](decisions/0002-laravel-root-layout.md).

## Verified host toolchain

| Tool | Version |
|---|---|
| macOS | 25.6.0 (arm64) |
| PHP (host) | 8.5.12 |
| Composer | 2.10.3 |
| Laravel framework | 13.38.0 |
| NativePHP Mobile | 4.6.1 |
| Embedded PHP (`nativephp.lock`) | 8.5.12 |
| Symfony AI Agent / Platform | 0.15.0 |
| Pest | 4.7.8 |
| Node.js / npm | 24.11.1 / 11.6.2 (not required by the current app) |

The host PHP minor version must match `nativephp.lock`; NativePHP bundles the same minor into the APK. Composer resolves dependencies against the host PHP, so a mismatch can produce a bundle that fails on device.

## Prerequisites

Host:

- PHP 8.3+ with `dom`, `simplexml`, `fileinfo`, `mbstring`, `pdo_sqlite` and `curl`
- Composer 2

Android builds:

- JDK 17 (the generated Gradle 8.14.5 / Android Gradle Plugin 8.13.2 toolchain targets Java 17)
- Android SDK with platform 36, build-tools 36.0.0 and platform-tools (`adb`)
- A physical Android device (API 33+) with USB debugging, or a running emulator
- `ANDROID_HOME` or `NATIVEPHP_ANDROID_SDK_LOCATION` pointing at the SDK; `adb` on `PATH`

## Clean checkout

```bash
git clone git@github.com:damijanc/fixit.git
cd fixit
bash scripts/bootstrap.sh
```

`scripts/bootstrap.sh` is idempotent and performs:

1. `composer install`
2. creates `.env` from `.env.example` if missing
3. generates `APP_KEY` if missing
4. creates `database/database.sqlite`
5. runs migrations
6. `php artisan native:install android` (downloads the embedded PHP runtime, cached in `nativephp/`)
7. `php artisan test`

App identity is non-secret and lives in `.env` / `.env.example` as `NATIVEPHP_APP_ID=com.fixit.app`. Never put provider API keys or signing credentials in these files.

## Test

```bash
php artisan test
# or
composer test
```

Tests use in-memory SQLite and `.env.testing`; no device is needed.

## AI provider smoke test

The app talks to any OpenAI-compatible endpoint through Symfony AI's generic bridge (`{base_url}/v1/chat/completions`).

Configuration (non-secret):

```dotenv
FIXIT_AI_BASE_URL=https://api.openai.com
FIXIT_AI_MODEL=gpt-4o-mini
FIXIT_AI_TIMEOUT=30
```

**Do not put a real API key in `.env` for an Android build.** NativePHP bundles `.env` into the APK. `FIXIT_AI_API_KEY` exists only for local host testing until T8 adds Android Keystore-backed key entry.

Host check against the bundled fake provider:

```bash
php -S 0.0.0.0:8099 scripts/dev/fake-openai-server.php
FIXIT_AI_BASE_URL=http://127.0.0.1:8099 \
FIXIT_AI_MODEL=fixit-fake \
FIXIT_AI_API_KEY=test-key \
php artisan fixit:ai-smoke
# => FixIt AI connectivity OK
```

Emulator check:

```bash
# terminal 1: fake provider on the host
php -S 0.0.0.0:8099 scripts/dev/fake-openai-server.php

# .env: point at the emulator host alias
FIXIT_AI_BASE_URL=http://10.0.2.2:8099
FIXIT_AI_MODEL=fixit-fake
FIXIT_AI_API_KEY=test-key

php artisan native:run android emulator-5554 --no-tty --no-vite
```

Then tap **Test AI connection** on the home screen. A successful call shows `OK: FixIt AI connectivity OK`. Evidence: [evidence/2026-10-26-android-emulator-ai-ok.png](evidence/2026-10-26-android-emulator-ai-ok.png).

A real provider call from the emulator is intentionally deferred until T8: the key must come from secure on-device storage instead of the bundled `.env`.

## Camera check (T4 spike)

The `nativephp/mobile-camera` plugin (1.1.0) opens the platform camera app and returns a photo path to PHP.

```bash
php artisan native:run android emulator-5554 --no-tty --no-vite
```

Then on the device:

1. Tap **Take a photo**.
2. Grant the camera permission (Android shows `Allow FixIt to take pictures and record video?`).
3. Capture a frame and confirm it in the system camera app.
4. The home screen renders the photo and logs `Photo captured: image/jpeg, <file>.jpg`.

Verified 2026-10-26 on the `Pixel_9a` emulator (Android 17 / API 37) using the virtual-scene camera:

- The system camera opened from embedded PHP; permission prompt and capture flow worked.
- A **1440×1920 JPEG** (62 KB, EXIF present) was written to app-private storage: `{noBackupFilesDir}/Camera/`.
- PHP received the path and the native UI rendered it as a preview.
- Evidence: [evidence/2026-10-26-android-emulator-camera.png](evidence/2026-10-26-android-emulator-camera.png).

Notes:

- This is the **system camera intent**, not an in-app camera preview. That is sufficient for photo capture but means camera controls belong to the OS app.
- The plugin declares `CAMERA` (and video-related) permissions; Android prompts at runtime.
- Photos are not uploaded anywhere yet. T4 must add explicit consent before any image leaves the device.
- Emulator camera must be configured (`hw.camera.back=virtualscene` or a host webcam); a device with no camera reports capture failure.



## Run on Android

Debug build, install and launch on the connected device:

```bash
export ANDROID_HOME="$HOME/Library/Android/sdk"
export JAVA_HOME="/opt/homebrew/opt/openjdk@17/libexec/openjdk.jdk/Contents/Home"
export PATH="$JAVA_HOME/bin:$ANDROID_HOME/platform-tools:$PATH"

php artisan native:run android emulator-5554 --no-tty --no-vite
```

Omit the device ID to be prompted, or pass the physical device serial instead of `emulator-5554`.

Verified 2026-10-26 on an emulator (`Pixel_9a`, Android 17 / API 37): `native:run` produced `nativephp/android/app/build/outputs/apk/debug/app-debug.apk` (101 MB), installed `com.fixit.app` and launched `com.nativephp.mobile.ui.MainActivity`. Screenshot: [evidence/2026-10-26-android-emulator-home.png](evidence/2026-10-26-android-emulator-home.png). The build downloaded the missing `platforms/android-36` automatically.

Useful variants:

```bash
php artisan native:run android --build=release
php artisan native:debug                 # host + Android toolchain report
php artisan native:install --help        # regenerate Android resources
```

`php artisan native:build` is the **iOS** build command on macOS; it is not used for Android. Android APKs are produced by `native:run` (debug) or `native:package` (signed release, see T11).

## Troubleshooting

- **`Host PHP 8.x does not match nativephp.lock`** — align the host PHP with the lock file, or re-run `php artisan native:install android --force` to re-pin and download the embedded runtime for the host minor.
- **`ADB is not installed or not in your PATH`** — add `$ANDROID_HOME/platform-tools` to `PATH`.
- **`native/` vs `nativephp/`** — `native` is the committed CLI wrapper; `nativephp/` is generated and gitignored.
- **JDK errors from Gradle** — confirm `JAVA_HOME` points at a JDK 17, not a newer major version.
