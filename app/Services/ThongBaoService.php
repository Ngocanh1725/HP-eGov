<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Integrations\Mock\SmsZnsNotificationMock;
use PDO;
use Throwable;

/**
 * Class ThongBaoService – Dịch vụ phát thông báo đa kênh Outbox Pattern (§2.2 Báo cáo Kiến trúc)
 * - Quản lý gửi thông báo In-app cho cán bộ và công dân
 * - Đẩy tin nhắn SMS Brandname "UBND TP HP", Zalo ZNS, Email vào Outbox queue (hang_doi_thong_bao)
 * - Tự động nạp mẫu tin nhắn cấu hình được (mau_thong_bao) và render biến
 */
class ThongBaoService
{
    /**
     * Gửi thông báo in-app trực tiếp
     *
     * @param int|null $nguoiNhanId ID cán bộ nhận (nếu có)
     * @param int|null $congDanId ID công dân nhận (nếu có)
     * @param string $tieuDe Tiêu đề thông báo
     * @param string $noiDung Nội dung chi tiết
     * @param string|null $lienKet Liên kết chuyển tiếp
     * @param string $loai Loại thông báo (HE_THONG, HO_SO, LIEN_THONG, SLA_CANH_BAO_VANG, SLA_QUA_HAN_DO)
     * @return int ID thông báo vừa tạo
     */
    public static function gui(
        ?int $nguoiNhanId,
        ?int $congDanId,
        string $tieuDe,
        string $noiDung,
        ?string $lienKet = null,
        string $loai = 'HE_THONG'
    ): int {
        $db = Database::connection();

        $stmt = $db->prepare("INSERT INTO thong_bao (
            nguoi_nhan_id, cong_dan_id, tieu_de, noi_dung, lien_ket, loai, da_doc, created_at
        ) VALUES (
            :uid, :cid, :title, :content, :link, :loai, 0, NOW()
        )");

        $stmt->execute([
            ':uid' => $nguoiNhanId,
            ':cid' => $congDanId,
            ':title' => $tieuDe,
            ':content' => $noiDung,
            ':link' => $lienKet,
            ':loai' => $loai,
        ]);

        return (int)$db->lastInsertId();
    }

