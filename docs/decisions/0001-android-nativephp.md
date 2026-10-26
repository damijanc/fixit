# ADR 0001 — Android-only NativePHP and Symfony AI

Status: Accepted, embedded compatibility partially verified. Date: 2026-10-26.

FixIt targets a useful mobile experience within a limited hackathon development budget.

Decision: Android APK; NativePHP/Laravel app; standalone Symfony AI for model integration; local SQLite; user-supplied provider credentials; no backend, iOS, RAG or multi-agent system in MVP.

Compatibility evidence (2026-10-26): Symfony AI `0.15.0` + generic OpenAI-compatible bridge ran from NativePHP's embedded PHP 8.5.12 on a `Pixel_9a` emulator and received a response from a local fake provider. A real provider call is pending secure key storage (T8).

Tradeoff: NativePHP is Laravel-based rather than full-stack Symfony. This is intentional because the goal is a PHP-based native Android application using Symfony AI.

Risks: Android embedded PHP compatibility, secure key storage, camera handling, CI packaging and signing.

If compatibility fails, document a reproducible blocker and request approval before changing architecture.
