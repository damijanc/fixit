# FixIt — Agent instructions

Read in order: AGENTS.md, docs/STATUS.md, docs/TASKS.md, docs/architecture.md, docs/mvp.md.

## Product and constraints
Android-only AI-guided repair assistant. NativePHP + Laravel, standalone Symfony AI packages, SQLite, user-supplied model key. No separate backend, iOS, RAG, accounts or multi-agent system in MVP. Keep token costs and scope small.

## Safety
Offer only low-risk, non-invasive troubleshooting. Never instruct users to open mains-powered devices, service gas equipment, bypass interlocks or perform hazardous tests. Validate AI output with deterministic safety rules. Explicit consent before uploading photos. Never commit API keys, keystores, credentials or logs containing secrets.

## Agent workflow
1. Read STATUS and TASKS and inspect actual code before editing.
2. Pick one task ID with explicit acceptance criteria.
3. Work on a feature branch and open a PR; avoid unrelated refactors.
4. Test changes. Report exact commands, environment and results. Never call untested code verified.
5. Update STATUS with branch/PR, changed files, evidence, blockers and next action.
6. Update TASKS status and architecture decisions if relevant.
7. End each PR with an agent handoff; do not depend on chat history.

## Evidence labels
PLANNED = not implemented; IN_PROGRESS = active; IMPLEMENTED = code exists but unverified; VERIFIED = concrete test/device evidence; BLOCKED = reproducible obstacle.

## Definition of done
Acceptance criteria met, tests added where applicable, documentation and handoff updated, and remaining Android-specific uncertainties stated.

## Guardrails
Do not change the chosen stack without approval. If Symfony AI cannot run inside embedded Android PHP, document exact reproduction and stop. Avoid autonomous agent loops and expensive speculative model calls. Mock providers in tests.
