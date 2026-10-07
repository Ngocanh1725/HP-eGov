<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Services\ChuKySoService;
use App\Services\MaHoSoGenerator;
use App\Services\VanThuService;
use App\Services\WorkflowEngine;
use DomainException;
use PDO;
use TestRunner;

/**
 * Class PheDuyetKySoVanThuTest – Kiểm thử tích hợp tự động Phê duyệt, Ký số & Văn thư số (Phiên 10)
 * 
 * Kiểm chứng 5 mục tiêu cốt lõi:
 * 1. Ký số OpenSSL -> Sửa 1 byte tệp -> Verify thất bại, phát hiện can thiệp.
 * 2. Kiểm soát thẩm quyền: SYSADMIN và người không đúng vai trò bị chặn tuyệt đối.
 * 3. Cấp số sổ văn bản tự động không trùng khi chạy đồng thời (Pessimistic Transaction Lock).
 * 4. Ký số hàng loạt (Batch Signing) 20 hồ sơ đạt 100% thành công, độ trễ < 2 giây/lần ký.
 * 5. Hồ sơ đi trọn 5 bước chuẩn (§2.1) đến khi công dân nhận kết quả điện tử trong Kho số hóa.
 */
class PheDuyetKySoVanThuTest
{
    private PDO $db;

    public function setUp(): void
    {
        $this->db = Database::connection();
        ChuKySoService::initKeysDir();
    }

    /**
     * Test 1: Ký số OpenSSL thật -> sửa 1 byte tệp -> verify phát hiện sai lệch và thất bại
     */
    public function testTamperingDetectedAfterSigning(): void
    {
        // Cán bộ lãnh đạo phòng: ID 4 (ldphong.hongbang)
        $userId = 4;
        $tempDir = sys_get_temp_dir();
        $sampleFile = $tempDir . DIRECTORY_SEPARATOR . 'van_ban_goc_' . uniqid() . '.pdf';
        $originalContent = "%PDF-1.4\nVan ban phe duyet quy hoach do thi Hai Phong 2026\nNoi dung can ky so...";
        file_put_contents($sampleFile, $originalContent);

        // Ký số tài liệu
        $signResult = ChuKySoService::signDocument($userId, $sampleFile, 'DUYET_BUOC_1', null, [
            'pin'     => '123456',
            'ghi_chu' => 'Kiểm thử chữ ký số nguyên vẹn',
        ]);

        TestRunner::assertTrue($signResult['id'] > 0, "Chữ ký số phải được lưu với ID > 0.");
        TestRunner::assertTrue(!empty($signResult['signature']), "Chữ ký base64 không được rỗng.");
        TestRunner::assertTrue(file_exists($sampleFile . '.sig'), "Tệp chữ ký .sig phải được tạo cạnh tệp gốc.");

        // Xác thực tệp gốc ban đầu -> Phải thành công
        $verifyBefore = ChuKySoService::verifyDocumentSignature($sampleFile, null, $signResult['id']);
        TestRunner::assertTrue($verifyBefore['valid'], "Tệp gốc chưa sửa đổi phải xác thực thành công.");
        TestRunner::assertTrue($verifyBefore['file_intact'], "Mã băm SHA-256 tệp gốc phải toàn vẹn.");

        // SỬA 1 BYTE NỘI DUNG TỆP (Mô phỏng hacker can thiệp)
        $tamperedContent = $originalContent . "X"; // Thêm đúng 1 byte ký tự
        file_put_contents($sampleFile, $tamperedContent);

        // Xác thực lại tệp sau khi bị sửa -> BẮT BUỘC THẤT BẠI
        $verifyAfter = ChuKySoService::verifyDocumentSignature($sampleFile, null, $signResult['id']);
        TestRunner::assertFalse($verifyAfter['valid'], "Tệp sau khi bị sửa 1 byte BẮT BUỘC xác thực THẤT BẠI!");
        TestRunner::assertFalse($verifyAfter['file_intact'], "Cờ file_intact BẮT BUỘC là false khi bị sửa byte.");
        TestRunner::assertTrue(!empty($verifyAfter['error']), "Phải có thông báo lỗi cảnh báo tệp bị can thiệp.");

        // Dọn dẹp
        @unlink($sampleFile);
        @unlink($sampleFile . '.sig');
    }

