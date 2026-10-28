# FixIt status

Updated: 2026-10-28

## Current state
- Project: Laravel 13.38.0 + NativePHP Mobile 4.6.1 at the repository root (ADR 0002).
- Main setup PR: #1 (`setup/project-skeleton`), followed by docs PRs #2/#3.
- Bootstrap branch: `task/t1-android-bootstrap`.
- Active branch: `main` (T13 merged as IMPLEMENTED in PR #18; real-key and physical-device smoke tests pending).
- Host PHP/Composer/Laravel/NativePHP versions: see [DEVELOPMENT.md](DEVELOPMENT.md).
- Clean-checkout bootstrap: VERIFIED on macOS (see handoff log).
- Host test suite: VERIFIED — 125 tests, 675 assertions (T17 branch, from `main`).
- Hugging Face photo diagnosis (T17): **VERIFIED on the ZTE 8050** with a real token on `fix/vision-photo-downscale` — Hugging Face requests are routed to `deepinfra` because the router's default pick (novita) returned 503 for full-size phone photos; photo errors are shown next to the Send button.
- Multi-provider AI (T13): **IMPLEMENTED + emulator fake-provider smoke** — DeepSeek, OpenRouter, Hugging Face, Anthropic and OpenAI are selectable; one encrypted provider/model/key selection; Symfony AI generic/HF/Anthropic/OpenAI bridges. Host tests cover platform construction, image payloads, migration, provider errors and no key cross-use. Emulator `Pixel_9a`: Settings selection, one-key save and Test connection passed for DeepSeek, OpenRouter and Hugging Face against the local fake provider; Hugging Face vision + multi-turn follow-up passed. Real provider keys and a physical device were not available.
- Debug APK build and launch: VERIFIED on an Android **emulator** (`Pixel_9a`, Android 17 / API 37) and on a physical **ZTE 8050** (Android 13 / API 33, arm64, serial `LZV50SDEDC3001352`), package `com.fixit.app`.
- Symfony AI in embedded Android PHP: **VERIFIED with a real DeepSeek call** on the physical ZTE 8050 — `deepseek-flash` returned `FixIt AI connectivity OK`.
- Camera capture: **VERIFIED** on emulator and physical ZTE 8050 — system camera, JPEG in app-private storage, path returned to PHP, preview rendered.
- Vision upload + consent: **VERIFIED** — explicit send, no upload on capture/cancel, base64 image data URL through Symfony AI; real `deepseek-flash` image response on the ZTE.
- SQLite repair sessions: **VERIFIED** on the emulator — photo, symptoms, vision result and follow-up turns persisted; force-stop/relaunch restored them. Evidence: `docs/evidence/2026-10-27-emulator-session-restored.png`.
- Multi-turn follow-up: **VERIFIED** on emulator — prior photo prompt + assistant result + user answer sent as a conversation; response persisted and restored after relaunch. Evidence: `docs/evidence/2026-10-27-emulator-followup-restored.png`.
- Deterministic safety filtering: **VERIFIED** by unit + UI tests — hazardous instructions are blocked before display; mains/gas mentions escalate; blocked raw text is not stored.
- Screens: **VERIFIED on emulator** — Home landing, Diagnosis, Result and History with navigation and saved-session lists. Evidence: `docs/evidence/2026-10-27-emulator-home-screens.png`, `...-history.png`, `...-result-screen.png`.
- UI polish (T14): **IMPLEMENTED** on `task/t14-ui-polish` — chat-style diagnosis thread, camera-first Home, cleaner Settings; host tests and emulator screens checked; real-key and physical-device pass pending.
- Symfony AI clean-up (T15): **IMPLEMENTED** on `task/t15-symfony-ai-refactor` — provider errors (401/404/429/5xx) now show the correct message; one Symfony AI `Agent` per request via `AgentFactory` + `SystemPromptInputProcessor`; prompts and follow-up history moved out of the Diagnosis component. Host tests pass; device pass pending.
- Android release build: **local signed release APK VERIFIED** — 76.77 MB, `apksigner` certificate checked, installed and launched on the `Pixel_9a` emulator. Built with an ephemeral local keystore.
- Android CI: **VERIFIED with stable signing** — run 40652416378 (main, 2026-10-28) built and uploaded a signed release APK after a transient NativePHP binary-download retry. `apksigner` shows `CN=FixIt Release, OU=Mobile, O=FixIt, L=Berlin, ST=Berlin, C=DE`.
- Release publishing: **VERIFIED on tag `v0.0.5-ci-test`** — GitHub Actions run 40659466606 built the signed APK, verified it, created a GitHub Release and attached `app-release.apk` + SHA-256. Test tag/release removed afterwards.
- CI maintenance: Node 20 warnings removed with `actions/checkout@v5` / `actions/setup-java@v5`; `sdkmanager` replaced by Android CLI (`android sdk install`). The only remaining annotation is the `ubuntu-latest` runner image migration notice and Android CLI telemetry tip.
- Release docs: `docs/JUDGE_GUIDE.md`, `docs/RELEASE.md`, `docs/DEMO.md`, `scripts/build-release.sh` added. Demo video not recorded.
- Provider: **multi-provider** — DeepSeek (`deepseek-flash`) is the default; OpenRouter, Hugging Face, Anthropic and OpenAI are curated. Provider/model/key live in one encrypted `ai-settings.enc`; the old DeepSeek `ai-key.enc` is migrated automatically. ADR 0004.
- Secure provider key: **VERIFIED on emulator** — saved from Settings, encrypted at rest with the Keystore-managed `APP_KEY`, absent from `.env` and the APK; one key at a time; switching provider does not reuse the old key. The old `sk-` prefix rule was removed.
- Remaining: real-key smoke tests (DeepSeek regression, OpenRouter Claude/GPT, Hugging Face, direct Anthropic/OpenAI) and the physical-device pass; physical install of the release APK; demo video.
- CI: PHP tests + tag-driven Android release VERIFIED on GitHub Actions.

## Next step
T13 is merged as **IMPLEMENTED** (PR #18). Run real-key smoke tests with DeepSeek, OpenRouter (Claude and GPT) and Hugging Face, then repeat on the ZTE 8050; direct Anthropic/OpenAI if keys are available. The emulator fake-provider pass and host suite are complete. After that, install the release APK on the ZTE and record the 90-second demo.

## Known risks
- Real-provider smoke tests are pending; provider/model IDs were checked against live catalogs and Symfony AI 0.15 catalogs, but account access, regional availability and vision pricing can differ.
- Tag builds fail without the stable signing secrets; the configured certificate is `CN=FixIt Release`.
- Physical-device install of the signed release APK is not yet verified.
- The official NativePHP SecureStorage plugin requires authenticated plugin access; ADR 0003/0004 use encrypted file storage with the Keystore-managed `APP_KEY` instead.
- DeepSeek vision mapping (base64 `image_url`) is verified; vision token cost is bounded (~1024 tokens/image) but not yet metered in-app.
- The camera plugin uses the Android system camera intent, so confirmation UI is controlled by the OS app; the hint mitigates but does not remove the hand-off.
- NativePHP `native:build` is iOS-only; Android uses `native:run`/`native:package`.
- Host PHP minor must match `nativephp.lock` (currently 8.5.12).
- Custom model IDs are intentionally not offered; only curated vision models are selectable.

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

### 2026-10-27 — T3 real DeepSeek call + key diagnostics
- Branch/PR: `task/t3-deepseek-diagnostics` (PR #10).
- Changed files: `app/Services/AI/ModelGateway.php`, `AiRequestFailedException.php`, `Settings` prefix validation + hint, `config/ai.php`, `.env.example`, tests; docs/evidence.
- Implemented: real provider error surfacing (HTTP status, safe messages, warning log), `sk-` prefix validation for DeepSeek keys, prefix hint in Settings.
- Verified (command, environment/device, result):
  - Physical **ZTE 8050** (Android 13 / API 33): saved the full DeepSeek key in Settings and tapped **Test connection**; result `Connected: FixIt AI connectivity OK` from `deepseek-flash` via `https://api.deepseek.com`.
  - The earlier failure was caused by pasting the key without the `sk-` prefix (401). The app now rejects that before saving and the 401 message names the prefix.
  - `php artisan test` — 27 passed, 134 assertions.
  - Evidence: `docs/evidence/2026-10-27-zte-deepseek-connected.png` (no key visible).
- Not verified: vision/image request; release build. Physical camera confirmation UX is tracked separately in PR #9.
- Blockers/decisions: provider errors now log safe class/message/code and expose the HTTP status to the user without the response body or key.
- Next executable action: T4 vision — consent + send the captured image to `deepseek-flash`.

### 2026-10-27 — T4 photo consent + real DeepSeek vision call
- Branch/PR: `task/t4-vision-consent` (PR #11).
- Changed files: `ModelGateway` image support + validation; `AiRequestFailedException` image errors; Home consent/preview/analyze/discard UI; `fake-openai-server.php` image detection; tests/fixture; docs/evidence.
- Implemented: explicit “Send photo to DeepSeek” consent action; no upload on capture/cancel; base64 JPEG data-URL image part through Symfony AI; discard deletes the local photo; provider failures preserve the photo and preview.
- Verified (command, environment/device, result):
  - `php artisan test` — 33 passed, 186 assertions.
  - Emulator `Pixel_9a` against the local fake provider: captured photo, tapped **Send photo to DeepSeek**, fake server detected `image_url`, result rendered. Evidence: `docs/evidence/2026-10-27-emulator-vision-fake.png`.
  - Physical **ZTE 8050** (Android 13 / API 33): captured photo, explicit send, **real `deepseek-flash` vision response** received and rendered (it identified the object and suggested cautious checks). Physical screenshot withheld because the photo showed personal content.
  - Cancel/discard flow verified: no upload on capture/cancel; discard deletes the local file.
- Not verified: symptoms input and multi-turn follow-up (T5/T6); deterministic safety filtering (T7); signed release.
- Blockers/decisions: the vision request is opt-in per photo; the photo stays in app-private storage until sent. Image size/type validated before send (JPEG/PNG/GIF/WebP, ≤32 MB).
- Next executable action: T5/T6 — persist sessions and add symptoms + one follow-up question using prior observations.

### 2026-10-27 — T5 SQLite repair sessions
- Branch/PR: `task/t5-sessions` (PR #12).
- Changed files: `repair_sessions`/`repair_observations` migration; `RepairSession`/`RepairObservation` models; `RepairSessionService`; first-boot migration check in `AppServiceProvider`; Home session persistence/resume + symptoms field; tests.
- Implemented: each captured photo starts a local session; symptoms, prompt and vision result are stored; Home resumes the latest session on mount; discard deletes the session; the app creates the schema on first device boot.
- Verified (command, environment/device, result):
  - `php artisan test` — 36 passed, 211 assertions.
  - Emulator `Pixel_9a`: captured photo, entered symptoms, sent to fake provider, force-stopped and relaunched the app — Home restored the previous session with `Previous repair session loaded from local storage.`, the photo preview and the vision result. Evidence: `docs/evidence/2026-10-27-emulator-session-restored.png`.
  - Device DB path: `app_storage/persisted_data/database/database.sqlite`.
- Not verified: physical-device session persistence (emulator evidence only); multi-turn follow-up; safety filtering.
- Blockers/decisions: NativePHP does not run migrations on device, so the app checks for `repair_sessions` on boot and runs `migrate --force` once. Console/test runs keep using explicit migrations.
- Next executable action: T6 — load prior observations, send a follow-up question and persist the answer.

### 2026-10-27 — T6 multi-turn follow-up
- Branch/PR: `task/t6-multiturn` (PR #13).
- Changed files: `ModelGateway::continueConversation(MessageBag)`; `RepairSessionService` follow-up persistence; Home follow-up input/send/restore; fake provider assistant-history detection; tests; docs/evidence.
- Implemented: follow-up conversation built from the stored photo prompt, vision result and earlier answers; answer + response persisted as observations; follow-up result restored on relaunch.
- Verified (command, environment/device, result):
  - `php artisan test` — 38 passed, 236 assertions.
  - Emulator `Pixel_9a`: resumed the T5 session, entered a follow-up answer, fake provider detected the assistant history and returned a follow-up response; force-stop/relaunch restored `Resumed the previous follow-up answer.` and the result. Evidence: `docs/evidence/2026-10-27-emulator-followup-restored.png`.
- Not verified: physical-device follow-up; deterministic safety filtering (T7); signed release.
- Blockers/decisions: follow-up sends text history only (not the image again) to keep token cost low; the photo prompt + prior result are included as context.
- Next executable action: T7 — deterministic safety filtering/escalation.

### 2026-10-27 — T7 deterministic safety filter
- Branch/PR: `task/t7-safety-filter` (PR #14).
- Changed files: `SafetyFilter` + `SafetyResult`; Home integration for vision and follow-up; session status escalation; tests; docs.
- Implemented: sentence-level checks with negation handling; blocks mains work, gas work, opening appliances, bypassing interlocks, live probing and microwaves; escalates mains/gas mentions; replaces blocked text with a professional-referral message; persists only the safe text and sets session status `escalated`.
- Verified (command, environment/device, result):
  - `php artisan test` — 47 passed, 279 assertions.
  - Unit cases cover safe external checks, dangerous instructions, gas, live probing, negated warnings, mains escalation and professional referral.
  - UI tests verify blocked vision and follow-up output is not displayed raw and the session is marked escalated.
- Not verified: adversarial prompt/model-output corpus beyond the covered cases; physical-device check of the safety banner; signed release.
- Blockers/decisions: deterministic, conservative filter; negation is sentence-local, so unusual sentence structure may still cause false positives/negatives. No raw blocked text is stored.
- Next executable action: T9 — diagnosis/result/history screens, then T10/T11 release work.

### 2026-10-27 — T9 Home / Diagnosis / Result / History screens
- Branch/PR: `task/t9-screens` (PR #15).
- Changed files: new `Home`, `Diagnosis`, `Result`, `History` NativeComponents + views; routes; split tests (`HomeTest`, `DiagnosisTest`, `HistoryTest`, `ResultTest`); docs/evidence.
- Implemented: landing screen with new/continue/history/settings; diagnosis flow moved intact to `/diagnosis`; fresh `/diagnosis` vs resumed `/diagnosis/{session}`; result screen with photo/symptoms/summary/follow-up/safety; history list of saved sessions.
- Verified (command, environment/device, result):
  - `php artisan test` — 53 passed, 347 assertions.
  - Emulator `Pixel_9a`: Home → History → Result navigation, latest-session resume, safety notice and follow-up display worked. Evidence: `docs/evidence/2026-10-27-emulator-home-screens.png`, `...-history.png`, `...-result-screen.png`.
- Not verified: physical-device pass of the new screens; release build.
- Blockers/decisions: Home shows the latest session; `/diagnosis` is a fresh session, `/diagnosis/{id}` resumes a saved one. History is capped at 30 sessions.
- Next executable action: T10/T11 — Android CI build, signed release APK, judge guide and demo.

### 2026-10-27 — T10/T11 Android CI + release packaging
- Branch/PR: `task/t10-android-ci` (PR #16).
- Changed files: `.github/workflows/android.yml`; `scripts/build-release.sh`; `docs/JUDGE_GUIDE.md`, `docs/RELEASE.md`, `docs/DEMO.md`; README/.gitignore/STATUS/TASKS/spec 005.
- Implemented: CI job that installs PHP/JDK 17/Android SDK 36, builds a signed release APK with repository secrets or an ephemeral CI key, verifies it with `apksigner`, and uploads the artifact. Local release-build script and judge/release/demo docs.
- Verified (command, environment/device, result):
  - `php artisan test` — 53 passed, 347 assertions.
  - Local `native:package android --build-type=release` with a throwaway keystore — produced a 76.77 MB APK; `apksigner verify` showed the expected certificate; installed and launched on the `Pixel_9a` emulator.
- Not verified: first GitHub Actions Android run; stable release signing identity; physical-device release install; demo video.
- Blockers/decisions: NativePHP's release-build warning says “unsigned” even when `apksigner` confirms signing; treated as a false warning but noted. CI falls back to an ephemeral keystore when secrets are absent, so artifact identity is not stable.
- Next executable action: observe the Android workflow, add `ANDROID_KEYSTORE_BASE64`/passwords/alias secrets for a stable identity, install on the ZTE, record the demo.

### 2026-10-27 — Android CI first successful run
- Branch/PR: `task/t10-android-ci` (PR #16).
- Changed files: `.github/workflows/android.yml` (setup-android v4 after v3's removed `tools` package); STATUS/TASKS/spec 005/RELEASE.
- Implemented: fixed the CI SDK setup; no app code changes.
- Verified (command, environment/device, result):
  - GitHub Actions run `40487118046` — Ubuntu, PHP 8.5, JDK 17, Android SDK 36 — **success** in ~8.5 min. Built a signed release APK, verified it with `apksigner`, uploaded the artifact.
  - `PHP tests` job passed in the same run.
- Not verified: stable signing identity (still ephemeral without secrets); physical install of the release APK; demo video.
- Blockers/decisions: `android-actions/setup-android@v3` fails on the removed `tools` package; v4 is required.
- Next executable action: configure signing secrets, install the release APK on the ZTE, record the demo.

### 2026-10-28 — Stable CI signing + warning cleanup
- Branch/PR: `fix/ci-deprecations` (PR #17).
- Changed files: `.github/workflows/android.yml`, `.github/workflows/quality.yml`, STATUS/TASKS/RELEASE.
- Implemented: bumped Node-based actions to v5; retained the stable keystore validation step. Documented the remaining sdkmanager deprecation.
- Verified (command, environment/device, result):
  - GitHub Actions run `40652416378` — main — **success** after retrying the transient NativePHP PHP-binary download. Signed APK uploaded; `apksigner` certificate is `CN=FixIt Release, OU=Mobile, O=FixIt, L=Berlin, ST=Berlin, C=DE`.
  - Keystore validation step passed with the repository secrets.
- Not verified: physical install of the release APK; demo video; Android CLI migration.
- Blockers/decisions: `sdkmanager` still works but is deprecated; Android CLI requires a separate download/installation and is deferred to its own change.
- Next executable action: install the release APK on the ZTE and record the demo; then migrate the CI SDK install to Android CLI.

### 2026-10-28 — Tag-only GitHub Releases
- Branch/PR: `fix/ci-deprecations` (PR #17).
- Changed files: `.github/workflows/android.yml`, docs/RELEASE/JUDGE_GUIDE/STATUS/TASKS/spec 005.
- Implemented: Android workflow now runs only on `v*` tags; builds the signed APK and creates/updates a GitHub Release with `app-release.apk` and `app-release.apk.sha256`; fails if the keystore secret is missing; sets app version/version-code from the tag/run number. CI SDK installation now uses Android CLI instead of deprecated `sdkmanager`.
- Verified (command, environment/device, result):
  - Tag `v0.0.5-ci-test` → run `40659466606` — success; GitHub Release contained `app-release.apk` (80.5 MB) and SHA-256. Test tag/release removed.
  - No `sdkmanager`/Node 20 deprecation warnings; stable keystore certificate used.
- Not verified: physical install of the release APK; demo video.
- Blockers/decisions: tag-only releases prevent per-push APK spam; a tag fails if signing secrets are absent.
- Next executable action: install the release APK on the ZTE and record the demo.

### 2026-10-28 — T13 multi-provider AI
- Branch/PR: `task/t13-multi-provider-ai` (PR #18, merged to `main` as `f9c7907`).
- Changed files: `composer.json`/`composer.lock` (Symfony AI Anthropic, Hugging Face, OpenAI bridges); `config/ai.php`; `app/Services/AI/Provider/*` (catalog, settings store, platform factory, registry); `ModelGateway`, exceptions; `AppServiceProvider`; `Settings`/`Diagnosis` components and views; `.env.example`; tests (`ModelGatewayTest`, `SettingsTest`, `DiagnosisTest`, provider unit tests, in-memory/fake test support); docs (spec 006, ADR 0004, architecture, DEVELOPMENT, JUDGE_GUIDE, README, TASKS, STATUS); evidence screenshots.
- Implemented: curated one-provider/one-model/one-key selection for DeepSeek, OpenRouter, Hugging Face, Anthropic and OpenAI; Symfony AI bridge factory; encrypted `ai-settings.enc` with legacy `ai-key.enc` migration; provider-named errors; vision capability guard; dynamic provider copy in Diagnosis; removal of the hard-coded `sk-` key rule.
- Verified (command, environment/device, result):
  - `php artisan test --compact` — host, PHP 8.5.12 — 94 passed, 581 assertions.
  - GitHub Actions `PHP tests` on PR #18 — run `40670700665` — **pass** (18 s).
  - Emulator `Pixel_9a` (Android 17 / API 37), JDK 17.0.21, debug APK — Settings shows DeepSeek/`deepseek-flash`, provider/model selects and provider-specific hints; switching to OpenRouter updates the model and blocks old-key reuse; one-key save + **Test connection** returned `Connected: FixIt AI connectivity OK` for DeepSeek, OpenRouter and Hugging Face against the local fake provider.
  - Emulator Hugging Face path — photo consent/button named Hugging Face, image uploaded through the HF bridge, synthetic vision result rendered, multi-turn follow-up succeeded and persisted.
  - Local fake-provider base URLs were removed from `.env`, app data cleared, APK rebuilt against `https://api.deepseek.com` with no saved key; APK scan found zero occurrences of the fake keys.
  - Evidence: `docs/evidence/2026-10-28-t13-settings.png`, `...-t13-openrouter-selected.png`, `...-t13-hf-fake-followup.png`.
- Not verified: real provider keys (DeepSeek regression, OpenRouter Claude/GPT, HF, direct Anthropic/OpenAI) and the physical ZTE 8050 pass; release build of T13; demo video.
- Blockers/decisions: no provider keys or physical device were available in this environment. Custom model IDs are deferred; only curated vision models are selectable. OpenAI direct uses the Responses API; Anthropic uses its Messages API. ADR 0004 records the one-key invariant.
- Next executable action: with user-supplied keys (entered on device, never committed), repeat Settings test + photo/follow-up for DeepSeek, OpenRouter Claude, OpenRouter GPT and HF on the ZTE; record limitations, then open the T13 PR.

### 2026-10-28 — T14 UI polish (design only)
- Branch/PR: `task/t14-ui-polish` (PR pending).
- Changed files: `resources/views/native/{home,diagnosis,result,history,settings}.blade.php`, new `resources/views/native/partials/message.blade.php`; `config/native-ui.php` (tonal secondary, `primary-container`, `warning-container`, softer neutrals); new `app/Support/DisplayText.php`; `RepairSessionService::followUpTurns()`; display-only properties in `Home`, `History`, `Diagnosis`, `Result`; tests (`HomeTest`, `DiagnosisTest`, `SettingsTest` copy updates; new `DisplayTextTest`, `followUpTurns` test); `docs/TASKS.md`; evidence screenshots.
- Implemented: top bars with icon buttons instead of stacked pill buttons; one filled button per screen; camera-first Home hero with latest-repair row; Diagnosis as a chat thread (photo + symptoms, every saved AI reply and user answer, amber safety card) anchored to the bottom with a pinned answer composer; single status line instead of four; Markdown stripped from AI text and replies split into lines; readable status labels; History/Result as list/thread; Settings key banner, Test/Save side by side, key storage notes grouped. No AI, safety, consent, storage or navigation behaviour changed; all action refs kept.
- Verified (command, environment/device, result):
  - `php artisan test` — host, PHP 8.5.12 — 99 passed, 598 assertions.
  - `php artisan native:run android emulator-5554 --no-tty --no-vite` — emulator `Pixel_9a` (Android 17 / API 37) — Home (empty and with sessions), Diagnosis camera card, Settings, and a seeded two-turn conversation in Diagnosis/Result/History rendered as designed; thread opened scrolled to the latest message. Sample session was inserted into the emulator SQLite DB (no AI call). Evidence: `docs/evidence/2026-10-28-t14-*.png`.
- Not verified: live photo → AI reply → follow-up flow with a real key on the redesigned screens; physical device; dark mode; large system font sizes; keyboard overlap with the pinned composer.
- Blockers/decisions: native `select` only takes plain option values (the value is persisted), so dropdowns still show provider/model IDs; the readable names are shown as supporting text. List-item rows paint square backgrounds, so list cards lose their rounded corners.
- Next executable action: merge, build, and on the ZTE run a real DeepSeek photo + two follow-ups; check keyboard behaviour on the composer and dark mode.

### 2026-10-28 — T15 Symfony AI integration clean-up
- Branch/PR: `task/t15-symfony-ai-refactor` (PR #22).
- Changed files: `app/Services/AI/{AiRequestFailedException,ModelGateway}.php`; new `app/Services/AI/{AgentFactory,SymfonyAgentFactory}.php`; new `app/Services/Diagnosis/{DiagnosisPrompts,ConversationBuilder}.php`; `app/NativeComponents/Diagnosis.php` (calls the new services, prompt methods removed); `app/Providers/AppServiceProvider.php` (binds `AgentFactory`); `tests/Support/TestGateway.php`; `tests/Unit/Services/AI/ModelGatewayTest.php`; new `AiRequestFailedExceptionTest`, `SymfonyAgentFactoryTest`, `DiagnosisPromptsTest`, `ConversationBuilderTest`, `tests/Feature/DiagnosisPromptWiringTest.php`; `docs/architecture.md`, `docs/TASKS.md`.
- Implemented:
  - Bug fix: Symfony AI throws typed exceptions without an HTTP code, so 401/400/404/429/5xx all showed "could not be reached. Check the internet connection". The type is now mapped to the right message (rate-limit `Retry-After` and context-too-long included).
  - One Symfony AI `Agent` per request from `SymfonyAgentFactory`, with `SystemPromptInputProcessor`; still one provider/model, no tools, memory or multi-agent.
  - `ModelGateway` lost its test-only `$platform`/`$model` constructor overrides (vision check and request could disagree) and takes an injected PSR logger.
  - Safety rules moved into the system prompt (`DiagnosisPrompts`); the stored `photo_prompt` is now only the photo request. Follow-up history building moved to `ConversationBuilder`.
- Verified (command, environment/device, result):
  - `php artisan test` — host macOS, PHP 8.5.12, after merging `main` with T14 — 122 passed, 650 assertions.
  - New exception tests run against the previous `AiRequestFailedException` — 12 failed (including real HTTP 401/429/502 through the generic bridge via `MockHttpClient`), confirming the bug; all pass with the fix.
  - `vendor/bin/pint --test` on changed files — passed.
- Not verified: real provider calls and the Android device after this change; model output quality with safety rules moved from the user message to the system prompt.
- Blockers/decisions: kept `symfony/ai-agent` (hackathon requires Symfony AI; Agent + input processor is the intended extension point). Structured output deferred until per-provider JSON-schema support is checked.
- Next executable action: on the ZTE with a real DeepSeek key, run Settings → Test connection with a wrong key (expect the 401 message), then a photo + follow-up; confirm replies still respect the safety rules.


### 2026-10-28 — T17 Hugging Face photo requests fail with 503
- Branch/PR: `fix/vision-photo-downscale` (PR pending). Independent of T16 (`fix/text-input-dropped-chars`, PR #23).
- Changed files: `config/ai.php` (`inference_provider` for Hugging Face, default `deepinfra`, override `FIXIT_AI_HUGGINGFACE_INFERENCE_PROVIDER`); `app/Services/AI/Provider/{ProviderDefinition,ProviderCatalog,SymfonyAiPlatformFactory}.php`; `app/NativeComponents/Diagnosis.php` + `resources/views/native/diagnosis.blade.php` (`visionError` box above the Send button); `tests/Unit/Services/AI/Provider/SymfonyAiPlatformFactoryTest.php`; `tests/Feature/DiagnosisTest.php`; `docs/STATUS.md`, `docs/TASKS.md`.
- Problem: on the ZTE, "Send photo to Hugging Face" seemed to do nothing. Every request returned HTTP 503; the message only appeared in the small status line at the bottom of the scroll view.
- Root cause: FixIt let the Hugging Face router choose the inference provider. For `Qwen/Qwen3-VL-30B-A3B-Instruct` it picks novita, which returns `internal_server_error` (503) for a full-size 3072×4032 / 1.7 MB phone JPEG. The text-only Test connection passed, so the key and model were fine.
- Evidence (user ran a script with their token on macOS; real FixIt photo; `max_tokens` 20): full photo → auto 503, `:novita` 503, `:deepinfra` 200, `:featherless-ai` 200; same photo at 1024 px → auto 200. The HF model API lists novita, featherless-ai and deepinfra as live for all three curated HF models.
- Rejected: shrinking the photo in PHP with GD. It worked in host tests, but on the ZTE the log showed `"gd": false` — the NativePHP Android PHP build does not load GD, so the original file was sent. The camera plugin has no size/quality option. The resize code was removed.
- Fix: Hugging Face requests carry the `:deepinfra` provider suffix via the Symfony AI bridge's `provider` argument. Failed photo requests now show in a highlighted box above the Send button and are cleared by the next attempt.
- Verified (command, environment/device, result):
  - `php artisan test` — host macOS, PHP 8.5.12 — 125 passed, 675 assertions; new tests fail before the change (2 platform-factory tests; 1 Diagnosis error-box test).
  - `vendor/bin/pint --test` on changed files — passed.
  - Physical ZTE 8050 (Android 13), debug build of this branch, real Hugging Face token, `Qwen/Qwen3-VL-30B-A3B-Instruct`: before — four 503s in `laravel.log`; after — new 2.2 MB photo sent, diagnosis returned (reported by the user on 2026-10-28), no new error in the log.
- Not verified: the visible error box on a device; the other two HF models through deepinfra with a photo; photo upload cost (full-size images use more tokens than needed).
- Blockers/decisions: images are still sent at full size because the Android PHP runtime has no GD; reducing size needs a native resize (camera/plugin side) and is a separate task. The UI also feels unresponsive while a request runs (reported by the user; deferred).
- Next executable action: merge T16 and T17, then fix the symptoms/follow-up fields that still drop characters.

## Handoff entry template

### YYYY-MM-DD — task
- Branch/PR:
- Changed files:
- Implemented:
- Verified (command, environment/device, result):
- Not verified:
- Blockers/decisions:
- Next executable action:
