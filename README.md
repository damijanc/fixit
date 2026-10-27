# FixIt

Android-only AI-guided repair assistant. NativePHP + Laravel, standalone Symfony AI packages, local SQLite and a user-supplied model key.

> **Status:** the app runs on an Android emulator and a physical **ZTE 8050**, completes real DeepSeek `deepseek-flash` text and image calls from embedded PHP, captures photos, stores them locally until explicit consent, persists repair sessions and follow-up turns in SQLite across restarts, filters hazardous model output deterministically, and keeps the provider key in on-device encrypted storage. The full diagnosis/history screens and release packaging are still pending. See [docs/STATUS.md](docs/STATUS.md) and [docs/TASKS.md](docs/TASKS.md).

## Quick start

Prerequisites: PHP 8.3+, Composer 2. For Android builds: a JDK and Android SDK (see [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)).

```bash
git clone git@github.com:damijanc/fixit.git
cd fixit
bash scripts/bootstrap.sh
php artisan test
```

Then:

```bash
php artisan native:run android   # debug build + install on a connected device
```

## Documentation

- [Product MVP](docs/mvp.md)
- [Architecture](docs/architecture.md)
- [Developer setup](docs/DEVELOPMENT.md)
- [Status and handoff log](docs/STATUS.md)
- [Task board](docs/TASKS.md)
- [Architecture decisions](docs/decisions/)

## Safety

FixIt only suggests low-risk, non-invasive troubleshooting. It must never instruct users to open mains-powered appliances, service gas equipment, bypass interlocks or run hazardous tests. AI output is filtered by deterministic safety rules. Never commit API keys or signing credentials.
