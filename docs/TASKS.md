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
| T19 | P0 | VERIFIED | Anthropic on current Claude models: Opus 5.5 (default) / Sonnet 5.5 / Haiku 5.5; `max_tokens` 16000 + explicit effort per model; read thinking (multi-part) replies; refusal and empty-reply messages; host tests on the real bridge; Test connection, photo diagnosis and follow-up passed on the ZTE 8050 with a real key |
| T22 | P0 | VERIFIED | Safety filter false positives: phone-charging advice (protective case, other socket, battery-charging setting, "remove the case") passes; casing/mains/protection-bypass instructions stay blocked; block reasons logged (matched words in debug builds); blocked ZTE photo now answered |
| T21 | P0 | VERIFIED | Show progress while an AI call runs: photo, follow-up and Settings Test run as NativePHP async tasks; spinners, progress text, pending answer bubble and locked controls render immediately; provider error messages kept; emulator screenshots + ZTE hand check with Claude |
| T20 | P1 | VERIFIED | Claude showcase: structured JSON diagnosis via `output_config.format` rendered as a card and checked field by field by the safety rules (mains/gas/battery hazards escalate); token usage and cache writes/reads logged; card and cache write seen on the ZTE with Opus 5.5 (cache read not yet observed) |
| T18 | P0 | VERIFIED | Symptoms and follow-up fields drop characters: bind without `native:model` so typed text is never echoed back; live change events keep Send current; fields clear by remounting; emulator fake-provider check sends exact text; host tests added; hand typing on the ZTE 8050 arrived complete (PR #25) |
| T17 | P0 | VERIFIED | Hugging Face photo requests returned 503: route HF to `deepinfra` (router default novita rejects full-size phone photos); show photo errors next to the Send button; host tests added; real photo diagnosis passed on the ZTE 8050. Photos are still uploaded full-size (no GD in Android PHP) |
| T15 | P1 | IMPLEMENTED | Symfony AI integration clean-up for code review: map Symfony AI typed exceptions to correct 401/404/429/5xx messages; `AgentFactory` + `SystemPromptInputProcessor`; no test-only constructor arguments in `ModelGateway`; prompts and follow-up history out of the Diagnosis component; host tests pass; real-key device smoke pending |
| T14 | P2 | IMPLEMENTED | UI polish, no behaviour change: camera-first Home, diagnosis as a full conversation thread with pinned composer, readable statuses, markdown stripped from AI text, list-style History/Result, Settings key banner and button hierarchy; host tests pass; emulator screens checked; physical-device pass pending |
| T16 | P0 | VERIFIED | API key field drops characters: debounce sync on the key field so live-sync echoes cannot overwrite typed or pasted text; emulator fake-provider check sends the exact key; host test added; real Hugging Face Test connection passed on the ZTE 8050 (PR #23); symptoms/follow-up fields still use live sync |
| T13 | P0 | IMPLEMENTED | Multi-provider AI: DeepSeek, OpenRouter, Hugging Face (P0), Anthropic/OpenAI (P1); one encrypted provider/model/key selection in Settings; Symfony AI bridges; host tests pass; physical-device smoke tests pending (PR #18, merged) |

## Rules
Do P0 compatibility work before feature development. Each task must be backed by a PR, acceptance evidence and STATUS handoff. No additional architecture without a concrete need.
