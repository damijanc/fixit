# FixIt status

Updated: 2026-10-26

## Current state
- Project: Laravel 13.38.0 + NativePHP Mobile 4.6.1 at the repository root (ADR 0002).
- Main setup PR: #1 (`setup/project-skeleton`), followed by docs PRs #2/#3.
- Bootstrap branch: `task/t1-android-bootstrap`.
- Active branch: `task/t4-camera-spike` (stacked on T3/#6).
- Host PHP/Composer/Laravel/NativePHP versions: see [DEVELOPMENT.md](DEVELOPMENT.md).
- Clean-checkout bootstrap: VERIFIED on macOS (see handoff log).
- Host test suite: VERIFIED — 15 tests, 53 assertions.
- Debug APK build and launch: VERIFIED on an Android **emulator** (`Pixel_9a`, Android 17 / API 37), package `com.fixit.app`.
- Physical-device build/launch: DEFERRED by decision on 2026-10-26; continue development and testing on the emulator, re-check on a real device once the build is ready to try.
- Symfony AI in embedded Android PHP: VERIFIED on the emulator against a local OpenAI-compatible fake provider; a real provider call is pending T8 secure key storage.
- Camera capture in embedded Android PHP: **VERIFIED** on the emulator — permission prompt, system camera, 1440×1920 JPEG written to app-private storage, path returned to PHP and preview rendered. Evidence: `docs/evidence/2026-10-26-android-emulator-camera.png`.
- Provider decision: **DeepSeek** (OpenAI-compatible, `https://api.deepseek.com`, `deepseek-flash` supports vision). Not wired into config defaults yet.
- FixIt home screen with AI connectivity and camera checks: IMPLEMENTED and emulator-tested.
- Sessions, safety checks, secure key storage, vision upload, full diagnosis screens: PLANNED.
- CI: PHP test workflow VERIFIED on GitHub Actions; Android CI build still pending (T10).

## Next step
T4: send the captured photo to DeepSeek's vision model with explicit user consent, then T8 secure key storage for a real key. Physical-device T2 verification is deferred until the build is ready to try on hardware.

## Known risks
- Physical-device behaviour (embedded PHP, WebView, camera, permissions) is still unproven.
- Secure key storage and signed CI APK build unimplemented.
- A real provider key must not go into `.env` for an APK build; T8 is the prerequisite for a real on-device call.
- DeepSeek vision takes base64/URL images via the OpenAI-compatible endpoint; the Symfony AI generic bridge mapping for image content still needs a real test.
- NativePHP `native:build` is iOS-only; Android uses `native:run`/`native:package`.
- Host PHP minor must match `nativephp.lock` (currently 8.5.12).

## Handoff log

### 2026-10-26 — T1 Android bootstrap (+ T2 emulator evidence)
- Branch/PR: `task/t1-android-bootstrap` (PR #5).
- Changed files: adopted the NativePHP Mobile starter at the repository root (`composer.json`, `composer.lock`, `artisan`, `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `tests/`, `phpunit.xml`, `.env.example`, `.env.testing`, `native`, `nativephp.lock`); rewrote `scripts/bootstrap.sh`; moved `DiagnosisState` to `app/Services/Diagnosis/`; updated `README.md`, `docs/DEVELOPMENT.md`, `docs/specs/001-android-bootstrap/spec.md`, `.github/workflows/quality.yml`; added `docs/decisions/0002-laravel-root-layout.md` and `docs/evidence/2026-10-26-android-emulator-home.png`; removed `stubs/`.
- Implemented: root Laravel/NativePHP project, idempotent bootstrap, Symfony AI packages, app id `com.fixit.app`, generated Android resources excluded from git, host CI test job.
- Verified (command, environment/device, result):
  - `git clone --branch task/t1-android-bootstrap … && bash scripts/bootstrap.sh` — fresh temp clone, macOS 25.6.0 arm64, PHP 8.5.12, Composer 2.10.3 — exit 0; Composer install, migrations, `native:install android`, 8 tests passed; working tree clean afterwards.
  - `php artisan test` — same host — 8 passed, 81 assertions.
  - `php artisan native:run android emulator-5554 --no-tty --no-vite` — emulator `Pixel_9a`, Android 17 / API 37, JDK 17.0.21, Android SDK 36 — built `nativephp/android/app/build/outputs/apk/debug/app-debug.apk` (101 MB), installed and launched `com.fixit.app/com.nativephp.mobile.ui.MainActivity`; screenshot in `docs/evidence/`.
  - `php artisan native:debug` — host, JDK 17, Gradle 8.14.5, embedded PHP 8.5.12 detected.
  - GitHub Actions `PHP tests` (PR #5) — pass, ~20 s.
- Not verified: physical Android device (deferred until build is ready to try); camera; real model call; release/signed build.
- Blockers/decisions: physical-device verification deferred by decision 2026-10-26; emulator is the interim target. `nativephp.lock` pinned to 8.5.12 to match host PHP (embedded runtime). NativePHP starter UI is still shown until T9.
- Next executable action: `adb devices` on a physical phone, then `php artisan native:run android <serial>` and record evidence for T2.

### 2026-10-26 — T3 Symfony AI call from embedded Android PHP
- Branch/PR: `task/t3-symfony-ai` (PR #6, stacked on T1/#5).
- Changed files: added `symfony/ai-generic-platform`; `config/ai.php`; `app/Services/AI/` (`ModelGateway`, `AiException`, `MissingApiKeyException`, `AiRequestFailedException`); `app/Console/Commands/AiSmokeCommand.php`; rewrote `app/NativeComponents/Home.php` and `resources/views/native/home.blade.php`; added `scripts/dev/fake-openai-server.php`; added `tests/Unit/Services/AI/ModelGatewayTest.php` and `tests/Feature/HomeTest.php` (removed `NativeHomeTest.php`); updated `.env.example`, `docs/DEVELOPMENT.md`; added `docs/evidence/2026-10-26-android-emulator-ai-ok.png`.
- Implemented: OpenAI-compatible Symfony AI gateway with injectable platform, user-safe errors, an artisan smoke command, and a FixIt home screen with a "Test AI connection" button.
- Verified (command, environment/device, result):
  - `php artisan test` — host — 12 passed, 39 assertions.
  - `php -S 0.0.0.0:8099 scripts/dev/fake-openai-server.php` + `php artisan fixit:ai-smoke` — host — returned `FixIt AI connectivity OK`.
  - `php artisan native:run android emulator-5554 --no-tty --no-vite` — emulator `Pixel_9a`, Android 17 / API 37 — rebuilt APK; tapping **Test AI connection** displayed `OK: FixIt AI connectivity OK`; fake server received the request from embedded PHP. Screenshot in `docs/evidence/2026-10-26-android-emulator-ai-ok.png`.
  - GitHub Actions `PHP tests` (PR #6) — pass, ~18 s.
- Not verified: real provider call (needs a user key from secure storage); physical device; vision/photos.
- Blockers/decisions: `FIXIT_AI_API_KEY` is development-only because NativePHP bundles `.env` into the APK; T8 secure key storage is the next prerequisite. The generic bridge targets OpenAI-compatible endpoints; Anthropic/Gemini would need their own Symfony AI bridges if required.
- Next executable action: implement T8 secure key entry (Android Keystore via `Native\Mobile\Facades\SecureStorage`), then run `fixit:ai-smoke`/home check against the user's real provider.

### 2026-10-26 — T4 camera compatibility spike
- Branch/PR: `task/t4-camera-spike` (PR opened from this branch, stacked on T3/#6).
- Changed files: added `nativephp/mobile-camera` 1.1.0 and registered `CameraServiceProvider`; extended `Home` with `takePhoto()` and camera event handlers (`PhotoTaken`, `PhotoCancelled`, `PermissionDenied`); added the camera card and preview to `resources/views/native/home.blade.php`; added camera tests to `tests/Feature/HomeTest.php`; updated `docs/DEVELOPMENT.md`, `docs/STATUS.md`, `docs/TASKS.md`; added `docs/evidence/2026-10-26-android-emulator-camera.png`.
- Implemented: explicit “Take a photo” action using `Camera::getPhoto()->start()`, runtime permission handling, photo preview from the app-private file path, and cancellation/denial messages.
- Verified (command, environment/device, result):
  - `php artisan test` — host — 15 passed, 53 assertions.
  - `php artisan native:run android emulator-5554 --no-tty --no-vite` — emulator `Pixel_9a`, Android 17 / API 37, virtual-scene back camera — permission dialog shown, system camera opened, photo captured and confirmed; PHP received `{noBackupFilesDir}/Camera/captured_*.jpg`; UI rendered the preview and `Photo captured: image/jpeg, …`.
  - Pulled the file via `adb run-as com.fixit.app`: valid **JPEG 1440×1920, 62 KB**, EXIF present.
- Not verified: physical-device camera; in-app camera preview (plugin uses the system camera intent); image upload/vision request.
- Blockers/decisions: camera is a **GO** on the emulator. The plugin uses the system camera app, which is acceptable for T4 but means the UI hands off to Android. Photo upload must remain opt-in with explicit consent.
- Next executable action: T4 — configure DeepSeek (`https://api.deepseek.com`, model `deepseek-flash`) and send the captured JPEG as a base64 `image_url` content part through Symfony AI after consent, first against the local fake provider.

## Handoff entry template
### YYYY-MM-DD — task
- Branch/PR:
- Changed files:
- Implemented:
- Verified (command, environment/device, result):
- Not verified:
- Blockers/decisions:
- Next executable action:
