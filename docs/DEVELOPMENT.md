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
| Symfony AI provider bridges | Generic / Hugging Face / Anthropic / OpenAI 0.15.0 |
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

## AI provider settings

FixIt supports DeepSeek, OpenRouter, Hugging Face, Anthropic and OpenAI through Symfony AI 0.15 platform bridges. The user selects one provider and one vision-capable model in **Provider settings** and enters one API key.

Non-secret configuration in `.env`:

```dotenv
FIXIT_AI_DEFAULT_PROVIDER=deepseek
FIXIT_AI_BASE_URL=https://api.deepseek.com
FIXIT_AI_TIMEOUT=60
```

`FIXIT_AI_DEFAULT_PROVIDER` only affects a fresh install with no saved selection. `FIXIT_AI_BASE_URL` is a development override for the DeepSeek-compatible endpoint, mainly to point at the local fake provider.

**The API key is never configured in `.env`.** It is entered in the app and encrypted with the NativePHP-managed `APP_KEY`; only ciphertext is written to `storage/app/private/ai-settings.enc`. NativePHP strips `APP_KEY` from the bundled `.env` and supplies it from Android Keystore at runtime, so the saved key cannot be recovered from the APK or a copied storage file. Exactly one key is stored; changing provider drops the previous provider's key unless a new key is entered in the same save. ADR 0004.

### Provider/model matrix

| Provider | Bridge | Curated vision models |
|---|---|---|
| DeepSeek | Generic OpenAI-compatible | `deepseek-flash` |
| OpenRouter | Generic OpenAI-compatible | `anthropic/claude-sonnet-4.5`, `openai/gpt-4.1`, `openai/gpt-4o`, `openai/gpt-5` |
| Hugging Face | `symfony/ai-hugging-face-platform` | `Qwen/Qwen3-VL-30B-A3B-Instruct`, `Qwen/Qwen3.8-27B`, `google/gemma-4-31B-it` |
| Anthropic | `symfony/ai-anthropic-platform` | `claude-sonnet-4-5`, `claude-haiku-4-5`, `claude-opus-4-1` |
| OpenAI | `symfony/ai-open-ai-platform` | `gpt-4.1`, `gpt-4o`, `gpt-5` |

OpenRouter is one provider/key: selecting `anthropic/claude-sonnet-4.5` or `openai/gpt-4.1` still uses the OpenRouter platform and OpenRouter key.

### Enter a provider key

1. Run the app on the emulator/device.
2. Tap **Provider settings**.
3. Select a provider and a model.
4. Paste the provider's API key. Keys are not prefix-filtered; the provider validates them.
5. Tap **Save settings** → **Test connection**.
6. Expected: `Connected: FixIt AI connectivity OK`.

The selection and key survive an app restart. **Remove saved key** deletes the key but keeps the provider/model. To switch provider, select it, enter that provider's key and save; the previous provider's key is not reused.

### Upgrade from the DeepSeek-only release

On first read, if `storage/app/private/ai-key.enc` exists and `ai-settings.enc` does not, FixIt migrates the key to DeepSeek + `deepseek-flash` in the new encrypted file and deletes the legacy file. No re-entry is required.

### Verify against the local fake provider (no real key)

```bash
php -S 0.0.0.0:8099 scripts/dev/fake-openai-server.php
```

Point the DeepSeek endpoint at it before building:

```dotenv
FIXIT_AI_BASE_URL=http://10.0.2.2:8099
```

For local Android smoke tests of the OpenRouter or Hugging Face bridges, the same fake server can be used with their development-only base-URL overrides:

```dotenv
FIXIT_AI_OPENROUTER_BASE_URL=http://10.0.2.2:8099
FIXIT_AI_HUGGINGFACE_BASE_URL=http://10.0.2.2:8099
```

These overrides are non-secret and are not set in a normal build. The fake server is OpenAI-compatible; it does not emulate Anthropic's Messages API or OpenAI's Responses API.

Then build, open Settings, save a test key, and tap **Test connection**.

### Automated provider tests

```bash
php artisan test --compact tests/Unit/Services/AI
php artisan test --compact tests/Feature/SettingsTest.php tests/Feature/DiagnosisTest.php
```

These use `MockPlatformFactory`, `MockHttpClient` and an in-memory settings store. No paid provider calls are made in CI.


## Camera check (T4 spike)

The `nativephp/mobile-camera` plugin (1.1.0) opens the platform camera app and returns a photo path to PHP.

```bash
php artisan native:run android emulator-5554 --no-tty --no-vite
```

Then on the device:

1. Tap **Take a photo**.
2. Grant the camera permission (Android shows `Allow FixIt to take pictures and record video?`).
3. Take the photo, then tap the **✓ checkmark** on the system camera’s review screen. FixIt receives nothing until you confirm.
4. The home screen renders the photo and logs `Photo captured: image/jpeg, <file>.jpg`.

Verified 2026-10-26 on the `Pixel_9a` emulator (Android 17 / API 37) using the virtual-scene camera:

