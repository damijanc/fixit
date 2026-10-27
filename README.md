# FixIt

Android-only AI-guided repair assistant. NativePHP + Laravel, standalone Symfony AI packages, local SQLite and a user-supplied model key.

> **Status:** the app runs on an Android emulator and a physical **ZTE 8050**, completes a **real DeepSeek `deepseek-flash` call** from embedded PHP, captures photos through the native camera into app-private storage, and stores a user-entered provider key securely from Settings. It has not yet sent a photo to a vision model or produced a signed release APK. See [docs/STATUS.md](docs/STATUS.md) and [docs/TASKS.md](docs/TASKS.md).

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
