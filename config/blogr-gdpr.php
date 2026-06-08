<?php

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
    ],

    'data_erasure' => [
        'enabled' => true,
    ],

    'consent_log' => [
        'enabled' => true,
        'retention_days' => 365,
    ],
];
