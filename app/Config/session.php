<?php

declare(strict_types=1);

return [
    'name' => env('SESSION_COOKIE_NAME', 'hpegov_session'),
    'lifetime' => (int) env('SESSION_LIFETIME', 1800), // 30 phút theo chuẩn an toàn
    'path' => '/',
    'domain' => '',
    'secure' => (bool) env('SESSION_SECURE_COOKIE', false),
    'httponly' => true,
    'samesite' => 'Lax', // hoặc Strict tùy khu vực
];
