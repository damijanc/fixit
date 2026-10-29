# Judge guide

FixIt is an Android-only, AI-guided repair assistant built with NativePHP, Laravel and Symfony AI. You photograph a broken household item, describe the problem, and get a cautious diagnosis with safe checks — never instructions to open mains-powered devices or service gas equipment.

**Bring your own provider.** FixIt runs Symfony AI inside the phone's embedded PHP, so the same diagnosis flow works with five providers and fourteen curated models — pick one in Settings and paste that provider's key. No FixIt backend sits in between.

## What you need

- An **Android 13+** phone (arm64), or an Android emulator.
- The **FixIt APK**: download `app-release.apk` from the **v1.0.0 GitHub Release** (or build it with [RELEASE.md](RELEASE.md)).
- **One API key** from any supported provider:
  - **Anthropic** — <https://console.anthropic.com/settings/keys> (preselected: Claude Opus 5.5, with the diagnosis card)
  - **DeepSeek** — <https://platform.deepseek.com/api_keys> (cheapest)
  - **OpenRouter** — <https://openrouter.ai/keys> (one key for Claude and GPT models)
  - **Hugging Face** — <https://huggingface.co/settings/tokens> (open-weight vision models)
  - **OpenAI** — <https://platform.openai.com/api-keys>
- Internet access for the model calls.

The key is entered in the app. It is **not** bundled in the APK, `.env` or the repository.

## Install and configure

1. Copy the APK to the phone and open it; allow installation from unknown sources when prompted.
2. Open **FixIt** and tap the **settings** (gear) icon.
3. **Anthropic** and **Claude Opus 5.5** are preselected. To use another provider, pick it and one of its models — every provider below works with the same flow.
4. Paste the API key, tap **Save**, then **Test**.
5. Expected: `Connected: FixIt AI connectivity OK`.

Switching provider requires entering that provider's key; FixIt never reuses the previous provider's key.

## 90-second demo

1. **Home** → **Start a new diagnosis**.
2. Tap **Take a photo**; point at something safe, e.g. a laptop, a phone with its charger, or a desk lamp.
3. Grant the camera permission. Take the photo, then tap the **✓ checkmark** on the review screen. The photo stays on the phone until you send it.
4. Add a symptom, e.g. "battery drains fast", and tap **Send photo to Anthropic**. A spinner and progress text show while Claude works.
5. Claude's answer appears as a **diagnosis card**: the object it recognised, the likely cause, a confidence level, numbered safe checks and one follow-up question. If there is a mains, gas or battery hazard, the card shows a warning to use a qualified professional.
6. Answer the question in **Your answer** and send it; Claude returns an updated card.
7. Tap the document icon for the result screen, or go back to **Home → History** to see the saved session; it survives an app restart.

## What Claude does here

- **Structured output.** With Claude models, FixIt requests a strict JSON schema (`output_config.format`): object, likely cause, confidence, up to five safe checks, hazard (`none`/`mains`/`gas`/`battery`/`other`), `needs_professional` and one question. The app renders it as the card; other providers return plain text.
- **Deterministic safety on top.** Every field of the reply is checked by FixIt's own rules. A single dangerous check (e.g. "remove the back cover") replaces the whole answer with a professional-referral message; a hazard flag or `needs_professional` adds the warning.
- **Current models and settings.** Claude Opus 5.5 (default), Sonnet 5.5 and Haiku 5.5, with `max_tokens` 16000 and `effort: medium`; thinking replies and refusals are handled.
- **Prompt caching and usage.** Every call logs input, output, cache-write and cache-read tokens. On the test phone the first photo call wrote 3–6k tokens to the cache. (Logs are readable from debug builds via `adb`; see [STATUS.md](STATUS.md) for recorded runs.)
- **Smaller uploads.** A small native Android plugin (`packages/photo-resize`) shrinks each photo to a 1568 px long edge before upload — Claude's own image limit — e.g. 2.6 MB → 178 KB, upright and without EXIF/GPS.

## Supported providers (Symfony AI)

Every provider goes through the same Symfony AI `Agent` and `Platform` interfaces; only the bridge differs. Switching provider is a Settings change, not a code change, and the photo, symptoms, follow-up history, safety filter and error messages work the same for all of them.

| Provider | Symfony AI bridge | Curated vision models | Tested on the ZTE 8050 with a real key |
|---|---|---|---|
| **Anthropic** (default) | `Bridge\Anthropic` | Claude Opus 5.5, Sonnet 5.5, Haiku 5.5 | Yes — photo, card, follow-up (Opus 5.5) |
| **DeepSeek** | `Bridge\Generic` (OpenAI-compatible) | DeepSeek Flash | Yes — photo diagnosis (before the multi-provider release) |
| **OpenRouter** | `Bridge\Generic` | Claude Sonnet 4.5, GPT-4.1, GPT-4o, GPT-5 | No — emulator against a local fake provider |
| **Hugging Face** | `Bridge\HuggingFace` (Inference Providers, routed via `deepinfra`) | Qwen3-VL 30B, Qwen3.8 27B, Gemma 4 31B | Yes — photo + follow-up (Qwen3-VL) |
| **OpenAI** | `Bridge\OpenAi` (Responses API) | GPT-4.1, GPT-4o, GPT-5 | No — host tests only |

Claude models additionally return the structured diagnosis card (see above); the other providers return a plain-text answer checked by the same safety filter.

## Safety

FixIt only supports low-risk, non-invasive checks. If a model suggests opening a mains-powered device, gas work, bypassing an interlock or a hazardous test, the deterministic safety filter blocks it and shows a professional-referral message. Do not open mains-powered devices or service gas equipment.

Do not photograph people, documents or personal information. The app asks before uploading a photo.

## Troubleshooting

| Symptom | Fix |
|---|---|
| `No API key saved for …` | Save a key for the selected provider in Settings |
| `… rejected the API key (401)` | Check that the key is active and belongs to the selected provider |
| `insufficient balance or quota (402)` | Add credit to that provider account |
| `denied access to this model (403)` | Use a model your account can access |
| `could not find the endpoint or model (404)` | Pick another curated model |
| `rate limit (429)` | Wait and retry |
| `could not be reached` | Check the phone's internet connection |
| `Save provider settings before testing` | You changed provider/model; save first, then test |
| `Enter an API key for … before saving` | Switching provider requires that provider's key; the old key is not reused |
| `cannot analyse photos` | The model is text-only; choose a vision-capable model |
| No photo arrives | In the camera review screen, tap the **✓ checkmark**; cancelling sends nothing |
| Camera does not open | Grant the camera permission in Android settings |
| App will not install | Enable unknown sources; the phone must be Android 13+ |

## Privacy and secrets

- Provider key: entered in the app, encrypted with the NativePHP Keystore-managed `APP_KEY`, stored in app-private `ai-settings.enc`. One key at a time, used only for the selected provider. Never in `.env` or the APK.
- Upgrade: a key saved by the early DeepSeek-only build is migrated to DeepSeek automatically.
- Photos: app-private storage, downscaled and stripped of EXIF on capture; uploaded only after **Send photo to {provider}**; **Discard photo** deletes them.
- Sessions: local SQLite only; no account or cloud sync.
