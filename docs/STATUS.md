# FixIt status

Updated: 2026-10-27

## Current state
- Project: Laravel 13.38.0 + NativePHP Mobile 4.6.1 at the repository root (ADR 0002).
- Main setup PR: #1 (`setup/project-skeleton`), followed by docs PRs #2/#3.
- Bootstrap branch: `task/t1-android-bootstrap`.
- Active branch: `task/t4-camera-confirmation-ux` (stacked on T8/#8).
- Host PHP/Composer/Laravel/NativePHP versions: see [DEVELOPMENT.md](DEVELOPMENT.md).
- Clean-checkout bootstrap: VERIFIED on macOS (see handoff log).
- Host test suite: VERIFIED — 26 tests, 134 assertions.
- Debug APK build and launch: VERIFIED on an Android **emulator** (`Pixel_9a`, Android 17 / API 37) and on a physical **ZTE 8050** (Android 13 / API 33, arm64, serial `LZV50SDEDC3001352`), package `com.fixit.app`.
- Symfony AI in embedded Android PHP: VERIFIED on the emulator against a local OpenAI-compatible fake provider; a real DeepSeek call is pending only a user-entered key.
- Camera capture in embedded Android PHP: **VERIFIED on emulator and physical ZTE 8050** — permission prompt, system camera, JPEG written to app-private storage, path returned to PHP and preview rendered. Physical capture: **3072×4032 JPEG, 2.4 MB**. Physical-device screenshot withheld because it contained personal content.
- Camera confirmation UX: added an explicit “tap ✓ to confirm” hint and a clearer cancel message after physical-test feedback (PR #9). Evidence: `docs/evidence/2026-10-27-zte-camera-confirmation-hint.png` and `...-camera-cancel-message.png`.
- Provider: **DeepSeek** (`https://api.deepseek.com`, `deepseek-flash`, vision-capable) is the configured default.
- Secure provider key: **VERIFIED on emulator** — entered in Settings, stored as AES ciphertext in app-private storage using the Keystore-managed `APP_KEY`, persisted across restart, absent from `.env` and the APK; remove works. ADR 0003. Physical-device key entry not yet repeated.
- FixIt home + provider settings screens: IMPLEMENTED and emulator-tested; physical camera flow tested.
- Sessions, safety checks, vision upload, diagnosis/result/history screens: PLANNED.
- CI: PHP test workflow VERIFIED on GitHub Actions; Android CI build still pending (T10).

## Next step
T4: send the captured photo to DeepSeek `deepseek-flash` with explicit user consent and show the vision result. A real DeepSeek text call can be made as soon as a key is entered in Settings on either device.

## Known risks
- Signed CI APK build unimplemented.
- The official NativePHP SecureStorage plugin requires authenticated plugin access; ADR 0003 uses encrypted file storage with the Keystore-managed `APP_KEY` instead.
- DeepSeek vision takes base64/URL images via the OpenAI-compatible endpoint; the Symfony AI generic bridge mapping for image content still needs a real test.
- The camera plugin uses the Android system camera intent, so confirmation UI is controlled by the OS app; the new hint mitigates but does not remove the hand-off.
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
- Branch/PR: `task/t4-camera-spike` (PR #7, stacked on T3/#6).
- Changed files: added `nativephp/mobile-camera` 1.1.0 and registered `CameraServiceProvider`; extended `Home` with `takePhoto()` and camera event handlers (`PhotoTaken`, `PhotoCancelled`, `PermissionDenied`); added the camera card and preview to `resources/views/native/home.blade.php`; added camera tests to `tests/Feature/HomeTest.php`; updated `docs/DEVELOPMENT.md`, `docs/STATUS.md`, `docs/TASKS.md`; added `docs/evidence/2026-10-26-android-emulator-camera.png`.
- Implemented: explicit “Take a photo” action using `Camera::getPhoto()->start()`, runtime permission handling, photo preview from the app-private file path, and cancellation/denial messages.
- Verified (command, environment/device, result):
  - `php artisan test` — host — 15 passed, 53 assertions.
  - `php artisan native:run android emulator-5554 --no-tty --no-vite` — emulator `Pixel_9a`, Android 17 / API 37, virtual-scene back camera — permission dialog shown, system camera opened, photo captured and confirmed; PHP received `{noBackupFilesDir}/Camera/captured_*.jpg`; UI rendered the preview and `Photo captured: image/jpeg, …`.
  - Pulled the file via `adb run-as com.fixit.app`: valid **JPEG 1440×1920, 62 KB**, EXIF present.
- Not verified: physical-device camera; in-app camera preview (plugin uses the system camera intent); image upload/vision request.
- Blockers/decisions: camera is a **GO** on the emulator. The plugin uses the system camera app, which is acceptable for T4 but means the UI hands off to Android. Photo upload must remain opt-in with explicit consent.
- Next executable action: T4 — configure DeepSeek (`https://api.deepseek.com`, model `deepseek-flash`) and send the captured JPEG as a base64 `image_url` content part through Symfony AI after consent, first against the local fake provider.

### 2026-10-26 — T8 secure provider key settings
- Branch/PR: `task/t8-secure-key-settings` (PR #8).
- Changed files: added `ApiKeyStore`, `EncryptedFileApiKeyStore`; updated `ModelGateway` and `AppServiceProvider`; added `Settings` NativeComponent + `resources/views/native/settings.blade.php` + `/settings` route; added Provider settings entry to Home; removed `AiSmokeCommand`; switched `config/ai.php` and `.env.example` to DeepSeek defaults with no key; added tests (`SettingsTest`, `EncryptedFileApiKeyStoreTest`, in-memory key store) and updated `ModelGatewayTest`/`HomeTest`; updated DEVELOPMENT, STATUS, TASKS, README, architecture; added ADR 0003; added settings evidence screenshots.
- Implemented: key entry from the app UI, encrypted at rest, test/remove actions, no `.env` key path, DeepSeek defaults.
- Verified (command, environment/device, result):
  - `php artisan test` — host — 25 passed, 124 assertions.
  - Emulator `Pixel_9a`, Android 17 / API 37 — saved `sk-fixit-secure-test-123` via Settings; app showed `A key is saved on this device`; Test connection returned `Connected: FixIt AI connectivity OK` against the local fake provider.
  - Force-stopped and relaunched the app — key still present; Test connection succeeded again.
  - On-device ciphertext: `app_storage/persisted_data/storage/app/private/ai-key.enc`, mode `0600`, 228 bytes, **0** plaintext occurrences.
  - Pulled APK + bundled `.env`: **0** occurrences of the test key; no `APP_KEY` in the bundle.
  - **Remove saved key** deleted the ciphertext file and showed `No key saved yet.`
  - Final rebuild with `https://api.deepseek.com` / `deepseek-flash` and no saved key: Settings shows DeepSeek defaults and `No key saved yet.`
- Not verified: real DeepSeek call (needs the user's key); physical device; vision upload.
- Blockers/decisions: the official `nativephp/mobile-secure-storage` plugin requires authenticated plugin access (HTTP 401), so ADR 0003 uses Laravel `Crypt` with the NativePHP Keystore-managed `APP_KEY` and app-private ciphertext. The input must use live `native:model` sync; `.blur` can race the Save action.
- Next executable action: enter a real DeepSeek key in Settings and tap Test connection; then build T4 vision upload.

### 2026-10-27 — T2 physical device + camera confirmation UX
- Branch/PR: `task/t4-camera-confirmation-ux` (PR #9, based on merged T8/main).
- Changed files: `app/NativeComponents/Home.php`, `resources/views/native/home.blade.php`, `tests/Feature/HomeTest.php`; docs and evidence updates.
- Implemented: explicit “take the photo and tap ✓ to confirm” hint; clearer no-photo/cancel message explaining the system camera review step.
- Verified (command, environment/device, result):
  - `adb devices -l` — physical `ZTE 8050`, Android 13 / API 33, arm64, serial `LZV50SDEDC3001352`.
  - `php artisan native:run android LZV50SDEDC3001352 --no-tty --no-vite` — APK built, installed and launched `com.fixit.app` on the phone.
  - Camera permission + system camera + capture worked on the phone; photo preview rendered and PHP received the path. Pulled file: **JPEG 3072×4032, 2.4 MB**.
  - Cancel flow on the phone now shows `No photo received. Take the photo, then tap the ✓ checkmark on the camera review screen.`
  - `php artisan test` — 26 passed, 134 assertions.
  - Evidence: `docs/evidence/2026-10-27-zte-camera-confirmation-hint.png`, `docs/evidence/2026-10-27-zte-camera-cancel-message.png`. The captured personal photo is intentionally not committed.
- Not verified: real DeepSeek key entry/call on the physical phone; vision upload; release build.
- Blockers/decisions: the camera plugin hands off to the Android system camera, so the confirmation UI is OS-owned. A custom in-app camera is deferred; the hint fixes the immediate confusion. **T2 is now VERIFIED on a named physical device.**
- Next executable action: T4 — explicit consent + image request to DeepSeek `deepseek-flash` on the physical phone.

## Handoff entry template
### YYYY-MM-DD — task
- Branch/PR:
- Changed files:
- Implemented:
- Verified (command, environment/device, result):
- Not verified:
- Blockers/decisions:
- Next executable action:
