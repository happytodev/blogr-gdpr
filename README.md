<div align="center">

# Blogr GDPR

[![Latest Version](https://img.shields.io/packagist/v/happytodev/blogr-gdpr.svg?style=flat-square)](https://packagist.org/packages/happytodev/blogr-gdpr)
[![Tests](https://img.shields.io/github/actions/workflow/status/happytodev/blogr-gdpr/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/happytodev/blogr-gdpr/actions)
[![Fix PHP code style](https://img.shields.io/github/actions/workflow/status/happytodev/blogr-gdpr/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/happytodev/blogr-gdpr/actions)
[![PHP Version](https://img.shields.io/packagist/php-v/happytodev/blogr-gdpr?style=flat-square)](https://packagist.org/packages/happytodev/blogr-gdpr)
[![Downloads](https://img.shields.io/packagist/dt/happytodev/blogr-gdpr?style=flat-square)](https://packagist.org/packages/happytodev/blogr-gdpr)
[![GitHub Stars](https://img.shields.io/github/stars/happytodev/blogr-gdpr?style=flat-square)](https://github.com/happytodev/blogr-gdpr)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](https://opensource.org/licenses/MIT)

**GDPR compliance plugin for Blogr CMS**

Cookie consent banner, privacy policy pages, data export & erasure requests, and consent logging — all fully integrated with Blogr's multilingual CMS.

</div>

---

## Features

- **Cookie Consent Banner** – Customizable position (top/bottom), theme (dark/light), and granular category selection (essential, analytics, marketing) with a "Customize" modal
- **Privacy Policy** – Auto-generated CMS page with per-locale content via the Blogr block builder; DPO contact details injected dynamically
- **Analytics Consent** – Gate for Google Analytics, Plausible, Umami, Matomo with per-provider control and configurable position
- **Contact Form Consent** – GDPR checkbox integrated into Blogr's contact form with Alpine.js validation
- **Data Export & Erasure** – Self-service request forms with email notifications to the DPO
- **Consent Logging** – Database-backed audit trail with configurable retention period
- **Filament Admin** – Full GDPR settings page in the Filament admin panel
- **Multilingual** – EN, FR, DE, ES translations included (extensible)

## Requirements

- PHP ^8.3
- Blogr ^1.3

## Installation

```bash
composer require happytodev/blogr-gdpr
```

Run the package migrations:

```bash
php artisan migrate:status
php artisan migrate
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=blogr-gdpr-config
```

Configure your DPO details in `config/blogr-gdpr.php`:

```php
'dpo' => [
    'name' => 'Your DPO Name',
    'email' => 'dpo@example.com',
    'address' => '123 Main Street, City, Country',
],
```

The analytics consent gate is automatically enabled when you configure a provider in Blogr's analytics settings — no additional setup needed.

## Maintenance

Prune expired consent log entries (recommended as a daily cron job):

```bash
0 3 * * * cd /path/to/project && php artisan blogr-gdpr:prune-logs >> /dev/null 2>&1
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
