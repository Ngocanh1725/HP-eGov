<?php

declare(strict_types=1);

/**
 * -----------------------------------------------------------------------------
 * HP-eGov: Script quét SLA tự động định kỳ (Cron Job)
 * Chạy mỗi 15 phút bằng Windows Task Scheduler
 * Lệnh cài đặt schtasks:
 * schtasks /create /tn "HPeGov_SlaScan" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\HP-eGov\cron\sla_scan.php" /sc minute /mo 15 /ru "SYSTEM"
 * -----------------------------------------------------------------------------
 */

$baseDir = dirname(__DIR__);
require_once $baseDir . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register($baseDir);
require_once $baseDir . '/app/Helpers/functions.php';

\App\Core\App::setBasePath($baseDir);
\App\Core\App::boot();

use App\Core\Database;
use App\Services\SlaEngine;
use App\Services\ThongBaoService;


echo "======================================================================\n";
echo " [HP-eGov] KHỞI CHẠY QUÉT TIẾN ĐỘ & SLA THỜI GIAN THỰC\n";
echo " Thời điểm chạy: " . date('Y-m-d H:i:s') . "\n";
echo "======================================================================\n";

$db = Database::connection();
$startTime = microtime(true);

// 1. Lấy toàn bộ hồ sơ đang hoạt động (chưa hoàn thành/từ chối/hủy)
$query = "SELECT hs.*, dv.ten_don_vi 
          FROM ho_so hs
          LEFT JOIN don_vi dv ON hs.don_vi_hien_tai_id = dv.id
          WHERE hs.is_deleted = 0 
            AND hs.trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA', 'TU_CHOI', 'DA_HUY')";

$stmt = $db->query($query);
$danhSach = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($danhSach);
$updated = 0;
$countVang = 0;
$countDo = 0;

echo "-> Tìm thấy {$total} hồ sơ đang hoạt động cần kiểm tra.\n\n";

