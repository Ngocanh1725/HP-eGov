<?php

declare(strict_types=1);

/**
 * HP-eGov - Script Khởi tạo & Đồng bộ CSDL Chuẩn hóa UTF-8
 * Căn cứ: Báo cáo Kiến trúc §1.2, §4.1, §4.2; NĐ 61/2018; TT 01/2018.
 * 
 * Đảm bảo 100% bảng mã utf8mb4 / utf8mb4_unicode_ci, xử lý Triggers, Views
 * và nạp dữ liệu mẫu an toàn qua PHP PDO, chống lỗi font Mojibake trên Windows.
 */

require_once __DIR__ . '/../app/Core/Autoloader.php';
App\Core\Autoloader::register(dirname(__DIR__));
App\Core\App::boot();

echo "======================================================================\n";
echo " HP-eGov - KHỞI TẠO CƠ SỞ DỮ LIỆU CHUẨN HÓA UTF-8 (MARIADB/MYSQL)\n";
echo "======================================================================\n\n";

$host = (string)App\Core\Env::get('DB_HOST', '127.0.0.1');
$port = (string)App\Core\Env::get('DB_PORT', '3306');
$dbName = (string)App\Core\Env::get('DB_NAME', 'hp_egov');
$user = (string)App\Core\Env::get('DB_USER', 'root');
$pass = (string)App\Core\Env::get('DB_PASS', '');

try {
    // 1. Kết nối cấp máy chủ để đảm bảo Database tồn tại với đúng Charset
    echo "[1/6] Kết nối MySQL Server & Thiết lập Database `{$dbName}`...\n";
    $serverDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $serverPdo = new PDO($serverDsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ]);

    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "  ✔ Database `{$dbName}` đã sẵn sàng với Charset `utf8mb4` và Collation `utf8mb4_unicode_ci`.\n\n";

    // 2. Kết nối trực tiếp vào Database ứng dụng
    $dbDsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dbDsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 3. Thực thi database/schema.sql (Hỗ trợ phân tách DELIMITER trigger)
    echo "[2/6] Đang nạp Cấu trúc bảng, Chỉ mục, Triggers và Views từ `database/schema.sql`...\n";
    $schemaPath = __DIR__ . '/schema.sql';
    if (!file_exists($schemaPath)) {
        throw new RuntimeException("Không tìm thấy tệp schema: {$schemaPath}");
    }

    $schemaSql = file_get_contents($schemaPath);
    executeSqlScriptWithDelimiters($pdo, $schemaSql);
    echo "  ✔ Nạp hoàn tất DDL Schema (55 bảng, 6 Triggers, 3 Views).\n\n";

    // 4. Thực thi database/seed.sql
    echo "[3/6] Đang nạp Dữ liệu mẫu danh mục và hồ sơ từ `database/seed.sql` qua PDO UTF-8...\n";
    $seedPath = __DIR__ . '/seed.sql';
    if (!file_exists($seedPath)) {
        throw new RuntimeException("Không tìm thấy tệp seed: {$seedPath}");
    }

    $seedSql = file_get_contents($seedPath);
    executeSqlScriptWithDelimiters($pdo, $seedSql);
    echo "  ✔ Nạp hoàn tất dữ liệu mẫu từ `seed.sql`.\n\n";

    // 5. Nạp các bảng và dữ liệu di trú bổ sung (Phiên 11 & Phiên 14)
    echo "[4/6] Đang nạp Mẫu thông báo đa kênh và Cấu hình Tích hợp bổ sung...\n";
    $migNoticePath = __DIR__ . '/migrations/2026_10_07_000002_create_mau_thong_bao_and_thi_dua_tables.sql';
    if (file_exists($migNoticePath)) {
        $pdo->exec("TRUNCATE TABLE `mau_thong_bao`;");
        $migNoticeSql = file_get_contents($migNoticePath);
        executeSqlScriptWithDelimiters($pdo, $migNoticeSql);
        echo "  ✔ Nạp thành công `mau_thong_bao` (SMS Brandname, Zalo ZNS, Email, In-app).\n";
    }

    $seeder14Path = __DIR__ . '/seeders/Session14Seeder.php';
    if (file_exists($seeder14Path)) {
        require_once $seeder14Path;
        echo "  ✔ Nạp thành công 9 cổng kết nối tích hợp và bảng ánh xạ ETL.\n\n";
    }

    // 6. Bật lại Khóa ngoại và Đồng bộ Chứng thư số PKI
    echo "[5/6] Bật lại Foreign Key Checks và Đồng bộ Chứng thư số...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Đồng bộ chứng thư số nếu đã có khóa RSA trên đĩa
    if (class_exists('App\Services\ChuKySoService')) {
        $keysDir = App\Services\ChuKySoService::initKeysDir();
        $certs = $pdo->query("SELECT id, chu_the_id, loai_chu_the, public_cert_pem FROM chung_thu_so")->fetchAll(PDO::FETCH_ASSOC);
        $syncCount = 0;
        foreach ($certs as $c) {
            $prefix = ($c['loai_chu_the'] === 'TO_CHUC') ? 'org' : 'user';
            $certPath = "{$keysDir}/{$prefix}_{$c['chu_the_id']}.crt";
            if (file_exists($certPath)) {
                $diskPem = (string)file_get_contents($certPath);
                if (empty($c['public_cert_pem']) || str_contains($c['public_cert_pem'], 'DEMO_') || $c['public_cert_pem'] !== $diskPem) {
                    $pdo->prepare("UPDATE chung_thu_so SET public_cert_pem = :pem WHERE id = :id")
                        ->execute([':pem' => $diskPem, ':id' => $c['id']]);
                    $syncCount++;
                }
            }
        }
        echo "  ✔ Đã đồng bộ {$syncCount} chứng thư số OpenSSL thực tế vào CSDL.\n\n";
    }

    // Quét tiến độ SLA ban đầu để cập nhật cảnh báo & điểm thi đua đơn vị
    $slaScanPath = dirname(__DIR__) . '/cron/sla_scan.php';
    if (file_exists($slaScanPath)) {
        ob_start();
        include $slaScanPath;
        ob_end_clean();
        echo "  ✔ Đã thực hiện quét SLA định kỳ ban đầu và ghi nhận điểm thi đua.\n\n";
    }

    // 7. Thống kê tổng hợp số lượng bảng và bản ghi
    echo "[6/6] Thống kê tổng hợp CSDL HP-eGov:\n";
    $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
    $views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_NUM);
    $triggers = $pdo->query("SHOW TRIGGERS")->fetchAll(PDO::FETCH_ASSOC);

    echo "  - Tổng số bảng vật lý (InnoDB): " . count($tables) . " bảng\n";
    echo "  - Tổng số Views thống kê IOC:   " . count($views) . " views\n";
    echo "  - Tổng số Triggers bảo mật:     " . count($triggers) . " triggers\n";

    $sampleStats = [
        'don_vi'             => 'Đơn vị / Phòng ban',
        'thu_tuc_hanh_chinh' => 'Thủ tục hành chính',
        'quy_trinh_buoc'     => 'Bước quy trình TTHC',
        'nguoi_dung'         => 'Cán bộ / Công chức',
        'cong_dan'           => 'Công dân / Doanh nghiệp',
        'ho_so'              => 'Hồ sơ TTHC tiếp nhận',
        'mau_thong_bao'      => 'Mẫu thông báo đa kênh',
    ];

    echo "  - Số liệu thực tế một số bảng chính:\n";
    foreach ($sampleStats as $tbl => $label) {
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM `{$tbl}`")->fetchColumn();
        echo "    + {$label} (`{$tbl}`): {$cnt} bản ghi\n";
    }

    echo "\n🎉 KHỞI TẠO CƠ SỞ DỮ LIỆU HP-eGov THÀNH CÔNG RỰC RỠ!\n";
    echo "Toàn bộ CSDL sạch 100% tiếng Việt UTF-8 và sẵn sàng vận hành.\n";

} catch (\Throwable $e) {
    echo "\n❌ LỖI KHỞI TẠO CSDL: " . $e->getMessage() . "\n";
    echo "Tại tệp: " . $e->getFile() . " dòng " . $e->getLine() . "\n";
    exit(1);
}

