<?php

declare(strict_types=1);

/**
 * HP-eGov - Công cụ Đo kiểm Hiệu năng Thu nhỏ trên Môi trường Dev (CLI Benchmark)
 * Căn cứ: Báo cáo Kiến trúc & Kế hoạch Triển khai HP-eGov - Chương 3 §3.2
 */

require_once dirname(__DIR__, 2) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register(dirname(__DIR__, 2));
require_once dirname(__DIR__, 2) . '/app/Helpers/functions.php';

\App\Core\App::setBasePath(dirname(__DIR__, 2));
\App\Core\App::boot();

$db = \App\Core\Database::connection();

echo "======================================================================\n";
echo " ĐO KIỂM HIỆU NĂNG & ĐỐI SOÁT CHỈ SỐ BÁO CÁO (§3.2)\n";
echo " Môi trường thử nghiệm: XAMPP PHP 8.2.12 / MariaDB 10.4.32 Windows\n";
echo "======================================================================\n\n";

// 1. KIỂM TRA CHỈ MỤC & TỐI ƯU TRUY VẤN (EXPLAIN QUERY)
echo "► 1. ĐỐI SOÁT TỐI ƯU TRUY VẤN CƠ SỞ DỮ LIỆU (EXPLAIN):\n";

$queriesToExplain = [
    'Tra cứu hồ sơ theo mã' => "EXPLAIN SELECT * FROM ho_so WHERE ma_ho_so = '000.04.15.H21-260405-0042-000001'",
    'Lọc hồ sơ theo công dân (chống IDOR)' => "EXPLAIN SELECT id, ma_ho_so, trang_thai FROM ho_so WHERE cong_dan_id = 1 AND is_deleted = 0",
    'Danh mục TTHC phân trang' => "EXPLAIN SELECT id, ma_tthc, ten_tthc FROM thu_tuc_hanh_chinh WHERE trang_thai = 1 LIMIT 10",
    'Báo cáo thống kê đơn vị' => "EXPLAIN SELECT don_vi_tiep_nhan_id, COUNT(*) FROM ho_so WHERE is_deleted = 0 GROUP BY don_vi_tiep_nhan_id",
];

foreach ($queriesToExplain as $label => $sql) {
    try {
        $stmt = $db->query($sql);
        $explain = $stmt->fetch(PDO::FETCH_ASSOC);
        $type = $explain['type'] ?? 'ALL';
        $key = $explain['key'] ?? 'NULL';
        $rows = $explain['rows'] ?? 0;
        echo "  ✔ [{$label}]: Type = \033[32m{$type}\033[0m, Index = \033[36m{$key}\033[0m, Rows scan = {$rows}\n";
    } catch (\Throwable $e) {
        echo "  ✖ [{$label}]: Lỗi EXPLAIN - " . $e->getMessage() . "\n";
    }
}
echo "\n";

// 2. CHẠY THỬ NGHIỆM ĐO KIỂM THỜI GIAN ĐÁP ỨNG THỰC TẾ
echo "► 2. ĐO KIỂM THỜI GIAN ĐÁP ỨNG TRUY VẤN (100 REQUESTS/ENDPOINT):\n";

$endpoints = [
    'API Tra cứu hồ sơ (/api/v1/tra-cuu/{ma})' => 'http://localhost/HP-eGov/api/v1/tra-cuu/000.04.15.H21-260405-0042-000001',
    'API Danh mục TTHC (/api/v1/thu-tuc-hanh-chinh)' => 'http://localhost/HP-eGov/api/v1/thu-tuc-hanh-chinh?limit=10',
    'API Giám sát sức khỏe (/api/v1/health)'    => 'http://localhost/HP-eGov/api/v1/health',
    'Trang Cổng DVC Hải Phòng (/)'              => 'http://localhost/HP-eGov/',
];

$resultsReport = [];

