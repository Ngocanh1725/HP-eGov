<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
\App\Core\App::boot();

use App\Core\Database;
use App\Integrations\Mock\LgspGatewayMock;
use App\Services\WorkflowEngine;

$db = Database::connection();

echo "=== KIỂM TRA TÀI KHOẢN CÁN BỘ ===\n";
$userLd = $db->query("SELECT * FROM nguoi_dung WHERE ten_dang_nhap = 'ldphong.hongbang'")->fetch(PDO::FETCH_ASSOC);
$userCv = $db->query("SELECT * FROM nguoi_dung WHERE ten_dang_nhap = 'chuyenvien.hongbang'")->fetch(PDO::FETCH_ASSOC);

echo "1. Lãnh đạo phòng: " . ($userLd['ho_ten'] ?? 'N/A') . " (ID: " . ($userLd['id'] ?? 0) . ")\n";
echo "2. Chuyên viên: " . ($userCv['ho_ten'] ?? 'N/A') . " (ID: " . ($userCv['id'] ?? 0) . ")\n";

echo "\n=== KIỂM TRA MOCK CỔNG LGSP (C06 / VILIS / ĐĂNG KIỂM) ===\n";
$lgsp = new LgspGatewayMock();
$c06 = $lgsp->verifyCitizen('031095000999', 'Tập đoàn Cảng biển Quốc tế Hải Phòng');
echo "C06: " . ($c06['success'] ? 'KHỚP' : 'KHÔNG KHỚP') . " - " . $c06['message'] . "\n";

$vilis = $lgsp->verifyLandData('45', '12', 'Nguyễn Văn Hùng');
echo "Đất đai (VILIS): " . ($vilis['success'] ? 'KHỚP' : 'KHÔNG KHỚP') . " - Diện tích: " . ($vilis['data']['dien_tich_m2'] ?? 0) . " m2\n";

$gtvt = $lgsp->verifyVehicleRegistration('15A-99999', 'Công ty Cảng HP');
echo "Đăng kiểm GTVT: " . ($gtvt['success'] ? 'KHỚP' : 'KHÔNG KHỚP') . " - Xe: " . ($gtvt['data']['nhan_hieu'] ?? 'N/A') . "\n";

echo "\n=== KIỂM TRA CSDL HỒ SƠ THỤ LÝ ===\n";
$countHs = (int)$db->query("SELECT COUNT(*) FROM ho_so WHERE is_deleted = 0")->fetchColumn();
echo "Tổng hồ sơ hợp lệ trong hệ thống: {$countHs}\n";

$latestHs = $db->query("SELECT id, ma_ho_so, trang_thai, sla_mau, don_vi_hien_tai_id FROM ho_so WHERE is_deleted = 0 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($latestHs) {
    echo "Hồ sơ mới nhất: {$latestHs['ma_ho_so']} | Trạng thái: {$latestHs['trang_thai']} | SLA: {$latestHs['sla_mau']}\n";
    $integrity = WorkflowEngine::verifyFileIntegrity((int)$latestHs['id']);
    echo "Tính toàn vẹn tệp đính kèm: " . ($integrity['valid'] ? 'TOÀN VẸN 100%' : 'CÓ LỖI') . "\n";
}

echo "\n=== KIỂM TRA TRANG QUY TRÌNH ADMIN (/admin/quy-trinh/2) ===\n";
$qt2 = $db->query("SELECT id, ten_quy_trinh FROM quy_trinh WHERE id = 2")->fetch(PDO::FETCH_ASSOC);
if ($qt2) {
    echo "Quy trình #2: {$qt2['ten_quy_trinh']}\n";
    $mermaid = WorkflowEngine::generateMermaidDiagram(2);
    echo "Sơ đồ Mermaid: " . substr($mermaid, 0, 60) . "...\n";
    $val = WorkflowEngine::validateWorkflow(2);
    echo "Thẩm định quy trình: " . ($val['valid'] ? 'HỢP LỆ' : 'CÓ LỖI') . " (Tổng giờ: {$val['total_hours']}h)\n";
}

echo "\n=== KIỂM TRA HOÀN TẤT THÀNH CÔNG! ===\n";
