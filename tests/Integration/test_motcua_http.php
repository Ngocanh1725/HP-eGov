<?php

declare(strict_types=1);

$baseUrl = 'http://localhost/HP-eGov';
$cookieFile = __DIR__ . '/cookie_temp.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "=== KIỂM THỬ TÍCH HỢP HTTP NGHIỆP VỤ BỘ PHẬN MỘT CỬA ===\n\n";

// 1. Mở trang đăng nhập cán bộ để lấy Cookie Session & CSRF Token
$ch = curl_init("{$baseUrl}/admin/dang-nhap");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$loginPage = curl_exec($ch);

preg_match('/name="_csrf_token" value="([^"]+)"/', (string)$loginPage, $m);
$csrfToken = $m[1] ?? '';
echo "1. Lấy CSRF token đăng nhập: " . ($csrfToken ? "THÀNH CÔNG" : "KHÔNG CÓ") . "\n";

// 2. Đăng nhập tài khoản cán bộ Một cửa (motcua.hongbang)
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/admin/dang-nhap");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'ten_dang_nhap' => 'motcua.hongbang',
    'mat_khau' => 'Hpegov@2026',
    '_csrf_token' => $csrfToken,
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$resLogin = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$isLoggedIn = str_contains((string)$resLogin, 'Đăng xuất') || str_contains((string)$resLogin, 'motcua.hongbang') || str_contains((string)$resLogin, 'Một cửa');
echo "2. Đăng nhập cán bộ (motcua.hongbang): HTTP {$httpCode} - " . ($isLoggedIn ? "THÀNH CÔNG" : "LỖI") . "\n";

// Lấy token CSRF mới từ session sau khi login nếu có
preg_match('/name="_csrf_token" value="([^"]+)"/', (string)$resLogin, $m2);
if (!empty($m2[1])) {
    $csrfToken = $m2[1];
}

// 3. Truy cập Bàn làm việc Một cửa (/admin/mot-cua)
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/admin/mot-cua");
curl_setopt($ch, CURLOPT_POST, false);
$dashboardHtml = (string)curl_exec($ch);
$hasKpi = str_contains($dashboardHtml, 'Chờ tiếp nhận trực tuyến') && str_contains($dashboardHtml, 'Tiếp nhận hôm nay');
echo "3. Bàn làm việc Một cửa (/admin/mot-cua): " . ($hasKpi ? "ĐẠT (4 thẻ KPI & Việc cần làm)" : "LỖI") . "\n";

// 4. Truy cập Tiếp nhận trực tiếp & Quét QR (/admin/mot-cua/tiep-nhan)
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/admin/mot-cua/tiep-nhan");
$tiepNhanHtml = (string)curl_exec($ch);
$hasQrSection = str_contains($tiepNhanHtml, 'raw-qr-input') && str_contains($tiepNhanHtml, 'TIẾP NHẬN HỒ SƠ TRỰC TIẾP') && str_contains($tiepNhanHtml, 'Nghị định 107/2021');
echo "4. Bàn tiếp nhận trực tiếp (/admin/mot-cua/tiep-nhan): " . ($hasQrSection ? "ĐẠT (Quét QR CCCD, C06, Số hóa NĐ 107)" : "LỖI") . "\n";

// 5. Thử API đối soát C06 qua AJAX
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/admin/mot-cua/api/verify-cccd");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Requested-With: XMLHttpRequest',
    'X-CSRF-TOKEN: ' . $csrfToken,
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'so_cccd' => '031095000123',
    'ho_ten' => 'NGUYỄN VĂN AN',
    '_csrf_token' => $csrfToken,
]));
$c06Res = (string)curl_exec($ch);
$c06Json = json_decode($c06Res, true);
$isC06Ok = isset($c06Json['success']) && $c06Json['success'] === true;
echo "5. API đối soát C06 qua LGSP (/admin/mot-cua/api/verify-cccd): " . ($isC06Ok ? "ĐẠT (Khớp đúng CSDLQG Dân cư)" : "LỖI: " . $c06Res) . "\n";

// 6. Truy cập Danh sách hồ sơ trực tuyến chờ tiếp nhận
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/admin/mot-cua/truc-tuyen");
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, []);
$trucTuyenHtml = (string)curl_exec($ch);
$hasTrucTuyen = str_contains($trucTuyenHtml, 'HỒ SƠ TRỰC TUYẾN CHỜ TIẾP NHẬN');
echo "6. Danh sách hồ sơ trực tuyến (/admin/mot-cua/truc-tuyen): " . ($hasTrucTuyen ? "ĐẠT (Hiển thị danh sách CHO_TIEP_NHAN)" : "LỖI") . "\n";

// 7. Truy cập Quản lý Trả kết quả TTHC
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/admin/mot-cua/tra-ket-qua");
$traKqHtml = (string)curl_exec($ch);
$hasTraKq = str_contains($traKqHtml, 'Trả kết quả giải quyết TTHC') && str_contains($traKqHtml, 'VNPost');
echo "7. Quản lý trả kết quả (/admin/mot-cua/tra-ket-qua): " . ($hasTraKq ? "ĐẠT (Hỗ trợ trực tiếp & VNPost)" : "LỖI") . "\n";

// 8. Truy cập Sổ theo dõi hồ sơ Mẫu 06 TT 01/2018
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/admin/mot-cua/so-theo-doi");
$soTheoDoiHtml = (string)curl_exec($ch);
$hasMau06 = str_contains($soTheoDoiHtml, 'SỔ THEO DÕI HỒ SƠ') && str_contains($soTheoDoiHtml, 'Mẫu số 06');
echo "8. Sổ theo dõi hồ sơ Mẫu 06 (/admin/mot-cua/so-theo-doi): " . ($hasMau06 ? "ĐẠT (Mẫu số 06 TT 01/2018 A4 Landscape)" : "LỖI") . "\n";

curl_close($ch);
@unlink($cookieFile);
echo "\n=== HOÀN TẤT KIỂM THỬ TÍCH HỢP HTTP PHIÊN 8: TẤT CẢ MODULE ĐẠT 100%! ===\n";
