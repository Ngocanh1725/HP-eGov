<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$pdo = Database::pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, true);

echo "1. Đặt session charset UTF-8 và tắt Foreign Key Checks...\n";
$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

echo "2. Đọc file database/seed.sql...\n";
$seedSql = file_get_contents(__DIR__ . '/seed.sql');
if ($seedSql === false) {
    die("LỖI: Không tìm thấy seed.sql\n");
}

echo "3. Thực thi seed.sql (Kích thước: " . strlen($seedSql) . " bytes)...\n";
// Để đảm bảo từng câu lệnh chạy trơn tru, chia theo regex hoặc chạy qua multi-statements
try {
    $pdo->exec($seedSql);
    // Flush any remaining active result sets từ multi-queries
    while ($pdo->query("SELECT 1")) {
        break;
    }
    echo " -> Đã nạp thành công database/seed.sql!\n";
} catch (\Throwable $e) {
    echo " -> Lỗi khi chạy multi-statement: " . $e->getMessage() . "\n";
    echo " -> Thử chạy từng câu lệnh riêng lẻ...\n";

    // Phân tích cú pháp split chuẩn
    $lines = explode("\n", $seedSql);
    $query = '';
    $successCount = 0;

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
            continue;
        }

        $query .= $line . "\n";
        if (str_ends_with($trimmed, ';')) {
            try {
                $pdo->exec($query);
                $successCount++;
            } catch (\Throwable $ex) {
                echo "Lỗi câu lệnh (dòng): " . $ex->getMessage() . "\nQuery snippet: " . substr($query, 0, 100) . "...\n";
            }
            $query = '';
        }
    }
    echo " -> Đã chạy $successCount câu lệnh riêng lẻ!\n";
}

echo "4. Nạp lại mau_thong_bao từ database/migrations/2026_10_07_000002_create_mau_thong_bao_and_thi_dua_tables.sql...\n";
$migrationFile = __DIR__ . '/migrations/2026_10_07_000002_create_mau_thong_bao_and_thi_dua_tables.sql';
if (file_exists($migrationFile)) {
    $migrationSql = file_get_contents($migrationFile);
    // Truncate mau_thong_bao và nạp lại
    $pdo->exec("TRUNCATE TABLE mau_thong_bao;");
    $pdo->exec($migrationSql);
    echo " -> Đã nạp lại mau_thong_bao thành công!\n";
}

echo "5. Bật lại Foreign Key Checks...\n";
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

echo "6. Chạy Session14Seeder...\n";
require_once __DIR__ . '/seeders/Session14Seeder.php';

echo "=== HOÀN TẤT RE-IMPORT DỮ LIỆU UTF-8 ===\n";
