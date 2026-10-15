# Changelog

All notable changes to `blogr-gdpr` will be documented in this file.

## v2.1.1 - 2026-10-15

### 📖 Documentation

- **Comments in English**: the comments added in 2.1.0 were written in French. They are in English
  now, like the rest of the package and like every message on its GitHub side.

## v2.1.0 - 2026-10-15

### 🐛 Bug Fixes

- **Settings are stored in the database instead of the config file** (#3). Saving the GDPR settings
  page rewrote `config/blogr-gdpr.php` — and wrote into the package's own copy first, under
  `vendor/`, which any `composer install` replaces. On a host that deploys by release directories,
  an admin save therefore held until the next deployment and then reverted. The values now go into
  the `blogr_gdpr_settings` table (created since the first migration, never used until now) and are
  applied on top of the file at boot; the committed `config/blogr-gdpr.php` keeps its role of
  defaults and its comments. `null` values are never stored, and the previous behaviour is kept
  while the table has not been migrated.
- **The cookie consent banner honours its own flag** (#4). `cookie_consent.enabled` was never read:
  the banner was pushed as soon as the plugin was enabled, so the settings toggle silently did
  nothing and a site whose measurement needs no consent could not avoid asking for one.
- **The "manage my preferences" link only appears when there is a consent interface to open** (#4).
  It was pushed into the footer whenever the plugin was enabled, while the function it calls lives in
  the banner: with no banner, the link opened nothing and raised an error in the console.
- **The analytics consent now actually gates the tracker** (#6). The choice was kept in the visitor's
  `localStorage` only, so the server could not act on it and the provider's tag went out to everyone —
  the consent was recorded, and the measurement ran anyway. The choice is now also written to a cookie
  (`blogr_gdpr_analytics`), and the page is served without the provider's tag until it says
  `accepted`. Accepting or declining goes through the server, which sets the cookie and redirects:
  there is no window in which the measurement runs without consent. The functions the banner calls
  (`blogrGdprShowAnalytics`, `blogrGdprHideAnalytics`) were defined nowhere and now exist, so the
  banner's own preferences window works too.
- **The privacy policy the plugin creates renders as text, not as its own markup** (#5). A Blogr
  content block holds markdown, escaped on purpose against XSS, while the plugin built HTML blocks:
  visitors read `<h2>1. Introduction</h2>`. The templates are markdown now, split on their headings,
  and a page already created by an earlier version is detected and rewritten on the next boot instead
  of staying broken.
- **The data access and erasure forms follow the site language** (#7). Their routes carried no locale,
  so the site's own `SetLocale` middleware never ran and both forms were rendered in the default
  language. They are also served under `/{locale}/gdpr/…`, which is what the footer links to. The
  prefixless addresses stay in place: published links, form posts and the banner's JavaScript keep
  working unchanged. Blogr's CMS route pattern would have swallowed the new prefix, so the plugin
  reserves `gdpr` before Blogr builds its pattern.

### ⬆️ Upgrading

- Re-publish the views to get the corrected screens:
  `php artisan vendor:publish --tag=blogr-gdpr-views` (add `--force` to overwrite the copies already
  published).
- Run `php artisan migrate` to create the settings table.

## v1.5.2 - 2026-06-29

### 🐛 Bug Fixes

- **Plugin**: add `RegistersLinkTypes` trait for LinkTypeRegistry support (required by Blogr v1.19.0)

## v1.5.1 - 2026-06-09

### 🐛 Bug Fixes

- **URL fields**: Fixed data export and data erasure URL fields not displaying their value in GDPR settings
- **Footer links**: Added "Upgrading" section in README with instructions to re-publish views and clear config cache after update if footer links don't appear

### 📖 Documentation

- **README**: Added upgrade guide with troubleshooting for missing footer links

## v1.5.0 - 2026-06-09

### ✨ Features

- **Security skill**: Dedicated OWASP Top 10 + CVE scanning skill (`security-manager`)
- **Rate limiting**: `throttle:10,60` on all POST routes
- **Authorization gates**: `canAccess()` on GdprSettings, `canViewAny()`/`canEdit()` on resources
- **CSP headers**: Content-Security-Policy, X-Content-Type-Options, X-Frame-Options, Referrer-Policy
- **Async notifications**: `DataRequestNotification` implements `ShouldQueue`
- **Security tests**: 3 new tests (queue, headers, rate limiting)
- **`.env.example`**: Created

## v1.4.3 - 2026-06-09

### 🐛 Bug Fixes

- **CI tests**: Replaced fragile blog layout rendering tests with direct composer logic tests to eliminate parallel test pollution

## v1.4.2 - 2026-06-09

### 🐛 Bug Fixes

- **CI test flakiness**: Added explicit config assertion in analytics consent test to diagnose and prevent parallel test pollution

## v1.4.1 - 2026-06-09

### 🐛 Bug Fixes

- **CI test flakiness**: Reset analytics provider config in beforeEach to prevent cross-test pollution
- **Pint code style**: Fixed style issues in GdrRequestResourceTest

## v1.4.0 - 2026-06-09

### ✨ Features

- **Unified cookie banner**: Single banner with embedded analytics consent — no more superposition
- **Data request links**: "Request my data" and "Request data deletion" in footer with `show_public_link` toggle
- **GDPR Settings help texts**: Every field now has detailed explanations in all languages
- **Provider descriptions**: Pro/con displayed in banner and preferences modal
- **Category descriptions**: "Example:" prefix for clarity (session, cart, etc.)
- **Consent log categories**: New toggleable column showing which categories were accepted
- **Cookie SVG icon**: Fun cookie illustration in the banner

### 🐛 Bug Fixes

- **Analytics gate reappearing**: Force-hide analytics gate when cookie banner is dismissed
- **Marketing line missing**: Restored in banner layout
- **bool type hints**: Removed strict type hints for PHP 8.4 compatibility
- **consent_data.categories TypeError**: Fixed dot notation returning `true` instead of `?array`
- **Cookie/analytics synchronization**: Modal preferences now sync with analytics consent state

## v1.3.0 - 2026-06-09

### ✨ Features

- **Auto-activate analytics gate**: Consent gate now shows automatically when `blogr.analytics.provider` is set — no manual `providers` config needed
- **"Accept All" now also accepts analytics**: Clicking "Accept All" on the cookie banner also sends analytics consent
- **Manage preferences modal**: Analytics toggle is shown in the cookie preferences modal when a provider is configured
- **README**: Added cron maintenance instructions for `blogr-gdpr:prune-logs`

### 🔧 Maintenance

- **Removed `ProcessDataRequests` command**: Data requests must be handled manually through the admin UI

## v1.2.0 - 2026-06-09

### ✨ Features

- **Admin resources**: Added Filament resources for GDPR Data Requests and Consent Logs with list/view/edit
- **Manage preferences**: New link in footer opens the cookie preferences modal with analytics toggle
- **Analytics provider guide**: Detailed comparison of Umami, GA, Plausible, and Matomo in config
- **Extension disable**: Admin navigation dynamically hides GDPR items when extension is disabled

### 🐛 Bug Fixes

- **Livewire registration**: Explicitly register resource pages with Livewire to fix 419 errors
- **Model table names**: Added `$table` to models to match migration table names
- **Infolist display**: Replaced form components with TextEntry for proper data display on view pages
- **Route registration**: Registered plugin via afterResolving to ensure resource routes are built
- **Completed_at**: Auto-set when status changes to completed

## v1.1.4 - 2026-06-08

### 📚 Docs

- **Release skill**: Added preview step to release-manager workflow

## v1.1.3 - 2026-06-08

### 🎨 Style

- **Pint code style**: Fixed 13 PHP code style issues across src and tests

## v1.1.2 - 2026-06-08

### 🔧 CI

- **Code style workflow**: Strip local path repository before installing dependencies

## v1.1.1 - 2026-06-08

### 🔧 CI

- **PHP code style**: Added fix-php-code-style-issues workflow with Pint

## v1.1.0 - 2026-06-08

### ✨ Features

- **Extension disable**: Added ExtensionRegistry integration for runtime disablement

### 🧪 Tests

- **Disablement tests**: Tests for extension enable/disable behavior

### 🔧 CI

- **GitHub Actions**: Tests workflow for PHP 8.3 & 8.4

### 📚 Docs

- **README**: Updated badges and description

### 🔩 Chores

- **OpenCode config**: Agent instructions and release-manager skill

## 1.0.0 - 2026-06-08

### ✨ Features

- **Cookie Consent Banner**: Customizable position (top/bottom), theme (dark/light), granular category selection (essential, analytics, marketing) with "Customize" modal
- **Analytics Consent Gate**: Per-provider control for Google Analytics, Plausible, Umami, Matomo; configurable consent position (head/body)
- **Contact Form Consent**: GDPR checkbox with Alpine.js validation, integrated into Blogr's contact form
- **Privacy Policy Page**: Auto-generated CMS page with per-locale blocks from JSON data files; DPO contact details dynamically injected at render time
- **Data Export & Erasure**: Self-service request forms with DPO email notifications
- **Consent Logging**: Database-backed audit trail with configurable retention (default 365 days)
- **Filament Settings Page**: Full GDPR configuration UI in the admin panel
- **Multilingual**: Complete EN, FR, DE, ES translations
