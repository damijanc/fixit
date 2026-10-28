# Demo video guide

Target: **90 seconds**, recorded on the physical ZTE 8050 or the emulator.

## Before recording

1. Install the signed release APK (see [JUDGE_GUIDE.md](JUDGE_GUIDE.md)).
2. Select a provider/model in **Provider settings**, enter a real key, and confirm **Test connection**.
3. Pick a safe object (mug, cable, desk lamp). Do not photograph people, documents or screens.
4. Turn on airplane mode? No — keep Wi-Fi/internet on.

## Storyboard

| Time | Action | Message |
|---|---|---|
| 0–10 s | Home screen | “FixIt — an Android-only AI repair assistant. Photos stay local until you choose to send them.” |
| 10–25 s | Start a new diagnosis → Take a photo | Show the camera confirmation hint and tap the **✓ checkmark**. |
| 25–35 s | Add symptoms, tap **Send photo to {provider}** | “Only the photo and symptoms are uploaded, after explicit consent. The selected provider is named on the button.” |
| 35–55 s | Show the cautious result and safety note | Highlight the deterministic safety filter and professional escalation. |
| 55–70 s | Answer the follow-up, tap **Send answer** | “The next turn reuses the saved observations.” |
| 70–85 s | Open **Result** / **History** | “Sessions persist locally in SQLite.” |
| 85–90 s | Provider settings | “The key lives in Android Keystore-backed storage, not the APK.” |

## Record

On a phone/emulator connected over ADB:

```bash
adb shell screenrecord --time-limit 120 /sdcard/fixit-demo.mp4
# perform the storyboard, then stop with Ctrl+C
adb pull /sdcard/fixit-demo.mp4 docs/evidence/fixit-demo.mp4
```

Keep the recording free of personal information and API keys. Do not commit a video containing a real key, private photos or account details.
