<?php

declare(strict_types=1);

/**
 * -----------------------------------------------------------------------------
 * HP-eGov: Hệ thống Một cửa điện tử liên thông TP. Hải Phòng
 * Bộ điều khiển giao diện dòng lệnh CLI (Command-Line Interface)
 * -----------------------------------------------------------------------------
 */

// Đảm bảo chỉ chạy trên môi trường CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Lệnh này chỉ được thực thi trên môi trường dòng lệnh (CLI).\n";
    exit(1);
}

define('HPEGOV_START', microtime(true));

// Nạp bootstrap ứng dụng
require_once __DIR__ . '/bootstrap.php';

use App\Services\ChuKySoService;

$command = $argv[1] ?? 'help';

switch ($command) {
    case 'keys:generate':
        echo "======================================================================\n";
        echo " [HP-eGov] KHỞI TẠO CẶP KHÓA & CHỨNG THƯ SỐ HỆ THỐNG (OPENSSL X.509)\n";
        echo "======================================================================\n";
        echo "Đang quét danh sách cán bộ có quyền ký và cơ quan tổ chức...\n\n";

        try {
            $result = ChuKySoService::generateAllSystemKeys();

            echo "✓ Đã khởi tạo thành công:\n";
            echo "  - Chứng thư số cá nhân (LD_PHONG, LD_CO_QUAN, CB_MOT_CUA, VAN_THU): {$result['users_count']} cán bộ\n";
            echo "  - Con dấu số điện tử tổ chức (UBND TP, các Sở, UBND Quận/Huyện):   {$result['orgs_count']} cơ quan\n\n";

            echo "Chi tiết chứng thư số đã cấp:\n";
            echo sprintf("%-4s | %-10s | %-12s | %-32s | %-24s\n", "STT", "LOẠI", "VAI TRÒ", "TÊN CHỦ THỂ", "SỐ SERIAL");
            echo str_repeat("-", 90) . "\n";

            $stt = 1;
            foreach ($result['details'] as $item) {
                echo sprintf(
                    "%-4d | %-10s | %-12s | %-32s | %-24s\n",
                    $stt++,
                    $item['type'],
                    $item['role'],
                    mb_substr($item['name'], 0, 30),
                    $item['serial']
                );
            }

            echo str_repeat("-", 90) . "\n";
            echo "Thư mục lưu trữ khóa riêng và chứng thư: storage/keys/ (Ngoài public root)\n";
            echo "Bảng quản lý chứng thư: chung_thu_so\n";
            echo "Thời gian hoàn thành: " . round(microtime(true) - HPEGOV_START, 3) . "s\n";
            echo "======================================================================\n";
            exit(0);
        } catch (\Throwable $e) {
            echo "\n❌ LỖI KHỞI TẠO KHÓA & CHỨNG THƯ SỐ:\n";
            echo $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
            exit(1);
        }

    case 'help':
    default:
        echo "HP-eGov CLI Runner v1.0\n";
        echo "Cú pháp sử dụng: php app/cli.php <command>\n\n";
        echo "Các lệnh khả dụng:\n";
        echo "  keys:generate    Khởi tạo cặp khóa RSA và chứng thư số X.509 cho cán bộ & cơ quan\n";
        echo "  help             Hiển thị danh sách trợ giúp lệnh\n";
        exit(0);
}
