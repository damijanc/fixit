#!/usr/bin/env bash
#
# Prepare a clean checkout for local development and Android builds.
# Safe to re-run: existing .env, database and downloaded native resources are kept.
#
set -euo pipefail

cd "$(dirname "$0")/.."

command -v php >/dev/null || { echo 'PHP 8.3+ is required.' >&2; exit 1; }
command -v composer >/dev/null || { echo 'Composer 2 is required.' >&2; exit 1; }

php -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' || {
    echo "PHP 8.3+ is required; found $(php -r 'echo PHP_VERSION;')." >&2
    exit 1
}

echo '==> Installing Composer dependencies'
composer install --no-interaction --prefer-dist

if [[ ! -f .env ]]; then
    echo '==> Creating .env from .env.example'
    cp .env.example .env
fi

if ! grep -Eq '^APP_KEY=.+' .env; then
    echo '==> Generating application key'
    php artisan key:generate --ansi
fi

if [[ ! -f database/database.sqlite ]]; then
    echo '==> Creating SQLite database'
    touch database/database.sqlite
fi

echo '==> Running migrations'
php artisan migrate --force

echo '==> Installing NativePHP Android resources (PHP runtime is cached under nativephp/)'
php artisan native:install android --no-interaction

echo '==> Running tests'
php artisan test

cat <<'EOF'

Bootstrap complete.

Next commands (Android build prerequisites are listed in docs/DEVELOPMENT.md):
  php artisan native:run android        # build debug APK and install on a connected device
  php artisan native:package android    # signed release APK/AAB
EOF
