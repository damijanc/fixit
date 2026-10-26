# ADR 0002 — Laravel project at repository root

Status: Accepted. Date: 2026-10-26.

## Context

The initial skeleton was an overlay repository with a `scripts/bootstrap.sh` that created a nested `fixit/` Laravel project from the NativePHP starter and copied files into it. That layout could not be run or tested directly from a checkout, and it left the project's build files outside version control until bootstrap generated them.

T1 required a clean checkout to produce a conventional NativePHP/Laravel project with recorded commands and versions.

## Decision

Adopt the NativePHP Mobile starter (`nativephp/mobile-starter`, Laravel 13) as the repository root:

- `composer.json`, `composer.lock`, `artisan`, `app/`, `config/`, `routes/`, `resources/`, `database/`, `tests/` live at the root.
- NativePHP's generated Android project and embedded PHP runtime live in `nativephp/` and stay gitignored; `nativephp.lock` pins the embedded PHP version and is committed.
- `scripts/bootstrap.sh` prepares a checkout in place instead of creating a nested project.
- `App\Services\Diagnosis\DiagnosisState` moved from `stubs/` to `app/` so it is autoloadable and testable.

## Consequences

- A clean checkout is immediately a valid Laravel project; bootstrap only installs dependencies and generates local artifacts.
- Standard Laravel tooling (Pest, Pint, artisan) works without extra paths.
- CI, Android packaging and IDE tooling can target the repository root.
- The upstream starter's branding/demo screen remains until the FixIt screens replace it (T9).
- Upstream starter updates are no longer pullable as a whole; dependency updates happen through Composer.
