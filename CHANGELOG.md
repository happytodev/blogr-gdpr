# Changelog

All notable changes to `blogr-gdpr` will be documented in this file.

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
