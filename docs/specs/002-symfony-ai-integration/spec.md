# Symfony AI integration

- Status: IN_PROGRESS
- Tasks: T3, T8
- Last reviewed: 2026-10-26

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
