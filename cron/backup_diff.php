<?php

declare(strict_types=1);

/**
 * HP-eGov - Cron Job: Differential Backup
 * Chu kỳ chạy: 23:00 hằng ngày
 * Trích xuất bản ghi thay đổi từ bản Full gần nhất, lưu trữ tối đa 30 ngày
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\BackupService;

echo "[" . date('Y-m-d H:i:s') . "] Bắt đầu tiến trình Differential Backup định kỳ ngày...\n";

$result = BackupService::runDiffBackup("Differential Backup tự động hàng ngày (Cron Job)");

if ($result['success']) {
    echo "[THÀNH CÔNG] " . $result['message'] . "\n";
    echo "  - Tệp lưu: " . $result['file'] . "\n";
    echo "  - Dung lượng: " . number_format($result['size']) . " bytes\n";
    echo "  - SHA-256: " . $result['sha256'] . "\n";
    echo "  - Thời gian thực thi: " . $result['elapsed_seconds'] . "s\n";

    // Tự động dọn dẹp các bản Diff cũ hơn 30 ngày
    $cleaned = BackupService::cleanOldDiffBackups();
    if ($cleaned > 0) {
        echo "  - Đã tự động dọn dẹp {$cleaned} bản Differential cũ hơn 30 ngày theo quy chế.\n";
    }

    exit(0);
} else {
    echo "[LỖI] " . $result['message'] . "\n";
    exit(1);
}
