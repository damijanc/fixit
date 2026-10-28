# Multi-provider AI (T13)

- Status: IMPLEMENTED — host tests and emulator fake-provider smoke tests pass; real-provider and physical-device smoke tests pending
- Tasks: T13
- Last reviewed: 2026-10-28
- Branch: `task/t13-multi-provider-ai`

## Goal

Let a FixIt user pick one provider, one model and enter one API key, then complete
the existing photo → diagnosis → follow-up workflow. Symfony AI remains the only
layer that talks to a model. The diagnosis workflow, prompts, safety filter and
session persistence must not learn provider-specific details.

```text
FixIt UI
   |
Repair diagnosis workflow (unchanged)
   |
ModelGateway
   |
ProviderRegistry  →  Symfony AI PlatformInterface
   |
selected provider + model
```

## Providers and models

Curated at release time; IDs were checked against the live provider catalogs
(OpenRouter `/api/v1/models`, Hugging Face Router `/v1/models`) and the Symfony AI
0.15 model catalogs.

| Provider | Priority | Symfony AI bridge | Curated vision models |
|---|---|---|---|
| DeepSeek | P0 | Generic OpenAI-compatible | `deepseek-flash` |
| OpenRouter | P0 | Generic OpenAI-compatible | `anthropic/claude-sonnet-4.5`, `openai/gpt-4.1`, `openai/gpt-4o`, `openai/gpt-5` |
| Hugging Face | P0 | `symfony/ai-hugging-face-platform` | `Qwen/Qwen3-VL-30B-A3B-Instruct`, `Qwen/Qwen3.8-27B`, `google/gemma-4-31B-it` |
| Anthropic | P1 | `symfony/ai-anthropic-platform` | `claude-sonnet-4-5`, `claude-haiku-4-5`, `claude-opus-4-1` |
| OpenAI | P1 | `symfony/ai-open-ai-platform` | `gpt-4.1`, `gpt-4o`, `gpt-5` |

OpenRouter serves Claude and GPT models through one OpenRouter key and one
OpenRouter platform. It is **not** a second Anthropic/OpenAI connection.

Custom model IDs are out of scope for T13. A model that is not in the curated
list cannot be selected, so every selectable model supports the photo workflow.
Unknown/invalid persisted selections produce a visible configuration error rather
than a silent provider switch.

## Credentials and settings

- One encrypted settings file (`storage/app/private/ai-settings.enc`) holds the
  selected provider, model and the one API key. JSON is encrypted with Laravel
  `Crypt`/`APP_KEY`, the mechanism already verified in ADR 0003.
- A key is only ever used for the provider it was saved under. Changing provider
  drops the old key unless a new key is entered in the same save. Removing the
  key keeps the provider/model selection.
- Upgrade path: if `ai-settings.enc` does not exist but the old `ai-key.enc`
  does, the legacy key is read, bound to DeepSeek + `deepseek-flash`, written to
  the new encrypted file and the legacy file is removed. Existing DeepSeek users
  therefore keep working and keep their key.
- Key validation is format-agnostic: trim, reject empty, reject whitespace and
  require at least 8 characters. The old `sk-` prefix rule and hint are removed.
  Provider-specific hints are informational only.
- Settings UI: provider select, model select, key entry, save, test connection,
  remove key, clear provider-specific error messages.
- "Test connection" runs against the saved selection only; unsaved changes must
  be saved first. This prevents a key from being sent to a provider it was not
  saved for.

## Architecture

New classes under `app/Services/AI/`:

- `ProviderDefinition`, `ModelDefinition` — immutable catalog entries.
- `ProviderCatalog` — reads `config/ai.php`, validates provider/model lookups.
- `ProviderSettings` — selected provider, model and optional key.
- `ProviderSettingsStore` — persistence boundary (mockable in tests).
- `EncryptedFileProviderSettingsStore` — encrypted implementation + legacy migration.
- `PlatformFactory` — constructs a Symfony AI `PlatformInterface` for a provider.
- `SymfonyAiPlatformFactory` — the only place that imports provider bridge factories.
- `ProviderRegistry` — resolves the saved selection, enforces key/vision capability,
  and creates the platform lazily.

