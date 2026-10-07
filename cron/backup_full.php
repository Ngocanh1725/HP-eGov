<?php

declare(strict_types=1);

/**
 * HP-eGov - Cron Job: Full Database Backup
 * Chu kỳ chạy: 00:00 Chủ nhật hàng tuần (qua Windows Task Scheduler / schtasks)
 * Xuất CSDL qua mysqldump --single-transaction, tính mã SHA-256, sao chép sang DR Site
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\BackupService;

echo "[" . date('Y-m-d H:i:s') . "] Bắt đầu tiến trình Full Backup định kỳ tuần...\n";

$result = BackupService::runFullBackup("Full Backup tự động định kỳ tuần (Cron Job)");

if ($result['success']) {
    echo "[THÀNH CÔNG] " . $result['message'] . "\n";
    echo "  - Tệp lưu: " . $result['file'] . "\n";
    echo "  - Dung lượng: " . number_format($result['size']) . " bytes\n";
    echo "  - SHA-256: " . $result['sha256'] . "\n";
    echo "  - Thời gian thực thi: " . $result['elapsed_seconds'] . "s\n";
    exit(0);
} else {
    echo "[LỖI] " . $result['message'] . "\n";
    exit(1);
}
