# Judge guide

FixIt is an Android-only, AI-guided repair assistant built with NativePHP, Laravel and Symfony AI.

## What you need

- An **Android 13+** phone (arm64), or the provided emulator.
- The signed **FixIt release APK**:
  - download `app-release.apk` from the latest **GitHub Release**, or
  - build it with [RELEASE.md](RELEASE.md).
- A **DeepSeek API key** from <https://platform.deepseek.com/api_keys>. The key is entered in the app; it is **not** bundled in the APK.
- Internet access for the DeepSeek calls.

## Install

1. Copy the APK to the phone.
2. Allow installation from the file manager/browser when prompted (unknown sources).
3. Open **FixIt**.
4. Tap **Provider settings**, paste the full DeepSeek key (it starts with `sk-`), tap **Save key**, then **Test connection**.
5. Expected: `Connected: FixIt AI connectivity OK`.

## 90-second demo

1. **Home** → **Start a new diagnosis**.
2. Tap **Take a photo**; point at a safe object such as a mug or a desk lamp.
3. Grant the camera permission. Take the photo, then tap the **✓ checkmark** on the review screen. The photo stays local until you send it.
4. Add a symptom, e.g. “no water flow”, then tap **Send photo to DeepSeek**.
5. Show the cautious result and the safety note.
6. Answer the follow-up question (e.g. “the light turns on but there is no water”) and tap **Send answer**.
7. Tap **Open result screen** / open **History** to show the saved session.
8. Show that the key lives in **Provider settings**, not in the APK.

## Safety

FixIt only supports low-risk, non-invasive checks. If the model suggests opening a mains-powered device, gas work, bypassing an interlock or a hazardous test, the deterministic safety filter blocks it and shows a professional-referral message. Do not open mains-powered devices or service gas equipment.

Do not photograph people, documents or personal information. The app asks before uploading a photo.

## Troubleshooting

| Symptom | Fix |
|---|---|
| `DeepSeek rejected the API key (401)` | Paste the full key including the `sk-` prefix; confirm it is active on platform.deepseek.com |
| `insufficient balance (402)` | Add credit to the DeepSeek account |
| `rate limit (429)` | Wait and retry |
| `could not be reached` | Check the phone’s internet connection; the endpoint is `https://api.deepseek.com` |
| No photo arrives | In the system camera review screen, tap the **✓ checkmark**; certifying/cancelling sends nothing |
| Camera does not open | Grant the camera permission in Android settings |
| App will not install | Enable “Install via USB”/unknown sources; the phone must be Android 13+ |

## Privacy and secrets

- Provider key: entered in the app, encrypted with the NativePHP Keystore-managed `APP_KEY`, stored in app-private storage. It is never in `.env` or the APK.
- Photos: app-private storage; uploaded only after **Send photo to DeepSeek**; **Discard photo** deletes them.
- Sessions: local SQLite only; no account or cloud sync.
