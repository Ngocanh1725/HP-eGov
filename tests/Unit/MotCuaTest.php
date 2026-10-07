<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Integrations\Mock\BuuChinhGatewayMock;
use App\Integrations\Mock\LgspGatewayMock;
use App\Services\ChuKySoService;
use App\Services\MaHoSoGenerator;
use App\Services\SlaEngine;
use App\Services\SoHoaService;
use App\Services\WorkflowEngine;
use DateTime;
use PDO;
use TestRunner;

/**
 * MotCuaTest – Kiểm thử đơn vị các nghiệp vụ Bộ phận Một cửa (Phiên 8/16)
 * Bao gồm: Sinh mã QCVN 102 chống trùng, C06 qua LGSP, Động cơ SLA, Số hóa NĐ 107/2021, VNPost
 */
class MotCuaTest
{
    private PDO $db;

    public function setUp(): void
    {
        $this->db = Database::connection();
    }

    /**
     * Test 1: Sinh mã hồ sơ QCVN 102 đảm bảo duy nhất và không trùng lặp
     */
    public function testSinhMaHoSoQcvn102KhongTrung(): void
    {
        $ma1 = MaHoSoGenerator::generate(1, '4123');
        $ma2 = MaHoSoGenerator::generate(1, '4123');
        $ma3 = MaHoSoGenerator::generate(2, '2888');

        TestRunner::assertTrue(strlen($ma1) >= 15, "Mã hồ sơ phải có độ dài chuẩn QCVN 102.");
        TestRunner::assertTrue($ma1 !== $ma2, "Hai mã hồ sơ sinh liên tiếp trong cùng cơ quan không được trùng nhau.");
        TestRunner::assertTrue($ma1 !== $ma3, "Hai mã hồ sơ khác cơ quan không được trùng nhau.");
    }

    /**
     * Test 2: Tra cứu & đối soát CCCD với C06 qua LgspGatewayMock
     */
    public function testLgspGatewayMockC06(): void
    {
        // CCCD 12 số hợp lệ
        $res = LgspGatewayMock::verifyCitizen('031095000123', 'NGUYỄN VĂN AN');
        TestRunner::assertTrue($res['success'], "Tra cứu C06 với CCCD 12 số phải thành công.");
        TestRunner::assertEquals('C06_MATCH_SUCCESS', $res['code'], "Mã kết quả đối soát phải là C06_MATCH_SUCCESS.");
        TestRunner::assertTrue(!empty($res['data']['ma_xac_thuc_lgsp']), "Phải có mã xác thực LGSP.");
        TestRunner::assertTrue(str_starts_with($res['data']['ma_xac_thuc_lgsp'], 'LGSP-C06-'), "Mã đối soát phải có tiền tố LGSP-C06-.");

        // CCCD không đủ 12 số
        $resInvalid = LgspGatewayMock::verifyCitizen('123456', 'NGUYỄN VĂN AN');
        TestRunner::assertFalse($resInvalid['success'], "CCCD sai định dạng phải trả về thất bại.");
    }

    /**
     * Test 3: Tính ngày hẹn trả qua SlaEngine (loại trừ T7, CN và chốt 17:00)
     */
    public function testSlaEngineCalculateNgayHenTra(): void
    {
        // Tạo mốc bắt đầu vào Thứ Hai: 2026-10-12 08:30:00
        $ngayTiepNhan = '2026-10-12 08:30:00';
        $soNgay = 5; // 5 ngày làm việc tiếp theo: T3, T4, T5, T6, (T7-CN nghỉ), T2 (19/10)
        
        $ngayHen = SlaEngine::calculateNgayHenTra($ngayTiepNhan, $soNgay);
        TestRunner::assertTrue(!empty($ngayHen), "SlaEngine phải trả về ngày hẹn trả hợp lệ.");
        
        $dtHen = new DateTime($ngayHen);
        TestRunner::assertEquals('17:00:00', $dtHen->format('H:i:s'), "Giờ hẹn trả mặc định phải chốt lúc 17:00.");
        TestRunner::assertEquals('2026-10-19', $dtHen->format('Y-m-d'), "5 ngày làm việc tiếp theo kể từ T2 phải kết thúc vào T2 tuần sau (19/10).");

        // 7 ngày làm việc: T2 (19) + T3 (20), T4 (21) -> 21/10/2026
        $ngayHen7 = SlaEngine::calculateNgayHenTra($ngayTiepNhan, 7);
        $dtHen7 = new DateTime($ngayHen7);
        TestRunner::assertEquals('2026-10-21', $dtHen7->format('Y-m-d'), "7 ngày làm việc phải tự động bỏ qua T7 và CN.");
    }

