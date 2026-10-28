# Judge guide

FixIt is an Android-only, AI-guided repair assistant built with NativePHP, Laravel and Symfony AI.

> **T13 status:** multi-provider support is implemented on `task/t13-multi-provider-ai` but has not been published as a new release. Until review and a new tag, build the APK from that branch (see [RELEASE.md](RELEASE.md)); the latest published release contains the earlier DeepSeek-only build.

## What you need

- An **Android 13+** phone (arm64), or the provided emulator.
- The **FixIt APK**:
  - download `app-release.apk` from the latest **GitHub Release**, or
  - build it with [RELEASE.md](RELEASE.md).
- **One API key** from one supported provider:
  - **DeepSeek** — <https://platform.deepseek.com/api_keys>
  - **OpenRouter** — <https://openrouter.ai/keys> (one key can use Claude and GPT models)
  - **Hugging Face** — <https://huggingface.co/settings/tokens>
  - **Anthropic** — <https://console.anthropic.com/settings/keys>
  - **OpenAI** — <https://platform.openai.com/api-keys>
- Internet access for the model calls.

The key is entered in the app. It is **not** bundled in the APK, `.env` or the repository.

## Install and configure

1. Copy the APK to the phone.
2. Allow installation from the file manager/browser when prompted (unknown sources).
3. Open **FixIt**.
4. Tap **Provider settings**.
5. Select **one provider** and a **vision-capable model**.
6. Paste that provider's API key (the app does not require an `sk-` prefix).
7. Tap **Save settings**, then **Test connection**.
8. Expected: `Connected: FixIt AI connectivity OK`.

Switching provider requires entering the new provider's key; FixIt never reuses the previous provider's key.

## 90-second demo

1. **Home** → **Start a new diagnosis**.
2. Tap **Take a photo**; point at a safe object such as a mug or a desk lamp.
3. Grant the camera permission. Take the photo, then tap the **✓ checkmark** on the review screen. The photo stays local until you send it.
4. Add a symptom, e.g. “no water flow”, then tap **Send photo to {provider}**. The button names the provider you selected.
5. Show the cautious result and the safety note.
6. Answer the follow-up question (e.g. “the light turns on but there is no water”) and tap **Send answer**.
7. Tap **Open result screen** / open **History** to show the saved session.
8. Show that the key lives in **Provider settings**, not in the APK.

## Suggested provider/model pairs

| Provider | Model | Why |
|---|---|---|
| DeepSeek | `deepseek-flash` | Default; cheapest regression path |
| OpenRouter | `anthropic/claude-sonnet-4.5` | One OpenRouter key, Claude vision |
| OpenRouter | `openai/gpt-4.1` | Same OpenRouter key, GPT vision |
| Hugging Face | `Qwen/Qwen3-VL-30B-A3B-Instruct` | Official Hugging Face bridge, vision |
| Anthropic | `claude-sonnet-4-5` | Direct Anthropic bridge |
| OpenAI | `gpt-4.1` | Direct OpenAI Responses bridge |

## Safety

FixIt only supports low-risk, non-invasive checks. If the model suggests opening a mains-powered device, gas work, bypassing an interlock or a hazardous test, the deterministic safety filter blocks it and shows a professional-referral message. Do not open mains-powered devices or service gas equipment.

Do not photograph people, documents or personal information. The app asks before uploading a photo.

## Troubleshooting

| Symptom | Fix |
|---|---|
| `No API key saved for …` | Save a key for the selected provider in **Provider settings** |
| `… rejected the API key (401)` | Check that the key is active and belongs to the selected provider |
| `insufficient balance or quota (402)` | Add credit to that provider account |
| `denied access to this model (403)` | Use a model your account can access |
| `could not find the endpoint or model (404)` | Pick another curated model |
| `rate limit (429)` | Wait and retry |
| `could not be reached` | Check the phone's internet connection |
| `Save provider settings before testing` | You changed provider/model; save first, then test |
| `Enter an API key for … before saving` | Switching provider requires that provider's key; the old key is not reused |
| `cannot analyse photos` | The model is text-only; choose a vision-capable model |
| No photo arrives | In the system camera review screen, tap the **✓ checkmark**; certifying/cancelling sends nothing |
| Camera does not open | Grant the camera permission in Android settings |
| App will not install | Enable “Install via USB”/unknown sources; the phone must be Android 13+ |

## Privacy and secrets

- Provider key: entered in the app, encrypted with the NativePHP Keystore-managed `APP_KEY`, stored in app-private `ai-settings.enc`. One key at a time; it is only used for the selected provider. It is never in `.env` or the APK.
- Upgrade: a key saved by the DeepSeek-only release is migrated automatically.
- Photos: app-private storage; uploaded only after **Send photo to {provider}**; **Discard photo** deletes them.
- Sessions: local SQLite only; no account or cloud sync.
