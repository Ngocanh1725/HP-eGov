<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Services\MaHoSoGenerator;
use App\Services\WorkflowEngine;
use PDO;
use TestRunner;

/**
 * LienThongLogisticsTest – Kịch bản kiểm thử tích hợp tự động Liên thông dự án Logistics cảng biển đa cơ quan (§3.1)
 * 
 * Luồng đi: UBND Q. Hồng Bàng (Đầu mối tiếp nhận B1)
 *          ➔ Sở Xây dựng (Thẩm định quy hoạch B2)
 *          ➔ Lấy ý kiến phối hợp Sở TN&MT (Thẩm định ĐTM B3)
 *          ➔ Sở TN&MT phản hồi ý kiến
 *          ➔ Sở KH&ĐT (Tổng hợp đầu tư B4)
 *          ➔ Trình Lãnh đạo UBND TP Hải Phòng (B5)
 * 
 * Tiêu chí kiểm chứng:
 * - Không deadlock khi chạy transaction và pessimistic lock
 * - Trạng thái hồ sơ và đơn vị hiện tại chuyển đổi chính xác
 * - Tính toàn vẹn tệp đính kèm (Mã băm SHA-256 bất biến qua mỗi trạm)
 * - Toàn bộ chuỗi luân chuyển được ghi vết đầy đủ trong ho_so_luan_chuyen và audit
 */
class LienThongLogisticsTest
{
    private PDO $db;

    public function setUp(): void
    {
        $this->db = Database::connection();
    }

    public function testLienThongLogisticsFlow(): void
    {
        // 1. Chuẩn bị hồ sơ dự án Logistics tại UBND Quận Hồng Bàng
        $tthcId = 7; // Thủ tục: Chấp thuận chủ trương đầu tư dự án logistics cảng biển
        $quyTrinh = $this->db->query("SELECT * FROM quy_trinh WHERE tthc_id = {$tthcId} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertTrue(!empty($quyTrinh), "Phải có quy trình cho thủ tục Logistics cảng biển.");

        $buocDau = $this->db->query("SELECT * FROM quy_trinh_buoc WHERE quy_trinh_id = {$quyTrinh['id']} AND stt_buoc = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertTrue(!empty($buocDau), "Phải có bước bắt đầu của quy trình.");

        $donViHongBangId = (int)$buocDau['don_vi_xu_ly_id']; // ID 30: Bộ phận Một cửa Hồng Bàng
        $canBoMotCuaId = 2; // Cán bộ Một cửa mẫu
        $maHoSo = MaHoSoGenerator::generate($donViHongBangId, '9999');

        // Tạo hồ sơ trong CSDL
        $stmtInsertHs = $this->db->prepare("INSERT INTO ho_so (
            ma_ho_so, tthc_id, quy_trinh_id, buoc_hien_tai_id, don_vi_tiep_nhan_id, don_vi_hien_tai_id,
            cong_dan_id, kenh_nop, nguoi_nop_ho_ten, nguoi_nop_cccd, nguoi_nop_sdt, du_lieu_eform,
            trang_thai, ngay_nop, ngay_tiep_nhan, ngay_hen_tra, sla_mau, tong_thoi_han_gio, created_at, updated_at
        ) VALUES (
            :ma, :tid, :qid, :bid, :dvid_tn, :dvid_ht,
            1, 'TRUC_TIEP', 'Tập đoàn Cảng biển Quốc tế Hải Phòng', '031095000999', '0912345678',
            '{\"ten_du_an\":\"Khu Logistics Cảng Lạch Huyện\",\"dien_tich_ha\":50.5,\"tong_von_ty_dong\":2500}',
            'MOI_TIEP_NHAN', NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY), 'XANH', 120, NOW(), NOW()
        )");
        $stmtInsertHs->execute([
            ':ma' => $maHoSo,
            ':tid' => $tthcId,
            ':qid' => $quyTrinh['id'],
            ':bid' => $buocDau['id'],
            ':dvid_tn' => $donViHongBangId,
            ':dvid_ht' => $donViHongBangId,
        ]);
        $hoSoId = (int)$this->db->lastInsertId();
        TestRunner::assertTrue($hoSoId > 0, "Khởi tạo hồ sơ dự án Logistics thành công.");

        // 2. Tạo tài liệu đính kèm (Báo cáo nghiên cứu tiền khả thi) và tính mã SHA-256
        $tempDir = dirname(__DIR__, 2) . '/storage/uploads/test';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $docContent = "BÁO CÁO NGHIÊN CỨU TIỀN KHẢ THI DỰ ÁN LOGISTICS CẢNG BIỂN LẠCH HUYỆN - TP HẢI PHÒNG";
        $docPath = $tempDir . '/bc_tien_kha_thi_' . uniqid() . '.pdf';
        file_put_contents($docPath, $docContent);
        $expectedSha256 = hash_file('sha256', $docPath);

        $this->db->prepare("INSERT INTO ho_so_tai_lieu (
            ho_so_id, ten_goc, ten_luu, duong_dan, mime_type, kich_thuoc_byte, sha256, loai_tai_lieu, created_at
        ) VALUES (
            :hid, 'BaoCaoTienKhaThi_LachHuyen.pdf', :tl, :dd, 'application/pdf', :size, :sha, 'DAU_VAO', NOW()
        )")->execute([
            ':hid' => $hoSoId,
            ':tl' => basename($docPath),
            ':dd' => $docPath,
            ':size' => filesize($docPath),
            ':sha' => $expectedSha256,
        ]);