    /**
     * Test 4: Đánh giá màu cảnh báo SLA (XANH, VANG, DO)
     */
    public function testSlaEngineEvaluateColor(): void
    {
        // Hồ sơ mới nhận, còn nhiều thời gian -> XANH
        $slaXanh = SlaEngine::evaluateSla(
            '2026-10-20 17:00:00',
            '2026-10-07 08:00:00',
            null,
            40
        );
        TestRunner::assertEquals('XANH', $slaXanh['mau'], "Hồ sơ mới nhận phải có màu XANH.");

        // Hồ sơ quá hạn -> DO
        $slaDo = SlaEngine::evaluateSla(
            '2026-10-05 17:00:00',
            '2026-10-01 08:00:00',
            null,
            40
        );
        TestRunner::assertEquals('DO', $slaDo['mau'], "Hồ sơ quá hạn hẹn trả phải có màu DO.");
    }

    /**
     * Test 5: Quy trình số hóa đầu vào theo Nghị định 107/2021/NĐ-CP & Ký số OpenSSL
     */
    public function testSoHoaNghiDinh107(): void
    {
        // 1. Kiểm tra cặp khóa và ký số OpenSSL của cán bộ Một cửa (User 2: motcua.hongbang)
        $userId = 2;
        $contentDemo = "NỘI DUNG TÀI LIỆU SCAN ĐẦU VÀO TTHC - HẢI PHÒNG";
        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_scan_' . uniqid() . '.pdf';
        file_put_contents($tempFile, $contentDemo);

        $kySoResult = ChuKySoService::signFile($userId, $tempFile, 'SO_HOA', null, 'Kiểm thử số hóa');
        TestRunner::assertTrue($kySoResult['id'] > 0, "Chữ ký phải được lưu vào CSDL với ID > 0.");
        TestRunner::assertTrue(!empty($kySoResult['signature']), "Chữ ký Base64 không được rỗng.");

        // 2. Xác thực chữ ký số vừa tạo
        $valid = ChuKySoService::verifySignature($kySoResult['sha256'], $kySoResult['signature'], $kySoResult['cert_path']);
        TestRunner::assertTrue($valid, "Chữ ký số cán bộ Một cửa vừa ký phải hợp lệ khi xác thực qua OpenSSL.");

        // 3. OCR mô phỏng trích xuất thông tin
        $ocr = SoHoaService::ocrMoPhong($tempFile, 'scan_cccd_031095000123_nguyen_van_an.pdf');
        TestRunner::assertEquals('031095000123', $ocr['so_cccd'], "OCR phải bóc tách được số CCCD từ tên file.");

        @unlink($tempFile);
    }

    /**
     * Test 6: Bưu điện VNPost BuuChinhGatewayMock
     */
    public function testBuuChinhGatewayMock(): void
    {
        $res = BuuChinhGatewayMock::createShipment(
            '000.00.01.H15-261007-4123-000001',
            'Nguyễn Văn An',
            'Số 18 Hoàng Diệu, Hồng Bàng, Hải Phòng',
            '0912345678'
        );

        TestRunner::assertTrue($res['success'], "Tạo vận đơn VNPost phải thành công.");
        TestRunner::assertTrue(str_starts_with($res['ma_van_don'], 'HP'), "Mã vận đơn bưu chính công ích phải bắt đầu bằng HP.");
        TestRunner::assertTrue(str_ends_with($res['ma_van_don'], 'VN'), "Mã vận đơn bưu chính công ích phải kết thúc bằng VN.");
    }

    /**
     * Test 7: Luân chuyển hồ sơ qua WorkflowEngine
     */
    public function testWorkflowEngineTransition(): void
    {
        // Lấy một quy trình có bước bắt đầu và bước kế tiếp
        $buocDau = $this->db->query("SELECT * FROM quy_trinh_buoc WHERE loai_buoc = 'TIEP_NHAN' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        
        if ($buocDau) {
            $next = WorkflowEngine::getNextStep((int)$buocDau['quy_trinh_id'], (int)$buocDau['id']);
            TestRunner::assertTrue($next !== null, "Sau bước TIEP_NHAN phải có bước tiếp theo trong quy trình.");
            TestRunner::assertGreaterThan((int)$buocDau['stt_buoc'], (int)$next['stt_buoc'], "Thứ tự bước tiếp theo phải lớn hơn bước đầu.");
        }
    }
}
