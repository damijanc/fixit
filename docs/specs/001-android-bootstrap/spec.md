# Android bootstrap

- Spec ID: 001-android-bootstrap
- Status: IN_PROGRESS
- Tasks: T1, T2
- Last reviewed: 2026-10-26

## Goal
Android bootstrap for the Android-only FixIt hackathon MVP.

## Requirements
- A clean checkout produces a conventional NativePHP/Laravel Android project.
- Android SDK/JDK/PHP/Composer/Node versions and commands are documented.
- A physical Android device can install and launch the APK.
- No app server is required at runtime.

## Acceptance criteria
- Clean setup succeeds with recorded versions and commands.
- APK is installed and launched on a named physical device.
- Failed commands and workarounds are documented.

## Constraints
Follow root `AGENTS.md`, `docs/architecture.md`, `docs/mvp.md` and the architecture decision record. Do not substitute another stack or silently expand scope.

## Verification
Record exact test commands, results, environment and device in `docs/STATUS.md`. Only mark VERIFIED after concrete evidence.

## Implementation notes

T1 implemented and verified on 2026-10-26:

- Repository restructured to a Laravel 13 project at the root using `nativephp/mobile-starter` (ADR 0002).
- `scripts/bootstrap.sh` now prepares the checkout in place, pins the embedded PHP runtime to the host minor via `nativephp.lock`, runs migrations and the test suite.
- NativePHP 4.6.1 generated `nativephp/android` successfully; embedded PHP 8.5.12 downloaded.
- Host tests pass (8 tests, 81 assertions).
- `php artisan native:run android` built a 101 MB debug APK and launched `com.fixit.app` on a `Pixel_9a` emulator (Android 17 / API 37). Screenshot in `docs/evidence/2026-10-26-android-emulator-home.png`.

Remaining for this spec: T2 still needs a **physical** device. `php artisan native:build` is iOS-only; use `php artisan native:run android` for Android debug builds.
