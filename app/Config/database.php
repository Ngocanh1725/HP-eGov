<?php

declare(strict_types=1);

return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            // Cấu hình ghi (Master Write)
            'write' => [
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => (int) env('DB_PORT', 3306),
                'database' => env('DB_DATABASE', 'hp_egov'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ],
            // Cấu hình đọc (Replica Read) - nếu trống sẽ dùng cấu hình write
            'read' => [
                'host' => env('DB_READ_HOST', ''),
                'port' => (int) env('DB_READ_PORT', 3306),
                'database' => env('DB_READ_DATABASE', 'hp_egov'),
                'username' => env('DB_READ_USERNAME', 'root'),
                'password' => env('DB_READ_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ],
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],
];
