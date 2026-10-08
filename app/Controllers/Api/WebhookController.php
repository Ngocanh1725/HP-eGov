<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;
use Throwable;

/**
 * Class WebhookController
 * Tiếp nhận Webhook từ trục LGSP / NDXP có xác thực chữ ký HMAC-SHA256 (§1.1, §1.3)
 */
class WebhookController extends ApiController
{
    /**
     * Tiếp nhận Webhook từ LGSP Hải Phòng
     *
     * @param Request $request
     * @return Response
     */
    public function handleLgsp(Request $request): Response
    {
        $signature = $request->header('X-LGSP-Signature') 
            ?: $request->header('HTTP_X_LGSP_SIGNATURE')
            ?: $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return $this->error('Thiếu tiêu đề chữ ký xác thực X-LGSP-Signature', 401);
        }

        // Lấy secret_key của dịch vụ LGSP từ CSDL
        $pdo = Database::pdo();
        $stmtConfig = $pdo->prepare("SELECT secret_key, che_do FROM cau_hinh_tich_hop WHERE ma_dich_vu = 'LGSP' LIMIT 1");
        $stmtConfig->execute();
        $config = $stmtConfig->fetch(PDO::FETCH_ASSOC);

        $secretKey = $config['secret_key'] ?? 'lgsp_hmac_secret_sha256_hp_connect_webhook';

        // Lấy raw payload
        $rawPayload = file_get_contents('php://input');
        if ($rawPayload === false || $rawPayload === '') {
            $rawPayload = json_encode($request->all(), JSON_UNESCAPED_UNICODE);
        }

        // Tính HMAC SHA-256
        $expectedHmac = hash_hmac('sha256', (string)$rawPayload, (string)$secretKey);

        // Chuẩn hóa chuỗi so khớp (bỏ tiền tố 'sha256=' nếu có)
        $cleanSig = preg_replace('/^sha256=/i', '', trim((string)$signature));

        if (!hash_equals(strtolower($expectedHmac), strtolower((string)$cleanSig))) {
            return $this->error('Chữ ký HMAC không hợp lệ hoặc dữ liệu bị giả mạo trên đường truyền', 403);
        }

        // Payload hợp lệ -> Parse nội dung JSON
        $data = json_decode((string)$rawPayload, true) ?: $request->all();
        $maHoSo = $data['ma_ho_so'] ?? null;
        $suKien = $data['event'] ?? 'DONG_BO_KET_QUA_LGSP';
        $trangThaiMoi = $data['trang_thai'] ?? null;

        $db = Database::connection();
        $db->beginTransaction();

        try {
            // Cập nhật trạng thái hồ sơ nếu có mã hồ sơ
            if ($maHoSo) {
                $stmtHs = $db->prepare("SELECT id, don_vi_tiep_nhan_id FROM ho_so WHERE ma_ho_so = :ma LIMIT 1");
                $stmtHs->execute([':ma' => $maHoSo]);
                $hoSo = $stmtHs->fetch(PDO::FETCH_ASSOC);

                if ($hoSo && $trangThaiMoi) {
                    $db->prepare("UPDATE ho_so SET trang_thai = :st, updated_at = NOW() WHERE id = :id")
                       ->execute([':st' => $trangThaiMoi, ':id' => $hoSo['id']]);

                    // Ghi luân chuyển
                    $db->prepare("
                        INSERT INTO ho_so_luan_chuyen (
                            ho_so_id, tu_don_vi_id, den_don_vi_id, nguoi_chuyen_id,
                            hanh_dong, trang_thai_truoc, trang_thai_sau, noi_dung_y_kien, thoi_gian_chuyen
                        ) VALUES (
                            :hs_id, :tu_dv, :den_dv, 1,
                            'DONG_BO_LGSP', :tt_truoc, :tt_sau, :yk, NOW()
                        )
                    ")->execute([
                        ':hs_id'    => $hoSo['id'],
                        ':tu_dv'    => $hoSo['don_vi_tiep_nhan_id'],
                        ':den_dv'   => $hoSo['don_vi_tiep_nhan_id'],
                        ':tt_truoc' => $hoSo['trang_thai'] ?? 'DANG_XU_LY',
                        ':tt_sau'   => $trangThaiMoi,
                        ':yk'       => "Đồng bộ tự động từ trục LGSP Hải Phòng ({$suKien})",
                    ]);
                }
            }

            // Ghi nhật ký vào nhat_ky_tich_hop
            $db->prepare("
                INSERT INTO nhat_ky_tich_hop (
                    dich_vu, endpoint, method, request_payload, response_payload, status_code, thoi_gian_xu_ly_ms
                ) VALUES (
                    'LGSP', '/api/v1/webhook/lgsp', 'WEBHOOK', :req, :res, 200, 12
                )
            ")->execute([
                ':req' => $rawPayload,
                ':res' => json_encode(['status' => 'ACCEPTED', 'timestamp' => date('Y-m-d H:i:s')]),
            ]);

            $db->commit();

            return $this->success([
                'event_received' => $suKien,
                'ma_ho_so'       => $maHoSo,
                'verified'       => true,
            ], 'Webhook LGSP đã được tiếp nhận và xác thực HMAC thành công');
        } catch (Throwable $e) {
            $db->rollBack();
            return $this->error('Lỗi xử lý nội dung webhook: ' . $e->getMessage(), 500);
        }
    }
}