$updateStmt = $db->prepare("UPDATE ho_so 
                            SET sla_mau = :mau, 
                                thoi_gian_da_dung_gio = :da_dung, 
                                updated_at = NOW() 
                            WHERE id = :id");

$insertDiemStmt = $db->prepare("INSERT INTO diem_thi_dua_don_vi 
                                (don_vi_id, ho_so_id, loai_su_kien, diem_thay_doi, ly_do, thoi_gian) 
                                VALUES (:dvid, :hsid, 'QUA_HAN_SLA', -1.0, :lydo, NOW())");

foreach ($danhSach as $hs) {
    $hoSoId = (int)$hs['id'];
    $maHoSo = (string)$hs['ma_ho_so'];
    $ngayHenTra = (string)($hs['ngay_hen_tra'] ?? date('Y-m-d 17:00:00'));
    $ngayTiepNhan = !empty($hs['ngay_tiepNhan']) ? (string)$hs['ngay_tiepNhan'] : (string)($hs['created_at'] ?? date('Y-m-d H:i:s'));
    $tongThoiHan = (float)($hs['tong_thoi_han_gio'] ?? 24.0);
    $soGioTamDung = (float)($hs['so_gio_tam_dung'] ?? 0.0);
    $oldMau = (string)($hs['sla_mau'] ?? 'XANH');

    // Đánh giá SLA thời gian thực
    $eval = SlaEngine::evaluateSla(
        ngayHenTra: $ngayHenTra,
        ngayTiepNhan: $ngayTiepNhan,
        ngayHoanThanh: null,
        tongThoiHanGio: $tongThoiHan,
        soGioTamDung: $soGioTamDung
    );

    $newMau = $eval['mau'];
    $daDungGio = $eval['so_gio_da_dung'];

    // Cập nhật CSDL
    $updateStmt->execute([
        ':mau' => $newMau,
        ':da_dung' => $daDungGio,
        ':id' => $hoSoId,
    ]);
    $updated++;

    // Xử lý biến đổi màu sắc
    if ($newMau !== $oldMau) {
        echo "  [MÃ {$maHoSo}] Chuyển màu SLA: {$oldMau} -> {$newMau} (Đã dùng: {$daDungGio}h / {$eval['tong_gio']}h)\n";

        // Trường hợp chuyển sang VÀNG: Push alert cho Chuyên viên và LĐ Phòng
        if ($newMau === 'VANG') {
            $countVang++;

            $title = "[CẢNH BÁO SLA VÀNG] Hồ sơ {$maHoSo} sắp quá hạn";
            $content = "Hồ sơ {$maHoSo} của công dân {$hs['nguoi_nop_ho_ten']} đã sử dụng {$daDungGio}h/{$eval['tong_gio']}h ({$eval['ty_le']}%), còn lại {$eval['so_gio_con_lai']}h làm việc. Đề nghị khẩn trương xử lý.";
            $link = "/admin/thu-ly/{$hoSoId}";

            // Gửi chuyên viên thụ lý nếu có
            if (!empty($hs['chuyen_vien_thu_ly_id'])) {
                ThongBaoService::gui(
                    nguoiNhanId: (int)$hs['chuyen_vien_thu_ly_id'],
                    congDanId: null,
                    tieuDe: $title,
                    noiDung: $content,
                    lienKet: $link,
                    loai: 'SLA_CANH_BAO_VANG'
                );
            }

            // Gửi Lãnh đạo phòng thuộc đơn vị
            $donViId = (int)$hs['don_vi_hien_tai_id'];
            $ldpStmt = $db->prepare("SELECT nd.id 
                                     FROM nguoi_dung nd
                                     JOIN nguoi_dung_vai_tro ndvt ON nd.id = ndvt.nguoi_dung_id
                                     JOIN vai_tro vt ON ndvt.vai_tro_id = vt.id
                                     WHERE (ndvt.don_vi_id = :dvid OR nd.don_vi_chinh_id = :dvid2) 
                                       AND vt.ma_vai_tro = 'LD_PHONG' 
                                       AND nd.trang_thai = 1");
            $ldpStmt->execute([':dvid' => $donViId, ':dvid2' => $donViId]);
            $ldpUsers = $ldpStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($ldpUsers as $ldpId) {
                ThongBaoService::gui(
                    nguoiNhanId: (int)$ldpId,
                    congDanId: null,
                    tieuDe: $title,
                    noiDung: $content,
                    lienKet: $link,
                    loai: 'SLA_CANH_BAO_VANG'
                );
            }
        }

        // Trường hợp chuyển sang ĐỎ: Khóa chuyển bước, Báo động Lãnh đạo cơ quan, Trừ điểm thi đua
        if ($newMau === 'DO') {
            $countDo++;

            $title = "[KHẨN CẤP - SLA ĐỎ] Hồ sơ {$maHoSo} đã quá hạn giải quyết!";
            $content = "Hồ sơ {$maHoSo} đã quá hạn ({$daDungGio}h/{$eval['tong_gio']}h). Chức năng chuyển bước đã bị khóa. Bắt buộc Lãnh đạo cơ quan lập và ký số Văn bản xin lỗi Mẫu số 04 trước khi hoàn tất.";
            $link = "/admin/sla/van-ban-xin-loi/{$hoSoId}";

            // Gửi Lãnh đạo cơ quan
            $donViId = (int)$hs['don_vi_hien_tai_id'];
            $ldcqStmt = $db->prepare("SELECT nd.id 
                                      FROM nguoi_dung nd
                                      JOIN nguoi_dung_vai_tro ndvt ON nd.id = ndvt.nguoi_dung_id
                                      JOIN vai_tro vt ON ndvt.vai_tro_id = vt.id
                                      WHERE (ndvt.don_vi_id = :dvid OR nd.don_vi_chinh_id = :dvid2) 
                                        AND vt.ma_vai_tro = 'LD_CO_QUAN' 
                                        AND nd.trang_thai = 1");
            $ldcqStmt->execute([':dvid' => $donViId, ':dvid2' => $donViId]);
            $ldcqUsers = $ldcqStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($ldcqUsers as $ldcqId) {
                ThongBaoService::gui(
                    nguoiNhanId: (int)$ldcqId,
                    congDanId: null,
                    tieuDe: $title,
                    noiDung: $content,
                    lienKet: $link,
                    loai: 'SLA_QUA_HAN_DO'
                );
            }


            // Ghi nhận trừ 1 điểm thi đua của đơn vị
            try {
                $insertDiemStmt->execute([
                    ':dvid' => $donViId,
                    ':hsid' => $hoSoId,
                    ':lydo' => "Hồ sơ {$maHoSo} quá hạn giải quyết TTHC ({$daDungGio}h/{$eval['tong_gio']}h) - trừ 1 điểm thi đua IOC",
                ]);
            } catch (\Throwable $e) {
                // Tiếp tục nếu có lỗi ghi điểm
            }
        }
    }
}

$elapsed = round(microtime(true) - $startTime, 4);

echo "\n----------------------------------------------------------------------\n";
echo " KẾT QUẢ QUÉT SLA:\n";
echo " - Tổng số hồ sơ đã kiểm tra: {$updated}\n";
echo " - Số hồ sơ chuyển cảnh báo VÀNG: {$countVang}\n";
echo " - Số hồ sơ chuyển cảnh báo ĐỎ (Quá hạn): {$countDo}\n";
echo " - Thời gian hoàn thành: {$elapsed} giây\n";
echo "======================================================================\n";
