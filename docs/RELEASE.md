# Android release build

## CI test build without a release

`.github/workflows/android.yml` also runs for:

- a pull request to `main` that changes the workflow file;
- a manual **Run workflow** dispatch.

Those runs build the same signed release APK with the stable keystore, verify it with `apksigner`, write a SHA-256, and upload it as the Actions artifact **`fixit-android-build-<run_number>`**. They do **not** create or modify a GitHub Release.

Download the artifact from the workflow run, unzip it, and install `app-release.apk`. The certificate is the same stable `CN=FixIt Release` identity, so it can update an existing release install.

PR builds require the same signing secrets; secrets are available for branches in this repository, not for fork PRs.

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
git tag -a v1.0.1 -m "FixIt 1.0.1"
git push origin v1.0.1
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
- GitHub Actions run `40659466606` (2026-10-28): tag-triggered release `v0.0.5-ci-test` built and verified the APK, then created a GitHub Release with `app-release.apk` and its SHA-256. Test tag/release removed afterwards.
- A local signed release APK built on macOS with JDK 17 / SDK 36 and installed/launched on the `Pixel_9a` emulator (2026-10-27).
- GitHub Actions run `40913701543` (2026-10-29): tag `v1.0.0` built, signed and verified the APK with the production `.env` and published the **FixIt 1.0.0** GitHub Release (`app-release.apk`, 82,292,739 bytes, SHA-256 `87d97558…5c2b`).
- Physical ZTE 8050 (2026-10-29): the downloaded `v1.0.0` APK matched its SHA-256, installed after uninstalling the debug build (different signing key), reports `versionName=1.0.0` and is not debuggable. A fresh install preselects Anthropic / Claude Opus 5.5; key saved, Test passed, and a laptop photo returned the Claude diagnosis card.

## CI warnings

- Node 20 deprecations were removed by bumping `actions/checkout@v5` and `actions/setup-java@v5`.
- `sdkmanager` was replaced with Android CLI (`android sdk install ...`); the deprecated warning is gone. Only the `ubuntu-latest` runner-image notice remains.
- Android CLI prints a one-time telemetry notice; it is informational, not a build warning.

## Updating a debug install

A debug build (`native:run`) and the release APK are signed with different keys, so Android refuses to update one with the other (`INSTALL_FAILED_UPDATE_INCOMPATIBLE`). Uninstall the debug build first; this deletes the saved key and local sessions.

## Versioning

Set `NATIVEPHP_APP_VERSION` and `NATIVEPHP_APP_VERSION_CODE` in `.env` before the build. Version code must increase for store updates. Do not put provider keys or signing passwords in `.env`; NativePHP bundles `.env` into the app.
