# FixIt

Android-only AI-guided repair assistant. NativePHP + Laravel, standalone Symfony AI packages, local SQLite and a user-supplied model key.

**▶ [Watch the 90-second demo](https://www.youtube.com/watch?v=zixGJWrnKGc)** · **[Download v1.0.0 (APK)](https://github.com/damijanc/fixit/releases/tag/v1.0.0)** · **[Judge guide](docs/JUDGE_GUIDE.md)**

> **Status:** v1.0.0 is released and verified on a physical **ZTE 8050**. Photograph a broken item, describe the problem, and Claude Opus 5.5 (default) returns a structured diagnosis card that FixIt's deterministic safety rules check line by line. Symfony AI runs inside the phone's embedded PHP, so DeepSeek, OpenRouter, Hugging Face and OpenAI work through the same flow. Photos are shrunk on the device and uploaded only after explicit consent; the provider key is encrypted on the device and sessions stay in local SQLite. Details and evidence: [docs/STATUS.md](docs/STATUS.md) and [docs/TASKS.md](docs/TASKS.md).

## Quick start

Prerequisites: PHP 8.3+, Composer 2. For Android builds: a JDK and Android SDK (see [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)).

```bash
git clone https://github.com/damijanc/fixit.git
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
- [Multi-provider AI spec](docs/specs/006-multi-provider-ai/spec.md)
- [Developer setup](docs/DEVELOPMENT.md)
- [Judge guide](docs/JUDGE_GUIDE.md)
- [Release build](docs/RELEASE.md)
- [Demo video guide](docs/DEMO.md)
- [Status and handoff log](docs/STATUS.md)
- [Task board](docs/TASKS.md)
- [Architecture decisions](docs/decisions/)

## Safety

FixIt only suggests low-risk, non-invasive troubleshooting. It must never instruct users to open mains-powered appliances, service gas equipment, bypass interlocks or run hazardous tests. AI output is filtered by deterministic safety rules. Never commit API keys or signing credentials.
