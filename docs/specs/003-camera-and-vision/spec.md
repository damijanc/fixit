# Camera and vision

- Status: IN_PROGRESS
- Tasks: T4
- Last reviewed: 2026-10-26

## Requirements
Capture or select image; preview and request explicit upload consent; send photo and symptoms to vision model.

## Acceptance criteria
Real-device image response works; cancel does not upload; failures preserve state.

## Handoff
Follow AGENTS.md and update docs/STATUS.md and docs/TASKS.md with verified evidence. Android compatibility is unverified until tested.

## Evidence (2026-10-26)

- Native system camera capture verified on the `Pixel_9a` emulator: runtime permission, 1440×1920 JPEG in app-private storage, path returned to PHP, preview rendered.
- Cancel/denial handlers implemented; no upload exists yet.
- Remaining: explicit upload consent UI, base64 image request to DeepSeek `deepseek-flash`, result display, and failure-state handling.
