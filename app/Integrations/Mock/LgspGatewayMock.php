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

    /**
     * Đối chiếu thông tin Thửa đất / Giấy chứng nhận quyền sử dụng đất với CSDL Đất đai
     *
     * @param string $soThua Số thửa đất
     * @param string $soToBanDo Số tờ bản đồ
     * @param string $chuSuDung Tên chủ sử dụng
     * @return array{success: bool, code: string, message: string, data: array}
     */
    public static function verifyLandData(string $soThua, string $soToBanDo, string $chuSuDung): array
    {
        $startTime = microtime(true);
        $cleanThua = trim($soThua);
        $cleanTo = trim($soToBanDo);
        $cleanChu = trim($chuSuDung);

        $isMatch = !empty($cleanThua) && !empty($cleanTo);

        $response = [
            'success' => $isMatch,
            'code' => $isMatch ? 'LAND_MATCH_SUCCESS' : 'LAND_NOT_FOUND',
            'message' => $isMatch ? 'Thông tin thửa đất khớp đúng với CSDL Đất đai TP. Hải Phòng (VILIS).' : 'Không tìm thấy thông tin thửa đất trên hệ thống.',
            'data' => [
                'so_thua' => $cleanThua,
                'so_to_ban_do' => $cleanTo,
                'chu_su_dung' => mb_strtoupper($cleanChu, 'UTF-8'),
                'dien_tich_m2' => '120.5',
                'muc_dich_su_dung' => 'Đất ở tại đô thị (ODT)',
                'tinh_trang_tranh_chap' => 'KHONG_TRANH_CHAP',
                'tinh_trang_the_chap' => 'KHONG_THE_CHAP',
                'quy_hoach_xay_dung' => 'PHU_HOP_QUY_HOACH_CHI_TIET_1_500',
                'ma_xac_thuc_vilis' => 'LGSP-LAND-' . strtoupper(substr(md5($cleanThua . $cleanTo . time()), 0, 16)),
                'thoi_diem_tra_cuu' => date('Y-m-d H:i:s'),
            ],
        ];

        $durationMs = (int)((microtime(true) - $startTime) * 1000);

        try {
            $db = Database::connection();
            $stmt = $db->prepare("INSERT INTO nhat_ky_tich_hop (
                he_thong, endpoint, phuong_thuc, du_lieu_gui, ma_trang_thai, du_lieu_nhan, thoi_gian_ms, created_at
            ) VALUES (
                'LGSP_LAND', '/api/v1/land/verify', 'POST', :req, '200', :res, :ms, NOW()
            )");
            $stmt->execute([
                ':req' => json_encode(['so_thua' => $cleanThua, 'so_to_ban_do' => $cleanTo, 'chu' => $cleanChu], JSON_UNESCAPED_UNICODE),
                ':res' => json_encode($response, JSON_UNESCAPED_UNICODE),
                ':ms' => $durationMs,
            ]);
        } catch (\Throwable) {
        }

        return $response;
    }

    /**
     * Đối chiếu thông tin Phương tiện / Đăng kiểm với CSDL Giao thông vận tải
     */
    public static function verifyVehicleRegistration(string $bienSoXe, string $chuXe): array
    {
        $startTime = microtime(true);
        $cleanBienSo = strtoupper(trim($bienSoXe));

        $response = [
            'success' => true,
            'code' => 'VEHICLE_MATCH_SUCCESS',
            'message' => 'Phương tiện hợp lệ, còn hạn kiểm định an toàn kỹ thuật và bảo vệ môi trường.',
            'data' => [
                'bien_so' => $cleanBienSo,
                'chu_xe' => mb_strtoupper($chuXe, 'UTF-8'),
                'nhan_hieu' => 'HYUNDAI',
                'loai_xe' => 'O_TO_TAI',
                'trong_tai_tan' => 15.0,
                'han_kiem_dinh' => date('d/m/Y', strtotime('+6 months')),
                'trang_thai_phu_hieu' => 'CHUA_CAP_PHU_HIEU',
                'ma_xac_thuc_vr' => 'LGSP-VR-' . strtoupper(substr(md5($cleanBienSo . time()), 0, 16)),
            ],
        ];

        $durationMs = (int)((microtime(true) - $startTime) * 1000);

        try {
            $db = Database::connection();
            $stmt = $db->prepare("INSERT INTO nhat_ky_tich_hop (
                he_thong, endpoint, phuong_thuc, du_lieu_gui, ma_trang_thai, du_lieu_nhan, thoi_gian_ms, created_at
            ) VALUES (
                'LGSP_VR', '/api/v1/vehicle/verify', 'POST', :req, '200', :res, :ms, NOW()
            )");
            $stmt->execute([
                ':req' => json_encode(['bien_so' => $cleanBienSo, 'chu' => $chuXe], JSON_UNESCAPED_UNICODE),
                ':res' => json_encode($response, JSON_UNESCAPED_UNICODE),
                ':ms' => $durationMs,
            ]);
        } catch (\Throwable) {
        }

        return $response;
    }
}
