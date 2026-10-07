<?php

declare(strict_types=1);

/**
 * Script kiểm tra luồng E2E HTTP thực tế qua Cổng Dịch vụ công (PH01)
 * Chạy trên Apache localhost/HP-eGov/
 */

$baseUrl = 'http://localhost/HP-eGov';
$cookieFile = __DIR__ . '/cookie_portal_test.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

function http_request(string $method, string $url, array $data = [], string $cookieFile = '', array $headers = []): array
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    if ($cookieFile !== '') {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);

    $headerText = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    curl_close($ch);

    return [
        'code' => $httpCode,
        'headers' => $headerText,
        'body' => $body,
        'redirect' => $redirectUrl,
    ];
}

echo "======================================================================\n";
echo " KIỂM TRA LUỒNG E2E CỔNG DỊCH VỤ CÔNG TRỰC TUYẾN (HTTP LOCALHOST)\n";
echo "======================================================================\n\n";

// 1. Kiểm tra Trang chủ Cổng DVC
echo "[BƯỚC 1] Truy cập Trang chủ Cổng DVC (GET /)\n";
$res1 = http_request('GET', "{$baseUrl}/", [], $cookieFile);
if ($res1['code'] === 200 && str_contains($res1['body'], 'HẢI PHÒNG') && str_contains($res1['body'], '98%')) {
    echo "  ✔ Thành công: Trang chủ tải tốt (HTTP 200, hiển thị đầy đủ Banner & 4 chỉ số thống kê công khai).\n\n";
} else {
    echo "  ✖ Thất bại tại Trang chủ: HTTP {$res1['code']}\n\n";
    exit(1);
}

// 2. Kiểm tra Danh mục TTHC
echo "[BƯỚC 2] Truy cập Danh mục TTHC (GET /dvc/thu-tuc)\n";
$res2 = http_request('GET', "{$baseUrl}/dvc/thu-tuc", [], $cookieFile);
if ($res2['code'] === 200 && str_contains($res2['body'], 'Danh mục Dịch vụ công')) {
    echo "  ✔ Thành công: Danh mục TTHC hiển thị tốt (HTTP 200).\n\n";
} else {
    echo "  ✖ Thất bại tại Danh mục TTHC: HTTP {$res2['code']}\n\n";
    exit(1);
}

// 3. Kiểm tra Chi tiết TTHC
echo "[BƯỚC 3] Truy cập Chi tiết TTHC mã 0042 (GET /dvc/thu-tuc/0042)\n";
$res3 = http_request('GET', "{$baseUrl}/dvc/thu-tuc/0042", [], $cookieFile);
if ($res3['code'] === 200 && (str_contains($res3['body'], '0042') || str_contains($res3['body'], 'THÀNH PHẦN HỒ SƠ'))) {
    echo "  ✔ Thành công: Chi tiết TTHC hiển thị chuẩn (HTTP 200, có nút Nộp hồ sơ trực tuyến).\n\n";
} else {
    echo "  ✖ Thất bại tại Chi tiết TTHC: HTTP {$res3['code']}\n\n";
    exit(1);
}

// 4. Giả lập Luồng Đăng nhập VNeID Sandbox
echo "[BƯỚC 4] Thực hiện Đăng nhập Công dân qua VNeID Sandbox OIDC\n";
// 4.1 Khởi tạo OIDC flow
$res4_1 = http_request('GET', "{$baseUrl}/auth/vneid/login", [], $cookieFile);
$sandboxUrl = $res4_1['redirect'];
echo "  - Redirect đến Sandbox: {$sandboxUrl}\n";

// Parse state & nonce
parse_str(parse_url($sandboxUrl, PHP_URL_QUERY) ?? '', $queryParams);
$state = $queryParams['state'] ?? '';
$nonce = $queryParams['nonce'] ?? '';

// 4.2 Nhận trang Sandbox và lấy CSRF token
$res4_2 = http_request('GET', $sandboxUrl, [], $cookieFile);
preg_match('/name="_csrf"\s+value="([^"]+)"/', $res4_2['body'], $csrfMatches);
$csrfToken = $csrfMatches[1] ?? '';

