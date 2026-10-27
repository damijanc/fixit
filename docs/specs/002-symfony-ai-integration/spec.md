# Symfony AI integration

- Status: VERIFIED
- Tasks: T3, T8
- Last reviewed: 2026-10-27

## Requirements
Use standalone Symfony AI components inside embedded Android PHP; support user-supplied provider keys and recoverable failures.

## Acceptance criteria
Real-device model call succeeds; errors are handled; no secrets shipped.

## Handoff
Follow AGENTS.md and update docs/STATUS.md and docs/TASKS.md with verified evidence. Android compatibility is unverified until tested.

## Evidence (2026-10-26)

- Symfony AI `0.15.0` (agent, platform, generic bridge) runs in NativePHP's embedded PHP 8.5.12 on a `Pixel_9a` emulator and reaches a local OpenAI-compatible fake provider. Text round-trip returns `FixIt AI connectivity OK`.
- Provider key entry and encrypted at-rest storage verified on the emulator from app Settings; key absent from `.env` and the APK (ADR 0003).
- DeepSeek is the configured default (`https://api.deepseek.com`, `deepseek-flash`).
- Remaining: real-device call (T2 deferred) and a real DeepSeek call with a user key.

## Evidence (2026-10-27)
- Real DeepSeek call succeeded from the physical **ZTE 8050** (Android 13 / API 33): `deepseek-flash` returned `FixIt AI connectivity OK` through the app’s Settings test.
- A pasted key missing the `sk-` prefix produced a 401; the app now rejects missing-prefix keys before saving and the 401 message names the prefix.
- Provider failures now surface an HTTP-status-specific, safe message and log class/message/code without the key or response body.
- Evidence: `docs/evidence/2026-10-27-zte-deepseek-connected.png`.
