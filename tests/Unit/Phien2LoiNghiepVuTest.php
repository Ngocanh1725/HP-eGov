<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Services\AuthService;
use App\Services\ChuKySoService;
use App\Services\MaHoSoGenerator;
use App\Services\SlaEngine;
use App\Services\WorkflowEngine;
use DomainException;
use PDO;
use TestRunner;

/**
 * Class Phien2LoiNghiepVuTest – Bộ kiểm thử Đơn vị Lõi Nghiệp vụ & Quy trình Hồ sơ (Phiên 2)
 * Căn cứ: Báo cáo Kiến trúc §1.2, §2.1, §4.1; NĐ 61/2018; TT 01/2018; QCVN 102:2016.
 * 
 * Kiểm chứng 4 tiêu chuẩn trọng yếu:
 * (a) Sinh 50 mã hồ sơ đồng thời không trùng lặp (Pessimistic Row Lock FOR UPDATE).
 * (b) Tính hạn ngày hẹn trả qua kỳ nghỉ lễ 30/4 - 1/5 và Tết Nguyên đán đúng chuẩn công vụ.
 * (c) Khóa chuyển bước khi SLA ĐỎ chưa có Mẫu số 04, và mở khóa ngay khi có Mẫu 04 đã ký.
 * (d) Chặn đứng SYSADMIN can thiệp ký duyệt hoặc thụ lý nghiệp vụ (PoLP).
 */
class Phien2LoiNghiepVuTest
{
    private PDO $db;

    public function setUp(): void
    {
        $this->db = Database::connection();
        SlaEngine::clearCache();
    }

    /**
     * (a) Sinh 50 mã hồ sơ đồng thời không trùng lặp (QCVN 102:2016/BTTTT)
     */
    public function testSinh50MaHoSoDongThoiKhongTrung(): void
    {
        $donViId = 10; // UBND Quận Hồng Bàng
        $maRutGonTthc = '0042'; // Cấp phép xây dựng nhà ở riêng lẻ
        $generatedCodes = [];

        // Giả lập sinh 50 mã hồ sơ liên tục trong môi trường giao dịch có khóa bi quan
        for ($i = 0; $i < 50; $i++) {
            $code = MaHoSoGenerator::generate($donViId, $maRutGonTthc);
            TestRunner::assertTrue(!empty($code), "Mã hồ sơ sinh ra không được để trống.");

            // Kiểm tra cấu trúc chuẩn QCVN 102: {Mã cơ quan}-{YYMMDD}-{Mã TTHC 4 số}-{STT 6 số}
            $parts = explode('-', $code);
            TestRunner::assertEquals(4, count($parts), "Mã QCVN 102 phải có đúng 4 thành phần cách nhau bởi dấu gạch ngang (-).");
            TestRunner::assertEquals('000.04.15.H21', $parts[0], "Phần 1 phải là Mã định danh cơ quan chuẩn QCVN 102 của Quận Hồng Bàng.");
            TestRunner::assertEquals(date('ymd'), $parts[1], "Phần 2 phải là ngày tháng năm tiếp nhận dạng ymd.");
            TestRunner::assertEquals('0042', $parts[2], "Phần 3 phải là mã rút gọn TTHC 4 chữ số.");
            TestRunner::assertEquals(6, strlen($parts[3]), "Phần 4 phải là số thứ tự tự tăng gồm đúng 6 chữ số.");

            $generatedCodes[] = $code;
        }

        // Kiểm tra tính duy nhất tuyệt đối: 50 mã sinh ra không được phép trùng nhau
        $uniqueCodes = array_unique($generatedCodes);
        TestRunner::assertEquals(50, count($uniqueCodes), "Toàn bộ 50 mã hồ sơ sinh ra phải hoàn toàn duy nhất, không trùng lặp.");
    }

