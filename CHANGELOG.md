# Changelog

All notable changes to `blogr-gdpr` will be documented in this file.

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
