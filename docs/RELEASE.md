# Android release build

## CI release (tags only)

`.github/workflows/android.yml` runs **only when a `v*` tag is pushed**. It:

- builds the signed release APK;
- verifies it with `apksigner`;
- creates or updates the GitHub Release for that tag and attaches:
  - `app-release.apk`
  - `app-release.apk.sha256`

Secrets required on tag builds:

- `ANDROID_KEYSTORE_BASE64` — base64 of the `.jks` file
- `ANDROID_KEYSTORE_PASSWORD`
- `ANDROID_KEY_ALIAS`
- `ANDROID_KEY_PASSWORD`

A tag build **fails** if the keystore secret is missing, so a release is never signed with an accidental throwaway key.

Create a release:

```bash
git checkout main
git pull
git tag v1.0.0
git push origin v1.0.0
```

The APK appears under **Releases** on GitHub, not just as a workflow artifact.

Create the secret value without committing the keystore:

```bash
base64 -i fixit-release.jks | tr -d '\n' | pbcopy   # macOS: paste into the GitHub secret
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
- GitHub Actions run `40652416378` (2026-10-28): stable repository signing secrets used; certificate `CN=FixIt Release, OU=Mobile, O=FixIt, L=Berlin, ST=Berlin, C=DE`. One retry was needed for a transient NativePHP PHP-binary download.
- A local signed release APK built on macOS with JDK 17 / SDK 36 and installed/launched on the `Pixel_9a` emulator (2026-10-27).
- Physical-device install of the release build is still pending.

## CI warnings

- Node 20 deprecations were removed by bumping `actions/checkout@v5`, `actions/setup-java@v5` and `actions/upload-artifact@v5`.
- `sdkmanager` is deprecated in favour of Android CLI (`android sdk install ...`). The workflow still uses `sdkmanager`; migrating requires installing the separate Android CLI binary and is tracked as follow-up work.

## Versioning

Set `NATIVEPHP_APP_VERSION` and `NATIVEPHP_APP_VERSION_CODE` in `.env` before the build. Version code must increase for store updates. Do not put provider keys or signing passwords in `.env`; NativePHP bundles `.env` into the app.
