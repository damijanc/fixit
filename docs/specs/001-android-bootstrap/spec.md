# Android bootstrap

- Spec ID: 001-android-bootstrap
- Status: PLANNED
- Tasks: T1, T2
- Last reviewed: 2026-10-26

## Goal
Android bootstrap for the Android-only FixIt hackathon MVP.

## Requirements
- A clean checkout produces a conventional NativePHP/Laravel Android project.
- Android SDK/JDK/PHP/Composer/Node versions and commands are documented.
- A physical Android device can install and launch the APK.
- No app server is required at runtime.

## Acceptance criteria
- Clean setup succeeds with recorded versions and commands.
- APK is installed and launched on a named physical device.
- Failed commands and workarounds are documented.

## Constraints
Follow root `AGENTS.md`, `docs/architecture.md`, `docs/mvp.md` and the architecture decision record. Do not substitute another stack or silently expand scope.

## Verification
Record exact test commands, results, environment and device in `docs/STATUS.md`. Only mark VERIFIED after concrete evidence.

## Implementation notes
Not implemented yet. Agent should inspect actual code, resolve version-specific APIs, and propose the smallest testable change before coding.
