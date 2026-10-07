<?php

declare(strict_types=1);

namespace App\Integrations\Mock;

use App\Core\Database;

/**
 * Class LgspGatewayMock – Giả lập kết nối LGSP Hải Phòng đối soát CSDLQG về Dân cư (C06 Bộ Công an)
 */
class LgspGatewayMock
{
    /**
     * Đối chiếu thông tin công dân với CSDLQG về Dân cư qua LGSP
     *
     * @param string $soCccd Số CCCD 12 số
     * @param string $hoTen Họ và tên
     * @param string|null $ngaySinh Ngày sinh Y-m-d hoặc d/m/Y
     * @return array{success: bool, code: string, message: string, data: array}
     */
    public static function verifyCitizen(string $soCccd, string $hoTen, ?string $ngaySinh = null): array
    {
        $startTime = microtime(true);
        $cleanCccd = trim($soCccd);
        $cleanHoTen = trim($hoTen);

        // Kiểm tra định dạng cơ bản: 12 chữ số
        $isValidFormat = (bool)preg_match('/^\d{12}$/', $cleanCccd);

        if (!$isValidFormat) {
            $response = [
                'success' => false,
                'code' => 'C06_INVALID_FORMAT',
                'message' => 'Số CCCD không đúng định dạng 12 chữ số quy chuẩn.',
                'data' => [],
            ];
        } else {
            // Giả lập đối chiếu khớp đúng 100% với C06
            $response = [
                'success' => true,
                'code' => 'C06_MATCH_SUCCESS',
                'message' => 'Thông tin đối soát hoàn toàn khớp đúng với CSDL Quốc gia về Dân cư.',
                'data' => [
                    'so_cccd' => $cleanCccd,
                    'ho_ten' => mb_strtoupper($cleanHoTen, 'UTF-8'),
                    'ngay_sinh' => $ngaySinh ?? '1990-01-01',
                    'trang_thai_cu_tru' => 'THUONG_TRU_HOP_PHAP',
                    'tinh_thanh' => 'Thành phố Hải Phòng',
                    'ma_xac_thuc_lgsp' => 'LGSP-C06-' . strtoupper(substr(md5($cleanCccd . time()), 0, 16)),
                    'thoi_diem_doi_chieu' => date('Y-m-d H:i:s'),
                ],
            ];
        }

        $durationMs = (int)((microtime(true) - $startTime) * 1000);

        // Ghi nhật ký tích hợp
        try {
            $db = Database::connection();
            $stmt = $db->prepare("INSERT INTO nhat_ky_tich_hop (
                he_thong, endpoint, phuong_thuc, du_lieu_gui, ma_trang_thai, du_lieu_nhan, thoi_gian_ms, created_at
            ) VALUES (
                'LGSP_C06', '/api/v1/citizen/verify', 'POST', :req, :code, :res, :ms, NOW()
            )");
            $stmt->execute([
                ':req' => json_encode(['so_cccd' => $cleanCccd, 'ho_ten' => $cleanHoTen, 'ngay_sinh' => $ngaySinh], JSON_UNESCAPED_UNICODE),
                ':code' => $response['success'] ? '200' : '400',
                ':res' => json_encode($response, JSON_UNESCAPED_UNICODE),
                ':ms' => $durationMs,
            ]);
        } catch (\Throwable) {
            // Không làm gián đoạn luồng nếu bảng log gặp sự cố
        }

        return $response;
    }
}
