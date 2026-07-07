# Blogr GDPR AGENTS.md

## ⚠️ Issue creation — MANDATORY

**Every user request for a bug fix or new feature MUST trigger a GitHub
issue before any code is written or proposed.** This ensures traceability.

- User says "there is a bug" → create issue with `--label bug`
- User says "I need a feature" → create issue with `--label feature`
- The issue is created via `gh issue create` immediately upon understanding the need
- The issue MUST be closed when the work is merged into `main` — the PR description MUST include `Closes #<issue_number>` to auto-close on merge
- Skipping this is a process error

## ⚠️ Commit policy — ZERO TOLERANCE

**NEVER commit, amend, tag, or push unless the user explicitly loads the
`release-manager` skill and requests a release.** All commits must go
through the `release-manager` workflow. Violating this rule is a process error.

## ⚠️ TDD requirement — ZERO TOLERANCE

**Every bug fix and every feature addition MUST be driven by tests written
first (TDD).** Run the test to confirm it fails before implementing, then run
it again to confirm it passes after.

### Naming convention

- **Bug regression tests**: `regression_<issue_number>_<description>`
- **Feature tests**: `feature_<description>`

### RED phase (mandatory before any implementation)

1. Write the test that proves the bug exists or validates the expected behavior
2. Run `vendor/bin/pest --filter <test_name>` — confirm it **fails** (RED)
3. This proves the test detects the problem

### GREEN phase

1. Implement the fix or feature
2. Run `vendor/bin/pest --filter <test_name>` — confirm it **passes** (GREEN)
3. **Anti-false-positive gate**: Comment out the new implementation code and re-run the test — it must fail again

## Project

A Laravel/Filament package (`happytodev/blogr-gdpr`) providing GDPR compliance
for Blogr CMS — consent management, data export, data erasure, and cookie consent.

## Resources

| File | Content |
|------|---------|
| [README.md](README.md) | Installation, prerequisites |

## Stack

- PHP 8.3+, Laravel 12.x, FilamentPHP v4, Pest PHP 4.0
- Testbench 10.x, in-memory SQLite
- Spatie Package Tools

## Commands

```bash
composer test                    # vendor/bin/pest
vendor/bin/pest tests/Feature/GdprControllerTest.php
php -l src/SomeFile.php          # Syntax check before commit
```

## Testing quirks

- Uses **Pest** + Orchestra Testbench with **SQLite :memory:**
- `TestCase` creates table schemas inline (not from migration files) — migration changes must be mirrored there
- `TestCase` writes a fake Vite `manifest.json` to avoid build issues
- `DisablementTest` registers blog routes in `beforeEach`
- Arch test forbids `dd()`, `dump()`, `ray()`
- CI runs PHP 8.3 + 8.4; local path repo `../blogr` stripped via `jq`
- Feature tests must declare `uses()` individually (Pest.php only covers base TestCase)

## Architecture

- **Namespace**: `Happytodev\BlogrGdpr\` → `src/`, tests under `Happytodev\BlogrGdpr\Tests\`
- **Service provider**: `BlogrGdprServiceProvider` loads views, translations, migrations, routes
- **Routes**: all under `web` middleware — consent, withdraw, data-export, data-erasure
- **Filament**: `GdprSettings` page, `GdprRequestResource`, `ConsentLogResource`
- **Extension**: registers with Blogr's `ExtensionRegistry`; can be disabled at runtime
- **Version**: lives in `composer.json` AND `BlogrGdprPlugin::getVersion()` — keep in sync

## Filament v4 gotchas

- **Table actions**: Use `Filament\Actions\Action` (NOT `Filament\Tables\Actions`)
- **Livewire 419**: Every Filament page component MUST be registered with `Livewire::component()`.
  Currently all 6 components are registered in the service provider's `boot()` method.

## Publishing

```bash
php artisan vendor:publish --tag=blogr-gdpr-config   # config/blogr-gdpr.php
php artisan vendor:publish --tag=blogr-gdpr-views     # views
php artisan vendor:publish --tag=blogr-gdpr-lang      # translations
```

## Release

See `.opencode/skills/release-manager/SKILL.md`. Uses `gh release create`.
