# ADR 0001 — Android-only NativePHP and Symfony AI

Status: Accepted, compatibility unverified. Date: 2026-10-26.

FixIt targets a useful mobile experience within a limited hackathon development budget.

Decision: Android APK; NativePHP/Laravel app; standalone Symfony AI for model integration; local SQLite; user-supplied provider credentials; no backend, iOS, RAG or multi-agent system in MVP.

Tradeoff: NativePHP is Laravel-based rather than full-stack Symfony. This is intentional because the goal is a PHP-based native Android application using Symfony AI.

Risks: Android embedded PHP compatibility, secure key storage, camera handling, CI packaging and signing.

If compatibility fails, document a reproducible blocker and request approval before changing architecture.
