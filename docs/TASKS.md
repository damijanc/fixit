# Task board

Use PLANNED, IN_PROGRESS, IMPLEMENTED, VERIFIED or BLOCKED. Only VERIFIED means tested.

| ID | Priority | Status | Task / acceptance |
|---|---|---|---|
| T1 | P0 | VERIFIED | Bootstrap from clean checkout; document exact working commands and versions — verified 2026-10-26 (host tests + emulator build) |
| T2 | P0 | BLOCKED | Build APK and launch on a physical Android device — emulator build/launch verified; blocked on physical device |
| T3 | P0 | PLANNED | Call a model through Symfony AI from Android embedded PHP |
| T4 | P0 | PLANNED | Capture photo and send to vision model with consent |
| T5 | P1 | PLANNED | SQLite repair sessions survive restart |
| T6 | P1 | PLANNED | Multi-turn diagnostic questions use prior observations |
| T7 | P1 | PLANNED | Deterministic safety filtering and escalation |
| T8 | P1 | PLANNED | Secure user API key settings; no embedded secrets |
| T9 | P1 | PLANNED | Home, diagnosis, result and history screens |
| T10 | P1 | IN_PROGRESS | Automated tests and CI Android build — host PHP test workflow added; Android CI build pending |
| T11 | P1 | PLANNED | Signed release APK, judge setup guide, demo video |
| T12 | P2 | PLANNED | Before/after photo comparison |

## Rules
Do P0 compatibility work before feature development. Each task must be backed by a PR, acceptance evidence and STATUS handoff. No additional architecture without a concrete need.
