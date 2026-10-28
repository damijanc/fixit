# Task board

Use PLANNED, IN_PROGRESS, IMPLEMENTED, VERIFIED or BLOCKED. Only VERIFIED means tested.

| ID | Priority | Status | Task / acceptance |
|---|---|---|---|
| T1 | P0 | VERIFIED | Bootstrap from clean checkout; document exact working commands and versions — verified 2026-10-26 (host tests + emulator build) |
| T2 | P0 | VERIFIED | Build APK and launch on Android — emulator + physical ZTE 8050 (Android 13) verified 2026-10-27 |
| T3 | P0 | VERIFIED | Call a model through Symfony AI from Android embedded PHP — real DeepSeek `deepseek-flash` call VERIFIED on physical ZTE 8050 (2026-10-27) |
| T4 | P0 | VERIFIED | Capture photo and send to vision model with consent — camera + consent + real DeepSeek `deepseek-flash` image response VERIFIED on emulator and physical ZTE (2026-10-27) |
| T5 | P1 | VERIFIED | SQLite repair sessions survive restart — session + observations persisted; force-stop/relaunch restore VERIFIED on emulator 2026-10-27 |
| T6 | P1 | VERIFIED | Multi-turn diagnostic questions use prior observations — follow-up conversation + persistence/restore VERIFIED on emulator 2026-10-27 |
| T7 | P1 | VERIFIED | Deterministic safety filtering and escalation — hazardous mains/gas/interlock instructions blocked before display; tests cover dangerous outputs (2026-10-27) |
| T8 | P1 | VERIFIED | Secure user API key settings; no embedded secrets — app Settings entry, encrypted storage, restart persistence and APK-absence VERIFIED on emulator (ADR 0003) |
| T9 | P1 | VERIFIED | Home, diagnosis, result and history screens — four screens + navigation VERIFIED on emulator 2026-10-27 |
| T10 | P1 | VERIFIED | Automated tests and CI Android build — PHP tests + signed Android release workflow VERIFIED (run 40487118046, 2026-10-27) |
| T11 | P1 | IN_PROGRESS | Signed release APK, judge setup guide, demo video — stable signing + tag GitHub Release publishing VERIFIED (run 40659466606); physical install + demo video pending |
| T12 | P2 | PLANNED | Before/after photo comparison |

## Rules
Do P0 compatibility work before feature development. Each task must be backed by a PR, acceptance evidence and STATUS handoff. No additional architecture without a concrete need.
