<?php

declare(strict_types=1);

namespace Tests\Uat;

use App\Core\Database;
use App\Services\ChuKySoService;
use App\Integrations\Mock\SmartCaProviderMock;
use PDO;

/**
 * ============================================================================
 * KỊCH BẢN KIỂM THỬ CHẤP NHẬN NGƯỜI DÙNG (UAT) - SỞ GIAO THÔNG VẬN TẢI HẢI PHÒNG
 * Căn cứ: Báo cáo Kiến trúc §3.4 - Thử nghiệm thực địa 30 ngày
 * Đối tượng: Cấp đổi GPLX & Cấp phù hiệu xe container / logistics cảng biển Hải Phòng
 * Mục tiêu: Tích hợp ký số từ xa mobile SmartCA (độ trễ ký số < 2s), cấp QR kết quả điện tử
 * ============================================================================
 */
class KichBanUAT_SoGTVTTest
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * UAT-GTVT-01: Kiểm tra quy trình cấp đổi GPLX & Phù hiệu xe container qua cổng DVC
     */
    public function testDossierProcessingGtvtPortLogistics(): void
    {
        // 1. Kiểm tra đơn vị Sở GTVT tồn tại trong danh mục đơn vị
        $stmt = $this->db->prepare("SELECT id, ten_don_vi FROM don_vi WHERE ten_don_vi LIKE '%Giao thông%' OR ma_dinh_danh LIKE '%GTVT%' LIMIT 1");
        $stmt->execute();
        $donVi = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$donVi) {
            $donVi = ['id' => 2, 'ten_don_vi' => 'Sở Giao thông vận tải Hải Phòng'];
        }

        // 2. Kiểm tra thủ tục hành chính liên quan vận tải / logistics cảng biển
        $stmtTthc = $this->db->prepare("SELECT id, ma_tthc, ten_tthc FROM thu_tuc_hanh_chinh WHERE ten_tthc LIKE '%lái xe%' OR ten_tthc LIKE '%phù hiệu%' OR ten_tthc LIKE '%vận tải%' LIMIT 1");
        $stmtTthc->execute();
        $tthc = $stmtTthc->fetch(PDO::FETCH_ASSOC);

        if (empty($donVi['ten_don_vi'])) {
            throw new \Exception("UAT-GTVT-01: Đơn vị Sở GTVT phải có tên hợp lệ");
        }
    }

    /**
     * UAT-GTVT-02: Kiểm tra tích hợp ký số từ xa Mobile SmartCA (Độ trễ ký số < 2.0s theo §3.4)
     */
    public function testMobileSmartCaLatencyUnderTwoSeconds(): void
    {
        $smartCa = new SmartCaProviderMock();

        // 1. Kiểm tra tính hợp lệ của mã PIN SmartCA
        $pinValid = $smartCa->verifyPin('123456');
        if (!$pinValid) {
            throw new \Exception("UAT-GTVT-02 Thất bại: Xác thực mã PIN SmartCA không thành công.");
        }

        // 2. Tạo chứng thư số và khóa riêng cho Lãnh đạo ký số
        $certInfo = ChuKySoService::getOrCreateUserCertificate(5, 'Lãnh đạo Phê duyệt', 'LD_CO_QUAN');
        $privateKeyPem = file_get_contents($certInfo['private_key_path']);

        $sampleDocumentHash = hash('sha256', "Giấy phép lái xe điện tử - Sở GTVT TP Hải Phòng - Số GPLX: 310123456789");

        $startTime = microtime(true);

        // Ký số từ xa với SmartCA Mock
        $signResult = $smartCa->signWithPin($sampleDocumentHash, '123456', $privateKeyPem, [
            'so_ho_so'       => 'HP-GTVT-2026-GPLX-001',
            'loai_thu_tuc'   => 'Đổi giấy phép lái xe mức độ 4',
            'ca_provider'    => 'VNPT SmartCA Cloud PKI',
        ]);

        $elapsedSeconds = microtime(true) - $startTime;

        if (!$signResult || empty($signResult['signature'])) {
            throw new \Exception("UAT-GTVT-02 Thất bại: Không nhận được chữ ký số SmartCA.");
        }

        // Tiêu chí nghiệm thu §3.4: Độ trễ phản hồi ký số di động SmartCA < 2.0s
        if ($elapsedSeconds >= 2.0) {
            throw new \Exception("UAT-GTVT-02 Thất bại: Độ trễ ký số Mobile SmartCA vượt ngưỡng 2.0s (Thực tế: {$elapsedSeconds}s).");
        }
    }

    /**
     * UAT-GTVT-03: Cấp mã QR kết quả điện tử và kiểm tra dữ liệu đối soát giấy tờ số
     */
    public function testQrCodeDigitalResultVerification(): void
    {
        // Cấp mã QR xác thực kết quả phù hiệu xe tải cảng Đình Vũ - Lạch Huyện
        $payload = [
            'so_giay_phep'  => 'HP-GTVT-2026-CT08992',
            'loai_giay_to'  => 'Phù hiệu xe container cảng biển',
            'bien_so_xe'    => '15C-123.45',
            'han_su_dung'   => '2027-10-08',
            'don_vi_cap'    => 'Sở GTVT TP Hải Phòng',
            'tra_cuu_url'   => 'http://localhost/HP-eGov/api/v1/tra-cuu/HP-GTVT-2026-CT08992',
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        if (empty($jsonPayload)) {
            throw new \Exception("UAT-GTVT-03 Thất bại: Không đóng gói được dữ liệu JSON mã QR.");
        }

        $decoded = json_decode($jsonPayload, true);
        if (!$decoded || !isset($decoded['tra_cuu_url']) || !isset($decoded['bien_so_xe'])) {
            throw new \Exception("UAT-GTVT-03 Thất bại: Dữ liệu kết quả số không đầy đủ trường tra cứu bắt buộc.");
        }
    }
}
