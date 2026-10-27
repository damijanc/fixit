# Android release build

## CI

`.github/workflows/android.yml` builds a signed release APK on every push/PR and on demand.

- If these repository secrets exist, the CI build uses them:
  - `ANDROID_KEYSTORE_BASE64` — base64 of the `.jks` file
  - `ANDROID_KEYSTORE_PASSWORD`
  - `ANDROID_KEY_ALIAS`
  - `ANDROID_KEY_PASSWORD`
- If they are absent, CI generates a throwaway keystore so the build still verifies. That APK is signed but its identity changes every run; use the real secrets for a distributable release.
- The signed APK is uploaded as the `fixit-android-release-apk` artifact.
- The workflow verifies the APK with `apksigner` before upload.

Create the secret value without committing the keystore:

```bash
base64 < fixit-release.jks | pbcopy   # macOS: paste into the GitHub secret
```

Never commit the keystore or passwords.

## Create a release keystore (once)

```bash
keytool -genkeypair -v \
  -keystore fixit-release.jks \
  -alias fixit \
  -keyalg RSA \
  -keysize 2048 \
  -validity 3650
```

Store the `.jks` and passwords outside the repository (password manager/CI secrets). Losing the keystore prevents updates signed as the same app.

## Build locally

```bash
export ANDROID_HOME="$HOME/Library/Android/sdk"
export JAVA_HOME="/opt/homebrew/opt/openjdk@17/libexec/openjdk.jdk/Contents/Home"
export PATH="$JAVA_HOME/bin:$ANDROID_HOME/platform-tools:$PATH"

export ANDROID_KEYSTORE_FILE="$HOME/secure/fixit-release.jks"
export ANDROID_KEYSTORE_PASSWORD='…'
export ANDROID_KEY_ALIAS='fixit'
export ANDROID_KEY_PASSWORD='…'

bash scripts/build-release.sh
# -> build/android/app-release.apk
```

For an AAB:

```bash
ANDROID_BUILD_TYPE=bundle OUTPUT_DIR=build/android bash scripts/build-release.sh
```

Verify:

```bash
"$ANDROID_HOME/build-tools/36.0.0/apksigner" verify --print-certs build/android/app-release.apk
```

## Verified

- GitHub Actions run `40487118046` (2026-10-27): Ubuntu, PHP 8.5, JDK 17, Android SDK 36 — signed release APK built, `apksigner`-verified and uploaded as `fixit-android-release-apk` (~8.5 min).
- A local signed release APK built on macOS with JDK 17 / SDK 36 and installed/launched on the `Pixel_9a` emulator (2026-10-27).
- Physical-device install of the release build is still pending.

## Versioning

Set `NATIVEPHP_APP_VERSION` and `NATIVEPHP_APP_VERSION_CODE` in `.env` before the build. Version code must increase for store updates. Do not put provider keys or signing passwords in `.env`; NativePHP bundles `.env` into the app.
