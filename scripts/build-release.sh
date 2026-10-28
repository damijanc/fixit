#!/usr/bin/env bash
#
# Build a signed release APK/AAB locally.
#
# Required environment variables (never commit these):
#   ANDROID_KEYSTORE_FILE       absolute path to the .jks/.keystore
#   ANDROID_KEYSTORE_PASSWORD
#   ANDROID_KEY_ALIAS
#   ANDROID_KEY_PASSWORD
#
# Optional:
#   ANDROID_BUILD_TYPE=release|bundle   (default: release)
#   OUTPUT_DIR=build/android            (default: build/android)
#
set -euo pipefail

cd "$(dirname "$0")/.."

for variable in ANDROID_KEYSTORE_FILE ANDROID_KEYSTORE_PASSWORD ANDROID_KEY_ALIAS ANDROID_KEY_PASSWORD; do
    if [[ -z "${!variable:-}" ]]; then
        echo "Missing $variable. See docs/RELEASE.md." >&2
        exit 1
    fi
done

if [[ ! -f "$ANDROID_KEYSTORE_FILE" ]]; then
    echo "Keystore not found: $ANDROID_KEYSTORE_FILE" >&2
    exit 1
fi

android_path="${ANDROID_HOME:-${ANDROID_SDK_ROOT:-}}"
if [[ -z "$android_path" ]]; then
    echo 'Set ANDROID_HOME or ANDROID_SDK_ROOT.' >&2
    exit 1
fi

command -v java >/dev/null || { echo 'JDK 17 is required on PATH.' >&2; exit 1; }

build_type="${ANDROID_BUILD_TYPE:-release}"
output_dir="${OUTPUT_DIR:-build/android}"

php artisan native:package android \
    --build-type="$build_type" \
    --keystore="$ANDROID_KEYSTORE_FILE" \
    --keystore-password="$ANDROID_KEYSTORE_PASSWORD" \
    --key-alias="$ANDROID_KEY_ALIAS" \
    --key-password="$ANDROID_KEY_PASSWORD" \
    --output="$output_dir" \
    --no-tty \
    --no-interaction

echo "Artifact written to $output_dir"