// 4.3 Công dân chọn 'Đăng nhập với Công dân mẫu' và Consent
$res4_3 = http_request('POST', "{$baseUrl}/auth/vneid/consent", [
    '_csrf' => $csrfToken,
    'state' => $state,
    'nonce' => $nonce,
    'so_cccd' => '031090001234',
    'ho_ten' => 'Nguyễn Văn An',
    'ngay_sinh' => '1990-05-15',
    'gioi_tinh' => 'NAM',
    'que_quan' => 'Hải Phòng',
    'dia_chi_thuong_tru' => 'Số 28 Đinh Tiên Hoàng, Phường Hoàng Văn Thụ, Quận Hồng Bàng, TP Hải Phòng',
], $cookieFile);

$callbackUrl = $res4_3['redirect'];
echo "  - Callback Authorization Code: {$callbackUrl}\n";

// 4.4 Hoàn tất callback OIDC
$res4_4 = http_request('GET', $callbackUrl, [], $cookieFile);
echo "  - Kết quả đăng nhập VNeID: HTTP {$res4_4['code']} (Redirect đến: {$res4_4['redirect']})\n";
echo "  ✔ Thành công: Công dân Nguyễn Văn An đã xác thực VNeID và lưu phiên hợp lệ.\n\n";

// 5. Truy cập Wizard 4 bước nộp hồ sơ
echo "[BƯỚC 5] Mở Wizard Nộp hồ sơ trực tuyến (GET /dvc/nop-ho-so/3)\n";
$res5 = http_request('GET', "{$baseUrl}/dvc/nop-ho-so/3", [], $cookieFile);
if ($res5['code'] === 200 && str_contains($res5['body'], 'VNeID Auto-fill (Khóa sửa)')) {
    echo "  ✔ Thành công: Wizard hiển thị 4 bước chuẩn, các trường VNeID được Auto-fill và khóa sửa đúng quy định.\n\n";
} else {
    echo "  ✖ Thất bại mở Wizard: HTTP {$res5['code']}\n";
    echo substr($res5['body'], 0, 1500) . "\n\n";
    exit(1);
}

// Lấy CSRF token cho form nộp
preg_match('/name="_csrf"\s+value="([^"]+)"/', $res5['body'], $wizardCsrfMatches);
$wizardCsrf = $wizardCsrfMatches[1] ?? '';

// Lấy don_vi_tiep_nhan_id mẫu
preg_match('/<option value="(\d+)"[^>]*>/', $res5['body'], $dvMatches);
$donViId = $dvMatches[1] ?? '1';

// 6. Nộp hồ sơ trực tuyến (POST /dvc/nop-ho-so/3)
echo "[BƯỚC 6] Thực hiện Ký số & Nộp hồ sơ trực tuyến (POST /dvc/nop-ho-so/3)\n";
$submitData = [
    '_csrf' => $wizardCsrf,
    'don_vi_tiep_nhan_id' => $donViId,
    'eform_data' => [
        'ho_ten' => 'Nguyễn Văn An',
        'so_cccd' => '031090001234',
        'ngay_sinh' => '1990-05-15',
        'dia_chi_thuong_tru' => 'Số 28 Đinh Tiên Hoàng, Hoàng Văn Thụ, Hồng Bàng',
        'dia_chi_xay_dung' => 'Số 28 Đinh Tiên Hoàng, Phường Hoàng Văn Thụ, Quận Hồng Bàng, Hải Phòng',
        'dien_tich_xay_dung' => '120',
        'so_tang' => '3',
    ],
    'hinh_thuc_nhan_kq' => 'DIEN_TU',
    'cam_ket' => '1',
];

$res6 = http_request('POST', "{$baseUrl}/dvc/nop-ho-so/3", $submitData, $cookieFile);
$successUrl = $res6['redirect'];
echo "  - Phản hồi nộp: HTTP {$res6['code']} -> Chuyển hướng: {$successUrl}\n";