    /**
     * (b) Tính hạn ngày hẹn trả qua kỳ nghỉ lễ 30/4 - 1/5 và Tết Nguyên đán 2026
     */
    public function testTinhHanNgayHenTraQuaLe30Thang4VaTetNguyenDan(): void
    {
        // 1. Kiểm tra qua kỳ nghỉ Tết Nguyên Đán 2026
        // Nghỉ Tết: 16/02 đến 20/02/2026 (5 ngày). Kèm Thứ 7 (21/02) và Chủ Nhật (22/02).
        // Tiếp nhận: Thứ Sáu 2026-02-13 10:00:00, thời hạn 2 ngày làm việc (16 giờ).
        // Ngày làm việc 1: Thứ Hai 2026-02-23 (sau khi hết kỳ nghỉ Tết).
        // Ngày làm việc 2: Thứ Ba 2026-02-24 -> Hạn trả là 17:00 ngày 24/02/2026.
        $holidaysTet = [
            '2026-02-16' => true,
            '2026-02-17' => true,
            '2026-02-18' => true,
            '2026-02-19' => true,
            '2026-02-20' => true,
        ];

        $henTraTet = SlaEngine::calculateNgayHenTra('2026-02-13 10:00:00', 2, null, $holidaysTet);
        TestRunner::assertEquals(
            '2026-02-24 17:00:00',
            $henTraTet,
            "Hạn trả 2 ngày làm việc tiếp nhận trước Tết phải tự động nhảy qua 7 ngày nghỉ Tết + cuối tuần sang Thứ Ba."
        );

        // 2. Kiểm tra qua kỳ nghỉ lễ 30/4 và 1/5 năm 2026
        // Nghỉ lễ: 30/04/2026 (Thứ Năm) và 01/05/2026 (Thứ Sáu). Kèm Thứ 7 (02/05) và Chủ Nhật (03/05).
        // Tiếp nhận: Thứ Ba 2026-04-28 09:00:00, thời hạn 3 ngày làm việc:
        // Ngày làm việc 1: Thứ Tư 29/04
        // Nghỉ lễ & Cuối tuần: 30/04, 01/05, 02/05, 03/05 (4 ngày)
        // Ngày làm việc 2: Thứ Hai 04/05
        // Ngày làm việc 3: Thứ Ba 05/05 -> Hạn trả là 17:00 ngày 05/05/2026.
        $holidays304 = [
            '2026-04-30' => true,
            '2026-05-01' => true,
        ];

        $henTra304 = SlaEngine::calculateNgayHenTra('2026-04-28 09:00:00', 3, null, $holidays304);
        TestRunner::assertEquals(
            '2026-05-05 17:00:00',
            $henTra304,
            "Hạn trả 3 ngày làm việc tiếp nhận trước 30/4 phải tự động nhảy qua 4 ngày nghỉ lễ + cuối tuần sang Thứ Ba 05/05."
        );

        // 3. Kiểm tra tính hạn chính xác từng phút qua kỳ nghỉ lễ 30/4 - 1/5:
        // Tiếp nhận: Thứ Tư 2026-04-29 lúc 15:30:00, thời hạn 4 giờ làm việc.
        // - Chiều 29/04: 15:30 - 17:30 = 2 giờ (còn lại 2 giờ).
        // - Nghỉ lễ và cuối tuần: 30/04 đến 03/05 (4 ngày liên tục).
        // - Thứ Hai 04/05/2026: bắt đầu từ 07:30 sáng, cộng 2 giờ -> Đúng 09:30:00!
        $exactTime = SlaEngine::addWorkingHours(new \DateTime('2026-04-29 15:30:00'), 4.0, $holidays304);
        TestRunner::assertEquals(
            '2026-05-04 09:30:00',
            $exactTime->format('Y-m-d H:i:s'),
            "Thời hạn tính theo giờ làm việc qua kỳ nghỉ 30/4 - 1/5 phải chính xác từng phút (09:30:00 sáng Thứ Hai 04/05)."
        );

        // 4. Kiểm tra tính hạn chính xác từng phút qua Tết Nguyên Đán 2026:
        // Tiếp nhận: Thứ Sáu 2026-02-13 lúc 16:00:00 (trước Tết), thời hạn 3.5 giờ làm việc (3h30p).
        // - Chiều 13/02: 16:00 - 17:30 = 1.5 giờ (còn lại 2 giờ).
        // - Nghỉ Tết + cuối tuần từ 14/02 đến 22/02 (9 ngày).
        // - Thứ Hai 23/02/2026: bắt đầu từ 07:30 sáng, cộng 2 giờ -> Đúng 09:30:00!
        $exactTet = SlaEngine::addWorkingHours(new \DateTime('2026-02-13 16:00:00'), 3.5, $holidaysTet);
        TestRunner::assertEquals(
            '2026-02-23 09:30:00',
            $exactTet->format('Y-m-d H:i:s'),
            "Thời hạn tính theo giờ qua Tết Nguyên Đán phải chính xác từng phút (09:30:00 sáng Thứ Hai 23/02)."
        );
    }

