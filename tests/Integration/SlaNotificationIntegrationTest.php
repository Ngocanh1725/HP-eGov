<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Integrations\Mock\SmsZnsNotificationMock;
use App\Services\SlaEngine;
use App\Services\ThongBaoService;
use App\Services\WorkflowEngine;
use PDO;

/**
 * Class SlaNotificationIntegrationTest – Kiểm thử tích hợp SLA Engine, Mẫu số 04 và Outbox Notification
 * Căn cứ: §2.2 Báo cáo Kiến trúc & Rule mục 11
 */
class SlaNotificationIntegrationTest
{
    private function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException("FAILED: {$message}");
        }
    }

    private function assertEquals(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException("FAILED: {$message} (Kỳ vọng: " . var_export($expected, true) . ", Thực tế: " . var_export($actual, true) . ")");
        }
    }

    /**
     * Test 1: Hồ sơ quá hạn SLA (màu ĐỎ) bị khóa chuyển bước, mở khóa sau khi có Văn bản xin lỗi Mẫu 04
     */
    public function testSlaDoLocksWorkflowAndMau04Unlocks(): void
    {
        $db = Database::connection();

        $maTest = 'TEST-SLA-' . bin2hex(random_bytes(4));

        // 1. Tạo hồ sơ test ở bước Thẩm tra (LĐ Phòng)
        $stmtHs = $db->prepare("INSERT INTO ho_so (
            ma_ho_so, tthc_id, quy_trinh_id, buoc_hien_tai_id, don_vi_tiep_nhan_id, don_vi_hien_tai_id,
            chuyen_vien_thu_ly_id, cong_dan_id, kenh_nop, nguoi_nop_ho_ten, nguoi_nop_cccd, nguoi_nop_sdt,
            trang_thai, sla_mau, tong_thoi_han_gio, thoi_gian_da_dung_gio, ngay_nop, ngay_tiep_nhan, ngay_hen_tra
        ) VALUES (
            :ma, 3, 1, 4, 30, 31, 3, 1, 'TRUC_TIEP', 'TRẦN VĂN SLA', '031090008888', '0912345678',
            'DANG_XU_LY', 'DO', 120, 130.0, NOW(), NOW(), DATE_SUB(NOW(), INTERVAL 1 DAY)
        )");
        $stmtHs->execute([':ma' => $maTest]);
        $hoSoId = (int)$db->lastInsertId();

        // Xóa văn bản xin lỗi cũ nếu có
        $db->exec("DELETE FROM van_ban_xin_loi WHERE ho_so_id = {$hoSoId}");

        // 2. Thử chuyển tiếp khi SLA ĐỎ và chưa có Mẫu 04 -> BỊ KHÓA
        $resLock = WorkflowEngine::chuyenTiep($hoSoId, 4, 5, 'Thử chuyển bước khi quá hạn');
        $this->assert($resLock['success'] === false, "Chuyển bước phải thất bại khi SLA ĐỎ chưa có Mẫu 04");
        $this->assertEquals('SLA_BREACH_LOCKED', $resLock['code'], "Mã lỗi phải là SLA_BREACH_LOCKED");

        // 3. Lãnh đạo cơ quan lập và ký số Mẫu 04
        $soVbxl = 'XL-TEST/' . date('Y');
        $stmtXl = $db->prepare("INSERT INTO van_ban_xin_loi (
            ho_so_id, so_van_ban, nguoi_ky_id, ly_do_qua_han, ngay_hen_moi, ngay_ky, trang_thai
        ) VALUES (
            :hsid, :so, 4, 'Hồ sơ phức tạp cần kiểm tra thêm thực địa', DATE_ADD(NOW(), INTERVAL 3 DAY), NOW(), 'DA_KY'
        )");
        $stmtXl->execute([
            ':hsid' => $hoSoId,
            ':so' => $soVbxl,
        ]);

        // 4. Thử chuyển tiếp lại -> Thành công và gỡ bỏ lỗi khóa
        $resUnlock = WorkflowEngine::chuyenTiep($hoSoId, 4, 5, 'Chuyển tiếp sau khi đã có Mẫu 04');
        $this->assert(
            ($resUnlock['code'] ?? '') !== 'SLA_BREACH_LOCKED',
            "Sau khi có Mẫu 04 ký số, lỗi SLA_BREACH_LOCKED phải được gỡ bỏ"
        );
        $this->assert($resUnlock['success'] === true, "Chuyển tiếp sau khi có Mẫu 04 phải thành công");
    }

    /**
     * Test 2: Hàng đợi Outbox Pattern phát thông báo và Worker dispatch thành công
     */
    public function testOutboxNotificationQueuedAndDispatched(): void
    {
        $db = Database::connection();

        // Lấy 1 hồ sơ thật trong DB để không vi phạm FK
        $hs = $db->query("SELECT * FROM ho_so LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $realHsId = (int)$hs['id'];

        $hoSoTest = [
            'id' => $realHsId,
            'ma_ho_so' => (string)$hs['ma_ho_so'],
            'ten_tthc' => 'Cấp Giấy phép xây dựng nhà ở riêng lẻ',
            'nguoi_nop_ho_ten' => 'NGUYỄN VĂN AN',
            'nguoi_nop_sdt' => '0987654321',
            'nguoi_nop_email' => 'nguyenvanan@test.haiphong.gov.vn',
            'cong_dan_id' => 1,
            'trang_thai' => 'MOI_TIEP_NHAN',
            'ngay_hen_tra' => '2026-10-15 17:00:00',
        ];

        // 1. Phát sự kiện thông báo tiếp nhận
        $res = ThongBaoService::guiSuKien($hoSoTest, 'TIEP_NHAN');

        $this->assert($res['sms_queued'] === true, "Tin nhắn SMS phải được đưa vào hàng đợi Outbox");
        $this->assert($res['zalo_queued'] === true, "Tin nhắn Zalo ZNS phải được đưa vào hàng đợi Outbox");
        $this->assert($res['email_queued'] === true, "Email phải được đưa vào hàng đợi Outbox");

        // 2. Kiểm tra bản ghi trong bảng hang_doi_thong_bao
        $stmt = $db->prepare("SELECT * FROM hang_doi_thong_bao WHERE ho_so_id = :hsid AND trang_thai = 'CHO_GUI'");
        $stmt->execute([':hsid' => $realHsId]);
        $queuedList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assert(count($queuedList) >= 3, "Phải có ít nhất 3 tin nhắn (SMS, Zalo, Email) đang ở trạng thái CHO_GUI");

        // 3. Giả lập Worker dispatch một tin nhắn
        $msg = $queuedList[0];
        $dispatchRes = SmsZnsNotificationMock::dispatch(
            kenh: $msg['kenh'],
            nguoiNhan: $msg['nguoi_nhan'],
            tieuDe: $msg['tieu_de'],
            noiDung: $msg['noi_dung'],
            hoSoId: (int)$msg['ho_so_id']
        );

        $this->assert($dispatchRes['success'] === true, "Dispatch tin nhắn qua Gateway Mock phải thành công");
        $this->assert(!empty($dispatchRes['msg_id']), "Phải nhận được message ID từ Gateway");

        // 4. Kiểm tra nhat_ky_tich_hop có bản ghi
        $logStmt = $db->prepare("SELECT COUNT(*) FROM nhat_ky_tich_hop WHERE dich_vu = :dv");
        $logStmt->execute([':dv' => $msg['kenh'] . '_GATEWAY']);
        $logCount = (int)$logStmt->fetchColumn();
        $this->assert($logCount > 0, "Phải ghi nhật ký tích hợp ngoại vi nhat_ky_tich_hop");

        // 5. Kiểm tra webhook_log có bản ghi
        $whStmt = $db->query("SELECT COUNT(*) FROM webhook_log WHERE su_kien LIKE 'NOTIFY_%_SENT'");
        $whCount = (int)$whStmt->fetchColumn();
        $this->assert($whCount > 0, "Phải ghi nhật ký Webhook webhook_log");
    }


    /**
     * Test 3: Nội suy chính xác các biến trong mẫu thông báo
     */
    public function testMauThongBaoTemplateVariablesReplacement(): void
    {
        $db = Database::connection();

        $hs = $db->query("SELECT * FROM ho_so LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $realHsId = (int)$hs['id'];

        $hoSo = [
            'id' => $realHsId,
            'ma_ho_so' => (string)$hs['ma_ho_so'],
            'ten_tthc' => 'Thủ tục Cấp đổi Giấy phép lái xe',
            'nguoi_nop_ho_ten' => 'LÊ THỊ BÌNH',
            'nguoi_nop_sdt' => '0912888999',
            'nguoi_nop_email' => 'binhle@haiphong.gov.vn',
            'cong_dan_id' => 1,
            'trang_thai' => 'HOAN_THANH',
            'ngay_hen_tra' => '2026-10-20 17:00:00',
        ];

        ThongBaoService::guiSuKien($hoSo, 'DA_CO_KET_QUA');

        // Kiểm tra nội dung tin nhắn đã được nội suy biến
        $stmt = $db->prepare("SELECT * FROM hang_doi_thong_bao WHERE ho_so_id = :hsid AND kenh = 'SMS' ORDER BY id DESC LIMIT 1");
        $stmt->execute([':hsid' => $realHsId]);
        $sms = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assert(!empty($sms), "Phải có tin nhắn SMS trong hàng đợi");
        $this->assert(str_contains($sms['noi_dung'], (string)$hs['ma_ho_so']), "Nội dung SMS phải chứa mã hồ sơ đã nội suy");
        $this->assert(!str_contains($sms['noi_dung'], '{ma_ho_so}'), "Không được còn sót biến chưa nội suy {ma_ho_so}");
    }


    /**
     * Test 4: Ghi điểm phạt thi đua khi hồ sơ quá hạn SLA
     */
    public function testPenaltiesRecordedOnSlaBreach(): void
    {
        $db = Database::connection();

        $countBefore = (int)$db->query("SELECT COUNT(*) FROM diem_thi_dua_don_vi WHERE loai_su_kien = 'QUA_HAN_SLA'")->fetchColumn();
        $this->assert($countBefore > 0, "Bảng diem_thi_dua_don_vi phải ghi nhận các điểm trừ thi đua của các hồ sơ quá hạn SLA");
    }
}
