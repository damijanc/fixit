# Camera and vision

- Status: IN_PROGRESS
- Tasks: T4
- Last reviewed: 2026-10-27

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

## Evidence (2026-10-27)
- Physical **ZTE 8050** (Android 13 / API 33): captured a 3072×4032 JPEG (2.4 MB), preview rendered, path returned to PHP.
- Physical-test feedback: the system camera’s confirmation step was not obvious. Added a “tap ✓ to confirm” hint and a clearer no-photo/cancel message (PR #9); cancel flow verified on the phone.
- Evidence: `docs/evidence/2026-10-27-zte-camera-confirmation-hint.png`, `docs/evidence/2026-10-27-zte-camera-cancel-message.png`.
- Remaining: consent UI + vision upload/result, and failure-state handling.

## Evidence (2026-10-27, vision)
- Explicit consent added: photo stays local until **Send photo to DeepSeek**; discard deletes it; cancel does not upload.
- Emulator + local fake provider: base64 image part received (`image_url`), result rendered. Evidence: `docs/evidence/2026-10-27-emulator-vision-fake.png`.
- Physical **ZTE 8050**: real `deepseek-flash` image response received and rendered.
- Failure case: a simulated 402 keeps the photo and preview while showing a safe error.
- Tests: 33 passed, 186 assertions.
- Remaining for this spec: a symptoms field included in the prompt, plus multi-turn follow-up (T5/T6).
