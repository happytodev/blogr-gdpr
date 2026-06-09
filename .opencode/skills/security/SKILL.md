---
name: security-manager
description: OWASP Top 10 reviews, CVE scanning, and security hardening for Blogr GDPR.
---

## When to use

Trigger phrases: "security review", "audit security", "run security scan", "check CVE", "OWASP",
"is this secure", "vulnerability", "harden", "rate limiting", "authorization".

## Workflow

### 1. Run CVE / vulnerability scanning

```bash
# PHP dependencies
composer audit --format=json | jq '.advisories'
composer show --latest --outdated 2>/dev/null | grep -E "Latest|⚠"

# JavaScript dependencies (if package.json present)
npm audit --audit-level=high 2>/dev/null || true

# GitHub Dependabot alerts
gh api /repos/happytodev/blogr-gdpr/dependabot/alerts --jq '.[] | {package, severity, state}'

# Secrets leak check (keys, tokens, passwords in source)
grep -rn "sk-[a-zA-Z0-9]\{20,\}\|pk-[a-zA-Z0-9]\{20,\}\|SG\.[a-zA-Z0-9]\{20,\}" src/ resources/ config/ 2>/dev/null || echo "No secrets found"
```

### 2. OWASP Top 10 Audit Checklist

| # | OWASP | What to check in Blogr GDPR | Commands |
|---|---|---|---|
| **A01** | Broken Access Control | `GdprSettings::canAccess()`, resource `canViewAny()`/`canEdit()` | `grep -rn "canAccess\|canViewAny\|canEdit\|canDelete" src/Filament/` |
| **A02** | Cryptographic Failures | CSRF tokens in all forms + fetch calls, HTTPS enforced, session secure cookie | `grep -rn "X-CSRF\|csrf_token\|@csrf" resources/views/` |
| **A03** | Injection | `{!! !!}` usage, raw SQL queries, user input in Blade | `grep -rn "{!!\|\!\!}" resources/views/` |
| **A04** | Insecure Design | Rate limiting on routes, DPO notification throttling, de-duplication | `grep -rn "throttle\|RateLimiter" routes/ src/` |
| **A05** | Security Misconfiguration | `APP_DEBUG` in .env, CORS config, debug bar, exposed info | `grep -rn "APP_DEBUG\|APP_ENV" .env* 2>/dev/null \|\| true` |
| **A06** | Vulnerable Components | `composer audit`, outdated deps, Dependabot alerts | See step 1 |
| **A07** | Identification Failures | Session lifetime, lockout, 2FA (Filament config in host app) | Check host app's `config/filament.php` |
| **A08** | Data Integrity Failures | CSP headers, signed URLs, SSL/TLS | `grep -rn "Content-Security-Policy\|CSP\|\.honeypot" src/` |
| **A09** | Security Logging | Consent log retention, audit trail completeness, monitoring | Check `blogr_gdpr_consent_logs` table |
| **A10** | SSRF | External HTTP requests from Guzzle/fetch | `grep -rn "guzzle\|Http::client\|curl\b" src/` |

### 3. Hardening actions (fix order by severity)

#### HIGH — Rate limiting on public POST routes
All `web` routes (`/gdpr/consent`, `/gdpr/withdraw`, `/gdpr/data-export`, `/gdpr/data-erasure`) must have a throttle
middleware to prevent abuse:

```php
// In src/BlogrGdprServiceProvider.php boot() or routes/web.php
Route::middleware(['web', 'throttle:10,60'])->group(function () {
    // existing routes
});
```

#### HIGH — Authorization gates on Filament resources
Each resource must define `canViewAny()` based on Filament user permissions or at minimum check the user is authenticated:

```php
public static function canViewAny(): bool
{
    return auth()->check() && auth()->user()->can('view_gdpr');
}
```

#### HIGH — `canAccess()` on GdprSettings
```php
public static function canAccess(): bool
{
    return auth()->check() && auth()->user()->can('view_gdpr_settings');
}
```

#### MEDIUM — Notification queue
Use queue for DPO email notifications to avoid blocking the HTTP response:

```php
Notification::route('mail', config('blogr-gdpr.dpo.email'))
    ->notify((new DataRequestNotification($requestRecord))->delay(now()->addSeconds(5)));
```

#### MEDIUM — CSP headers
Add Content-Security-Policy header to public routes:

```php
// In a middleware or controller constructor
$response->header('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'");
```

#### LOW — Add .env.example
```bash
echo "BLOGR_GDPR_ENABLED=true" > .env.example
```

### 4. Verification tests (TDD)

When fixing a security issue, write a test that proves the vulnerability is closed:

```php
it('rate limits consent endpoint', function () {
    for ($i = 0; $i < 15; $i++) {
        $this->post(route('gdpr.consent'), ['consent_type' => 'cookies']);
    }
    $this->post(route('gdpr.consent'), ['consent_type' => 'cookies'])
        ->assertStatus(429); // Too Many Requests
});

it('blocks unauthenticated access to gdpr settings', function () {
    $this->get(route('filament.admin.pages.gdpr-settings'))
        ->assertRedirect(route('filament.admin.auth.login'));
});
```

### 5. Release security checks (pre-release gate)

Before any release, run:

```bash
# 1. CVE scan
composer audit --no-ansi
[ $? -eq 0 ] || { echo "STOP: vulnerabilities found"; exit 1; }

# 2. Secrets check
grep -rn "sk-[a-zA-Z0-9]\{20,\}\|pk-[a-zA-Z0-9]\{20,\}" src/ resources/ config/ && { echo "STOP: secrets in source"; exit 1; }

# 3. Authorization check
grep -rn "class.*extends.*Resource\|class.*extends.*Page" src/Filament/ | while read line; do
  file=$(echo "$line" | cut -d: -f1)
  grep -q "canAccess\|canViewAny" "$file" || echo "WARNING: $file has no access control"
done

# 4. No raw SQL / unescaped output
grep -rn "DB::raw\|DB::statement\|{!!\|!!}" src/ | grep -v "vendor\|\.blade\.php" && { echo "STOP: raw SQL or unescaped output"; exit 1; }

# 5. Full tests
vendor/bin/pest || { echo "STOP: tests failing"; exit 1; }

echo "✅ Security gate passed"
```

### 6. Remediation reference

| Issue | Fix PR required | Tracked as |
|---|---|---|
| No rate limiting | `routes/web.php` + `throttle` middleware | Security issue #1 |
| No canAccess() on GdprSettings | `src/Filament/Pages/GdprSettings.php` | Security issue #2 |
| No canViewAny() on resources | `src/Filament/Resources/*.php` | Security issue #3 |
| Sync notifications | `DataRequestNotification` → queue | Security issue #4 |
| No CSP headers | Middleware or controller | Security issue #5 |
