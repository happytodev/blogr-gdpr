# AGENTS.md — Blogr GDPR

A Laravel/Filament package (`happytodev/blogr-gdpr`) providing GDPR compliance for Blogr CMS.

## Commands

| What | How |
|---|---|
| Run all tests | `composer test` (runs `vendor/bin/pest`) |
| Single file | `vendor/bin/pest tests/Feature/GdprControllerTest.php` |

Order: tests only (no lint/typecheck step before).

## Test quirks

- Uses **Pest** + Orchestra Testbench with **SQLite :memory:**
- `TestCase` creates table schemas inline (not from migration files) — so migration changes must be mirrored there
- `TestCase` also writes a fake Vite `manifest.json` to avoid build issues
- `DisablementTest` registers `blog.feed`, `cms.page.show`, `blog.index`, `blogr.cms.contact.submit` routes in `beforeEach`
- Arch test forbids `dd()`, `dump()`, `ray()` — add to `src/` not `tests/`
- CI runs PHP 8.3 + 8.4; local path repo `../blogr` is stripped via `jq` before install

## Architecture

- **Namespace**: `Happytodev\BlogrGdpr\` → `src/`, tests under `Happytodev\BlogrGdpr\Tests\`
- **Service provider** `src/BlogrGdprServiceProvider.php` loads views (`blogr-gdpr::`), translations, migrations, routes (`/gdpr/*`)
- **Extension registration**: registers with Blogr's `ExtensionRegistry`; can be disabled at runtime
- **Routes**: all under `web` middleware — POST `/gdpr/consent`, `/gdpr/withdraw`, GET+POST `/gdpr/data-export`, `/gdpr/data-erasure`
- **Version lives in two places**: `composer.json` and `BlogrGdprPlugin::getVersion()` — keep in sync

## Publishing

```bash
php artisan vendor:publish --tag=blogr-gdpr-config   # config/blogr-gdpr.php
php artisan vendor:publish --tag=blogr-gdpr-views     # resources/views/vendor/blogr-gdpr
php artisan vendor:publish --tag=blogr-gdpr-lang      # lang/vendor/blogr-gdpr
```

## Release

See `.opencode/skills/release-manager/SKILL.md`. Uses `gh release create`.
