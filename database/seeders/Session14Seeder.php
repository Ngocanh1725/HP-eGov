<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Database;

$pdo = Database::pdo();

echo "--- BẮT ĐẦU SEED DỮ LIỆU PHIÊN 14 ---\n";

// 1. Seed 9 cổng dịch vụ tích hợp
$dichVuList = [
    [
        'ma' => 'VNEID',
        'ten' => 'Cổng định danh điện tử VNeID Mức độ 2',
        'nha_cung_cap' => 'Cục Cảnh sát QLHC về TTXH (C06 - Bộ Công An)',
        'endpoint' => 'https://vneid.bocongan.gov.vn/api/v2/oauth2/token',
        'che_do' => 'MOCK',
        'api_key' => 'vneid_prod_hp_2026_sec_key_xyz987',
        'secret_key' => 'vneid_sha256_client_secret_hp_egov_city',
    ],
    [
        'ma' => 'LGSP',
        'ten' => 'Trục kết nối liên thông dữ liệu TP Hải Phòng (LGSP/NDXP)',
        'nha_cung_cap' => 'Sở Khoa học và Công nghệ / Sở Thông tin & Truyền thông TP. Hải Phòng',
        'endpoint' => 'https://lgsp.haiphong.gov.vn/api/v1/data-share',
        'che_do' => 'MOCK',
        'api_key' => 'lgsp_token_gw_haiphong_ndxp_2026',
        'secret_key' => 'lgsp_hmac_secret_sha256_hp_connect_webhook',
    ],
    [
        'ma' => 'C06',
        'ten' => 'Cơ sở dữ liệu Quốc gia về Dân cư (Đề án 06/CP)',
        'nha_cung_cap' => 'Trung tâm Dữ liệu Quốc gia về Dân cư (C06)',
        'endpoint' => 'https://c06.bocongan.gov.vn/dancu/v1/xac-thuc-cong-dan',
        'che_do' => 'MOCK',
        'api_key' => 'c06_token_cert_secure_link_hpegov',
        'secret_key' => 'c06_rsa_private_key_sig_2026',
    ],
    [
        'ma' => 'CSDL_TTHC_QG',
        'ten' => 'Cơ sở dữ liệu Quốc gia về Thủ tục hành chính',
        'nha_cung_cap' => 'Cục Kiểm soát TTHC - Văn phòng Chính phủ (VPCP)',
        'endpoint' => 'https://dichvucong.gov.vn/api/sync/tthc-quoc-gia',
        'che_do' => 'MOCK',
        'api_key' => 'vpcp_tthc_qg_api_key_haiphong_31',
        'secret_key' => 'vpcp_tthc_qg_secret_hash_verify',
    ],
    [
        'ma' => 'SMARTCA',
        'ten' => 'Dịch vụ Ký số từ xa tập trung (SmartCA / Cloud CA)',
        'nha_cung_cap' => 'VNPT SmartCA / Ban Cơ yếu Chính phủ',
        'endpoint' => 'https://smartca-api.haiphong.gov.vn/v1/signatures',
        'che_do' => 'MOCK',
        'api_key' => 'smartca_client_id_hp_can_bo_gw',
        'secret_key' => 'smartca_hsm_secret_pin_authorization',
    ],
    [
        'ma' => 'SMS',
        'ten' => 'Cổng tin nhắn Brandname Hành chính công',
        'nha_cung_cap' => 'Viettel Telecom (Brandname: TP.HAIPHONG)',
        'endpoint' => 'https://smsbrandname.haiphong.gov.vn/api/send',
        'che_do' => 'MOCK',
        'api_key' => 'sms_viettel_brandname_hp_token',
        'secret_key' => 'sms_signature_secret_key_pass',
    ],
    [
        'ma' => 'ZALO_ZNS',
        'ten' => 'Cổng Zalo Notification Service (ZNS Chuyên dụng)',
        'nha_cung_cap' => 'Zalo Cloud (OA: Trung tâm Phục vụ Hành chính công Hải Phòng)',
        'endpoint' => 'https://business.openapi.zalo.me/message/template',
        'che_do' => 'MOCK',
        'api_key' => 'zalo_oa_access_token_hp_official_2026',
        'secret_key' => 'zalo_app_secret_proof_mac_key',
    ],
    [
        'ma' => 'VNPOST',
        'ten' => 'Cổng dịch vụ Chuyển phát bưu chính công ích',
        'nha_cung_cap' => 'Bưu điện TP. Hải Phòng - Tổng công ty Bưu điện VN',
        'endpoint' => 'https://api.vnpost.vn/public/v1/postal-orders',
        'che_do' => 'MOCK',
        'api_key' => 'vnpost_customer_code_haiphong_egov',
        'secret_key' => 'vnpost_secret_token_order_tracking',
    ],
    [
        'ma' => 'CLAMAV',
        'ten' => 'Hệ thống Quét mã độc & An toàn tệp tin tập trung',
        'nha_cung_cap' => 'Cụm máy chủ ClamAV Demon Sở TT&TT Hải Phòng',
        'endpoint' => 'http://10.31.0.45:3310/scan',
        'che_do' => 'MOCK',
        'api_key' => 'clamav_daemon_auth_key_internal',
        'secret_key' => 'clamav_stream_socket_secret',
    ],
];

