<?php

declare(strict_types=1);

/**
 * HP-eGov - Cron Job: Transaction Log Backup
 * Chu kỳ chạy: 2 giờ/lần (bảo đảm cam kết RPO ≤ 2 giờ)
 * Trích xuất log thay đổi CSDL nhat_ky_kiem_toan hoặc sao chép MySQL binlog
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\BackupService;

echo "[" . date('Y-m-d H:i:s') . "] Bắt đầu tiến trình Transaction Log Backup (RPO ≤ 2h)...\n";

$result = BackupService::runLogBackup("Transaction Log tự động 2h/lần (Cron Job)");

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
