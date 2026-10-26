# FixIt status

Updated: 2026-10-26

## Current state
- Project: Laravel 13.38.0 + NativePHP Mobile 4.6.1 at the repository root (ADR 0002).
- Main setup PR: #1 (`setup/project-skeleton`), followed by docs PRs #2/#3.
- Bootstrap branch: `task/t1-android-bootstrap`.
- Active branch: `task/t3-symfony-ai` (stacked on T1).
- Host PHP/Composer/Laravel/NativePHP versions: see [DEVELOPMENT.md](DEVELOPMENT.md).
- Clean-checkout bootstrap: VERIFIED on macOS (see handoff log).
- Host test suite: VERIFIED — 12 tests, 39 assertions.
- Debug APK build and launch: VERIFIED on an Android **emulator** (`Pixel_9a`, Android 17 / API 37), package `com.fixit.app`.
- Physical-device build/launch: DEFERRED by decision on 2026-10-26; continue development and testing on the emulator, re-check on a real device once the build is ready to try.
- Symfony AI in embedded Android PHP: VERIFIED on the emulator against a local OpenAI-compatible fake provider; a real provider call is pending T8 secure key storage.
- FixIt home screen with an AI connectivity check: IMPLEMENTED and emulator-tested.
- Camera, sessions, safety checks, secure key storage, full diagnosis screens: PLANNED.
- CI: PHP test workflow VERIFIED on GitHub Actions (PR #5, ~20 s); Android CI build still pending (T10).

## Next step
T8: store a user-supplied provider key in Android Keystore-backed secure storage. Then complete T3 with one real provider call from the emulator, followed by T4 (camera/vision). Physical-device T2 verification is deferred until the build is ready to try on hardware.

## Known risks
- Physical-device behaviour (embedded PHP, WebView, camera) is still unproven.
- Secure key storage and signed CI APK build unimplemented.
- A real provider key must not go into `.env` for an APK build; T8 is the prerequisite for a real on-device call.
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
- Not verified: real provider call (needs a user key from secure storage); physical device; vision/photos.
- Blockers/decisions: `FIXIT_AI_API_KEY` is development-only because NativePHP bundles `.env` into the APK; T8 secure key storage is the next prerequisite. The generic bridge targets OpenAI-compatible endpoints; Anthropic/Gemini would need their own Symfony AI bridges if required.
- Next executable action: implement T8 secure key entry (Android Keystore via `Native\Mobile\Facades\SecureStorage`), then run `fixit:ai-smoke`/home check against the user's real provider.

## Handoff entry template
### YYYY-MM-DD — task
- Branch/PR:
- Changed files:
- Implemented:
- Verified (command, environment/device, result):
- Not verified:
- Blockers/decisions:
- Next executable action:
