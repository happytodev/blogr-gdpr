<?php

/*
 * Blogr GDPR – Analytics Provider Guide
 * ---------------------------------------
 * The analytics consent gate is automatically activated when a provider is
 * configured in Blogr's own analytics settings (`blogr.analytics.provider`).
 * The `providers` array below is optional — if left empty, the gate still
 * works based on which provider Blogr is set to use.
 *
 * Overview of supported providers:
 *
 * ── Umami ──────────────────────────────────────────────────────────────
 *   • Privacy-first, no cookies, no personal data collected
 *   • Lightweight, real-time dashboard
 *   • 🇪🇺 GDPR-compliant by design (can rely on legitimate interest)
 *   • ─ Self-hosted or cloud
 *   • ✗ Fewer advanced features than GA (no funnels, attribution, etc.)
 *
 * ── Google Analytics ───────────────────────────────────────────────────
 *   • Powerful: segments, funnels, attribution, audiences
 *   • Free tier available
 *   • ─ Collects extensive browsing data, requires explicit consent
 *   • ─ Uses cookies (GDPR consent needed), page-load overhead
 *   • ─ Data hosted on US servers (Schrems II considerations)
 *
 * ── Plausible ──────────────────────────────────────────────────────────
 *   • Lightweight, cookie-free, privacy-first
 *   • 🇪🇺 GDPR-compliant by design (no consent required)
 *   • Clean dashboard, great for content-driven sites
 *   • ─ Self-hosted or cloud
 *   • ✗ Less advanced than GA (no funnel analysis, native ecommerce)
 *
 * ── Matomo ─────────────────────────────────────────────────────────────
 *   • Full-featured open-source alternative to GA
 *   • Self-hosted → full data control, GDPR-compliant
 *   • Ecommerce, funnels, heatmaps, session recordings
 *   • ─ Requires a database; heavier to maintain
 *   • ✗ Cloud version available but hosted externally
 */

return [
    'enabled' => env('BLOGR_GDPR_ENABLED', true),

    'cookie_consent' => [
        'enabled' => true,
        'required' => true,
        'position' => 'bottom',
        'theme' => 'dark',
        'info_url' => '',
        'categories' => [
            'essential' => [
                'label' => 'Essential',
                'description' => 'Required for the site to function properly.',
                'required' => true,
                'default' => true,
            ],
            'analytics' => [
                'label' => 'Analytics',
                'description' => 'Help us understand how visitors interact with our site.',
                'required' => false,
                'default' => false,
            ],
            'marketing' => [
                'label' => 'Marketing',
                'description' => 'Used to deliver relevant advertisements.',
                'required' => false,
                'default' => false,
            ],
        ],
    ],

    'analytics_consent' => [
        'enabled' => true,
        'required' => true,
        'position' => 'body',
        'providers' => [],
    ],

    'contact_consent' => [
        'enabled' => true,
        'required' => true,
    ],

    'privacy_policy' => [
        'auto_create' => true,
        'version' => 1,
        'updated_at' => null,
    ],

    'dpo' => [
        'name' => '',
        'email' => '',
        'address' => '',
    ],

    'data_export' => [
        'enabled' => true,
        'show_public_link' => true,
    ],

    'data_erasure' => [
        'enabled' => true,
        'show_public_link' => true,
    ],

    'consent_log' => [
        'enabled' => true,
        'retention_days' => 365,
    ],
];