- The system camera opened from embedded PHP; permission prompt and capture flow worked.
- A **1440×1920 JPEG** (62 KB, EXIF present) was written to app-private storage: `{noBackupFilesDir}/Camera/`.
- PHP received the path and the native UI rendered it as a preview.
- Evidence: [evidence/2026-10-26-android-emulator-camera.png](evidence/2026-10-26-android-emulator-camera.png).

Verified 2026-10-27 on a physical **ZTE 8050** (Android 13 / API 33, arm64):

- APK installed and launched; camera permission + system camera capture worked.
- Captured **3072×4032 JPEG (2.4 MB)**; preview rendered and PHP received the path.
- The confirmation step was not obvious during the first physical test, so the camera card now includes “tap the ✓ checkmark to confirm” and a clearer cancel message. Cancel flow verified on the phone.
- Evidence: [evidence/2026-10-27-zte-camera-confirmation-hint.png](evidence/2026-10-27-zte-camera-confirmation-hint.png), [evidence/2026-10-27-zte-camera-cancel-message.png](evidence/2026-10-27-zte-camera-cancel-message.png). The captured personal photo is not committed.

Notes:

- This is the **system camera intent**, not an in-app camera preview. The confirmation UI belongs to the OS camera app; a custom in-app camera is deferred.
- The plugin declares `CAMERA` (and video-related) permissions; Android prompts at runtime.
- Photos are not uploaded anywhere yet. T4 must add explicit consent before any image leaves the device.
- Emulator camera must be configured (`hw.camera.back=virtualscene` or a host webcam); a device with no camera reports capture failure.

## Vision request (T4)

After a photo is captured, the app shows a preview and an explicit consent action:

- **Send photo to DeepSeek** uploads the photo and prompt for analysis.
- **Discard photo** deletes the local file.
- Capturing or cancelling the camera never uploads anything.
- If the provider fails, the photo and preview stay on screen and a safe error is shown.

The provider key is read from Settings; the image is sent as a base64 `image_url` data URL through Symfony AI to `deepseek-flash`. Accepted formats: JPEG, PNG, GIF, WebP; max 32 MB.

Verified 2026-10-27:

- Emulator + local fake provider: the fake server detected the `image_url` part and returned a test response. Evidence: [evidence/2026-10-27-emulator-vision-fake.png](evidence/2026-10-27-emulator-vision-fake.png).
- Physical **ZTE 8050**: real `deepseek-flash` image response received and rendered.
- Failure path: simulated 402 preserved the photo/preview and showed a safe balance error.
- `php artisan test`: 33 passed, 186 assertions.

The symptoms field is included in the prompt and stored on the session; multi-turn follow-up is still pending (T6).

## Repair sessions (T5)

- The app stores repair sessions and observations in SQLite at `app_storage/persisted_data/database/database.sqlite` on device.
- Capturing a photo creates a session; symptoms, the vision prompt and the result are stored.
- On launch, Home restores the latest session (photo preview, symptoms and result).
- **Discard photo** deletes the session and the local photo.
- NativePHP does not run migrations on device, so `AppServiceProvider` creates the session schema once on first boot with `migrate --force`. Host/CI runs use the normal explicit `php artisan migrate`.

Verified 2026-10-27 on the `Pixel_9a` emulator: captured a photo, entered symptoms, ran a fake-provider vision request, force-stopped and relaunched the app; the session and result were restored. Evidence: [evidence/2026-10-27-emulator-session-restored.png](evidence/2026-10-27-emulator-session-restored.png).

After the vision result, **Continue the diagnosis** sends the stored photo prompt, prior assistant result and new answer as a text conversation (the image is not re-uploaded). The answer and response are stored as observations and restored after relaunch. Evidence: [evidence/2026-10-27-emulator-followup-restored.png](evidence/2026-10-27-emulator-followup-restored.png).

## Safety filter (T7)

`App\Services\Safety\SafetyFilter` scans each model-output sentence before display:

- Blocks mains work, live wiring, gas work, opening appliance cases, bypassing interlocks, hazardous probing and microwaves.
- Ignores a sentence that is explicitly negated (`do not`, `never`, `avoid`).
- Escalates any mains/gas mention with a qualified-professional warning.
- Replaces blocked output with a safe referral message and never stores the raw hazardous text.
- Marks the session `escalated`.

Covered by unit and UI tests; `php artisan test` includes dangerous-output cases. This is a deterministic backstop, not a substitute for the prompt or professional judgement.

## Screens

- `/` — Home landing: start a new diagnosis, resume the latest session, open History or Settings.
- `/diagnosis` — fresh diagnosis; `/diagnosis/{session}` resumes a saved one.
- `/result/{session}` — saved photo, symptoms, summary, follow-up and safety notice.
- `/history` — up to 30 saved sessions, newest first.
- `/settings` — provider key and connection test.

Verified on the `Pixel_9a` emulator: Home → History → Result navigation, latest-session resume and follow-up display. Evidence: [evidence/2026-10-27-emulator-home-screens.png](evidence/2026-10-27-emulator-home-screens.png), [history](evidence/2026-10-27-emulator-history.png), [result](evidence/2026-10-27-emulator-result-screen.png).








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
