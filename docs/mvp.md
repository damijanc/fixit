# FixIt MVP

## Journey
1. Start a repair session.
2. Take/select a photo and describe symptoms.
3. AI identifies a likely category and asks one diagnostic question.
4. Follow-up questions incorporate previous answers.
5. Present cautious findings, safe next steps, and escalation guidance.
6. Persist the session locally.

## Non-goals
Accounts, cloud sync, RAG, multi-agent orchestration, parts marketplace, iOS, offline inference, and a separate backend.

## Acceptance criteria
- Android APK installs on a real device.
- A real photo reaches a user-configured vision model through Symfony AI.
- Multi-turn state survives application restart.
- Deterministic restrictions block hazardous repair instructions.
- Provider errors do not lose session state.
- No API keys or signing credentials committed or embedded by default.
- Clean-clone build and test instructions.

## Sample scenarios
- Coffee machine with no water flow
- Bicycle with squeaking wheel (non-invasive checks)
- Desk lamp not switching on (external checks only)
