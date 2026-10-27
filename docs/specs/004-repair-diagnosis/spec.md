# Interactive repair diagnosis

- Status: VERIFIED
- Tasks: T5, T6, T7, T9
- Last reviewed: 2026-10-27

## Requirements
SQLite sessions survive restart; one question per turn uses earlier observations; deterministic safety filters and escalation.

## Acceptance criteria
Tests cover session continuity and dangerous outputs; full safe scenario works.

## Handoff
Follow AGENTS.md and update docs/STATUS.md and docs/TASKS.md with verified evidence. Android compatibility is unverified until tested.

## Evidence (2026-10-27)

- Repair sessions and observations persist in SQLite. On the emulator, force-stopping and relaunching the app restored the photo, symptoms and vision result.
- The app creates the session schema on first device boot (`migrate --force`) because NativePHP does not run migrations automatically.
- Symptoms are included in the vision prompt and stored on the session.
- Multi-turn follow-up is implemented: the stored photo prompt, vision result and prior answers are sent as a conversation; the answer and response persist and restore after relaunch (verified on emulator, 2026-10-27).
- Deterministic safety filtering is implemented and unit/UI-tested: hazardous mains/gas/interlock instructions are blocked before display with a professional-referral message; mains/gas mentions escalate; blocked raw text is not stored.
- Home, Diagnosis, Result and History screens are implemented with navigation; verified on the emulator. Evidence: `docs/evidence/2026-10-27-emulator-home-screens.png`, `...-history.png`, `...-result-screen.png`.
- Physical-device pass of the new screens remains desirable; the release build is tracked under spec 005.
