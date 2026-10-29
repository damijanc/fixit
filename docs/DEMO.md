# Demo video guide

Target: **90 seconds**, recorded on the physical ZTE 8050 with the v1.0.0 release APK and Claude Opus 5.5.

## Before recording

1. Install the signed release APK (see [JUDGE_GUIDE.md](JUDGE_GUIDE.md)).
2. In Settings, keep **Anthropic / Claude Opus 5.5**, enter the key, tap **Save**, then **Test**. Then leave Settings so the key never appears on screen.
3. Pick a safe object with an obvious, low-risk problem: a laptop whose battery drains, a phone that will not charge (with its cable), a desk lamp that flickers. Do not photograph people, documents or open screens with private content.
4. Keep Wi-Fi on; enable Do Not Disturb so no notifications show.
5. Do one practice run first — the first Claude call takes ~15–25 s; trim the wait in editing or narrate over it.

## Storyboard and voiceover

Screen only. The voiceover is generated with a text-to-speech service (ElevenLabs recommended; OpenAI TTS or macOS `say` also work) from the lines below — about 190 words, 55 s of speech at a normal pace, leaving room for pauses. Cut the 15–25 s Claude wait in editing.

| Time | Screen | Voiceover |
|---|---|---|
| 0–8 s | Home | This is FixIt, an Android repair assistant. Point your phone at something broken, and get safe next steps. |
| 8–16 s | Start a new diagnosis → Take a photo → ✓ | Take a photo. It is shrunk on the phone, and stays there until you decide to send it. |
| 16–22 s | Type the symptom, tap **Send photo to Anthropic** (spinner; cut the wait) | Describe the problem, and send it to Claude Opus 5.5. |
| 22–32 s | Diagnosis card, scroll slowly | Claude answers in a strict format: what it sees, the likely cause, how confident it is, and a few safe checks to try. |
| 32–45 s | Card warning row (battery/mains photo) or the safe checks | FixIt then checks every line with its own safety rules. Anything like opening a mains device is blocked, and battery, gas or mains risks add a warning to call a professional. |
| 45–57 s | Type an answer, send; updated card | Answer the question, and Claude updates the diagnosis. The shared context is cached, so follow-ups cost less. |
| 57–72 s | Settings → open the **Provider** list | Claude is the default, but Symfony AI runs inside the app, so the same flow works with DeepSeek, OpenRouter, Hugging Face or OpenAI. |
| 72–90 s | Settings "How your key is stored" → Home → History | Bring your own key. It is encrypted on the device, and sessions stay local. No account, no backend. That's FixIt. |

Good card moments: a photo where Claude flags a **battery** risk shows the warning row; a phone + charger photo shows ordinary safe checks passing the filter.

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
