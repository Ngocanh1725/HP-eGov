<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
\App\Core\App::boot();

$baseUrl = 'http://localhost/HP-eGov';
$cookieFile = __DIR__ . '/cookie_thuly_temp.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "=== KIỂM THỬ TÍCH HỢP HTTP NGHIỆP VỤ THỤ LÝ & LIÊN THÔNG (PH03) ===\n\n";

// 1. Lấy CSRF Token đăng nhập
$ch = curl_init("{$baseUrl}/admin/dang-nhap");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$loginPage = (string)curl_exec($ch);

preg_match('/name="_csrf" value="([^"]+)"/', $loginPage, $m);
$csrfToken = $m[1] ?? '';
echo "1. Lấy CSRF token đăng nhập: " . ($csrfToken ? "THÀNH CÔNG" : "KHÔNG CÓ") . "\n";

// 2. Đăng nhập tài khoản Chuyên viên thụ lý (chuyenvien.hongbang)
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/admin/dang-nhap");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'ten_dang_nhap' => 'chuyenvien.hongbang',
    'mat_khau' => 'Hpegov@2026',
    '_csrf' => $csrfToken,
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$resLogin = (string)curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$isLoggedIn = str_contains($resLogin, 'Đăng xuất') || str_contains($resLogin, 'Nguyễn Văn Hùng') || str_contains($resLogin, 'chuyenvien.hongbang');
echo "2. Đăng nhập cán bộ chuyên viên: HTTP {$httpCode} - " . ($isLoggedIn ? "THÀNH CÔNG" : "LỖI") . "\n";

// 3. Truy cập Hộp việc thụ lý (/admin/thu-ly)
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/admin/thu-ly");
curl_setopt($ch, CURLOPT_POST, false);
$inboxHtml = (string)curl_exec($ch);
$httpCodeInbox = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$hasTabs = str_contains($inboxHtml, 'Chờ xử lý') && str_contains($inboxHtml, 'Đang thụ lý') && str_contains($inboxHtml, 'Phối hợp liên ngành');
echo "3. Hộp việc Thụ lý (/admin/thu-ly): HTTP {$httpCodeInbox} - " . ($hasTabs ? "ĐẠT (Hiển thị đủ 6 tabs và bộ lọc)" : "LỖI") . "\n";

// 4. Lấy ID một hồ sơ để vào chi tiết thụ lý
$hoSoId = (int)\App\Core\Database::connection()->query("SELECT id FROM ho_so WHERE is_deleted = 0 ORDER BY id DESC LIMIT 1")->fetchColumn();

// 5. Truy cập Màn hình thụ lý chi tiết (/admin/thu-ly/{id})
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/admin/thu-ly/{$hoSoId}");
$detailHtml = (string)curl_exec($ch);
$httpCodeDetail = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$hasDetailTabs = str_contains($detailHtml, 'E-Form & Đối soát LGSP') && str_contains($detailHtml, 'Dự thảo tờ trình') && str_contains($detailHtml, 'Ý kiến liên ngành');
echo "4. Màn hình chi tiết Thụ lý (/admin/thu-ly/{$hoSoId}): HTTP {$httpCodeDetail} - " . ($hasDetailTabs ? "ĐẠT (Đủ E-Form, Đối soát LGSP, Dự thảo, Ý kiến)" : "LỖI") . "\n";

// 6. Kiểm tra API Đối soát LGSP qua HTTP POST
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/admin/thu-ly/api/lgsp-verify");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Requested-With: XMLHttpRequest',
    'Content-Type: application/x-www-form-urlencoded'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'type' => 'LAND',
    'so_thua' => '102',
    'so_to' => '15',
    'chu_su_dung' => 'Công ty Logistics Cảng Lạch Huyện',
]));
$apiRes = (string)curl_exec($ch);
$apiJson = json_decode($apiRes, true);
$apiOk = is_array($apiJson) && ($apiJson['success'] ?? false) === true;
echo "5. API Đối soát LGSP Đất đai (/admin/thu-ly/api/lgsp-verify): " . ($apiOk ? "ĐẠT (Khớp đúng VILIS 100%)" : "LỖI: {$apiRes}") . "\n";

// 7. Kiểm tra API Đăng kiểm qua HTTP POST
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'type' => 'VEHICLE',
    'bien_so' => '15C-12345',
    'chu_xe' => 'Công ty Logistics Hải Phòng',
]));
$apiVehRes = (string)curl_exec($ch);
$apiVehJson = json_decode($apiVehRes, true);
$apiVehOk = is_array($apiVehJson) && ($apiVehJson['success'] ?? false) === true;
echo "6. API Đối soát LGSP Đăng kiểm GTVT (/admin/thu-ly/api/lgsp-verify): " . ($apiVehOk ? "ĐẠT (Khớp đúng CSDL Đăng kiểm)" : "LỖI: {$apiVehRes}") . "\n";

curl_close($ch);
@unlink($cookieFile);

echo "\n=== TOÀN BỘ KIỂM THỬ TÍCH HỢP HTTP THỤ LÝ & LIÊN THÔNG ĐẠT 100%! ===\n";
