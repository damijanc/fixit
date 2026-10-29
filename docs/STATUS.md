# FixIt status

Updated: 2026-10-29

## Current state
- Project: Laravel 13.38.0 + NativePHP Mobile 4.6.1 at the repository root (ADR 0002).
- Main setup PR: #1 (`setup/project-skeleton`), followed by docs PRs #2/#3.
- Bootstrap branch: `task/t1-android-bootstrap`.
- Active branch: `main` (T13 merged as IMPLEMENTED in PR #18; real-key and physical-device smoke tests pending).
- Host PHP/Composer/Laravel/NativePHP versions: see [DEVELOPMENT.md](DEVELOPMENT.md).
- Clean-checkout bootstrap: VERIFIED on macOS (see handoff log).
- Host test suite: VERIFIED — 213 tests, 1044 assertions (T23 branch, includes T16–T22).
- Photo downscale (T23): **VERIFIED on the ZTE 8050** on `task/t23-photo-downscale` — a local NativePHP plugin (`packages/photo-resize`, Android Kotlin) shrinks each captured photo to a 1568 px long edge before it is stored or uploaded (2.6 MB → 178 KB), applies the EXIF rotation and drops EXIF/GPS.
- Claude showcase (T20): **VERIFIED on the ZTE 8050** on `task/t20-claude-showcase` — with Claude, the diagnosis is a strict JSON schema (`output_config.format`) rendered as a card (object, likely cause, confidence, numbered safe checks, professional warning, question); every field passes the safety rules and mains/gas/battery hazards escalate; token usage and prompt-cache writes/reads are logged per call.
- Safety filter false positives (T22): **VERIFIED** on `task/t22-safety-false-positives` (PR #28) — phone-charging advice (protective case, other wall socket, battery-charging setting, "remove the case") no longer blocked; opening casings, mains work and protection bypasses stay blocked; blocks logged by rule id (plus matched words in debug builds); the blocked ZTE photo now gets an answer.
- Busy feedback (T21): **VERIFIED**, merged in PR #27 — AI calls (photo, follow-up, Settings Test) run as NativePHP async tasks on a background PHP thread, so spinners, progress text and locked controls render immediately; emulator screenshots of each busy state plus a hand check with Claude on the ZTE 8050.
- Anthropic / Claude (T19): **VERIFIED on the ZTE 8050** with a real key, merged in PR #26 — Claude Opus 5.5 (default), Sonnet 5.5 and Haiku 5.5; `max_tokens` 16000 and `effort: medium` per model; thinking replies and refusals handled; Test connection, photo diagnosis and follow-up passed.
- Diagnosis text fields (T18): **VERIFIED**, merged in PR #25 — symptoms and follow-up fields no longer drop characters (no echo of typed text); emulator fake-provider check plus hand typing on the ZTE 8050.
- API key field (T16): **VERIFIED**, merged in PR #23 — the key field no longer drops characters (live-sync echo race in NativePHP's text input); emulator fake-provider check plus real Hugging Face Test connection on the ZTE 8050.
- Hugging Face photo diagnosis (T17): **VERIFIED on the ZTE 8050** with a real token, merged in PR #24 — Hugging Face requests are routed to `deepinfra` because the router's default pick (novita) returned 503 for full-size phone photos; photo errors are shown next to the Send button.
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

### 2026-10-28 — T16 API key field drops characters
- Branch/PR: `fix/text-input-dropped-chars` (PR #23).
- Changed files: `resources/views/native/settings.blade.php` (`native:model.debounce.600ms="apiKey"`); `tests/Feature/SettingsTest.php`; `docs/STATUS.md`, `docs/TASKS.md`.
- Problem: a valid Hugging Face token (checked with `whoami-v2`) failed with 401 on the ZTE 8050. The key field looked right while typing, but characters were missing from the saved key.
- Root cause: plain `native:model` is live sync. Each keystroke goes to PHP, and the re-rendered `value` comes back. NativePHP's Android `OutlinedTextInputRenderer` replaces the local text when the pushed value differs from the last value sent, so a late echo of an older value overwrites newer input, and the echo of the newer value is then ignored as already sent. Characters vanish from fast or `adb`-typed input and can vanish from manual typing.
- Fix: the key field uses debounce sync (600 ms), so nothing is sent mid-typing and no stale echo can arrive. `native:model.blur` was rejected: tapping **Save** does not take focus from the field, so the new key never reached PHP and the old key was kept while the UI said "saved".
- Verified (command, environment/device, result):
  - Emulator `Pixel_9a` (Android 17 / API 37), debug build with `FIXIT_AI_HUGGINGFACE_BASE_URL=http://10.0.2.2:8099` and a fake provider that logs the `Authorization` header; dummy keys typed with `adb shell input text`.
  - Before (live sync): `hf_Ab12_cd34XYZ` typed → field and request `hf_A12_cd34XYZ`; character-by-character `hf_Ab12_cd34XYZ_9qW` → `hf_Ab12_c34XYZ_9q`.
  - Blur sync: all characters kept in the field, but Save stored the previous key (request still sent the old key).
  - Debounce 600 ms: fast `hf_Fast1_Ab12_cd34XYZ_9qW` and character-by-character `hf_Slow2_Ab12_cd34XYZ_9qW` both arrived exactly; Test connection returned `Connected: FixIt AI connectivity OK`.
  - New host test fails against the live binding and passes with the fix; `php artisan test` — 123 passed, 653 assertions.
  - Physical ZTE 8050 (Android 13), debug build of this branch, real Hugging Face fine-grained token (Inference Providers permission), `Qwen/Qwen3-VL-30B-A3B-Instruct`: key pasted, Save, **Test connection → connected** (reported by the user on 2026-10-28). The same token failed with 401 on the live-sync build.
- Not verified: a photo diagnosis through Hugging Face on the ZTE; tapping Save within 600 ms of the last keystroke (would save the text from before that keystroke).
- Blockers/decisions: the race is in NativePHP's text input renderer and should be reported upstream. The symptoms and follow-up answer fields still use live sync and can drop characters the same way; they were not changed because a debounce there can lose text when Send is tapped right after typing.
- Next executable action: run a photo diagnosis + follow-up through Hugging Face on the ZTE; then decide how to protect the symptoms/follow-up fields.


### 2026-10-28 — T17 Hugging Face photo requests fail with 503
- Branch/PR: `fix/vision-photo-downscale` (PR #24). Independent of T16 (`fix/text-input-dropped-chars`, PR #23).
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

### 2026-10-29 — T18 Symptoms and follow-up fields drop characters
- Branch/PR: `fix/diagnosis-input-dropped-chars` (PR #25), from `main` after PRs #23/#24.
- Changed files: `app/NativeComponents/Diagnosis.php` (`$symptomsInitial`); `resources/views/native/diagnosis.blade.php` (both fields bound without `native:model`); `tests/Feature/DiagnosisTest.php`; `docs/STATUS.md`, `docs/TASKS.md`.
- Problem: same live-sync echo race as T16 (see that entry). On `main`, `main_build_Ab12_cd34XYZ_9qW_Lm56` typed into the symptoms field reached the provider as `main_uild_A12cd34XYZ_9qWLm56`.
- Fix: the fields keep live change events (`_change="__syncProperty(...)"`, `sync-mode="live"`), so Send always sees the latest text, but their `value` is no longer the typed property: symptoms show `$symptomsInitial` (set on resume), follow-up shows `""`. PHP therefore never pushes stale text back. Clearing relies on remounting: discarding the photo removes the symptoms card; a successful follow-up adds turns above the composer, which changes the field's positional node id, and NativePHP's `key(node.id)` gives it fresh empty state. A failed follow-up keeps the same id and the typed text.
- Rejected: debounce (T16's fix) — Send right after typing would lose the last characters. `native:key` with a reset counter — corrupted the Android layout after the photo send (title showed the symptoms text, photo and reply missing; keyed hashed ids vs positional ids).
- Verified (command, environment/device, result):
  - Emulator `Pixel_9a` (API 37), debug builds against the fake provider (`FIXIT_AI_HUGGINGFACE_BASE_URL=http://10.0.2.2:8099`) logging the user text; emulator camera photo; text typed with `adb shell input text`, Send tapped 0.3 s later.
  - `main`: symptoms lost 4 characters (above); layout correct.
  - This branch: symptoms `fix_build_Ab12_cd34XYZ_9qW_Lm56` sent exactly; follow-ups `answer1_…` and `answer2_light_on_Ab12_cd34XYZ_9qW_Lm56` sent exactly; field empty after each send; layout correct (screenshot checked).
  - Send tapped in the same millisecond as the last keystroke (no gap) lost only the final character: the tap event can overtake the last change event. Not reachable by a finger.
  - `php artisan test` — 132 passed, 740 assertions; three new tests fail on `main`.
  - Physical ZTE 8050 (Android 13), debug build of this branch, real Hugging Face token, Gboard: symptoms and several follow-up answers typed by hand all arrived complete; the answer field cleared after each send (reported by the user on 2026-10-29).
- Not verified: Gboard autocorrect replacing a whole word was not checked separately.
- Next executable action: merge PR #25; then T19 (the UI feels unresponsive while a request runs).

### 2026-10-29 — T19 Anthropic integration on current Claude models
- Branch/PR: `task/t19-anthropic` (PR pending), from `main` after PR #25.
- Changed files: `config/ai.php` (Anthropic models + per-model `options`); `app/Services/AI/Provider/{ModelDefinition,ProviderCatalog}.php` (model request options); `app/Services/AI/ModelGateway.php` (options passed to `Agent::call`, multi-part text, refusal check); `app/Services/AI/AiRequestFailedException.php` (`refused()`, empty-reply message); new `tests/Unit/Services/AI/AnthropicGatewayTest.php`; `tests/Unit/Services/AI/Provider/{ProviderCatalog,SymfonyAiPlatformFactory}Test.php`; `docs/{JUDGE_GUIDE,DEVELOPMENT,STATUS,TASKS}.md`.
- Problems found in the T13 integration (never run with a real key):
  - Models were a generation old (Sonnet 4.5, Haiku 4.5, Opus 4.1).
  - Current Claude models always think; the reply holds a `thinking` block before the `text`, so Symfony AI returns a `MultiPartResult` whose `getContent()` is an array. `ModelGateway` required a string, so every call to a current Claude model would have failed with "empty or unsupported response".
  - FixIt sent no `max_tokens`; the bridge default (1000) is shared with thinking and can truncate the answer.
  - A refusal (`stop_reason: refusal`) or a reply without content surfaced as "could not be reached. Check the internet connection".
- Fix: Anthropic models are `claude-opus-5-5` (first), `claude-sonnet-5-5`, `claude-haiku-5-5`, each with `options: max_tokens 16000, output_config.effort medium` (Opus 5.5 and Haiku 5.5 default to medium, Sonnet 5.5 to high; set explicitly). Model options reach `Agent::call()` for every provider (empty for the others). The gateway reads `MultiPartResult::asText()`, throws `refused()` on a content-filter finish reason, and maps the bare platform `RuntimeException` (no usable content) to "returned no answer". No sampling parameters are sent. The Symfony AI Anthropic bridge stays (stack constraint); it already applies prompt-caching breakpoints by default.
- Verified (command, environment/device, result):
  - `php artisan test` — host macOS, PHP 8.5.12 — 138 passed, 756 assertions; the 6 new tests drive the real Symfony AI Anthropic bridge against a mocked Messages API and fail before the change.
  - `vendor/bin/pint --test` — changed files pass (two untouched files are pre-existing failures).
  - Physical ZTE 8050 (Android 13), debug build of this branch, the user's Anthropic key (entered via `adb input text` from the Mac clipboard), Claude Opus 5.5: Test connection → "Connected"; photo diagnosis → 778-character answer stored, session `awaiting_follow_up`, 23 s or less from photo capture (incl. typing) to stored answer; one follow-up → 617-character answer. No errors in `laravel.log`.
- Not verified: Sonnet 5.5 and Haiku 5.5 with a real key; a real refusal; token usage and cache hits (not logged yet).
- Next executable action: T20 — structured JSON diagnosis via `output_config.format` and logging token usage/cache reads to show prompt caching.

### 2026-10-29 — T21 Show progress while an AI call runs
- Branch/PR: `task/t21-busy-feedback` (PR #27, merged), from `main` after PR #26.
- Changed files: `app/NativeComponents/{Diagnosis,Settings}.php`; `app/Services/AI/AiException.php` (`displayMessage()`); `resources/views/native/{diagnosis,settings}.blade.php`; `tests/Pest.php` (`AsyncTask::fake()` for Feature tests); `tests/Feature/{Diagnosis,Settings}Test.php`; `docs/STATUS.md`, `docs/TASKS.md`.
- Problem: tapping Send photo, sending a follow-up or Settings → Test looked like nothing happened for 10–30 s. The handlers called the model synchronously on the UI PHP thread, so the existing loading button and "Thinking…" indicator could not render until the reply had arrived — by then the busy flag was false again.
- Fix: each handler sets the busy state and dispatches the call with `$this->async(static fn () => …)` (NativePHP `AsyncTask`, a booted background PHP context). `finished()` runs the existing safety filter, persistence and UI updates on the UI thread; `failed()` shows `AiException::displayMessage()`, which keeps the original user-safe message carried by `AsyncTaskException::originalClass()` and reports anything else. Task timeout is `ai.timeout + 30` s, so the HTTP timeout reports first. The follow-up is reloaded from its session id in the background (models do not cross threads).
- UI: photo — loading button "Sending to …", a progress row "… is looking at your photo. This can take up to 30 seconds.", symptoms field and Discard disabled. Follow-up — the answer appears in the thread as a pending bubble with "Thinking…", the composer is disabled and its button spins; a failed send remounts the field with the answer (`$followUpInitial`). Settings — Test button spins with "Testing…", status "Contacting …", Save disabled.
- Verified (command, environment/device, result):
  - `php artisan test` — 146 passed, 839 assertions; new tests assert one async dispatch per action, the busy-state rendering (`loading`/`disabled` props, progress row, pending bubble) and that provider messages survive the background hop; they fail before the change.
  - Emulator `Pixel_9a` (API 37), debug build against a fake provider that waits 6 s: screenshots 2 s after each tap show the busy states; replies arrive via `finished()` (session saved, thread rendered, field cleared); with the fake server stopped, Settings → Test showed FixIt's own "could not be reached…" message from the background task.
  - Physical ZTE 8050, real Anthropic key (Claude Opus 5.5): Test, photo diagnosis and a follow-up — progress shows immediately and all completed (reported by the user on 2026-10-29).
- Not verified: leaving the screen while a task runs (NativePHP scopes the callback to the component and drops it if the screen is gone; the reply is then lost, not saved).
- Next executable action: T20 — structured JSON diagnosis and token/cache logging.

### 2026-10-29 — T22 Safety filter blocked ordinary charging advice
- Branch/PR: `task/t22-safety-false-positives` (PR #28), from `main` after PR #26 (independent of T21 / PR #27).
- Changed files: `app/Services/Safety/SafetyFilter.php`; `tests/Unit/Services/Safety/SafetyFilterTest.php`; `docs/STATUS.md`, `docs/TASKS.md`.
- Problem: a photo of a phone with a cable plus "phone is not charging" was replaced by the block message on the ZTE (Claude Opus 5.5). The filter stored and logged nothing about why.
- Root cause, found in three rounds on the device:
  - Offline probe of typical charging advice: "Remove the phone's protective case" and "Take off any case or cover" hit `opening_appliance`; "Connect the charger to a different wall socket" hit `mains_work`; "Disable optimised battery charging protection" hit `bypass_interlock`; "Plug the charger into another wall socket" escalated.
  - After the first fix the new log showed `opening_appliance` again. The rule matched a verb and a target word anywhere within 60 characters ("Open Settings on your device", "Remove the cable and look at the back").
  - After tightening, the debug log showed the exact match: `"Remove the case"` — the protective case.
- Fix:
  - Accessories (protective/phone/silicone/... case or cover, "any case", "case or cover", screen protector) are neutralised before the block rules. "The/your case" counts as the accessory only when the sentence mentions nothing inside (board, circuit, screws, wires, inside, internal, motor, element, fuse, replacing/disconnecting the battery).
  - `opening_appliance`: "disassemble", "take apart", "pry/prise open/off" block on their own; otherwise the casing word (back, case, casing, housing, panel, cover, appliance, device, boiler, machine, screws) must be the verb's object — at most three words between, never across and/then/or/to/on/in/from/with/for/of/at/so/if, not a possessive ("device's").
  - `mains_work`: work verbs only (work on, touch, rewire, repair, open) — connecting or unplugging a charger is not mains work.
  - `bypass_interlock`: "protection" only as surge/thermal/overload/overcurrent/circuit protection.
  - Escalation no longer fires on "wall socket" alone; mains-powered, live wire, high voltage and voltages still escalate.
  - A block logs `Safety filter blocked model output` with rule ids; debug builds add the matched words. The blocked text is still never stored.
- Verified (command, environment/device, result):
  - `php artisan test` — 179 passed, 852 assertions. `SafetyFilterTest`: 16 safe charging/settings sentences must pass unchanged, 19 dangerous counterparts must stay blocked with the expected rule (back cover, case screws, housing, open the device, open up the back panel, remove screws, unscrew cover, disassemble, take apart, pry open, remove the case to reach the board / check wires inside / replace the battery, rewire/open/touch mains, live wire, bypass fuse, disable surge/thermal protection, safety switch), plus escalation and both log formats.
  - `vendor/bin/pint --test` on changed files — passed.
  - Physical ZTE 8050, real Anthropic key (Claude Opus 5.5), same phone-with-cable photo and "phone is not charging": blocked on `main` and twice during the fix (log reasons above); with the final build an 807-character answer was stored, status `awaiting_follow_up`, no block logged (2026-10-29 10:56).
- Not verified: other appliance categories against real model output (the rules are still keyword-based; new false positives will show their matched words in debug logs).
- Next executable action: merge PR #28; then T20.

### 2026-10-29 — T20 Claude showcase: structured diagnosis and usage logging
- Branch/PR: `task/t20-claude-showcase`, from `main` after PR #28.
- Changed files: `app/Services/Diagnosis/DiagnosisReply.php` (new), `app/Services/Diagnosis/DiagnosisPrompts.php`, `app/Services/Safety/SafetyFilter.php`, `app/Services/AI/ModelGateway.php`, `app/Services/AI/Provider/ModelDefinition.php`, `app/Services/AI/Provider/ProviderCatalog.php`, `config/ai.php`, `app/NativeComponents/Diagnosis.php`, `app/Support/DisplayText.php`, `resources/views/native/partials/diagnosis-card.blade.php` (new), `resources/views/native/partials/message.blade.php`; tests `tests/Unit/Services/Diagnosis/DiagnosisReplyTest.php` (new), `tests/Unit/Services/AI/AnthropicGatewayTest.php`, `tests/Unit/Services/AI/ModelGatewayTest.php`, `tests/Feature/DiagnosisTest.php`; `docs/STATUS.md`, `docs/TASKS.md`.
- Implemented:
  - `DiagnosisReply`: strict schema (object, likely_cause, confidence low/medium/high, up to five safe_checks, hazard none/mains/gas/battery/other, needs_professional, question) and validated parsing. Models opt in with `structured_output` in `config/ai.php` (all three Claude models); `ModelGateway` then sends `response_format` → the Anthropic bridge's `output_config.format`. Other providers keep the plain-text prompts and filter.
  - `SafetyFilter::assessDiagnosis()` checks the cause, every check and the question separately; any blocked part blocks the whole reply; a mains/gas/battery hazard, `needs_professional` or an escalating phrase sets the professional warning.
  - Assistant messages that parse as a reply render as a card; history titles and plain-text views use `DisplayText::plain()`.
  - `ModelGateway` logs `AI usage` with provider, model, input/output tokens, cache read and cache creation tokens for every call (Symfony AI's Anthropic bridge already sets the cache breakpoints).
  - After the first device run: likely cause no longer bold, prompt asks for short fields, battery hazard escalates (the first card suggested checking for a swollen battery without a warning).
- Verified (command, environment/device, result):
  - `php artisan test` — 208 passed, 1031 assertions (schema, parsing, rejection of incomplete JSON, safety on structured replies incl. battery hazard, request body sent to the Anthropic bridge, no schema for non-structured models, usage log, card rendering in the Diagnosis component).
  - Physical ZTE 8050, real Anthropic key, Claude Opus 5.5, MacBook photo + "battery is draining fast": card rendered with object, cause, confidence, five checks and a question; log `input_tokens 4, output_tokens 742, cache_creation_tokens 5607`.
  - Same diagnosis, follow-up "it drains when laptop is on" with the final build: updated card (medium confidence, Activity Monitor / Chrome / brightness checks); log `output_tokens 539, cache_creation_tokens 1480, cache_read_tokens 0`.
- Not verified: a cache **read** on the device. The follow-up uses a different system prompt and resends the history without the photo, so it cannot reuse the photo call's prefix; a second follow-up in the same diagnosis should read the 1480 cached tokens. Structured output on Sonnet/Haiku 5.5 was not run with a real key.
- Next executable action: merge; then T23 (shrink photos before upload).

### 2026-10-29 — T23 Shrink photos before upload
- Branch/PR: `task/t23-photo-downscale`, from `main` after PR #29.
- Changed files: `packages/photo-resize/` (new local NativePHP plugin: `nativephp.json`, `composer.json`, `README.md`, `resources/android/PhotoResizeFunctions.kt`, `src/PhotoResize.php`, `src/PhotoResizeServiceProvider.php`); `composer.json` + `composer.lock` (path repository, `fixit/photo-resize: @dev`); `app/Providers/NativeServiceProvider.php` (plugin enabled); `app/NativeComponents/Diagnosis.php` (`shrinkPhoto()` in `handlePhotoTaken`); `tests/Unit/PhotoResizeTest.php` (new), `tests/Feature/DiagnosisTest.php`; `docs/STATUS.md`, `docs/TASKS.md`.
- Why native: the Android PHP build has no GD (T17) and no Imagick; the camera plugin has no size or quality option.
- Implemented:
  - Bridge function `PhotoResize.Downscale` (Context-only, Android): decodes with a power-of-two sample size, scales to a 1568 px long edge (Claude's own downscale limit), applies the EXIF orientation, writes JPEG quality 85 to a temp file and renames it over the original — a failure leaves the photo untouched. Photos already within the limit are not rewritten. Errors use NativePHP's `BridgeResponse.error`.
  - `Fixit\PhotoResize\PhotoResize::downscale()` returns path/size or `null` (bridge missing, error, bad JSON).
  - `Diagnosis::handlePhotoTaken()` shrinks the photo before the session is saved; logs `Photo downscaled` (bytes before/after, dimensions) or `Photo not downscaled`.
  - The rewritten file has no EXIF, so GPS and device metadata are no longer uploaded.
- Verified (command, environment/device, result):
  - `php artisan test` — 213 passed, 1044 assertions (bridge parameters, three failure cases, capture flow calls the bridge and keeps the path).
  - `vendor/bin/pint --test` on changed PHP files — passed. `php artisan native:plugin:validate packages/photo-resize` — OK.
  - `php artisan native:run android emulator-5554 --no-tty --no-vite` — Kotlin compiled; emulator photo 1440×1920 64 KB → 1176×1568 25 KB.
  - Physical ZTE 8050 (`native:run android LZV50SDEDC3001352`), portrait photo of a laptop: `Downscaled 3072x4032 to 1195x1568, 178507 bytes` (from 2,598,636 bytes); pulled file is upright, no orientation tag; Claude Opus 5.5 card answer correct ("Laptop fan is loud"), photo request `cache_creation_tokens 3327` vs 5607 for the full-size photo earlier.
- Not verified: Hugging Face photo diagnosis with the smaller file (the `deepinfra` routing from T17 stays); a landscape photo; very low-memory devices (OutOfMemoryError is caught and the original kept).
- Next executable action: merge; then a cache-read check (second follow-up) or T12.

## Handoff entry template

### YYYY-MM-DD — task
- Branch/PR:
- Changed files:
- Implemented:
- Verified (command, environment/device, result):
- Not verified:
- Blockers/decisions:
- Next executable action:
