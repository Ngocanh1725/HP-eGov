<?php

declare(strict_types=1);

namespace App\Integrations\Mock;

use App\Core\Database;
use PDO;
use Throwable;

/**
 * SmsZnsNotificationMock – Giả lập dịch vụ Gửi thông báo đa kênh (SMS Brandname & Zalo ZNS & Email)
 * Tuân thủ §2.2 Báo cáo Kiến trúc:
 * - SMS Brandname: "UBND TP HP"
 * - Zalo ZNS: Trung tâm Phục vụ hành chính công TP. Hải Phòng (kèm QR & link tra cứu)
 * - Email: Thư thông báo chính thức có định dạng HTML
 */
class SmsZnsNotificationMock
{
    /**
     * Phát sự kiện thông báo đa kênh tới người nộp hồ sơ (giữ tương thích ngược)
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

        // 1. Ghi thông báo trong hệ thống nếu có congDanId
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
            } catch (Throwable) {
                // Tiếp tục
            }
        }

        // 2. Ghi nhật ký tích hợp
        self::logIntegration('SMS_ZALO_GATEWAY', '/api/v1/notify/dispatch', [
            'brandname' => 'UBND TP HP',
            'receiver' => $soDienThoai,
            'ma_ho_so' => $maHoSo,
            'content' => $noiDung,
        ], [
            'sms_msg_id' => $smsId,
            'zalo_msg_id' => $znsId,
            'delivery_status' => 'SUCCESS',
        ]);

        return [
            'sms_id' => $smsId,
            'zns_msg_id' => $znsId,
            'status' => 'DELIVERED',
        ];
    }

    /**
     * Gửi một tin nhắn từ hàng đợi Outbox (SMS, Zalo ZNS hoặc Email)
     *
     * @param string $kenh Kênh (SMS, ZALO, EMAIL)
     * @param string $nguoiNhan Số điện thoại hoặc Email
     * @param string|null $tieuDe Tiêu đề
     * @param string $noiDung Nội dung tin nhắn
     * @param int|null $hoSoId ID hồ sơ
     * @return array{success: bool, msg_id: string, response: string}
     */
    public static function dispatch(
        string $kenh,
        string $nguoiNhan,
        ?string $tieuDe,
        string $noiDung,
        ?int $hoSoId = null
    ): array {
        $msgId = strtoupper($kenh) . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
        $endpoint = match ($kenh) {
            'SMS' => '/api/v1/sms/send-brandname',
            'ZALO' => '/api/v1/zns/template-message',
            'EMAIL' => '/api/v1/smtp/send-mail',
            default => '/api/v1/notify/dispatch'
        };

        $reqPayload = [
            'channel' => $kenh,
            'brandname' => $kenh === 'SMS' ? 'UBND TP HP' : 'Trung tâm Phục vụ HCC Hải Phòng',
            'receiver' => $nguoiNhan,
            'title' => $tieuDe,
            'content' => $noiDung,
            'ho_so_id' => $hoSoId,
            'timestamp' => date('c'),
        ];

        $resPayload = [
            'code' => 200,
            'message' => 'Gửi tin thành công qua cổng ' . $kenh,
            'message_id' => $msgId,
            'status' => 'DELIVERED',
            'received_at' => date('Y-m-d H:i:s'),
        ];

        // 1. Ghi vào nhat_ky_tich_hop
        self::logIntegration($kenh . '_GATEWAY', $endpoint, $reqPayload, $resPayload);

        // 2. Ghi vào webhook_log
        self::logWebhook("NOTIFY_{$kenh}_SENT", $reqPayload, "https://gateway.haiphong.gov.vn{$endpoint}", 200);

        return [
            'success' => true,
            'msg_id' => $msgId,
            'response' => json_encode($resPayload, JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Ghi nhật ký tích hợp (nhat_ky_tich_hop)
     */
    private static function logIntegration(string $heThong, string $endpoint, array $req, array $res): void
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare("INSERT INTO nhat_ky_tich_hop 
                (dich_vu, endpoint, method, request_payload, response_payload, status_code, thoi_gian_xu_ly_ms, created_at) 
                VALUES (:dv, :ep, 'POST', :req, :res, 200, 35, NOW())");
            $stmt->execute([
                ':dv' => $heThong,
                ':ep' => $endpoint,
                ':req' => json_encode($req, JSON_UNESCAPED_UNICODE),
                ':res' => json_encode($res, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (Throwable) {
            // Không ngắt luồng
        }
    }


    /**
     * Ghi nhật ký Webhook (webhook_log)
     */
    private static function logWebhook(string $suKien, array $payload, string $urlDich, int $statusCode): void
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare("INSERT INTO webhook_log 
                (su_kien, payload, url_dich, status_code, thoi_gian_goi) 
                VALUES (:sk, :pl, :url, :sc, NOW())");
            $stmt->execute([
                ':sk' => $suKien,
                ':pl' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                ':url' => $urlDich,
                ':sc' => $statusCode,
            ]);
        } catch (Throwable) {
            // Không ngắt luồng
        }
    }
}
