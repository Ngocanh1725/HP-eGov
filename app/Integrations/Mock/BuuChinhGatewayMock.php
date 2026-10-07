<?php

declare(strict_types=1);

namespace App\Integrations\Mock;

use App\Core\Database;

/**
 * Class BuuChinhGatewayMock – Giả lập kết nối Dịch vụ bưu chính công ích VNPost chuyển phát kết quả TTHC
 */
class BuuChinhGatewayMock
{
    /**
     * Tạo vận đơn chuyển phát kết quả TTHC qua VNPost
     *
     * @param string $maHoSo Mã hồ sơ HP-eGov
     * @param string $nguoiNhan Tên người nhận kết quả
     * @param string $diaChiNhan Địa chỉ nhận thư
     * @param string $soDienThoai Số điện thoại liên hệ
     * @return array{success: bool, ma_van_don: string, trang_thai_van_don: string, thoi_gian_hen_giao: string}
     */
    public static function createShipment(
        string $maHoSo,
        string $nguoiNhan,
        string $diaChiNhan,
        string $soDienThoai
    ): array {
        $startTime = microtime(true);

        // Sinh mã vận đơn chuẩn VNPost bưu chính công ích: HP{yyMMdd}{6 số ngẫu nhiên}VN
        $stt = str_pad((string)random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        $maVanDon = 'HP' . date('ymd') . $stt . 'VN';

        $response = [
            'success' => true,
            'ma_van_don' => $maVanDon,
            'trang_thai_van_don' => 'DA_TIEP_NHAN_BUU_CUC',
            'ma_buu_cuc_gui' => '180000', // Bưu cục Trung tâm TP. Hải Phòng
            'thoi_gian_hen_giao' => date('d/m/Y', strtotime('+2 days')),
            'phi_cuoc' => 0, // Miễn cước bưu chính công ích hoặc thanh toán sau
        ];

        $durationMs = (int)((microtime(true) - $startTime) * 1000);

        try {
            $db = Database::connection();
            $stmt = $db->prepare("INSERT INTO nhat_ky_tich_hop (
                he_thong, endpoint, phuong_thuc, du_lieu_gui, ma_trang_thai, du_lieu_nhan, thoi_gian_ms, created_at
            ) VALUES (
                'VNPOST', '/api/v1/shipments/create', 'POST', :req, '200', :res, :ms, NOW()
            )");
            $stmt->execute([
                ':req' => json_encode([
                    'ma_ho_so' => $maHoSo,
                    'nguoi_nhan' => $nguoiNhan,
                    'dia_chi' => $diaChiNhan,
                    'sdt' => $soDienThoai,
                ], JSON_UNESCAPED_UNICODE),
                ':res' => json_encode($response, JSON_UNESCAPED_UNICODE),
                ':ms' => $durationMs,
            ]);
        } catch (\Throwable) {
        }

        return $response;
    }
}
