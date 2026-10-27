# APK CI and release

- Status: IN_PROGRESS
- Tasks: T10, T11
- Last reviewed: 2026-10-27

## Requirements
Run tests in PRs; build signed APK in GitHub Actions; publish release with instructions and 90-second demo.

## Acceptance criteria
Clean runner builds; release installs on real Android; no signing/provider secrets exposed.

## Handoff
Follow AGENTS.md and update docs/STATUS.md and docs/TASKS.md with verified evidence. Android compatibility is unverified until tested.

## Evidence (2026-10-27)

- `.github/workflows/android.yml` installs PHP 8.5, JDK 17 and Android SDK 36, builds a signed release APK, verifies it with `apksigner`, and uploads `fixit-android-release-apk`. It uses repository secrets when present, otherwise an ephemeral CI keystore.
- GitHub Actions run `40487118046` succeeded (~8.5 min) and uploaded the signed APK; PHP tests also passed.
- Local release build verified: 76.77 MB signed APK, certificate checked, installed and launched on the `Pixel_9a` emulator.
- `docs/JUDGE_GUIDE.md`, `docs/RELEASE.md`, `docs/DEMO.md` and `scripts/build-release.sh` added.
- Remaining: stable signing secrets, physical-device release install, demo recording.
