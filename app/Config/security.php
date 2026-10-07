<?php

declare(strict_types=1);

return [
    'rate_limit' => [
        'max_requests' => (int) env('RATE_LIMIT_MAX', 60),
        'decay_seconds' => (int) env('RATE_LIMIT_WINDOW', 60),
    ],
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(self), microphone=(), geolocation=()',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self';",
    ],
    'upload' => [
        'max_size_mb' => 10,
        'allowed_mimes' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
        ],
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'],
    ],
    'auth' => [
        'max_login_attempts' => 5,
        'lockout_minutes' => 15,
        'captcha_threshold' => 3,
        'password_min_length' => 10,
    ],
];