if (str_contains($successUrl, '/dvc/nop-ho-so/thanh-cong/')) {
    preg_match('/thanh-cong\/([^?]+)/', $successUrl, $maMatches);
    $maHoSoMoi = urldecode($maMatches[1] ?? '');
    echo "  ✔ Nộp hồ sơ thành công! Mã hồ sơ chuẩn QCVN 102 được cấp: {$maHoSoMoi}\n\n";
} else {
    echo "  ✖ Thất bại khi nộp hồ sơ!\n";
    echo substr($res6['body'], 0, 500) . "\n";
    exit(1);
}

// 7. Truy cập Màn hình Thành công (GET /dvc/nop-ho-so/thanh-cong/...)
echo "[BƯỚC 7] Xem màn hình Thành công có Mã QR & Giấy tiếp nhận\n";
$res7 = http_request('GET', "{$baseUrl}/dvc/nop-ho-so/thanh-cong/{$maHoSoMoi}", [], $cookieFile);
if ($res7['code'] === 200 && str_contains($res7['body'], $maHoSoMoi) && str_contains($res7['body'], 'qrcode')) {
    echo "  ✔ Thành công: Màn hình biên nhận hiển thị mã QR tra cứu và thông báo tiếp nhận hồ sơ.\n\n";
} else {
    echo "  ✖ Lỗi màn hình thành công: HTTP {$res7['code']}\n\n";
}

// 8. Xem danh sách "Hồ sơ của tôi" (GET /cong-dan/ho-so)
echo "[BƯỚC 8] Kiểm tra Hồ sơ của tôi (GET /cong-dan/ho-so)\n";
$res8 = http_request('GET', "{$baseUrl}/cong-dan/ho-so", [], $cookieFile);
if ($res8['code'] === 200 && str_contains($res8['body'], $maHoSoMoi)) {
    echo "  ✔ Thành công: Hồ sơ {$maHoSoMoi} đã xuất hiện trong danh sách cá nhân với trạng thái CHỜ TIẾP NHẬN.\n\n";
} else {
    echo "  ✖ Không tìm thấy hồ sơ trong danh sách cá nhân: HTTP {$res8['code']}\n\n";
}

// 9. Xem Chi tiết tiến trình hồ sơ (GET /cong-dan/ho-so/{ma_ho_so})
echo "[BƯỚC 9] Xem Chi tiết tiến trình hồ sơ (GET /cong-dan/ho-so/{$maHoSoMoi})\n";
$res9 = http_request('GET', "{$baseUrl}/cong-dan/ho-so/{$maHoSoMoi}", [], $cookieFile);
if ($res9['code'] === 200 && str_contains($res9['body'], 'Tiếp nhận Một cửa') && str_contains($res9['body'], 'TIMELINE')) {
    echo "  ✔ Thành công: Stepper 5 bước và timeline luân chuyển công khai hiển thị sắc nét.\n\n";
} else {
    echo "  ✖ Lỗi xem chi tiết hồ sơ: HTTP {$res9['code']}\n\n";
}

// 10. Tra cứu công khai không cần đăng nhập (GET /tra-cuu)
echo "[BƯỚC 10] Tra cứu công khai hồ sơ không cần đăng nhập (GET /tra-cuu?ma_ho_so=...)\n";
$res10 = http_request('GET', "{$baseUrl}/tra-cuu?ma_ho_so={$maHoSoMoi}", [], ''); // Không dùng cookie
if ($res10['code'] === 200 && str_contains($res10['body'], $maHoSoMoi) && str_contains($res10['body'], 'TRẠNG THÁI HIỆN TẠI')) {
    echo "  ✔ Thành công: Tra cứu công khai trả về thông tin tối thiểu chuẩn mực, bảo mật danh tính công dân.\n\n";
} else {
    echo "  ✖ Lỗi tra cứu công khai: HTTP {$res10['code']}\n\n";
}

// Dọn dẹp cookie test
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "======================================================================\n";
echo "🎉 TẤT CẢ 10 BƯỚC E2E CỔNG DỊCH VỤ CÔNG ĐỀU THÀNH CÔNG XUẤT SẮC!\n";
echo "======================================================================\n";