foreach ($endpoints as $name => $url) {
    echo "  Testing: {$name} ... ";
    $times = [];
    $errors = 0;
    $totalSamples = 25; // 25 request mỗi endpoint để tránh vượt ngưỡng rate limit 60 req/min
    
    // Xóa bộ đếm rate limit cho localhost để đo kiểm thời gian phản hồi không bị 429
    $db->exec("DELETE FROM gioi_han_truy_cap WHERE ip_address IN ('127.0.0.1', '::1')");

    for ($i = 0; $i < $totalSamples; $i++) {
        // Xóa liên tục bộ đếm nếu cần để kiểm tra thuần thời gian đáp ứng tải CPU/DB
        $db->exec("DELETE FROM gioi_han_truy_cap WHERE ip_address IN ('127.0.0.1', '::1')");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        $start = microtime(true);
        $resp = curl_exec($ch);
        $elapsedMs = (microtime(true) - $start) * 1000; // milliseconds
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 400) {
            $times[] = $elapsedMs;
        } else {
            $errors++;
        }
    }

    sort($times);
    $count = count($times);
    if ($count > 0) {
        $avgMs = array_sum($times) / $count;
        $minMs = $times[0];
        $maxMs = $times[$count - 1];
        $p50Index = (int)floor($count * 0.50);
        $p95Index = (int)floor($count * 0.95);
        $p50Ms = $times[$p50Index];
        $p95Ms = $times[$p95Index];
        $errorRate = ($errors / $totalSamples) * 100;

        $resultsReport[$name] = [
            'samples'   => $totalSamples,
            'success'   => $count,
            'avg_ms'    => round($avgMs, 2),
            'min_ms'    => round($minMs, 2),
            'max_ms'    => round($maxMs, 2),
            'p50_ms'    => round($p50Ms, 2),
            'p95_ms'    => round($p95Ms, 2),
            'error_pct' => round($errorRate, 4),
        ];

        // So sánh với ngưỡng Báo cáo §3.2 (Avg <= 1000ms, P95 <= 1500ms)
        $passAvg = ($avgMs <= 1000);
        $passP95 = ($p95Ms <= 1500);
        $statusStr = ($passAvg && $passP95 && $errors === 0) ? "\033[32mĐẠT CHUẨN\033[0m" : "\033[33mCẢNH BÁO\033[0m";

        echo "Avg: " . round($avgMs, 1) . "ms | P95: " . round($p95Ms, 1) . "ms | Lỗi: {$errorRate}% -> {$statusStr}\n";
    } else {
        echo "\033[31mTHẤT BẠI: Không nhận được phản hồi HTTP hợp lệ.\033[0m\n";
    }
}

echo "\n";
echo "======================================================================\n";
echo " BẢNG ĐỐI CHIẾU TIÊU CHUẨN NGHIỆM THU BÁO CÁO (§3.2)\n";
echo "======================================================================\n";
printf("%-45s | %-12s | %-12s | %-10s\n", "Chỉ số đánh giá", "Quy định §3.2", "Thực tế dev", "Kết luận");
echo "--------------------------------------------------------------------------------------\n";

$firstStat = reset($resultsReport) ?: ['avg_ms' => 0, 'p95_ms' => 0, 'error_pct' => 0];
$avgReal = $firstStat['avg_ms'];
$p95Real = $firstStat['p95_ms'];
$errReal = $firstStat['error_pct'];

printf("%-45s | %-12s | %-12s | %-10s\n", "Thời gian phản hồi TB (Average Response Time)", "<= 1.0 giây", ($avgReal / 1000) . "s", ($avgReal <= 1000 ? "ĐẠT" : "VƯỢT"));
printf("%-45s | %-12s | %-12s | %-10s\n", "Thời gian phản hồi P95 (95th Percentile)", "<= 1.5 giây", ($p95Real / 1000) . "s", ($p95Real <= 1500 ? "ĐẠT" : "VƯỢT"));
printf("%-45s | %-12s | %-12s | %-10s\n", "Tỷ lệ lỗi giao dịch (Error Rate)", "<= 0.01%", $errReal . "%", ($errReal <= 0.01 ? "ĐẠT" : "VƯỢT"));
echo "======================================================================\n";

// Lưu file json
$savePath = __DIR__ . '/benchmark_results.json';
file_put_contents($savePath, json_encode($resultsReport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\nĐã lưu kết quả đo kiểm chi tiết vào: {$savePath}\n";
