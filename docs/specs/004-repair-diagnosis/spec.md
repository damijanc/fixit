# Interactive repair diagnosis

- Status: IN_PROGRESS
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
- Remaining: deterministic safety filtering/escalation (T7) and the full diagnosis/result/history screens (T9).