        // Kiểm tra toàn vẹn tài liệu trước khi luân chuyển
        $checkInit = WorkflowEngine::verifyFileIntegrity($hoSoId);
        TestRunner::assertTrue($checkInit['valid'], "Tài liệu ban đầu phải toàn vẹn 100%.");

        // 3. TRẠM 1 ➔ TRẠM 2: UBND Q. Hồng Bàng chuyển liên thông sang Sở Xây dựng (Bước 2)
        $buoc2 = $this->db->query("SELECT * FROM quy_trinh_buoc WHERE quy_trinh_id = {$quyTrinh['id']} AND stt_buoc = 2 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertTrue(!empty($buoc2), "Phải có bước 2: Sở Xây dựng thẩm định quy hoạch.");

        $resChuyen1 = WorkflowEngine::chuyenTiep($hoSoId, $canBoMotCuaId, (int)$buoc2['id'], "Một cửa Hồng Bàng chuyển hồ sơ liên thông sang Sở Xây dựng.");
        TestRunner::assertTrue($resChuyen1['success'], "Chuyển liên thông từ Hồng Bàng sang Sở Xây dựng phải thành công.");

        // Kiểm chứng trạng thái và đơn vị hiện tại
        $hsSauTram1 = $this->db->query("SELECT * FROM ho_so WHERE id = {$hoSoId}")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertEquals((int)$buoc2['don_vi_xu_ly_id'], (int)$hsSauTram1['don_vi_hien_tai_id'], "Đơn vị hiện tại phải chuyển sang Sở Xây dựng (ID 41).");
        TestRunner::assertEquals((int)$buoc2['id'], (int)$hsSauTram1['buoc_hien_tai_id'], "Bước hiện tại phải là Bước 2.");

        // Kiểm chứng mã băm SHA-256 của file không bị thay đổi
        $checkTram1 = WorkflowEngine::verifyFileIntegrity($hoSoId);
        TestRunner::assertTrue($checkTram1['valid'], "Mã băm SHA-256 của tệp đính kèm không đổi khi đến Sở Xây dựng.");

        // 4. TẠI SỞ XÂY DỰNG: Chuyên viên Sở XD gửi yêu cầu lấy ý kiến phối hợp sang Sở TN&MT (Bước 3 / Liên thông ngang)
        $chuyenVienSxdId = 15; // Chuyên viên Sở Xây dựng
        $donViStnmtId = 51;    // Phòng ĐTM - Sở TN&MT
        $hanTraLoi = date('Y-m-d 17:00:00', strtotime('+3 days'));

        $resLayYk = WorkflowEngine::layYKien(
            $hoSoId, 
            $chuyenVienSxdId, 
            $donViStnmtId, 
            "Đề nghị Sở TN&MT thẩm định đánh giá tác động môi trường (ĐTM) khu vực bến cảng logistics.",
            $hanTraLoi
        );
        TestRunner::assertTrue($resLayYk['success'], "Sở Xây dựng gửi yêu cầu lấy ý kiến phối hợp thành công.");
        TestRunner::assertTrue($resLayYk['y_kien_id'] > 0, "Yêu cầu ý kiến phải được lưu vào CSDL.");

        // Kiểm chứng bản ghi ý kiến trong ho_so_y_kien_phoi_hop
        $ykRecord = $this->db->query("SELECT * FROM ho_so_y_kien_phoi_hop WHERE id = {$resLayYk['y_kien_id']}")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertEquals('CHO_Y_KIEN', $ykRecord['trang_thai'], "Trạng thái ý kiến phải là CHO_Y_KIEN.");

        // 5. TẠI SỞ TN&MT: Chuyên viên Sở TN&MT phản hồi ý kiến chuyên môn
        $chuyenVienStnmtId = 18; // Chuyên viên Sở TN&MT
        $noiDungPhanHoi = "Sở TN&MT xác nhận: Báo cáo ĐTM dự án đáp ứng tiêu chuẩn xả thải loại A, phù hợp quy hoạch môi trường TP Hải Phòng.";
        $resTraLoiYk = WorkflowEngine::traLoiYKien($resLayYk['y_kien_id'], $chuyenVienStnmtId, $noiDungPhanHoi);
        TestRunner::assertTrue($resTraLoiYk['success'], "Sở TN&MT phản hồi ý kiến chuyên môn thành công.");

        $ykSauPhanHoi = $this->db->query("SELECT * FROM ho_so_y_kien_phoi_hop WHERE id = {$resLayYk['y_kien_id']}")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertEquals('DA_TRA_LOI', $ykSauPhanHoi['trang_thai'], "Trạng thái ý kiến chuyển sang DA_TRA_LOI.");

        // 6. TRẠM 2 ➔ TRẠM 3: Sở Xây dựng tổng hợp, chuyển tiếp sang Sở KH&ĐT (Bước 4)
        $buoc4 = $this->db->query("SELECT * FROM quy_trinh_buoc WHERE quy_trinh_id = {$quyTrinh['id']} AND stt_buoc = 4 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertTrue(!empty($buoc4), "Phải có bước 4: Sở KH&ĐT tổng hợp.");

        $resChuyen2 = WorkflowEngine::chuyenTiep($hoSoId, $chuyenVienSxdId, (int)$buoc4['id'], "Sở XD hoàn tất thẩm định quy hoạch và tiếp nhận ý kiến ĐTM, chuyển Sở KH&ĐT.");
        TestRunner::assertTrue($resChuyen2['success'], "Chuyển tiếp sang Sở KH&ĐT thành công.");

        $hsSauTram2 = $this->db->query("SELECT * FROM ho_so WHERE id = {$hoSoId}")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertEquals((int)$buoc4['don_vi_xu_ly_id'], (int)$hsSauTram2['don_vi_hien_tai_id'], "Đơn vị hiện tại phải là Sở KH&ĐT (ID 61).");

        // 7. TẠI SỞ KH&ĐT: Lập dự thảo Quyết định chủ trương và trình lên Lãnh đạo UBND Thành phố (Bước 5)
        $chuyenVienSkhdtId = 22; // Chuyên viên Sở KH&ĐT
        $buoc5 = $this->db->query("SELECT * FROM quy_trinh_buoc WHERE quy_trinh_id = {$quyTrinh['id']} AND stt_buoc = 5 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertTrue(!empty($buoc5), "Phải có bước 5: Lãnh đạo UBND Thành phố phê duyệt.");

        $resChuyen3 = WorkflowEngine::chuyenTiep($hoSoId, $chuyenVienSkhdtId, (int)$buoc5['id'], "Sở KH&ĐT kính trình Lãnh đạo UBND TP xem xét chấp thuận chủ trương đầu tư.");
        TestRunner::assertTrue($resChuyen3['success'], "Trình duyệt lên UBND Thành phố thành công.");

        $hsSauTram3 = $this->db->query("SELECT * FROM ho_so WHERE id = {$hoSoId}")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertEquals(1, (int)$hsSauTram3['don_vi_hien_tai_id'], "Đơn vị hiện tại phải là UBND TP Hải Phòng (ID 1).");
        TestRunner::assertEquals('CHO_PHE_DUYET', $hsSauTram3['trang_thai'], "Trạng thái tại UBND TP phải là CHO_PHE_DUYET.");

        // 8. KIỂM TRA TÍNH TOÀN VẸN CUỐI CÙNG & NHẬT KÝ LUÂN CHUYỂN
        // (a) Kiểm tra toàn vẹn tệp đính kèm sau khi đi qua 4 cơ quan
        $finalIntegrity = WorkflowEngine::verifyFileIntegrity($hoSoId);
        TestRunner::assertTrue($finalIntegrity['valid'], "Tất cả tệp tin vẫn toàn vẹn 100% (Mã băm SHA-256 không bị can thiệp qua 4 cơ quan).");

        // (b) Kiểm tra nhật ký luân chuyển toàn trình
        $luanChuyenCount = (int)$this->db->query("SELECT COUNT(*) FROM ho_so_luan_chuyen WHERE ho_so_id = {$hoSoId}")->fetchColumn();
        TestRunner::assertGreaterThan(4, $luanChuyenCount, "Phải ghi nhận đầy đủ các lượt luân chuyển, lấy ý kiến và phản hồi trong CSDL.");

        // Dọn dẹp tệp tạm
        @unlink($docPath);
    }

    /**
     * Kiểm thử nghiệp vụ SLA ĐỎ: Chặn chuyển bước thông thường nếu chưa có Phiếu xin lỗi Mẫu 04 (NĐ 61/2018)
     */
    public function testSlaDoBlockAndForm04(): void
    {
        // Tạo hồ sơ bị quá hạn (SLA ĐỎ)
        $tthcId = 7;
        $quyTrinh = $this->db->query("SELECT id FROM quy_trinh WHERE tthc_id = {$tthcId} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $buocDau = $this->db->query("SELECT id, don_vi_xu_ly_id FROM quy_trinh_buoc WHERE quy_trinh_id = {$quyTrinh['id']} AND stt_buoc = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("INSERT INTO ho_so (
            ma_ho_so, tthc_id, quy_trinh_id, buoc_hien_tai_id, don_vi_tiep_nhan_id, don_vi_hien_tai_id,
            cong_dan_id, kenh_nop, nguoi_nop_ho_ten, nguoi_nop_cccd, nguoi_nop_sdt,
            trang_thai, ngay_nop, ngay_tiep_nhan, ngay_hen_tra, sla_mau, tong_thoi_han_gio, created_at, updated_at
        ) VALUES (
            :ma, :tid, :qid, :bid, :dvid_tn, :dvid_ht,
            1, 'TRUC_TIEP', 'Doanh nghiệp Thử nghiệm SLA Đỏ', '031095000888', '0912000888',
            'DANG_XU_LY', NOW(), NOW(), DATE_SUB(NOW(), INTERVAL 2 DAY), 'DO', 120, NOW(), NOW()
        )");
        $maHs = 'HP-TEST-SLA-DO-' . uniqid();
        $stmt->execute([
            ':ma' => $maHs,
            ':tid' => $tthcId,
            ':qid' => $quyTrinh['id'],
            ':bid' => $buocDau['id'],
            ':dvid_tn' => (int)$buocDau['don_vi_xu_ly_id'],
            ':dvid_ht' => (int)$buocDau['don_vi_xu_ly_id'],
        ]);
        $hoSoId = (int)$this->db->lastInsertId();

        // 1. Chuyển bước khi chưa có Mẫu 04 -> Phải bị chặn và báo lỗi rõ ràng
        $resBlock = WorkflowEngine::chuyenTiep($hoSoId, 2, null, "Thử chuyển bước khi quá hạn");
        TestRunner::assertFalse($resBlock['success'], "Chuyển bước hồ sơ SLA ĐỎ phải bị chặn nếu thiếu Mẫu 04.");
        TestRunner::assertContains("Mẫu số 04", $resBlock['message'], "Thông báo lỗi phải nêu rõ yêu cầu Phiếu xin lỗi Mẫu 04.");

        // 2. Thêm Phiếu xin lỗi Mẫu 04 vào bảng van_ban_xin_loi và ho_so_tai_lieu
        $this->db->prepare("INSERT INTO van_ban_xin_loi (
            ho_so_id, so_van_ban, nguoi_ky_id, ly_do_qua_han, ngay_hen_moi, ngay_ky, trang_thai
        ) VALUES (
            :hid, '04/XL-UBND', 2, 'Do khối lượng thẩm định phức tạp cần xin ý kiến liên ngành', DATE_ADD(NOW(), INTERVAL 5 DAY), NOW(), 'DA_KY'
        )")->execute([':hid' => $hoSoId]);

        // 3. Chuyển bước lại sau khi đã có Mẫu 04 -> Phải thành công
        $buoc2 = $this->db->query("SELECT id FROM quy_trinh_buoc WHERE quy_trinh_id = {$quyTrinh['id']} AND stt_buoc = 2 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $resPass = WorkflowEngine::chuyenTiep($hoSoId, 2, (int)$buoc2['id'], "Chuyển bước kèm Phiếu xin lỗi Mẫu 04.");
        TestRunner::assertTrue($resPass['success'], "Chuyển bước hồ sơ SLA ĐỎ phải thành công khi đã có Phiếu xin lỗi Mẫu 04: " . ($resPass['message'] ?? ''));
    }

    /**
     * Kiểm thử phát hiện tệp tin bị can thiệp / sửa đổi nội dung qua mã băm SHA-256
     */
    public function testFileTamperingDetectedBySha256(): void
    {
        $hoSoId = (int)$this->db->query("SELECT id FROM ho_so ORDER BY id DESC LIMIT 1")->fetchColumn();
        $tempFile = dirname(__DIR__, 2) . '/storage/uploads/test/tamper_test_' . uniqid() . '.txt';
        file_put_contents($tempFile, "Nội dung gốc chưa chỉnh sửa");
        $originalHash = hash_file('sha256', $tempFile);

        $this->db->prepare("INSERT INTO ho_so_tai_lieu (
            ho_so_id, ten_goc, ten_luu, duong_dan, mime_type, kich_thuoc_byte, sha256, loai_tai_lieu, created_at
        ) VALUES (
            :hid, 'BanChungMinhQuyHoach.txt', :tl, :dd, 'text/plain', 50, :sha, 'DAU_VAO', NOW()
        )")->execute([
            ':hid' => $hoSoId,
            ':tl' => basename($tempFile),
            ':dd' => $tempFile,
            ':sha' => $originalHash,
        ]);
        $docId = (int)$this->db->lastInsertId();

        // Ban đầu toàn vẹn
        $check1 = WorkflowEngine::verifyFileIntegrity($hoSoId);
        TestRunner::assertTrue($check1['valid'], "Tệp tin ban đầu phải toàn vẹn.");

        // Giả lập kẻ xấu can thiệp sửa nội dung tệp tin trên ổ đĩa
        file_put_contents($tempFile, "NỘI DUNG ĐÃ BỊ THAY ĐỔI TRÁI PHÉP!");

        // Hệ thống phát hiện sai lệch SHA-256
        $check2 = WorkflowEngine::verifyFileIntegrity($hoSoId);
        TestRunner::assertFalse($check2['valid'], "Hệ thống phải phát hiện tệp tin bị can thiệp khi SHA-256 không khớp.");
        TestRunner::assertGreaterThan(0, count($check2['errors']), "Phải có thông báo lỗi chỉ ra tệp bị thay đổi.");

        // Dọn dẹp tệp tạm
        @unlink($tempFile);
    }

    /**
     * Kiểm thử tính năng Thu hồi hồ sơ đã chuyển
     */
    public function testThuHoiAction(): void
    {
        $tthcId = 7;
        $quyTrinh = $this->db->query("SELECT id FROM quy_trinh WHERE tthc_id = {$tthcId} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $buoc1 = $this->db->query("SELECT id, don_vi_xu_ly_id FROM quy_trinh_buoc WHERE quy_trinh_id = {$quyTrinh['id']} AND stt_buoc = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $buoc2 = $this->db->query("SELECT id, don_vi_xu_ly_id FROM quy_trinh_buoc WHERE quy_trinh_id = {$quyTrinh['id']} AND stt_buoc = 2 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("INSERT INTO ho_so (
            ma_ho_so, tthc_id, quy_trinh_id, buoc_hien_tai_id, don_vi_tiep_nhan_id, don_vi_hien_tai_id,
            cong_dan_id, kenh_nop, nguoi_nop_ho_ten, nguoi_nop_cccd, nguoi_nop_sdt,
            trang_thai, ngay_nop, ngay_tiep_nhan, ngay_hen_tra, sla_mau, tong_thoi_han_gio, created_at, updated_at
        ) VALUES (
            :ma, :tid, :qid, :bid, :dvid_tn, :dvid_ht,
            1, 'TRUC_TIEP', 'Doanh nghiệp Thu Hồi Test', '031095000777', '0912000777',
            'MOI_TIEP_NHAN', NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 10 DAY), 'XANH', 120, NOW(), NOW()
        )");
        $stmt->execute([
            ':ma' => 'HP-TEST-THUHOI-' . uniqid(),
            ':tid' => $tthcId,
            ':qid' => $quyTrinh['id'],
            ':bid' => $buoc1['id'],
            ':dvid_tn' => (int)$buoc1['don_vi_xu_ly_id'],
            ':dvid_ht' => (int)$buoc1['don_vi_xu_ly_id'],
        ]);
        $hoSoId = (int)$this->db->lastInsertId();

        $canBoChuyenId = 2; // Cán bộ 1
        // Chuyển sang bước 2
        $resChuyen = WorkflowEngine::chuyenTiep($hoSoId, $canBoChuyenId, (int)$buoc2['id'], "Chuyển nhầm bước cần thu hồi");
        TestRunner::assertTrue($resChuyen['success'], "Chuyển tiếp bước 2 thành công.");

        // Thu hồi lại bước 1
        $resThuHoi = WorkflowEngine::thuHoi($hoSoId, $canBoChuyenId, "Cán bộ thu hồi do gửi nhầm");
        TestRunner::assertTrue($resThuHoi['success'], "Thu hồi hồ sơ thành công.");

        // Kiểm tra hồ sơ đã quay về bước 1 và đơn vị ban đầu
        $hsSauThuHoi = $this->db->query("SELECT * FROM ho_so WHERE id = {$hoSoId}")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertEquals((int)$buoc1['id'], (int)$hsSauThuHoi['buoc_hien_tai_id'], "Hồ sơ phải quay về Bước 1 sau thu hồi.");
        TestRunner::assertEquals((int)$buoc1['don_vi_xu_ly_id'], (int)$hsSauThuHoi['don_vi_hien_tai_id'], "Đơn vị xử lý phải quay về đơn vị ban đầu.");
    }

    /**
     * Kiểm thử luồng liên thông cấp cơ sở: Xã/Phường -> Quận/Huyện kèm kiểm tra toàn vẹn tệp & chữ ký qua từng trạm (§3.1)
     */
    public function testLienThongXaHuyenWithFileIntegrityAndSignatures(): void
    {
        // 1. Khởi tạo hồ sơ liên thông tại UBND Phường Hoàng Văn Thụ (ID: 80)
        $donViXaId = 80;       // UBND Phường Hoàng Văn Thụ
        $donViHuyenId = 10;     // UBND Quận Hồng Bàng
        $donViMotCuaHuyen = 30; // Bộ phận Một cửa Hồng Bàng
        $tthcId = 4;           // Đăng ký biến động quyền sử dụng đất

        $quyTrinh = $this->db->query("SELECT * FROM quy_trinh WHERE tthc_id = 3 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertTrue(!empty($quyTrinh), "Phải có quy trình mẫu.");

        $buocDau = $this->db->query("SELECT * FROM quy_trinh_buoc WHERE quy_trinh_id = {$quyTrinh['id']} AND stt_buoc = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $buocPheDuyet = $this->db->query("SELECT * FROM quy_trinh_buoc WHERE quy_trinh_id = {$quyTrinh['id']} AND stt_buoc = 5 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        $maHoSo = '000.04.15.30301.H21-' . date('ymd') . '-0043-' . str_pad((string)rand(100, 999), 6, '0', STR_PAD_LEFT);

        $stmtHs = $this->db->prepare("INSERT INTO ho_so (
            ma_ho_so, tthc_id, quy_trinh_id, buoc_hien_tai_id, don_vi_tiep_nhan_id, don_vi_hien_tai_id,
            cong_dan_id, kenh_nop, nguoi_nop_ho_ten, nguoi_nop_cccd, nguoi_nop_sdt,
            trang_thai, ngay_nop, ngay_tiep_nhan, ngay_hen_tra, sla_mau, tong_thoi_han_gio, created_at, updated_at
        ) VALUES (
            :ma, :tid, :qid, :bid, :dvid_tn, :dvid_ht,
            1, 'TRUC_TIEP', 'Nguyễn Thị Loan (Công dân Phường Hoàng Văn Thụ)', '031192004567', '0912888999',
            'MOI_TIEP_NHAN', NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 10 DAY), 'XANH', 80, NOW(), NOW()
        )");
        $stmtHs->execute([
            ':ma' => $maHoSo,
            ':tid' => $tthcId,
            ':qid' => $quyTrinh['id'],
            ':bid' => $buocDau['id'],
            ':dvid_tn' => $donViXaId,
            ':dvid_ht' => $donViXaId,
        ]);
        $hoSoId = (int)$this->db->lastInsertId();
        TestRunner::assertTrue($hoSoId > 0, "Khởi tạo hồ sơ liên thông Xã - Huyện thành công.");

        // 2. Trạm 1 (Cấp Xã): Đính kèm tài liệu Giấy chứng nhận QSDĐ và ký số hóa NĐ 107
        $tempDir = dirname(__DIR__, 2) . '/storage/uploads/test';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $docPath = $tempDir . '/gcn_dat_xahuyen_' . uniqid() . '.pdf';
        file_put_contents($docPath, "%PDF-1.4 GIẤY CHỨNG NHẬN QUYỀN SỬ DỤNG ĐẤT - PHƯỜNG HOÀNG VĂN THỤ");
        $initialSha256 = hash_file('sha256', $docPath);

        $this->db->prepare("INSERT INTO ho_so_tai_lieu (
            ho_so_id, ten_goc, ten_luu, duong_dan, mime_type, kich_thuoc_byte, sha256, loai_tai_lieu, created_at
        ) VALUES (
            :hid, 'GiayChungNhanQSDD_HoangVanThu.pdf', :tl, :dd, 'application/pdf', :size, :sha, 'DAU_VAO', NOW()
        )")->execute([
            ':hid' => $hoSoId,
            ':tl' => basename($docPath),
            ':dd' => $docPath,
            ':size' => filesize($docPath),
            ':sha' => $initialSha256,
        ]);
        $taiLieuId = (int)$this->db->lastInsertId();

        // Cán bộ Một cửa ký số hóa
        $canBoXaId = 2; // Cán bộ Một cửa mẫu
        $sigSoHoa = \App\Services\ChuKySoService::signDocument($canBoXaId, $docPath, 'SO_HOA', $taiLieuId, [
            'ghi_chu' => 'UBND Phường Hoàng Văn Thụ số hóa hồ sơ đầu vào'
        ]);
        TestRunner::assertTrue(!empty($sigSoHoa['signature']), "Chữ ký số hóa tại cấp Xã phải thành công.");

        // Kiểm tra chữ ký số hóa
        $verifySoHoa = \App\Services\ChuKySoService::verifyDocumentSignature($docPath, null, (int)$sigSoHoa['id']);
        TestRunner::assertTrue($verifySoHoa['valid'], "Chữ ký số hóa tại cấp Xã phải hợp lệ 100%.");
        TestRunner::assertTrue($verifySoHoa['file_intact'], "Tệp tin tại cấp Xã nguyên vẹn.");

        // 3. Trạm 2: Chuyển liên thông từ UBND Xã lên UBND Quận Hồng Bàng
        $resChuyenHuyen = WorkflowEngine::chuyenTiep(
            $hoSoId,
            $canBoXaId,
            (int)$buocPheDuyet['id'],
            "UBND Phường Hoàng Văn Thụ chuyển hồ sơ liên thông lên UBND Quận Hồng Bàng thẩm định"
        );
        TestRunner::assertTrue($resChuyenHuyen['success'], "Chuyển liên thông Xã lên Huyện thành công.");

        // Cập nhật đơn vị hiện tại sang Huyện
        $this->db->query("UPDATE ho_so SET don_vi_hien_tai_id = {$donViHuyenId} WHERE id = {$hoSoId}");

        // Kiểm tra tính toàn vẹn SHA-256 sau khi hồ sơ đến cấp Huyện
        $checkHuyen = WorkflowEngine::verifyFileIntegrity($hoSoId);
        TestRunner::assertTrue($checkHuyen['valid'], "Tính toàn vẹn SHA-256 của tệp đính kèm được bảo toàn khi đến cấp Huyện.");

        // 4. Trạm 3 (Cấp Huyện): Lãnh đạo UBND Quận Hồng Bàng ký số phê duyệt
        $ldHuyenId = 5; // Lê Ngọc Tân - Phó Chủ tịch UBND Q. Hồng Bàng
        $sigPheDuyet = \App\Services\ChuKySoService::signDocument($ldHuyenId, $docPath, 'PHAT_HANH', $taiLieuId, [
            'ghi_chu' => 'Lãnh đạo UBND Quận Hồng Bàng ký số phê duyệt'
        ]);
        TestRunner::assertTrue(!empty($sigPheDuyet['signature']), "Lãnh đạo Quận ký số thành công.");

        // Văn thư cấp Huyện đóng dấu số tổ chức
        $vanThuHuyenId = 6; // Hoàng Thu Thủy - Văn thư Q. Hồng Bàng
        $sigDongDau = \App\Services\ChuKySoService::signDocument($vanThuHuyenId, $docPath, 'DONG_DAU_CO_QUAN', $taiLieuId, [
            'ghi_chu' => 'Đóng dấu số cơ quan UBND Quận Hồng Bàng'
        ]);
        TestRunner::assertTrue(!empty($sigDongDau['signature']), "Văn thư Quận đóng dấu số cơ quan thành công.");

        // 5. Trạm 4: Trả kết quả điện tử về lại UBND Phường Hoàng Văn Thụ
        $resTraVeXa = WorkflowEngine::chuyenTiep(
            $hoSoId,
            $vanThuHuyenId,
            (int)$buocDau['id'],
            "UBND Quận Hồng Bàng chuyển kết quả điện tử đã ký số về UBND Phường Hoàng Văn Thụ để trả công dân"
        );
        TestRunner::assertTrue($resTraVeXa['success'], "Chuyển kết quả từ Huyện về Xã thành công.");
        $this->db->query("UPDATE ho_so SET don_vi_hien_tai_id = {$donViXaId}, trang_thai = 'HOAN_THANH', ngay_hoan_thanh = NOW() WHERE id = {$hoSoId}");

        // 6. Đối soát toàn vẹn cuối cùng tại cấp Xã
        $checkCuoi = WorkflowEngine::verifyFileIntegrity($hoSoId);
        TestRunner::assertTrue($checkCuoi['valid'], "Tệp tin toàn vẹn 100% SHA-256 sau trọn vẹn luồng liên thông Xã -> Huyện -> Xã.");

        // Kiểm tra lịch sử luân chuyển trong ho_so_luan_chuyen
        $luanChuyen = $this->db->query("SELECT COUNT(*) FROM ho_so_luan_chuyen WHERE ho_so_id = {$hoSoId}")->fetchColumn();
        TestRunner::assertGreaterThanOrEqual(2, (int)$luanChuyen, "Phải ghi nhận đầy đủ các vết luân chuyển liên thông Xã - Huyện.");

        // Dọn dẹp tệp tạm
        @unlink($docPath);
        @unlink($docPath . '.sig');
    }
}