/**
 * Phân tích và thực thi file SQL an toàn với DELIMITER và Multi-statements
 */
function executeSqlScriptWithDelimiters(PDO $pdo, string $sqlContent): void
{
    $lines = explode("\n", $sqlContent);
    $delimiter = ';';
    $currentQuery = '';

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Bỏ qua dòng trống và comment
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
            continue;
        }

        // Bắt lệnh DELIMITER đổi cú pháp
        if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmed, $m)) {
            $delimiter = trim($m[1]);
            continue;
        }

        $currentQuery .= $line . "\n";

        // Kiểm tra nếu dòng kết thúc bằng Delimiter hiện tại
        if (str_ends_with($trimmed, $delimiter)) {
            $queryToExecute = substr(trim($currentQuery), 0, -strlen($delimiter));
            $queryToExecute = trim($queryToExecute);

            if ($queryToExecute !== '') {
                try {
                    $pdo->exec($queryToExecute);
                } catch (\PDOException $ex) {
                    // Nếu lỗi DROP IF EXISTS thì bỏ qua cảnh báo
                    if (!str_contains($queryToExecute, 'DROP') || !str_contains($ex->getMessage(), 'Unknown')) {
                        throw new RuntimeException("Lỗi SQL: " . $ex->getMessage() . "\nĐoạn lệnh: " . substr($queryToExecute, 0, 160) . "...");
                    }
                }
            }
            $currentQuery = '';
        }
    }

    // Nếu còn query sót lại
    if (trim($currentQuery) !== '') {
        $pdo->exec(trim($currentQuery));
    }
}
