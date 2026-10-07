<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'HP-eGov'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost/HP-eGov'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    'locale' => env('APP_LOCALE', 'vi'),
    'key' => env('APP_KEY', 'default-insecure-key-change-in-production'),
    'ma_tinh_qcvn102' => env('MA_TINH_QCVN102', 'H21'),
    'integration_mode' => env('INTEGRATION_MODE', 'mock'),
];
