# Architecture (planned)

```text
Android / NativePHP (embedded Laravel + PHP)
  ├── Camera/photo picker → temporary local image
  ├── UI → diagnosis controller / application service
  ├── SQLite → sessions, observations, repair summaries
  └── Symfony AI Agent + Platform (standalone Composer packages)
        └── HTTPS → user-selected vision-capable model
```

No separate Symfony server. Running Symfony AI within NativePHP's embedded Android PHP runtime is **verified**: on 2026-10-26 against a local fake provider on the emulator, and on 2026-10-27 with a **real DeepSeek `deepseek-flash` call from a physical ZTE 8050**. The integration uses the standalone `symfony/ai-agent`, `symfony/ai-platform` and `symfony/ai-generic-platform` packages with an injectable platform for mock-based tests.

## Responsibilities
- NativePHP: Android integration and UI
- Laravel: validation, persistence and application services
- Symfony AI: model requests and structured outputs
- Deterministic validation: reject unsafe suggested actions before display

Photos must be uploaded only on user action. API credentials must never be hardcoded or bundled; investigate Android secure storage.

## Safety
Support low-risk, non-invasive troubleshooting. No instructions for opening mains-powered appliances, servicing gas equipment, defeating interlocks, or hazardous tests. Escalate to qualified professionals when appropriate. AI confidence is not a calibrated probability.

## Technical spikes
- Embedded PHP extensions and Symfony AI HTTP compatibility — **verified on emulator with a fake provider and on physical ZTE with a real DeepSeek call** (2026-10-27)
- Camera image handling — **verified on emulator and physical ZTE 8050**: system camera intent, app-private JPEG, path returned to PHP (2026-10-27)
- Secure local provider key storage — **verified on emulator** (ADR 0003): encrypted at rest with the NativePHP Keystore-managed `APP_KEY`, entered in app Settings
- Signed APK build in GitHub Actions — pending
