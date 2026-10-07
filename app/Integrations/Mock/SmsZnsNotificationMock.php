<?php

declare(strict_types=1);

namespace App\Integrations\Mock;

use App\Core\Database;
use PDO;

/**
 * SmsZnsNotificationMock – Giả lập dịch vụ Gửi thông báo đa kênh (SMS Brandname & Zalo ZNS)
 * Tuân thủ §2.2 Báo cáo Kiến trúc:
 * - SMS Brandname: "UBND TP HP"
 * - Zalo ZNS: Xác thực dịch vụ công thông báo trạng thái hồ sơ kèm mã QR / liên kết tra cứu
 */
class SmsZnsNotificationMock
{
    /**
     * Phát sự kiện thông báo đa kênh tới người nộp hồ sơ
     *
     * @param string $soDienThoai Số điện thoại người nhận
     * @param string $maHoSo Mã hồ sơ định danh
     * @param string $tieuDe Tiêu đề thông báo
     * @param string $noiDung Nội dung chi tiết
     * @param int|null $congDanId ID tài khoản công dân (nếu có)
     * @return array{sms_id: string, zns_msg_id: string, status: string}
     */
    public static function notify(
        string $soDienThoai,
        string $maHoSo,
        string $tieuDe,
        string $noiDung,
        ?int $congDanId = null
    ): array {
        $db = Database::connection();
        $smsId = 'SMS_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));
        $znsId = 'ZNS_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));

        // 1. Ghi thông báo trong hệ thống nếu có côngDanId
        if ($congDanId !== null && $congDanId > 0) {
            try {
                $stmt = $db->prepare("INSERT INTO thong_bao (cong_dan_id, tieu_de, noi_dung, lien_ket, loai, da_doc, created_at) 
                                      VALUES (:cid, :td, :nd, :lk, 'HO_SO', 0, NOW())");
                $stmt->execute([
                    ':cid' => $congDanId,
                    ':td' => $tieuDe,
                    ':nd' => $noiDung,
                    ':lk' => "/cong-dan/ho-so/{$maHoSo}",
                ]);
            } catch (\Throwable) {
                // Tiếp tục nếu lỗi ghi thông báo
            }
        }

        // 2. Ghi nhật ký tích hợp ngoại vi (nhat_ky_tich_hop)
        try {
            $reqPayload = json_encode([
                'brandname' => 'UBND TP HP',
                'receiver' => $soDienThoai,
                'ma_ho_so' => $maHoSo,
                'content' => $noiDung,
            ], JSON_UNESCAPED_UNICODE);

            $resPayload = json_encode([
                'sms_msg_id' => $smsId,
                'zalo_msg_id' => $znsId,
                'delivery_status' => 'SUCCESS',
            ], JSON_UNESCAPED_UNICODE);

            $stmtLog = $db->prepare("INSERT INTO nhat_ky_tich_hop 
                (he_thong, endpoint, phuong_thuc, du_lieu_gui, du_lieu_nhan, ma_trang_thai, thoi_gian_ms, created_at) 
                VALUES ('SMS_ZALO_GATEWAY', '/api/v1/notify/dispatch', 'POST', :req, :res, 200, 45, NOW())");
            $stmtLog->execute([
                ':req' => $reqPayload,
                ':res' => $resPayload,
            ]);
        } catch (\Throwable) {
            // Không ngắt luồng nếu bảng chưa hoàn thiện
        }

        return [
            'sms_id' => $smsId,
            'zns_msg_id' => $znsId,
            'status' => 'DELIVERED',
        ];
    }
}
