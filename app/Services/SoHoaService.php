<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Class SoHoaService – Quy trình số hóa hồ sơ đầu vào 4 bước theo Nghị định 107/2021/NĐ-CP
 * 
 * B1: Kiểm tra tính pháp lý đối chiếu bản chính / bản sao chứng thực
 * B2: Tải tệp quét scan (khuyến nghị >= 300 DPI, PDF)
 * B3: OCR mô phỏng trích xuất dữ liệu hành chính (CCCD, Họ tên, Số thửa đất, Biển số...)
 * B4: Ký số cán bộ Một cửa & Đồng bộ vào Kho dữ liệu điện tử cá nhân
 */
class SoHoaService
{
    /**
     * Bóc tách thông tin OCR mô phỏng từ tệp tài liệu
     *
     * @param string $tenTep Tên tệp gốc
     * @param string $loaiGiayTo Tên/loại thành phần giấy tờ
     * @param array<string, mixed> $citizenInfo Dữ liệu công dân bổ trợ
     * @return array<string, mixed>
     */
    public static function extractOcr(string $tenTep, string $loaiGiayTo, array $citizenInfo = []): array
    {
        $tenLower = mb_strtolower($tenTep, 'UTF-8');
        $loaiLower = mb_strtolower($loaiGiayTo, 'UTF-8');
        $extracted = [];

        // 1. Nhận diện CCCD / Định danh cá nhân
        if (str_contains($tenLower, 'cccd') || str_contains($loaiLower, 'cccd') || str_contains($tenLower, 'can_cuoc')) {
            $extracted['loai_tai_lieu_ocr'] = 'CAN_CUOC_CONG_DAN';
            $extracted['so_cccd'] = $citizenInfo['so_cccd'] ?? '031090001234';
            $extracted['ho_ten'] = $citizenInfo['ho_ten'] ?? 'NGUYỄN VĂN AN';
            $extracted['ngay_sinh'] = $citizenInfo['ngay_sinh'] ?? '15/05/1990';
            $extracted['do_tin_cay'] = '98.5%';
        }
        // 2. Nhận diện Giấy tờ nhà đất / Giấy chứng nhận quyền sử dụng đất
        elseif (str_contains($tenLower, 'dat') || str_contains($loaiLower, 'dat') || str_contains($tenLower, 'so_do') || str_contains($loaiLower, 'gcn')) {
            $extracted['loai_tai_lieu_ocr'] = 'GIAY_CHUNG_NHAN_DAT';
            $extracted['so_thua_dat'] = '45';
            $extracted['so_to_ban_do'] = '12';
            $extracted['dien_tich_m2'] = '120.5';
            $extracted['muc_dich_su_dung'] = 'Đất ở tại đô thị (ODT)';
            $extracted['do_tin_cay'] = '96.2%';
        }
        // 3. Nhận diện Giấy phép lái xe / Đăng ký xe
        elseif (str_contains($tenLower, 'gplx') || str_contains($loaiLower, 'lai_xe') || str_contains($tenLower, 'dang_ky_xe')) {
            $extracted['loai_tai_lieu_ocr'] = 'GIAY_PHEP_LAI_XE';
            $extracted['hang_gplx'] = 'B2';
            $extracted['so_gplx'] = '310190001234';
            $extracted['bien_so_xe'] = '15A-888.88';
            $extracted['do_tin_cay'] = '97.0%';
        }
        // 4. Mặc định: Trích xuất thông tin chung
        else {
            $extracted['loai_tai_lieu_ocr'] = 'VAN_BAN_HANH_CHINH_KHAC';
            $extracted['tieu_de_van_ban'] = $loaiGiayTo;
            $extracted['so_trang'] = 1;
            $extracted['do_tin_cay'] = '95.0%';
        }

        return $extracted;
    }

    /**
     * Alias trích xuất OCR mô phỏng phục vụ test nghiệp vụ
     */
    public static function ocrMoPhong(string $duongDanTep, string $tenTep, string $loaiGiayTo = 'Căn cước công dân'): array
    {
        return self::extractOcr($tenTep, $loaiGiayTo, ['so_cccd' => '031095000123']);
    }