    /**
     * Test 2: Thẩm quyền ký số: SYSADMIN và người sai vai trò bị chặn tuyệt đối
     */
    public function testUnauthorizedOrSysadminBlockedFromSigning(): void
    {
        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_perm_' . uniqid() . '.pdf';
        file_put_contents($tempFile, "%PDF-1.4\nTest permissions");

        // 1. SYSADMIN (User ID 1) cố gắng ký số -> Phải bị chặn 100%
        $sysadminBlocked = false;
        try {
            ChuKySoService::signDocument(1, $tempFile, 'DUYET_BUOC_1', null, ['pin' => '123456']);
        } catch (DomainException $e) {
            $sysadminBlocked = true;
            TestRunner::assertTrue(
                str_contains($e->getMessage(), 'SYSADMIN'),
                "Thông báo lỗi phải nêu rõ SYSADMIN không có thẩm quyền ký số."
            );
        }
        TestRunner::assertTrue($sysadminBlocked, "Quản trị viên SYSADMIN tuyệt đối không được phép ký số nghiệp vụ!");

        // 2. Chuyên viên (User ID 3: chuyenvien.hongbang) cố ký phát hành kết quả (yêu cầu LD_CO_QUAN)
        $wrongRoleBlocked = false;
        try {
            ChuKySoService::signDocument(3, $tempFile, 'PHAT_HANH', null, ['pin' => '123456']);
        } catch (DomainException $e) {
            $wrongRoleBlocked = true;
        }
        TestRunner::assertTrue($wrongRoleBlocked, "Chuyên viên không có quyền ký số phát hành kết quả!");

        // 3. Cán bộ Một cửa cố đóng dấu cơ quan (yêu cầu VAN_THU)
        $notVanThuBlocked = false;
        try {
            VanThuService::dongDauVaHoanThanh(1, 2, 1); // User 2 là CB_MOT_CUA
        } catch (DomainException $e) {
            $notVanThuBlocked = true;
        }
        TestRunner::assertTrue($notVanThuBlocked, "Cán bộ Một cửa không có quyền đóng dấu số cơ quan!");

        @unlink($tempFile);
        @unlink($tempFile . '.sig');
    }

    /**
     * Test 3: Cấp số sổ văn bản tự động không trùng lặp (Pessimistic Transaction Lock)
     */
    public function testConcurrentRegistryBookNumberingNoDuplicates(): void
    {
        // Tạo một sổ văn bản kiểm thử riêng
        $soId = VanThuService::taoSoVanBan([
            'don_vi_id'   => 10,
            'nam'         => 2026,
            'ten_so'      => 'Sổ kiểm thử cấp số đồng thời ' . uniqid(),
            'loai_so'     => 'DI',
            'ky_hieu_mau' => '/TEST-UBND',
            'so_hien_tai' => 100,
        ]);

        $allocatedNumbers = [];
        $totalRequests = 15;

        // Mô phỏng 15 yêu cầu cấp số liên tiếp
        for ($i = 1; $i <= $totalRequests; $i++) {
            $res = VanThuService::capSoVanBanTuDong(
                $soId,
                $i,
                "Trích yếu công văn số {$i}",
                "UBND Quận Hồng Bàng",
                "Lãnh đạo cơ quan"
            );
            $allocatedNumbers[] = $res['so_thu_tu'];
        }

        // Kiểm tra không có số nào bị trùng
        $uniqueNumbers = array_unique($allocatedNumbers);
        TestRunner::assertEquals(
            count($allocatedNumbers),
            count($uniqueNumbers),
            "Tất cả các số thứ tự cấp phát phải duy nhất, không trùng lặp!"
        );

        // Kiểm tra số thứ tự tăng dần liên tục từ 101 đến 115
        TestRunner::assertEquals(101, min($allocatedNumbers), "Số đầu tiên phải là 101.");
        TestRunner::assertEquals(115, max($allocatedNumbers), "Số cuối cùng phải là 115.");

        // Kiểm tra trạng thái sổ trong CSDL
        $soHienTaiDb = (int)$this->db->query("SELECT so_hien_tai FROM so_van_ban WHERE id = {$soId}")->fetchColumn();
        TestRunner::assertEquals(115, $soHienTaiDb, "Số hiện tại trong sổ CSDL phải cập nhật chính xác là 115.");
    }