$stmtDv = $pdo->prepare("
    INSERT INTO cau_hinh_tich_hop (ma_dich_vu, ten_dich_vu, nha_cung_cap, endpoint_url, che_do, api_key, secret_key, trang_thai_ket_noi, lan_kiem_tra_cuoi, thong_tin_phan_hoi)
    VALUES (:ma, :ten, :ncc, :ep, :cd, :ak, :sk, 'KET_NOI_TOT', NOW(), 'Khởi tạo cấu hình mặc định (MOCK READY)')
    ON DUPLICATE KEY UPDATE
        ten_dich_vu = VALUES(ten_dich_vu),
        nha_cung_cap = VALUES(nha_cung_cap),
        endpoint_url = VALUES(endpoint_url),
        api_key = VALUES(api_key),
        secret_key = VALUES(secret_key)
");

foreach ($dichVuList as $dv) {
    $stmtDv->execute([
        'ma' => $dv['ma'],
        'ten' => $dv['ten'],
        'ncc' => $dv['nha_cung_cap'],
        'ep' => $dv['endpoint'],
        'cd' => $dv['che_do'],
        'ak' => $dv['api_key'],
        'sk' => $dv['secret_key'],
    ]);
}
echo "✓ Đã seed 9 dịch vụ tích hợp.\n";

// 2. Seed Ánh xạ TTHC
$mappingTthc = [
    ['ma_cu' => 'MC-KS-01', 'ten' => 'Đăng ký khai sinh (Hệ thống Một cửa cũ)', 'ma_moi' => '1.000123.000.00.00.H21'],
    ['ma_cu' => 'MC-CS-02', 'ten' => 'Chứng thực bản sao điện tử (Một cửa cũ)', 'ma_moi' => '1.000124.000.00.00.H21'],
    ['ma_cu' => 'MC-XD-03', 'ten' => 'Cấp phép xây dựng nhà ở đô thị (Một cửa cũ)', 'ma_moi' => '1.000456.000.00.00.H21'],
    ['ma_cu' => 'MC-DD-04', 'ten' => 'Biến động đất đai sổ đỏ (Một cửa cũ)', 'ma_moi' => '1.000789.000.00.00.H21'],
    ['ma_cu' => 'MC-GTVT-05', 'ten' => 'Đổi giấy phép lái xe (Một cửa cũ)', 'ma_moi' => '1.002345.000.00.00.H21'],
    ['ma_cu' => 'MC-DKKD-06', 'ten' => 'Đăng ký hộ kinh doanh cá thể (Một cửa cũ)', 'ma_moi' => '1.000123.000.00.00.H21'],
];

$stmtMapTthc = $pdo->prepare("
    INSERT INTO etl_anh_xa_tthc (ma_tthc_cu, ten_tthc_cu, ma_tthc_moi, thu_tuc_id, ghi_chu)
    VALUES (:ma_cu, :ten, :ma_moi, (SELECT id FROM thu_tuc_hanh_chinh WHERE ma_tthc = :ma_moi_ref LIMIT 1), 'Ánh xạ chuẩn hóa từ Một cửa cũ')
    ON DUPLICATE KEY UPDATE
        ten_tthc_cu = VALUES(ten_tthc_cu),
        ma_tthc_moi = VALUES(ma_tthc_moi),
        thu_tuc_id = VALUES(thu_tuc_id)
");

foreach ($mappingTthc as $m) {
    $stmtMapTthc->execute([
        'ma_cu' => $m['ma_cu'],
        'ten' => $m['ten'],
        'ma_moi' => $m['ma_moi'],
        'ma_moi_ref' => $m['ma_moi'],
    ]);
}
echo "✓ Đã seed bảng ánh xạ TTHC.\n";

// 3. Seed Ánh xạ ĐVHC
$mappingDvhc = [
    ['ma_cu' => 'HB-01', 'ten' => 'UBND Quận Hồng Bàng cũ', 'ma_tctk' => '303'],
    ['ma_cu' => 'NQ-02', 'ten' => 'UBND Quận Ngô Quyền cũ', 'ma_tctk' => '304'],
    ['ma_cu' => 'LC-03', 'ten' => 'UBND Quận Lê Chân cũ', 'ma_tctk' => '305'],
    ['ma_cu' => 'HA-04', 'ten' => 'UBND Quận Hải An cũ', 'ma_tctk' => '306'],
    ['ma_cu' => 'KA-05', 'ten' => 'UBND Quận Kiến An cũ', 'ma_tctk' => '307'],
    ['ma_cu' => 'DS-06', 'ten' => 'UBND Quận Đồ Sơn cũ', 'ma_tctk' => '308'],
    ['ma_cu' => 'TN-07', 'ten' => 'UBND Huyện Thủy Nguyên cũ', 'ma_tctk' => '311'],
    ['ma_cu' => 'AD-08', 'ten' => 'UBND Huyện An Dương cũ', 'ma_tctk' => '312'],
    ['ma_cu' => 'HP-UBND', 'ten' => 'UBND Thành phố Hải Phòng cũ', 'ma_tctk' => '31'],
];

$stmtMapDvhc = $pdo->prepare("
    INSERT INTO etl_anh_xa_dvhc (ma_dvhc_cu, ten_dvhc_cu, ma_tctk, don_vi_id, ghi_chu)
    VALUES (:ma_cu, :ten, :ma_tctk, (SELECT id FROM don_vi WHERE dvhc_id = (SELECT id FROM dm_dvhc WHERE ma_dvhc = :ma_tctk_ref LIMIT 1) LIMIT 1), 'Ánh xạ ĐVHC Một cửa cũ sang mã TCTK')
    ON DUPLICATE KEY UPDATE
        ten_dvhc_cu = VALUES(ten_dvhc_cu),
        ma_tctk = VALUES(ma_tctk),
        don_vi_id = VALUES(don_vi_id)
");

foreach ($mappingDvhc as $m) {
    $stmtMapDvhc->execute([
        'ma_cu' => $m['ma_cu'],
        'ten' => $m['ten'],
        'ma_tctk' => $m['ma_tctk'],
        'ma_tctk_ref' => $m['ma_tctk'],
    ]);
}
echo "✓ Đã seed bảng ánh xạ ĐVHC.\n";

// 4. Seed mẫu API Token cho Cán bộ Lãnh đạo & Công dân
// Token lãnh đạo mẫu: 'hpegov_leader_token_secret_sample_2026' -> hash sha256
// Token công dân mẫu: 'hpegov_citizen_token_secret_sample_2026' -> hash sha256
$leaderTokenRaw = 'hpegov_leader_token_secret_sample_2026';
$citizenTokenRaw = 'hpegov_citizen_token_secret_sample_2026';

// Tìm một lãnh đạo
$ld = $pdo->query("SELECT nd.id FROM nguoi_dung nd JOIN nguoi_dung_vai_tro ndvt ON nd.id = ndvt.nguoi_dung_id JOIN vai_tro vt ON ndvt.vai_tro_id = vt.id WHERE vt.ma_vai_tro IN ('LD_PHONG', 'LD_CO_QUAN', 'UBND_TP') LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$leaderId = $ld ? (int)$ld['id'] : 1;

// Tìm một công dân
$cd = $pdo->query("SELECT id FROM cong_dan LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$citizenId = $cd ? (int)$cd['id'] : 1;

$stmtToken = $pdo->prepare("
    INSERT INTO api_tokens (nguoi_dung_id, cong_dan_id, ten_token, token_hash, scopes, expires_at, trang_thai)
    VALUES (:uid, :cid, :ten, :hash, :scopes, DATE_ADD(NOW(), INTERVAL 1 YEAR), 1)
    ON DUPLICATE KEY UPDATE scopes = VALUES(scopes), expires_at = VALUES(expires_at)
");

$stmtToken->execute([
    'uid' => $leaderId,
    'cid' => null,
    'ten' => 'Mobile App Lãnh đạo Thành phố',
    'hash' => hash('sha256', $leaderTokenRaw),
    'scopes' => 'tra_cuu,thu_tuc,lanh_dao',
]);

$stmtToken->execute([
    'uid' => null,
    'cid' => $citizenId,
    'ten' => 'Ứng dụng Công dân Hải Phòng SmartCity',
    'hash' => hash('sha256', $citizenTokenRaw),
    'scopes' => 'tra_cuu,thu_tuc,cong_dan',
]);

echo "✓ Đã seed 2 API Tokens mẫu (Lãnh đạo & Công dân).\n";
echo "--- HOÀN TẤT SEED PHIÊN 14 ---\n";
