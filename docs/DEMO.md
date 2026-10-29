# Demo video guide

Target: **90 seconds**, recorded on the physical ZTE 8050 with the v1.0.0 release APK and Claude Opus 5.5.

## Before recording

1. Install the signed release APK (see [JUDGE_GUIDE.md](JUDGE_GUIDE.md)).
2. In Settings, keep **Anthropic / Claude Opus 5.5**, enter the key, tap **Save**, then **Test**. Then leave Settings so the key never appears on screen.
3. Pick a safe object with an obvious, low-risk problem: a laptop whose battery drains, a phone that will not charge (with its cable), a desk lamp that flickers. Do not photograph people, documents or open screens with private content.
4. Keep Wi-Fi on; enable Do Not Disturb so no notifications show.
5. Do one practice run first — the first Claude call takes ~15–25 s; trim the wait in editing or narrate over it.

## Storyboard

| Time | Screen | Narration |
|---|---|---|
| 0–8 s | Home | "FixIt is an Android repair assistant. Point the camera at something broken and get safe next steps." |
| 8–20 s | Start a new diagnosis → Take a photo → ✓ | "The photo is shrunk on the phone and stays local until I choose to send it." |
| 20–30 s | Type the symptom, tap **Send photo to Anthropic** | "Only now are the photo and symptoms sent — to Claude Opus 5.5." Spinner visible. |
| 30–55 s | Diagnosis card (scroll slowly) | "Claude answers in a strict JSON schema: what it sees, the likely cause, confidence, numbered safe checks and one question. FixIt's own rules check every field — anything like 'open the back cover' is blocked, and mains, gas or battery hazards add this professional warning." |
| 55–75 s | Type the answer, send; updated card | "The follow-up reuses the conversation, and the shared prompt is cached, so later turns cost less." |
| 75–85 s | Back → Home → History | "Sessions are stored locally in SQLite — no account, no cloud." |
| 85–90 s | Settings (key field masked) | "Bring your own key; it is encrypted with the Android Keystore and never shipped in the app." |

Good card moments: a photo where Claude flags **battery** risk shows the warning row; a phone + charger photo shows ordinary, safe checks passing the filter.

## Record

On a phone connected over ADB:

```bash
adb shell screenrecord --time-limit 180 /sdcard/fixit-demo.mp4
```

Perform the storyboard, then stop with Ctrl+C and pull the file:

```bash
adb pull /sdcard/fixit-demo.mp4 ~/Desktop/fixit-demo.mp4
```

Keep the recording free of personal information and API keys. Do not commit a video containing a real key, private photos or account details; attach the video to the GitHub Release or the submission instead.