`ModelGateway` keeps its public methods (`ask`, `askWithImage`,
`continueConversation`) and remains injectable with a mock `PlatformInterface`.
Provider errors are translated to safe, provider-named messages.

The diagnosis screen asks the registry for the current provider display name; it
does not branch on provider IDs and needs no change when a provider is added.

## Implementation plan

1. Add the Anthropic, Hugging Face and OpenAI Symfony AI bridge packages at
   `^0.15` (matching `symfony/ai-platform` 0.15).
2. Add the provider catalog to `config/ai.php` and the classes above.
3. Replace `ApiKeyStore`/`EncryptedFileApiKeyStore` with the encrypted settings
   store; migrate the legacy key file on first read.
4. Rewire `AppServiceProvider` (catalog, store, platform factory, registry,
   gateway) and the Settings screen.
5. Replace hard-coded DeepSeek copy in Diagnosis with the selected provider name.
6. Add unit, feature and architecture-boundary tests (mocks only, no paid calls).
7. Update STATUS/TASKS/architecture/DEVELOPMENT/JUDGE_GUIDE/README.
8. Smoke-test DeepSeek, OpenRouter (Claude + GPT) and Hugging Face on a physical
   Android device; record exact IDs, device and limitations.

## Acceptance criteria

- [x] Fresh install: select a provider and model, save one key, run a photo
      diagnosis and a follow-up (emulator, fake OpenAI-compatible provider).
- [x] Switching provider/model needs no diagnosis, prompt, safety or persistence
      change.
- [x] DeepSeek remains the default and an existing `ai-key.enc` key is migrated
      and still works (unit tests).
- [x] A key is never sent to a provider other than the one it was saved for.
- [x] All model calls go through Symfony AI platform bridges; no direct HTTP
      client for a provider.
- [x] Settings shows understandable errors for missing key, auth failure,
      rate limit, unavailable model and unsupported image input (host tests).
- [x] No credential appears in source, `.env.example`, logs, tests or the APK
      (host tests + APK scan of fake keys).
- [x] `php artisan test` passes; no network/paid calls in CI.
- [ ] DeepSeek, OpenRouter (Claude and GPT) and Hugging Face are verified with
      real provider keys on a physical Android device. Emulator fake-provider
      smoke tests pass for DeepSeek, OpenRouter and Hugging Face, including
      Hugging Face vision and multi-turn; real keys and the physical device were
      not available in this environment.

## Verification (2026-10-28)

- `php artisan test --compact` — 94 passed, 581 assertions.
- Emulator `Pixel_9a` (Android 17 / API 37), debug APK built with JDK 17:
  - Settings shows DeepSeek/`deepseek-flash` by default, with provider/model
    selects, provider-specific key hint, save/test/remove actions.
  - Switching to OpenRouter changes the model to
    `anthropic/claude-sonnet-4.5`, names OpenRouter in the hint and refuses to
    reuse the DeepSeek key.
  - One-key save and **Test connection** succeeded for DeepSeek, OpenRouter and
    Hugging Face against the local fake OpenAI-compatible provider.
  - Hugging Face path: photo consent names Hugging Face, the send button names
    Hugging Face, an image was uploaded through the HF bridge, the synthetic
    result rendered, and the multi-turn follow-up succeeded and persisted.
  - Evidence: `docs/evidence/2026-10-28-t13-settings.png`,
    `...-t13-openrouter-selected.png`, `...-t13-hf-fake-followup.png`.
- APK scan: zero occurrences of the three fake test keys. App data was cleared
  and the APK rebuilt with the real DeepSeek endpoint and no saved key.

## Out of scope

Automatic failover, multiple simultaneous keys/providers, model benchmarking,
billing dashboards, custom model IDs, RAG/agents, provider proxies, and any
rework of the diagnosis workflow.
