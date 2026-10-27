# Architecture (planned)

```text
Android / NativePHP (embedded Laravel + PHP)
  ├── Camera/photo picker → temporary local image
  ├── UI → diagnosis controller / application service
  ├── SQLite → sessions, observations, repair summaries
  └── Symfony AI Agent + Platform (standalone Composer packages)
        └── HTTPS → user-selected vision-capable model
```

No separate Symfony server. Running Symfony AI within NativePHP's embedded Android PHP runtime is **verified**: on 2026-10-26 against a local fake provider on the emulator, and on 2026-10-27 with **real DeepSeek `deepseek-flash` text and image calls from a physical ZTE 8050**. The integration uses the standalone `symfony/ai-agent`, `symfony/ai-platform` and `symfony/ai-generic-platform` packages with an injectable platform for mock-based tests.

## Responsibilities
- NativePHP: Android integration and UI
- Laravel: validation, persistence and application services
- Symfony AI: model requests and structured outputs
- Deterministic validation: reject unsafe suggested actions before display

Photos must be uploaded only on user action. API credentials must never be hardcoded or bundled; investigate Android secure storage.

## Safety
Support low-risk, non-invasive troubleshooting. No instructions for opening mains-powered appliances, servicing gas equipment, defeating interlocks, or hazardous tests. Escalate to qualified professionals when appropriate. AI confidence is not a calibrated probability.

A deterministic `SafetyFilter` scans model output before display, blocks hazardous instructions, escalates mains/gas mentions, and never stores blocked raw text. Prompt instructions plus the deterministic filter are both required.

## Technical spikes
- Embedded PHP extensions and Symfony AI HTTP compatibility — **verified on emulator with a fake provider and on physical ZTE with real DeepSeek text and image calls** (2026-10-27)
- Camera image handling — **verified on emulator and physical ZTE 8050**: system camera intent, app-private JPEG, path returned to PHP (2026-10-27)
- Vision upload — **verified with explicit consent** on emulator (fake provider) and physical ZTE (real `deepseek-flash` image response); base64 `image_url` via Symfony AI (2026-10-27)
- SQLite repair sessions — **verified on emulator**: session/observations persist and restore after force-stop/relaunch (2026-10-27)
- Multi-turn follow-up — **verified on emulator**: stored photo prompt + prior result + answer sent as a conversation and restored after relaunch (2026-10-27)
- Deterministic safety filter — **verified by tests**: hazardous mains/gas/interlock instructions blocked before display; mains/gas mentions escalated (2026-10-27)
- Home/Diagnosis/Result/History screens — **verified on emulator**: navigation, latest-session resume and saved result/history work (2026-10-27)
- Secure local provider key storage — **verified on emulator** (ADR 0003): encrypted at rest with the NativePHP Keystore-managed `APP_KEY`, entered in app Settings
- Signed APK build — **local signed release APK verified on emulator**; GitHub Actions workflow added, first run pending (2026-10-27)