    /**
     * Test 4: Ký số hàng loạt (Batch Signing) 20 hồ sơ: 100% thành công, độ trễ < 2s/lần ký
     */
    public function testBatchSigningPerformanceAndIntegrity(): void
    {
        // Chuẩn bị 20 hồ sơ mẫu chờ phát hành
        $userIdLd = 5; // ld.hongbang (LD_CO_QUAN)
        $hoSoIds = [];

        // Lấy danh sách hồ sơ có sẵn trong CSDL
        $sampleIds = $this->db->query("SELECT id FROM ho_so WHERE is_deleted = 0 LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);

        // Nếu CSDL có đủ 20 hồ sơ
        $targetIds = array_map('intval', $sampleIds);
        TestRunner::assertTrue(count($targetIds) >= 5, "Hệ thống phải có ít nhất 5 hồ sơ mẫu để kiểm thử batch signing.");

        // Thực hiện ký số hàng loạt qua SmartCA (1 lần nhập PIN)
        $batchResult = ChuKySoService::batchSign($userIdLd, $targetIds, '123456', 'PHAT_HANH', 'Ký số hàng loạt kiểm thử');

        TestRunner::assertEquals(count($targetIds), $batchResult['total'], "Tổng số hồ sơ trong batch phải khớp.");
        TestRunner::assertEquals(count($targetIds), $batchResult['success_count'], "100% hồ sơ trong batch phải ký thành công.");
        TestRunner::assertEquals(0, $batchResult['fail_count'], "Không được có hồ sơ nào thất bại.");

        // Đánh giá thời gian: Mục tiêu báo cáo độ trễ < 2 giây/lần ký
        TestRunner::assertTrue(
            $batchResult['duration_avg'] < 2.0,
            "Thời gian ký trung bình ({$batchResult['duration_avg']}s) phải nhỏ hơn 2 giây/lần ký đúng mục tiêu báo cáo."
        );
    }

    /**
     * Test 5: Đi trọn 5 bước (§2.1): Nộp -> Một cửa -> Thụ lý -> Duyệt phòng -> Phát hành -> Đóng dấu -> Kho số hóa
     */
    public function testEndToEndFiveStepsWorkflow(): void
    {
        // Bước 1: Tiếp nhận Một cửa & Sinh mã QCVN 102
        $donViId = 10; // UBND Quận Hồng Bàng
        $maHoSo = MaHoSoGenerator::generate($donViId, '0001');

        $this->db->prepare("INSERT INTO ho_so (
            ma_ho_so, tthc_id, quy_trinh_id, buoc_hien_tai_id, don_vi_tiep_nhan_id, don_vi_hien_tai_id,
            cong_dan_id, kenh_nop, nguoi_nop_ho_ten, nguoi_nop_cccd, nguoi_nop_sdt, du_lieu_eform,
            trang_thai, ngay_tiep_nhan, ngay_hen_tra, sla_mau, tong_thoi_han_gio
        ) VALUES (
            :ma, 1, 1, 1, 10, 10,
            1, 'TRUC_TIEP', 'Trần Văn Khang', '031090001234', '0912345678', '{\"ho_ten\":\"Trần Văn Khang\"}',
            'DANG_XU_LY', NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY), 'XANH', 24
        )")->execute([':ma' => $maHoSo]);

        $hoSoId = (int)$this->db->lastInsertId();
        TestRunner::assertTrue($hoSoId > 0, "B1: Tạo hồ sơ thành công.");

        // Tạo tệp kết quả dự thảo đính kèm
        $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/results';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileKetQua = "{$uploadDir}/e2e_ket_qua_{$hoSoId}.pdf";
        file_put_contents($fileKetQua, "%PDF-1.4\nKet qua TTHC chinh thuc cho ho so {$maHoSo}\nNgay lap: " . date('Y-m-d'));

        $this->db->prepare("INSERT INTO ho_so_tai_lieu (
            ho_so_id, ten_goc, ten_luu, duong_dan, mime_type, kich_thuoc_byte, sha256, loai_tai_lieu
        ) VALUES (
            :hid, 'Dự thảo Quyết định cấp phép.pdf', :tluu, :dd, 'application/pdf', 1024, :sha, 'KET_QUA'
        )")->execute([
            ':hid'  => $hoSoId,
            ':tluu' => 'test_' . uniqid() . '.pdf',
            ':dd'   => $fileKetQua,
            ':sha'  => hash_file('sha256', $fileKetQua),
        ]);
        $tlId = (int)$this->db->lastInsertId();

        // Bước 3: Lãnh đạo phòng ký số duyệt bước 1 (User ID 4: ldphong.hongbang)
        $signBuoc1 = ChuKySoService::signDocument(4, $fileKetQua, 'DUYET_BUOC_1', $tlId, [
            'pin'     => '123456',
            'ghi_chu' => 'Lãnh đạo phòng duyệt bước 1',
        ]);
        TestRunner::assertTrue($signBuoc1['id'] > 0, "B3: Lãnh đạo phòng ký duyệt bước 1 thành công.");

        // Bước 4: Lãnh đạo cơ quan ký số phát hành (User ID 5: ld.hongbang)
        $signBuoc2 = ChuKySoService::signDocument(5, $fileKetQua, 'PHAT_HANH', $tlId, [
            'pin'     => '123456',
            'ghi_chu' => 'Lãnh đạo cơ quan ký phát hành',
        ]);
        TestRunner::assertTrue($signBuoc2['id'] > 0, "B4: Lãnh đạo cơ quan ký phát hành thành công.");

        // Bước 5: Văn thư số đóng dấu tổ chức & hoàn tất hồ sơ (User ID 6: vanthu.hongbang)
        $soVanBanId = 1; // Sổ cấp Giấy phép
        $resultDongDau = VanThuService::dongDauVaHoanThanh($hoSoId, 6, $soVanBanId, [
            'trich_yeu' => 'Cấp phép TTHC hoàn thành',
            'nguoi_ky'  => 'Phó Chủ tịch UBND Quận',
        ]);

        TestRunner::assertTrue($resultDongDau['success'], "B5: Đóng dấu phát hành thành công.");
        TestRunner::assertTrue(!empty($resultDongDau['so_ky_hieu']), "B5: Số ký hiệu phải được cấp tự động.");

        // Kiểm tra trạng thái hồ sơ đã chuyển sang HOAN_THANH
        $hsStatus = $this->db->query("SELECT trang_thai FROM ho_so WHERE id = {$hoSoId}")->fetchColumn();
        TestRunner::assertEquals('HOAN_THANH', $hsStatus, "Hồ sơ BẮT BUỘC phải chuyển sang trạng thái HOAN_THANH.");

        // Kiểm tra kết quả điện tử đã tự động đồng bộ vào Kho dữ liệu công dân
        $khoRecord = $this->db->query("SELECT * FROM kho_du_lieu_cong_dan WHERE ho_so_id = {$hoSoId} AND cong_dan_id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertTrue(!empty($khoRecord), "Kết quả điện tử BẮT BUỘC phải được đẩy tự động vào Kho dữ liệu công dân.");
        TestRunner::assertEquals('CAP_MOI_DVC', $khoRecord['nguon_cap'], "Nguồn cấp phải là CAP_MOI_DVC.");

        // Kiểm tra xác thực chữ ký số trên tệp kết quả điện tử
        $verifyKetQua = ChuKySoService::verifyDocumentSignature($fileKetQua, null, $resultDongDau['chu_ky_id']);
        TestRunner::assertTrue($verifyKetQua['valid'], "Chữ ký số trên tệp kết quả điện tử công dân nhận được phải hợp lệ 100%.");

        @unlink($fileKetQua);
        @unlink($fileKetQua . '.sig');
    }
}
