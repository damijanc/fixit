# Developer setup

**Provisional:** current repository is a bootstrap overlay, not a complete NativePHP/Laravel app. These commands have not yet been tested.

## Prerequisites
PHP, Composer, Laravel installer, Node.js/npm, Android Studio/SDK, compatible JDK and an Android device with debugging enabled. Check current NativePHP documentation for exact supported versions.

## Checkout
```bash
git clone git@github.com:damijanc/fixit.git
cd fixit
git checkout setup/project-skeleton
bash scripts/bootstrap.sh
cd fixit
php artisan native:install android
php artisan native:run android
```

The bootstrap script attempts to create a nested `fixit/` NativePHP starter, install Symfony AI packages and copy starter files. Verify and fix commands in T1. The long-term preferred repository layout is a conventional Laravel project at root; decide after T1 and record the decision.

## Verification checklist
- [ ] Clean bootstrap succeeds; tool versions recorded
- [ ] APK builds and installs on a real Android device
- [ ] Symfony AI model call succeeds from Android
- [ ] Camera/photo vision request succeeds
- [ ] Keys stored securely and never bundled
- [ ] Automated tests and signed release workflow

## Agent handoff
Assign an agent a task ID. Require reading AGENTS.md, STATUS.md, TASKS.md, and relevant architecture docs. Require a PR, tests, status updates and an exact next step.

First assignment: **T1 only**. Run bootstrap on a clean machine, fix installation issues, record actual versions/commands, update docs, and stop before diagnosis features.
