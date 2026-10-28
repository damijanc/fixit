# Architecture

```text
Android / NativePHP (embedded Laravel + PHP)
  ├── Camera/photo picker → temporary local image
  ├── UI → diagnosis NativeComponent
  ├── SQLite → sessions, observations, repair summaries
  ├── Provider settings → encrypted provider/model/key selection
  └── ModelGateway
        └── ProviderRegistry → Symfony AI PlatformInterface
              └── HTTPS → selected provider + model
```

No separate Symfony server. Running Symfony AI within NativePHP's embedded Android PHP runtime is **verified**: on 2026-10-26 against a local fake provider on the emulator, and on 2026-10-27 with **real DeepSeek `deepseek-flash` text and image calls from a physical ZTE 8050**. T13 adds DeepSeek, OpenRouter, Hugging Face, direct Anthropic and direct OpenAI through Symfony AI's 0.15 platform bridges with an injectable platform for mock-based tests.

## Provider abstraction

- `ProviderCatalog` is a curated, code-owned list of providers, model IDs and vision capability. It is configuration, not diagnosis logic.
- `ProviderSettingsStore` persists one provider, one model and one key in `ai-settings.enc` (Laravel `Crypt` with the Keystore-managed `APP_KEY`). ADR 0004.
- `SymfonyAiPlatformFactory` is the only app class that imports Symfony AI bridge factories:
  - DeepSeek and OpenRouter → generic OpenAI-compatible platform
  - Hugging Face → `symfony/ai-hugging-face-platform`
  - Anthropic → `symfony/ai-anthropic-platform`
  - OpenAI → `symfony/ai-open-ai-platform`
- `ProviderRegistry` resolves the saved selection, enforces a key for the selected provider and vision capability, and constructs the platform lazily.
- `ModelGateway` is the workflow boundary: `ask`, `askWithImage`, `continueConversation`. It does not know provider IDs.
- Changing provider/model does not touch the diagnosis component, prompts, safety filter, SQLite schema or repair services. The diagnosis screen only asks the gateway for the current provider's display name.

## Responsibilities

- NativePHP: Android integration and UI
- Laravel: validation, persistence and application services
- Symfony AI: model requests and structured outputs
- Deterministic validation: reject unsafe suggested actions before display

Photos must be uploaded only on user action. API credentials must never be hardcoded or bundled; they are entered in Settings and encrypted on device. Exactly one provider key is stored at a time; switching provider drops the old key unless a key for the new provider is entered in the same save.

## Safety

Support low-risk, non-invasive troubleshooting. No instructions for opening mains-powered appliances, servicing gas equipment, defeating interlocks, or hazardous tests. Escalate to qualified professionals when appropriate. AI confidence is not a calibrated probability.

A deterministic `SafetyFilter` scans model output before display, blocks hazardous instructions, escalates mains/gas mentions, and never stores blocked raw text. Prompt instructions plus the deterministic filter are both required.

## Technical spikes

- Embedded PHP extensions and Symfony AI HTTP compatibility — **verified on emulator with a fake provider and on physical ZTE with real DeepSeek text and image calls** (2026-10-27)
- Camera image handling — **verified on emulator and physical ZTE 8050**: system camera intent, app-private JPEG, path returned to PHP (2026-10-27)
- Vision upload — **verified with explicit consent** on emulator (fake provider) and physical ZTE (real `deepseek-flash` image response); base64 `image_url` via Symfony AI (2026-10-27)
- SQLite repair sessions — **verified on emulator**: session/observations persist and restore after force-stop/relaunch (2026-10-27)
- Multi-turn follow-up — **verified on emulator**: stored photo prompt + prior result + answer sent as a conversation and restored after relaunch (2026-10-27)
- Deterministic safety filter — **verified by tests**: hazardous mains/gas/interlock instructions blocked before display; mains/gas mentions escalated (2026-10-27)
- Home/Diagnosis/Result/History screens — **verified on emulator**: navigation, latest-session resume and saved result/history work (2026-10-27)
- Secure local provider key storage — **verified on emulator** (ADR 0003/0004): encrypted at rest with the NativePHP Keystore-managed `APP_KEY`, entered in app Settings
- Multi-provider platform construction — **verified by mocked Symfony AI HTTP tests** for DeepSeek, OpenRouter, Hugging Face, Anthropic and OpenAI, including image payload formats; real-device smoke status is tracked in [STATUS.md](STATUS.md)
- Signed APK build — **VERIFIED locally and in GitHub Actions**: tag-only release workflow builds with JDK 17/SDK 36 and uploads a signed APK to the GitHub Release; run 40659466606 used the stable repository keystore (cert CN=FixIt Release) (2026-10-28)