    /**
     * Phát sự kiện thông báo đa kênh theo Mẫu tin cấu hình (Outbox Pattern)
     *
     * @param array<string, mixed> $hoSo Dữ liệu hồ sơ
     * @param string $maMau Mã mẫu định danh (TIEP_NHAN, DANG_THU_LY, YEU_CAU_BO_SUNG, DA_CO_KET_QUA, XIN_LOI_QUA_HAN)
     * @param array<string, mixed> $extraParams Các tham số bổ sung (như so_van_ban, ngay_hen_moi...)
     * @return array{inapp_id: int|null, sms_queued: bool, zalo_queued: bool, email_queued: bool}
     */
    public static function guiSuKien(array $hoSo, string $maMau, array $extraParams = []): array
    {
        $db = Database::connection();

        // 1. Lấy mẫu thông báo tương ứng từ CSDL
        $stmt = $db->prepare("SELECT * FROM mau_thong_bao WHERE ma_mau = :ma AND trang_thai = 1 LIMIT 1");
        $stmt->execute([':ma' => $maMau]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            // Không tìm thấy mẫu, bỏ qua
            return ['inapp_id' => null, 'sms_queued' => false, 'zalo_queued' => false, 'email_queued' => false];
        }

        // 2. Chuẩn bị bảng biến thay thế
        $maHoSo = (string)($hoSo['ma_ho_so'] ?? '');
        $tenTthc = (string)($hoSo['ten_tthc'] ?? 'Thủ tục hành chính');
        $hoTen = (string)($hoSo['nguoi_nop_ho_ten'] ?? 'Quý công dân');
        $sdt = (string)($hoSo['nguoi_nop_sdt'] ?? '');
        $email = (string)($hoSo['nguoi_nop_email'] ?? '');
        $congDanId = !empty($hoSo['cong_dan_id']) ? (int)$hoSo['cong_dan_id'] : null;
        $hoSoId = !empty($hoSo['id']) ? (int)$hoSo['id'] : null;
        $trangThai = (string)($hoSo['trang_thai'] ?? '');

        $ngayHenTraFormatted = !empty($extraParams['ngay_hen_moi'])
            ? date('d/m/Y H:i', strtotime((string)$extraParams['ngay_hen_moi']))
            : (!empty($hoSo['ngay_hen_tra']) ? date('d/m/Y H:i', strtotime((string)$hoSo['ngay_hen_tra'])) : 'Đang cập nhật');

        $linkTraCuu = "http://localhost/HP-eGov/tra-cuu?ma_ho_so=" . urlencode($maHoSo);
        $soVanBan = (string)($extraParams['so_van_ban'] ?? '');

        $variables = [
            '{ma_ho_so}' => $maHoSo,
            '{ten_tthc}' => $tenTthc,
            '{ho_ten}' => $hoTen,
            '{trang_thai}' => $trangThai,
            '{ngay_hen_tra}' => $ngayHenTraFormatted,
            '{link_tra_cuu}' => $linkTraCuu,
            '{so_van_ban}' => $soVanBan,
        ];

        // 3. Render nội dung
        $tieuDe = str_replace(array_keys($variables), array_values($variables), (string)$template['tieu_de_mau']);
        $noiDungInApp = str_replace(array_keys($variables), array_values($variables), (string)$template['noi_dung_inapp']);
        $noiDungSms = str_replace(array_keys($variables), array_values($variables), (string)$template['noi_dung_sms']);
        $noiDungZalo = str_replace(array_keys($variables), array_values($variables), (string)$template['noi_dung_zalo']);
        $noiDungEmail = str_replace(array_keys($variables), array_values($variables), (string)($template['noi_dung_email'] ?? $template['noi_dung_inapp']));

        $result = [
            'inapp_id' => null,
            'sms_queued' => false,
            'zalo_queued' => false,
            'email_queued' => false,
        ];

        // 4. Kênh In-App: Gửi trực tiếp nếu bật
        if ((int)$template['kenh_inapp_active'] === 1 && $congDanId !== null) {
            try {
                $result['inapp_id'] = self::gui(
                    nguoiNhanId: null,
                    congDanId: $congDanId,
                    tieuDe: $tieuDe,
                    noiDung: $noiDungInApp,
                    lienKet: "/cong-dan/ho-so",
                    loai: 'HO_SO'
                );
            } catch (Throwable) {
                // Bỏ qua lỗi in-app
            }
        }

        // 5. Kênh SMS Brandname (Outbox pattern)
        if ((int)$template['kenh_sms_active'] === 1 && !empty($sdt)) {
            try {
                $queueStmt = $db->prepare("INSERT INTO hang_doi_thong_bao 
                    (ho_so_id, kenh, nguoi_nhan, tieu_de, noi_dung, trang_thai, so_lan_thu, thoi_gian_tao) 
                    VALUES (:hsid, 'SMS', :rcv, :title, :content, 'CHO_GUI', 0, NOW())");
                $queueStmt->execute([
                    ':hsid' => $hoSoId,
                    ':rcv' => $sdt,
                    ':title' => 'UBND TP HP',
                    ':content' => $noiDungSms,
                ]);
                $result['sms_queued'] = true;
            } catch (Throwable) {
                // Không ngắt luồng
            }
        }

        // 6. Kênh Zalo ZNS (Outbox pattern)
        if ((int)$template['kenh_zalo_active'] === 1 && !empty($sdt)) {
            try {
                $queueStmt = $db->prepare("INSERT INTO hang_doi_thong_bao 
                    (ho_so_id, kenh, nguoi_nhan, tieu_de, noi_dung, trang_thai, so_lan_thu, thoi_gian_tao) 
                    VALUES (:hsid, 'ZALO', :rcv, :title, :content, 'CHO_GUI', 0, NOW())");
                $queueStmt->execute([
                    ':hsid' => $hoSoId,
                    ':rcv' => $sdt,
                    ':title' => 'Trung tâm Phục vụ HCC Hải Phòng (ZNS)',
                    ':content' => $noiDungZalo,
                ]);
                $result['zalo_queued'] = true;
            } catch (Throwable) {
                // Không ngắt luồng
            }
        }

        // 7. Kênh Email (Outbox pattern)
        if ((int)$template['kenh_email_active'] === 1 && !empty($email)) {
            try {
                $queueStmt = $db->prepare("INSERT INTO hang_doi_thong_bao 
                    (ho_so_id, kenh, nguoi_nhan, tieu_de, noi_dung, trang_thai, so_lan_thu, thoi_gian_tao) 
                    VALUES (:hsid, 'EMAIL', :rcv, :title, :content, 'CHO_GUI', 0, NOW())");
                $queueStmt->execute([
                    ':hsid' => $hoSoId,
                    ':rcv' => $email,
                    ':title' => $tieuDe,
                    ':content' => $noiDungEmail,
                ]);
                $result['email_queued'] = true;
            } catch (Throwable) {
                // Không ngắt luồng
            }
        }

        return $result;
    }
}
