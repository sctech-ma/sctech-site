<?php

declare(strict_types=1);

return [
    'session_name' => env('SESSION_NAME', 'sctech_session'),
    'idle_seconds' => (int) env('SESSION_IDLE_MINUTES', '30') * 60,
    'absolute_seconds' => (int) env('SESSION_ABSOLUTE_HOURS', '8') * 3600,
    'trusted_proxies' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_PROXIES', ''))
    ))),
    'hsts_enabled' => filter_var(env('HSTS_ENABLED', 'false'), FILTER_VALIDATE_BOOL),
    'upgrade_insecure_requests' => filter_var(
        env('CSP_UPGRADE_INSECURE_REQUESTS', 'false'),
        FILTER_VALIDATE_BOOL
    ),
    'form_rate_15_min' => (int) env('FORM_RATE_15_MIN', '5'),
    'form_rate_day' => (int) env('FORM_RATE_DAY', '20'),
    'lead_retention_days' => (int) env('LEAD_RETENTION_DAYS', '0'),
    'consent_policy_version' => env('CONSENT_POLICY_VERSION', 'draft'),
    'upload_max_bytes' => 5 * 1024 * 1024,
    'upload_max_pixels' => 20_000_000,
    'upload_mimes' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
];
