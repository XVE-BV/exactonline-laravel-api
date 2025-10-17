# Project Guidelines

## Project Overview
`skylence/exactonline-laravel-api` is a Laravel package that integrates with Exact Online. It focuses on robust OAuth token management (10‑minute access tokens with proactive refresh), webhook handling, rate limiting, and a clean developer experience through a strict Action Pattern architecture.

Authoritative docs you should consult when making changes:
- docs/implementation-plan.md — high‑level roadmap and architecture
- docs/action-pattern-design.md — Action Pattern rules, structure, and examples
- docs/ai-coding-rules.md — strict coding rules and templates (MUST follow)

Key tech requirements:
- PHP: ^8.3 | ^8.4
- Laravel components: ^11 | ^12
- Core deps: picqer/exact-php-client, spatie/laravel-package-tools

## Repository Structure (essentials)
- src/Actions/** — Business logic split by domain
  - OAuth/, API/, Webhooks/, RateLimit/, Connection/
- src/Http/** — Controllers, Middleware
- src/Models/** — Eloquent models (ExactConnection, ExactWebhook, …)
- src/Events/**, src/Exceptions/**, src/Support/** (Config helpers, etc.)
- config/exactonline-laravel-api.php — Action resolution and settings
- database/migrations/** — Package migrations
- routes/** — Package routes
- tests/** — Pest tests using Orchestra Testbench
- docs/** — Architecture and contributor docs

Refer to docs/action-pattern-design.md for the canonical structure and naming conventions.

## How Junie should work in this repo
- Make minimal, focused changes aligned with docs/ai-coding-rules.md.
- Implement or modify business logic using the Action Pattern only.
- Keep controllers thin; never move business logic into controllers.
- Prefer small PRs; add tests for significant logic changes.

## Running Tests and Quality Checks
- Install dependencies: composer install
- Run tests (Pest): composer test
  - With coverage: composer run test-coverage
- Static analysis (PHPStan): composer analyse
- Code style (Laravel Pint): composer format

Notes:
- Tests run with Orchestra Testbench; no full Laravel app needed.
- phpunit.xml.dist is present; Pest is the primary test runner.

## Code Style and Conventions
- Follow Laravel Pint defaults; CI auto-fixes style via .github/workflows/fix-php-code-style-issues.yml.
- Obey all rules in docs/ai-coding-rules.md (STRICT). Highlights:
  - Use Actions with a single public execute(...) method.
  - No constructor injection in actions; pass dependencies via method parameters.
  - Implement proactive token refresh with distributed locks for OAuth.

## Build/Release
- This is a Laravel package; no separate build step.
- Ensure composer dump-autoload runs (composer handles automatically).
- Service provider and package discovery are configured via composer.json extra.laravel.

## Useful paths
- Config: config/exactonline-laravel-api.php
- Service provider: src/ExactonlineLaravelApiServiceProvider.php
- Example controller: src/Http/Controllers/**

If something is ambiguous, prefer the guidance in docs/action-pattern-design.md and docs/ai-coding-rules.md over ad‑hoc solutions.
