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

    /**
     * Tiếp nhận Webhook thanh toán trực tuyến từ Cổng DVCQG PayGov / VNPAY (§1.1, §2.2)
     *
     * @param Request $request
     * @return Response
     */
    public function handlePayment(Request $request): Response
    {
        $signature = $request->header('X-Payment-Signature')
            ?: $request->header('HTTP_X_PAYMENT_SIGNATURE')
            ?: $request->header('X-Signature');

        $rawPayload = file_get_contents('php://input');
        if ($rawPayload === false || $rawPayload === '') {
            $rawPayload = json_encode($request->all(), JSON_UNESCAPED_UNICODE);
        }

        $secretKey = 'payment_hmac_secret_sha256_hp_egov';

        // Bắt buộc phải có chữ ký xác thực
        if (!$signature) {
            return $this->error('Thiếu tiêu đề chữ ký xác thực X-Payment-Signature', 401);
        }

        // Xác thực HMAC
        $expectedHmac = hash_hmac('sha256', (string)$rawPayload, $secretKey);
        $cleanSig = preg_replace('/^sha256=/i', '', trim((string)$signature));
        if (!hash_equals(strtolower($expectedHmac), strtolower((string)$cleanSig))) {
            return $this->error('Chữ ký số HMAC thanh toán không hợp lệ hoặc dữ liệu bị sửa đổi', 403);
        }

        $data = json_decode((string)$rawPayload, true) ?: $request->all();
        $maHoSo = $data['ma_ho_so'] ?? null;
        $maGiaoDich = $data['ma_giao_dich'] ?? ('PAY-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)));
        $soTien = (float)($data['so_tien'] ?? 0);
        $congThanhToan = $data['cong_thanh_toan'] ?? 'VNPAY';
        $trangThai = strtoupper((string)($data['trang_thai'] ?? 'SUCCESS'));

        if (!$maHoSo) {
            return $this->error('Thiếu mã hồ sơ trong thông điệp thanh toán', 422);
        }

        $db = Database::connection();
        $stmtHs = $db->prepare("SELECT * FROM ho_so WHERE ma_ho_so = :ma AND is_deleted = 0 LIMIT 1");
        $stmtHs->execute([':ma' => $maHoSo]);
        $hoSo = $stmtHs->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            return $this->error("Không tìm thấy hồ sơ có mã: {$maHoSo}", 404);
        }

        $db->beginTransaction();
        try {
            // Cập nhật eform JSON chứa thông tin biên lai điện tử
            $eform = json_decode((string)$hoSo['du_lieu_eform'], true) ?: [];
            $eform['thong_tin_thanh_toan'] = [
                'trang_thai'           => 'DA_THANH_TOAN',
                'ma_giao_dich'         => $maGiaoDich,
                'so_tien'              => $soTien,
                'cong_thanh_toan'      => $congThanhToan,
                'thoi_gian_thanh_toan' => date('Y-m-d H:i:s'),
                'ma_bien_lai'          => 'BL-' . date('ymd') . '-' . substr(md5($maGiaoDich), 0, 6),
            ];

            $updateStmt = $db->prepare("UPDATE ho_so SET du_lieu_eform = :ef, updated_at = NOW() WHERE id = :id");
            $updateStmt->execute([
                ':ef' => json_encode($eform, JSON_UNESCAPED_UNICODE),
                ':id' => $hoSo['id'],
            ]);

            // Tạo bản ghi biên lai điện tử trong ho_so_tai_lieu
            $receiptFileName = "bien_lai_{$maGiaoDich}.pdf";
            $receiptPath = "storage/receipts/{$receiptFileName}";
            $receiptSha256 = hash('sha256', $maGiaoDich . '|' . $soTien . '|' . $maHoSo);

            $db->prepare("INSERT INTO ho_so_tai_lieu (
                ho_so_id, ten_goc, ten_luu, duong_dan, mime_type, kich_thuoc_byte, sha256, loai_tai_lieu, created_at
            ) VALUES (
                :hs_id, :ten_goc, :ten_luu, :duong_dan, 'application/pdf', 2048, :sha, 'KET_QUA', NOW()
            )")->execute([
                ':hs_id'     => $hoSo['id'],
                ':ten_goc'   => "Biên lai thu phí, lệ phí điện tử ({$maGiaoDich}).pdf",
                ':ten_luu'   => $receiptFileName,
                ':duong_dan' => $receiptPath,
                ':sha'       => $receiptSha256,
            ]);

            // Ghi nhật ký luân chuyển
            $db->prepare("INSERT INTO ho_so_luan_chuyen (
                ho_so_id, tu_don_vi_id, den_don_vi_id, nguoi_chuyen_id, hanh_dong, trang_thai_truoc, trang_thai_sau, noi_dung_y_kien, thoi_gian_chuyen
            ) VALUES (
                :hs_id, :tu_dv, :den_dv, 1, 'THANH_TOAN_TRUC_TUYEN', :st_truoc, :st_sau, :yk, NOW()
            )")->execute([
                ':hs_id'    => $hoSo['id'],
                ':tu_dv'    => $hoSo['don_vi_hien_tai_id'],
                ':den_dv'   => $hoSo['don_vi_hien_tai_id'],
                ':st_truoc' => $hoSo['trang_thai'],
                ':st_sau'   => $hoSo['trang_thai'],
                ':yk'       => "Nộp phí/lệ phí trực tuyến thành công qua {$congThanhToan}: " . number_format($soTien, 0, ',', '.') . " VNĐ. Mã GD: {$maGiaoDich}",
            ]);

            // Ghi Audit Log WORM
            \App\Services\AuditService::log(
                null,
                'THANH_TOAN_TRUC_TUYEN',
                'ho_so',
                (int)$hoSo['id'],
                null,
                "Hồ sơ {$maHoSo} đã thanh toán {$soTien} VNĐ qua {$congThanhToan} (GD: {$maGiaoDich})"
            );

            // Ghi nhật ký tích hợp
            $db->prepare("INSERT INTO nhat_ky_tich_hop (
                dich_vu, endpoint, method, request_payload, response_payload, status_code, thoi_gian_xu_ly_ms, created_at
            ) VALUES (
                'PAYMENT_GATEWAY', '/api/v1/webhook/payment', 'WEBHOOK', :req, :res, 200, 15, NOW()
            )")->execute([
                ':req' => $rawPayload,
                ':res' => json_encode(['status' => 'PAYMENT_RECORDED', 'ma_giao_dich' => $maGiaoDich]),
            ]);

            $db->commit();

            return $this->success([
                'ma_ho_so'             => $maHoSo,
                'ma_giao_dich'         => $maGiaoDich,
                'so_tien'              => $soTien,
                'trang_thai_thanh_toan'=> 'DA_THANH_TOAN',
                'bien_lai'             => $receiptFileName,
            ], 'Biên lai điện tử và kết quả thanh toán đã được cập nhật thành công vào hồ sơ.');
        } catch (Throwable $e) {
            $db->rollBack();
            return $this->error('Lỗi cập nhật thanh toán: ' . $e->getMessage(), 500);
        }
    }
}
