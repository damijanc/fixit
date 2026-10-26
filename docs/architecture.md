# Architecture (planned)

```text
Android / NativePHP (embedded Laravel + PHP)
  ├── Camera/photo picker → temporary local image
  ├── UI → diagnosis controller / application service
  ├── SQLite → sessions, observations, repair summaries
  └── Symfony AI Agent + Platform (standalone Composer packages)
        └── HTTPS → user-selected vision-capable model
```

No separate Symfony server. Running Symfony AI within NativePHP's embedded Android PHP runtime remains an **unverified compatibility assumption**.

## Responsibilities
- NativePHP: Android integration and UI
- Laravel: validation, persistence and application services
- Symfony AI: model requests and structured outputs
- Deterministic validation: reject unsafe suggested actions before display

Photos must be uploaded only on user action. API credentials must never be hardcoded or bundled; investigate Android secure storage.

## Safety
Support low-risk, non-invasive troubleshooting. No instructions for opening mains-powered appliances, servicing gas equipment, defeating interlocks, or hazardous tests. Escalate to qualified professionals when appropriate. AI confidence is not a calibrated probability.

## Technical spikes
- Embedded PHP extensions and Symfony AI HTTP compatibility
- Camera image handling on physical Android
- Secure local provider key storage
- Signed APK build in GitHub Actions
