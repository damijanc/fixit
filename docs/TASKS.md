# Task board

Use PLANNED, IN_PROGRESS, IMPLEMENTED, VERIFIED or BLOCKED. Only VERIFIED means tested.

| ID | Priority | Status | Task / acceptance |
|---|---|---|---|
| T1 | P0 | VERIFIED | Bootstrap from clean checkout; document exact working commands and versions — verified 2026-10-26 (host tests + emulator build) |
| T2 | P0 | VERIFIED | Build APK and launch on Android — emulator + physical ZTE 8050 (Android 13) verified 2026-10-27 |
| T3 | P0 | IN_PROGRESS | Call a model through Symfony AI from Android embedded PHP — gateway + emulator fake-provider call VERIFIED; real DeepSeek call pending user-entered key |
| T4 | P0 | IN_PROGRESS | Capture photo and send to vision model with consent — camera capture VERIFIED on emulator + physical ZTE; confirmation UX hint added; vision upload/consent pending |
| T5 | P1 | PLANNED | SQLite repair sessions survive restart |
| T6 | P1 | PLANNED | Multi-turn diagnostic questions use prior observations |
| T7 | P1 | PLANNED | Deterministic safety filtering and escalation |
| T8 | P1 | VERIFIED | Secure user API key settings; no embedded secrets — app Settings entry, encrypted storage, restart persistence and APK-absence VERIFIED on emulator (ADR 0003) |
| T9 | P1 | PLANNED | Home, diagnosis, result and history screens — minimal FixIt home started |
| T10 | P1 | IN_PROGRESS | Automated tests and CI Android build — host PHP test workflow added; Android CI build pending |
| T11 | P1 | PLANNED | Signed release APK, judge setup guide, demo video |
| T12 | P2 | PLANNED | Before/after photo comparison |

## Rules
Do P0 compatibility work before feature development. Each task must be backed by a PR, acceptance evidence and STATUS handoff. No additional architecture without a concrete need.
