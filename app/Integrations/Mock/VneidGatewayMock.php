<?php

declare(strict_types=1);

namespace App\Integrations\Mock;

use App\Core\Database;
use Throwable;

/**
 * Class VneidGatewayMock – Giả lập Cổng Xác thực & Định danh điện tử Quốc gia VNeID Mức độ 2
 * Căn cứ: Nghị định số 59/2022/NĐ-CP của Chính phủ & Đề án 06/CP
 */
class VneidGatewayMock
{
    /**
     * Xác thực danh tính công dân với VNeID Mức độ 2
     *
     * @param string $soCccd Số căn cước công dân 12 số
     * @param string $otpOrToken Mã OTP hoặc Bearer Token phiên xác thực
     * @return array{success: bool, code: string, message: string, muc_do_dinh_danh: int, data: array}
     */
    public static function authenticateLevel2(string $soCccd, string $otpOrToken = '123456'): array
    {
        $startTime = microtime(true);
        $cleanCccd = trim($soCccd);

        // Kiểm tra định dạng số CCCD
        if (!preg_match('/^\d{12}$/', $cleanCccd)) {
            return [
                'success'           => false,
                'code'              => 'VNEID_INVALID_CCCD',
                'message'           => 'Số CCCD không đúng quy chuẩn 12 chữ số.',
                'muc_do_dinh_danh'  => 0,
                'data'              => [],
            ];
        }

        // Mô phỏng hồ sơ định danh điện tử Mức 2
        $profile = self::getVerifiedCitizenProfile($cleanCccd);

        $durationMs = (int)((microtime(true) - $startTime) * 1000);

        self::logIntegration('VNEID_LEVEL2_AUTH', '/api/v1/vneid/level2/auth', [
            'so_cccd' => $cleanCccd,
            'otp'     => '***' . substr($otpOrToken, -3),
        ], [
            'status'           => 'AUTHENTICATED',
            'muc_do_dinh_danh' => 2,
            'sub'              => $profile['vneid_sub'],
        ], $durationMs);

        return [
            'success'          => true,
            'code'             => 'VNEID_AUTH_LEVEL2_SUCCESS',
            'message'          => 'Xác thực định danh điện tử VNeID Mức độ 2 thành công theo NĐ 59/2022/NĐ-CP.',
            'muc_do_dinh_danh' => 2,
            'data'             => $profile,
        ];
    }

    /**
     * Trích xuất thông tin nhân thân đã xác thực từ CSDL Quốc gia về Dân cư (C06)
     *
     * @param string $soCccd
     * @return array<string, mixed>
     */
    public static function getVerifiedCitizenProfile(string $soCccd): array
    {
        $cleanCccd = trim($soCccd);

        // Tra cứu trước trong CSDL local xem công dân đã đăng ký chưa
        try {
            $db = Database::connection();
            $stmt = $db->prepare("SELECT * FROM cong_dan WHERE so_cccd = :cccd LIMIT 1");
            $stmt->execute([':cccd' => $cleanCccd]);
            $local = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($local) {
                return [
                    'so_cccd'            => $local['so_cccd'],
                    'vneid_sub'          => $local['vneid_sub'] ?: ('vneid_sub_' . $cleanCccd),
                    'ho_ten'             => $local['ho_ten'],
                    'ngay_sinh'          => $local['ngay_sinh'] ?: '1990-01-01',
                    'gioi_tinh'          => $local['gioi_tinh'] ?: 'NAM',
                    'dan_toc'            => 'Kinh',
                    'ton_giao'           => 'Không',
                    'que_quan'           => $local['que_quan'] ?: 'Hải Phòng',
                    'dia_chi_thuong_tru' => $local['dia_chi_thuong_tru'] ?: 'Thành phố Hải Phòng',
                    'so_dien_thoai'      => $local['so_dien_thoai'] ?: '0901234567',
                    'email'              => $local['email'] ?: "congdan.{$cleanCccd}@haiphong.gov.vn",
                    'muc_do_dinh_danh'   => 2,
                    'xac_thuc_c06'       => true,
                ];
            }
        } catch (Throwable) {
            // Không ngắt nếu query lỗi
        }

        // Dữ liệu mô phỏng chuẩn C06
        return [
            'so_cccd'            => $cleanCccd,
            'vneid_sub'          => 'vneid_sub_' . $cleanCccd,
            'ho_ten'             => 'NGUYỄN VĂN HẢI',
            'ngay_sinh'          => '1988-10-15',
            'gioi_tinh'          => 'NAM',
            'dan_toc'            => 'Kinh',
            'ton_giao'           => 'Không',
            'que_quan'           => 'Quận Hồng Bàng, Thành phố Hải Phòng',
            'dia_chi_thuong_tru' => 'Số 15 Lê Đại Hành, Phường Hoàng Văn Thụ, Quận Hồng Bàng, Hải Phòng',
            'so_dien_thoai'      => '0912345678',
            'email'              => "nvhai.{$cleanCccd}@dvc.haiphong.gov.vn",
            'muc_do_dinh_danh'   => 2,
            'xac_thuc_c06'       => true,
        ];
    }

    /**
     * Xác thực dữ liệu chip sinh trắc học trên thẻ CCCD (MRZ / ICAO 9303)
     */
    public static function verifyBiometricChip(string $soCccd, string $chipData): array
    {
        $isValid = strlen(trim($chipData)) > 10;
        return [
            'success' => $isValid,
            'code'    => $isValid ? 'CHIP_VERIFIED' : 'CHIP_INVALID',
            'message' => $isValid ? 'Dữ liệu chip điện tử C06 hợp lệ và khớp đúng sinh trắc học.' : 'Dữ liệu chip không hợp lệ.',
            'so_cccd' => $soCccd,
        ];
    }

    /**
     * Ghi nhật ký tích hợp
     */
    private static function logIntegration(string $service, string $endpoint, array $req, array $res, int $durationMs): void
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare("INSERT INTO nhat_ky_tich_hop (
                dich_vu, endpoint, method, request_payload, response_payload, status_code, thoi_gian_xu_ly_ms, created_at
            ) VALUES (
                :srv, :ep, 'POST', :req, :res, 200, :ms, NOW()
            )");
            $stmt->execute([
                ':srv' => $service,
                ':ep'  => $endpoint,
                ':req' => json_encode($req, JSON_UNESCAPED_UNICODE),
                ':res' => json_encode($res, JSON_UNESCAPED_UNICODE),
                ':ms'  => $durationMs,
            ]);
        } catch (Throwable) {
            // Không làm gián đoạn luồng
        }
    }
}