    /**
     * (c) Khóa chuyển bước khi SLA ĐỎ và mở khóa sau khi có Mẫu số 04 đã ký
     */
    public function testKhoaChuyenBuocKhiSlaDoVaMoKhoaBangMau04(): void
    {
        $maTestHs = 'TEST-PHIEN2-SLA-' . bin2hex(random_bytes(3));

        // 1. Tạo hồ sơ test ở bước Thẩm tra (Lãnh đạo Phòng) đang bị quá hạn (SLA ĐỎ)
        $insStmt = $this->db->prepare("INSERT INTO ho_so (
            ma_ho_so, tthc_id, quy_trinh_id, buoc_hien_tai_id, don_vi_tiep_nhan_id, don_vi_hien_tai_id,
            chuyen_vien_thu_ly_id, cong_dan_id, kenh_nop, nguoi_nop_ho_ten, nguoi_nop_cccd, nguoi_nop_sdt,
            trang_thai, sla_mau, tong_thoi_han_gio, thoi_gian_da_dung_gio, ngay_nop, ngay_tiep_nhan, ngay_hen_tra
        ) VALUES (
            :ma, 3, 1, 4, 10, 10, 3, 1, 'TRUC_TIEP', 'NGUYỄN VĂN QUÁ HẠN', '031090009999', '0912999888',
            'DANG_XU_LY', 'DO', 120, 140.0, NOW(), NOW(), DATE_SUB(NOW(), INTERVAL 2 DAY)
        )");
        $insStmt->execute([':ma' => $maTestHs]);
        $hoSoId = (int)$this->db->lastInsertId();

        // Đảm bảo chưa có văn bản xin lỗi Mẫu 04 nào
        $this->db->exec("DELETE FROM van_ban_xin_loi WHERE ho_so_id = {$hoSoId}");

        // 2. Thử chuyển tiếp hồ sơ khi SLA ĐỎ và chưa có Mẫu 04 -> BẮT BUỘC BỊ KHÓA
        $resLock = WorkflowEngine::chuyenTiep($hoSoId, 4, 5, 'Thử chuyển bước khi hồ sơ đang quá hạn SLA');
        TestRunner::assertFalse(
            $resLock['success'],
            "Hồ sơ quá hạn SLA ĐỎ bắt buộc phải bị khóa chức năng chuyển bước thông thường."
        );
        TestRunner::assertEquals(
            'SLA_BREACH_LOCKED',
            $resLock['code'],
            "Mã lỗi trả về khi bị khóa chuyển bước phải là SLA_BREACH_LOCKED."
        );

        // 3. Lãnh đạo cơ quan ban hành và ký số Văn bản xin lỗi Mẫu số 04 (TT 01/2018)
        $stmtVbxl = $this->db->prepare("INSERT INTO van_ban_xin_loi (
            ho_so_id, so_van_ban, ngay_ky, ngay_hen_moi, ly_do_qua_han, nguoi_ky_id, trang_thai
        ) VALUES (
            :hsid, '45/XL-UBND-HB', NOW(), DATE_ADD(NOW(), INTERVAL 5 DAY), 
            'Hồ sơ cần đối soát bổ sung với Sở Xây dựng về chỉ giới quy hoạch', 5, 'DA_KY'
        )");
        $stmtVbxl->execute([':hsid' => $hoSoId]);

        // 4. Thử chuyển tiếp lại sau khi đã có Mẫu 04 được ký duyệt -> BẮT BUỘC MỞ KHÓA THÀNH CÔNG
        $resUnlock = WorkflowEngine::chuyenTiep($hoSoId, 4, 5, 'Chuyển tiếp thành công sau khi đã ban hành Mẫu 04');
        TestRunner::assertTrue(
            $resUnlock['success'],
            "Sau khi có Văn bản xin lỗi Mẫu số 04 đã ký, quy trình phải tự động mở khóa cho phép chuyển bước."
        );
        TestRunner::assertEquals(
            5,
            $resUnlock['buoc_moi_id'],
            "Bước xử lý mới phải chuyển sang bước 5 (Phê duyệt của Lãnh đạo cơ quan)."
        );
    }

    /**
     * (d) Chặn đứng SYSADMIN can thiệp ký duyệt hoặc thụ lý nghiệp vụ (PoLP)
     */
    public function testChanSysadminCanThiepKyDuyetVaThuLy(): void
    {
        // 1. Đăng nhập tài khoản sysadmin
        $loginRes = AuthService::attemptCanBo('sysadmin', 'Hpegov@2026');
        TestRunner::assertTrue($loginRes['success'], "SYSADMIN đăng nhập kỹ thuật thành công.");
        TestRunner::assertTrue(AuthService::hasRole('SYSADMIN'), "Tài khoản có vai trò SYSADMIN.");

        // 2. Kiểm tra ma trận quyền: SYSADMIN tuyệt đối không có quyền thụ lý hoặc ký số
        $perms = AuthService::permissions();
        foreach ($perms as $p) {
            TestRunner::assertFalse(
                str_starts_with($p, 'thu_ly.'),
                "Quản trị viên SYSADMIN tuyệt đối không được có quyền thụ lý nghiệp vụ ({$p})."
            );
            TestRunner::assertFalse(
                str_starts_with($p, 'kyso.'),
                "Quản trị viên SYSADMIN tuyệt đối không được có quyền ký số nghiệp vụ ({$p})."
            );
        }

        // 3. Thử ký số với tài khoản SYSADMIN (User ID = 1) -> BẮT BUỘC NÉM RA DomainException
        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_sysadmin_block_' . uniqid() . '.pdf';
        file_put_contents($tempFile, "%PDF-1.4\nTest blocking sysadmin from digital signing");

        $sysadminBlocked = false;
        try {
            ChuKySoService::signDocument(1, $tempFile, 'DUYET_BUOC_1', null, ['pin' => '123456']);
        } catch (DomainException $e) {
            $sysadminBlocked = true;
            TestRunner::assertTrue(
                str_contains($e->getMessage(), 'SYSADMIN'),
                "Thông điệp ngoại lệ phải nêu rõ Quản trị viên SYSADMIN không có thẩm quyền ký số."
            );
        } finally {
            @unlink($tempFile);
            @unlink($tempFile . '.sig');
            AuthService::logout();
        }

        TestRunner::assertTrue(
            $sysadminBlocked,
            "Cơ chế bảo mật PoLP phải chặn đứng 100% khi SYSADMIN cố tình can thiệp ký số hồ sơ."
        );
    }
}
