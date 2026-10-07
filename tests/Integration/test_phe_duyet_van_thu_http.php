<?php

declare(strict_types=1);

/**
 * Kiểm tra các endpoint HTTP của Phê duyệt, Ký số & Văn thư số
 */

$baseUrl = 'http://localhost/HP-eGov';

function getUrl(string $url): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $response];
}

echo "1. Kiểm tra trang công khai /kiem-tra-chu-ky:\n";
$res1 = getUrl("{$baseUrl}/kiem-tra-chu-ky");
echo "HTTP Code: {$res1['code']}\n";
assert($res1['code'] === 200, "Trang tra cứu chữ ký số công khai phải trả về 200 OK");
assert(str_contains($res1['body'], 'Tra cứu & Xác thực Chữ ký số Điện tử'), "Phải có tiêu đề kiểm tra chữ ký số");
echo "✓ Đạt 200 OK\n\n";

echo "2. Kiểm tra trang /admin/phe-duyet/phong khi chưa đăng nhập (Bắt buộc Redirect 302 về /admin/dang-nhap):\n";
$res2 = getUrl("{$baseUrl}/admin/phe-duyet/phong");
echo "HTTP Code: {$res2['code']}\n";
assert($res2['code'] === 302, "Phải chuyển hướng 302 khi chưa đăng nhập");
echo "✓ Đạt 302 Redirect\n\n";

echo "3. Kiểm tra trang /admin/phe-duyet/co-quan khi chưa đăng nhập:\n";
$res3 = getUrl("{$baseUrl}/admin/phe-duyet/co-quan");
echo "HTTP Code: {$res3['code']}\n";
assert($res3['code'] === 302, "Phải chuyển hướng 302 khi chưa đăng nhập");
echo "✓ Đạt 302 Redirect\n\n";

echo "4. Kiểm tra trang /admin/van-thu khi chưa đăng nhập:\n";
$res4 = getUrl("{$baseUrl}/admin/van-thu");
echo "HTTP Code: {$res4['code']}\n";
assert($res4['code'] === 302, "Phải chuyển hướng 302 khi chưa đăng nhập");
echo "✓ Đạt 302 Redirect\n\n";

echo "🎉 TẤT CẢ CÁC ENDPOINT HTTP PHÊ DUYỆT & VĂN THƯ ĐỀU HOẠT ĐỘNG HOÀN HẢO!\n";
