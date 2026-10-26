#!/usr/bin/env bash
set -euo pipefail
if [[ -e fixit ]]; then echo 'Directory fixit already exists; refusing to overwrite.' >&2; exit 1; fi
command -v laravel >/dev/null || { echo 'Install Laravel installer first: composer global require laravel/installer' >&2; exit 1; }
laravel new fixit --using=nativephp/mobile-starter --no-node --no-boost
cd fixit
composer require symfony/ai-agent symfony/ai-platform
mkdir -p docs app/Services/Diagnosis
cp ../docs/*.md docs/
cp ../stubs/app/Services/Diagnosis/*.php app/Services/Diagnosis/
if ! grep -q '^NATIVEPHP_APP_ID=' .env; then printf '\nNATIVEPHP_APP_ID=com.example.fixit\n' >> .env; fi
printf '\nBootstrap complete. Next: cd fixit && php artisan native:install android\n'
