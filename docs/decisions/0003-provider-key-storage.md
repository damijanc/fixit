# ADR 0003 — Provider key storage without a proprietary plugin

Status: Accepted. Date: 2026-10-26.

## Context

T8 requires the user to enter their provider key in the app, never in `.env` or the APK.

NativePHP exposes a `SecureStorage` PHP facade, but the native Android implementation ships as the proprietary `nativephp/mobile-secure-storage` plugin. Installing it from `plugins.nativephp.com` returned HTTP 401 without authenticated plugin access, so it cannot be a required dependency for this build.

NativePHP itself keeps `APP_KEY` in the Android Keystore / iOS Keychain on device and strips it from the bundled `.env` (verified: the installed bundle has no `APP_KEY`).

## Decision

Store the provider key as Laravel `Crypt` ciphertext in app-private storage:

- `EncryptedFileApiKeyStore` encrypts with `APP_KEY` and writes only ciphertext to `storage/app/private/ai-key.enc` (mode `0600`).
- `APP_KEY` is supplied at runtime by NativePHP from the Android Keystore.
- `ApiKeyStore` is an interface; tests use an in-memory implementation, and production binds the encrypted file store.
- No key is read from `.env`, config, logs, or bundled assets.

## Consequences

- Meets the requirement: the key is entered in Settings and is not recoverable from the APK or a copied storage file without the Keystore-managed `APP_KEY`.
- Avoids a paid/authenticated NativePHP plugin and works on the current emulator build.
- If the `APP_KEY` changes, the saved key cannot be decrypted; the user must re-enter it. This is acceptable and safer than silently falling back.
- If the official plugin becomes available, `EncryptedFileApiKeyStore` can be swapped behind `ApiKeyStore` without changing the UI or gateway.
- The ciphertext file is app-private. Whether NativePHP's persisted-data directory is excluded from Android backups has not been verified.
