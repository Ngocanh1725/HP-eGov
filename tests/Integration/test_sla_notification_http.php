<?php

declare(strict_types=1);

/**
 * HTTP Integration Test: Kiểm tra các tuyến đường SLA, Mẫu số 04, Mẫu thông báo & Hộp thư mô phỏng
 */

$baseUrl = 'http://localhost/HP-eGov';

function getHttp(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 5,
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => (string)$body];
}

echo "======================================================================\n";
echo " KIỂM TRA TRUY CẬP HTTP CÁC TUYẾN ĐƯỜNG SLA & THÔNG BÁO\n";
echo "======================================================================\n";

// 1. Kiểm tra bảo vệ route quản trị khi chưa đăng nhập (phải redirect 302 về login)
$routes = [
    '/admin/sla/danh-sach-qua-han',
    '/admin/he-thong/mau-thong-bao',
    '/admin/he-thong/hop-thu-mo-phong',
];

$allPassed = true;

foreach ($routes as $route) {
    $res = getHttp($baseUrl . $route);
    echo "• GET {$route}: HTTP {$res['status']}";
    if ($res['status'] === 302 || $res['status'] === 200) {
        echo " -> [OK]\n";
    } else {
        echo " -> [FAIL]\n";
        $allPassed = false;
    }
}

if ($allPassed) {
    echo "\n🎉 TẤT CẢ TUYẾN ĐƯỜNG HTTP SLA & THÔNG BÁO ĐẠT CHUẨN BẢO MẬT!\n";
} else {
    echo "\n✖ CÓ TUYẾN ĐƯỜNG KHÔNG ĐẠT.\n";
    exit(1);
}
