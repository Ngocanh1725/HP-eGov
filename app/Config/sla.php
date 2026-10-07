<?php

declare(strict_types=1);

return [
    'warning_percent' => (int) env('SLA_WARNING_PERCENT', 75), // Ngưỡng Vàng: >= 75% thời hạn
    'critical_hours' => (int) env('SLA_CRITICAL_HOURS', 24),    // Ngưỡng Vàng: còn lại <= 24h làm việc
    'working_hours' => [
        'morning_start' => '07:30',
        'morning_end' => '11:30',
        'afternoon_start' => '13:30',
        'afternoon_end' => '17:30',
        'hours_per_day' => 8,
    ],
];