    /**
     * Thực hiện quy trình số hóa toàn vẹn và đồng bộ Kho dữ liệu điện tử
     *
     * @param int $hoSoId ID hồ sơ
     * @param int|null $thanhPhanId ID thành phần hồ sơ quy định
     * @param array $fileData Mảng file upload $_FILES['file']
     * @param int $canBoId ID cán bộ Một cửa thực hiện số hóa
     * @param int $congDanId ID công dân (chủ hồ sơ)
     * @param bool $xacNhanBanChinh Cán bộ đã đối chiếu bản chính
     * @param string $tenGiayTo Tên thành phần giấy tờ
     * @return array Kết quả số hóa
     */
    public static function processDigitization(
        int $hoSoId,
        ?int $thanhPhanId,
        array $fileData,
        int $canBoId,
        int $congDanId,
        bool $xacNhanBanChinh,
        string $tenGiayTo
    ): array {
        if (!$xacNhanBanChinh) {
            throw new \InvalidArgumentException("Bắt buộc phải đối chiếu và xác nhận tính pháp lý của bản chính theo NĐ 107/2021/NĐ-CP.");
        }

        // 1. Tải lên và kiểm tra bảo mật tệp scan
        $uploadResult = FileUploadSecurityService::processUpload($fileData, 'sohoa');

        // 2. OCR mô phỏng trích xuất dữ liệu
        $ocrData = self::extractOcr($uploadResult['ten_goc'], $tenGiayTo);

        // 3. Ký số số hóa cá nhân bằng chứng thư của cán bộ Một cửa
        $signResult = ChuKySoService::signFile(
            $canBoId,
            $uploadResult['duong_dan'],
            'SO_HOA',
            null,
            "Số hóa đầu vào NĐ 107/2021: {$tenGiayTo}"
        );

        $db = Database::connection();

        // 4. Lưu bản ghi vào ho_so_tai_lieu
        $stmtTl = $db->prepare("INSERT INTO ho_so_tai_lieu (
            ho_so_id, thanh_phan_id, ten_goc, ten_luu, duong_dan, mime_type, 
            kich_thuoc_byte, sha256, loai_tai_lieu, so_hoa_boi_id, ngay_so_hoa, 
            chu_ky_so_id, created_at
        ) VALUES (
            :hid, :tpid, :tengoc, :tenluu, :path, :mime,
            :size, :sha, 'SO_HOA', :cbid, NOW(),
            :sigid, NOW()
        )");

        $stmtTl->execute([
            ':hid' => $hoSoId,
            ':tpid' => $thanhPhanId,
            ':tengoc' => $uploadResult['ten_goc'],
            ':tenluu' => $uploadResult['ten_luu'],
            ':path' => $uploadResult['duong_dan'],
            ':mime' => $uploadResult['mime_type'],
            ':size' => $uploadResult['kich_thuoc_byte'],
            ':sha' => $uploadResult['sha256'],
            ':cbid' => $canBoId,
            ':sigid' => $signResult['id'],
        ]);

        $taiLieuId = (int)$db->lastInsertId();

        // Cập nhật lại ID tài liệu vào bản ghi chu_ky_so
        $db->prepare("UPDATE chu_ky_so SET ho_so_tai_lieu_id = :tlid WHERE id = :sigid")
           ->execute([':tlid' => $taiLieuId, ':sigid' => $signResult['id']]);

        // 5. Đồng bộ vào Kho dữ liệu điện tử cá nhân để tái sử dụng
        if ($congDanId > 0) {
            $stmtKho = $db->prepare("INSERT INTO kho_du_lieu_cong_dan (
                cong_dan_id, ho_so_id, ten_tai_lieu, ma_loai_giay_to, duong_dan, 
                sha256, ngay_so_hoa, ngay_het_han, nguon_cap, trang_thai
            ) VALUES (
                :cid, :hid, :ten, :maloai, :path,
                :sha, NOW(), DATE_ADD(NOW(), INTERVAL 5 YEAR), 'SO_HOA_MOT_CUA', 1
            )");

            $stmtKho->execute([
                ':cid' => $congDanId,
                ':hid' => $hoSoId,
                ':ten' => $tenGiayTo,
                ':maloai' => $ocrData['loai_tai_lieu_ocr'] ?? 'GIAY_TO_SO_HOA',
                ':path' => $uploadResult['duong_dan'],
                ':sha' => $uploadResult['sha256'],
            ]);
        }

        // Ghi Audit log
        AuditService::log(
            $canBoId,
            'SO_HOA_TAI_LIEU_ND107',
            'ho_so_tai_lieu',
            $taiLieuId,
            null,
            [
                'ho_so_id' => $hoSoId,
                'ten_tai_lieu' => $tenGiayTo,
                'sha256' => $uploadResult['sha256'],
                'ocr' => $ocrData
            ]
        );

        return [
            'tai_lieu_id' => $taiLieuId,
            'ten_tai_lieu' => $tenGiayTo,
            'duong_dan' => $uploadResult['duong_dan'],
            'sha256' => $uploadResult['sha256'],
            'ocr' => $ocrData,
            'chu_ky_so_id' => $signResult['id'],
        ];
    }
}
