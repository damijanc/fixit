# ADR 0004 — One provider, one key, one encrypted settings file

Status: Accepted. Date: 2026-10-28. Task: T13.

## Context

T13 adds DeepSeek, OpenRouter, Hugging Face, Anthropic and OpenAI. The product
promise is one provider, one API key and one repair workflow. The previous
storage (ADR 0003) held only a key; a selected provider/model and its key must
never get out of sync, or a DeepSeek key could be sent to OpenRouter.

## Decision

- Keep one encrypted file, `storage/app/private/ai-settings.enc`, containing
  `{provider, model, api_key}` as Laravel `Crypt`/`APP_KEY` ciphertext.
- `ProviderSettingsStore` is the persistence boundary. It owns the rule that a
  key is kept only while the provider stays the same and is dropped when the
  provider changes without a new key.
- On first read, if `ai-key.enc` (ADR 0003) exists, migrate it to DeepSeek +
  `deepseek-flash` in the new file and delete the old file. Existing users keep
  their key and default configuration.
- `ProviderCatalog` is code/config, not diagnosis logic. `SymfonyAiPlatformFactory`
  is the only class importing Symfony AI bridge factories.
- `ProviderRegistry` resolves the saved selection, validates the key and vision
  capability, and lazily builds the `PlatformInterface`. `ModelGateway` keeps
  its three public methods and remains injectable with a mock platform.
- One key per app. Switching provider requires entering the new provider's key
  before saving; there is no silent reuse and no key for a different provider.

## Consequences

- The security invariant is enforced in one place instead of the UI only.
- Users who switch providers must paste the new key, which is the intended
  "one provider, one key" behaviour.
- Removing the key keeps the provider/model so the user can paste a replacement.
- The old `ai-key.enc` path remains read-only migration input; no new key is
  ever written there.
- ADR 0003's encryption decision remains valid; this ADR only broadens the
  encrypted payload and changes the class names.
