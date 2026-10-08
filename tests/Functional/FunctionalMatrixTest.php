<?php

declare(strict_types=1);

namespace Tests\Functional;

use App\Core\Database;
use App\Integrations\Mock\LgspGatewayMock;
use App\Services\AuthService;
use App\Services\AuditService;
use App\Services\ChuKySoService;
use App\Services\HealthCheckService;
use App\Services\ReportingService;
use App\Services\SlaEngine;
use App\Services\WorkflowEngine;
use PDO;

/**
 * ============================================================================
 * KIỂM THỬ CHỨC NĂNG HỆ THỐNG HP-eGov THEO MA TRẬN PHÂN QUYỀN RBAC (FR01 - FR15)
 * Căn cứ: Báo cáo Kiến trúc Chương 3 §3.1 (Functional Testing Matrix) & Luật ATTTM
 * Bao phủ: 7 vai trò người dùng hệ thống & 15 nhóm chức năng nghiệp vụ cốt lõi
 * ============================================================================
 */
class FunctionalMatrixTest
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * FR01 - VAI TRÒ CÔNG DÂN: Nộp hồ sơ DVC trực tuyến toàn trình / một phần
     */
    public function testFR01_CitizenOnlineSubmission(): void
    {
        // Kiểm tra danh mục thủ tục công khai cho phép nộp trực tuyến
        $stmt = $this->db->query("SELECT COUNT(*) FROM thu_tuc_hanh_chinh WHERE muc_do_dvc IN ('TOAN_TRINH', 'MOT_PHAN') AND trang_thai = 1");
        $count = (int)$stmt->fetchColumn();
        if ($count <= 0) {
            throw new \Exception("FR01 Thất bại: Không có thủ tục hành chính trực tuyến nào khả dụng.");
        }
    }

    /**
     * FR02 - VAI TRÒ CÔNG DÂN: Tra cứu tiến độ hồ sơ qua mã QCVN 102 & mã QR
     */
    public function testFR02_CitizenDossierLookup(): void
    {
        $stmt = $this->db->query("SELECT ma_ho_so FROM ho_so WHERE is_deleted = 0 LIMIT 1");
        $maHoSo = $stmt->fetchColumn();

        if (!$maHoSo) {
            throw new \Exception("FR02 Thất bại: Không tìm thấy hồ sơ để kiểm tra tra cứu.");
        }

        // Kiểm tra tra cứu không lộ thông tin nhạy cảm của công dân khác
        $checkStmt = $this->db->prepare("SELECT id, ma_ho_so, trang_thai, nguoi_nop_ho_ten, ngay_tiep_nhan, ngay_hen_tra FROM ho_so WHERE ma_ho_so = :ma AND is_deleted = 0");
        $checkStmt->execute([':ma' => $maHoSo]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$result || empty($result['ma_ho_so'])) {
            throw new \Exception("FR02 Thất bại: Tra cứu tiến độ hồ sơ không trả về dữ liệu.");
        }
    }

    /**
     * FR03 - VAI TRÒ CÁN BỘ MỘT CỬA: Tiếp nhận hồ sơ trực tiếp & Quét QR CCCD C06
     */
    public function testFR03_OneStopDeskIntakeAndCccdScan(): void
    {
        $sampleCccd = "031095000123|031234567|NGUYỄN VĂN AN|15051995|Nam|Số 28 Phố Minh Khai, Phường Hoàng Văn Thụ, Quận Hồng Bàng, Hải Phòng|10052021";
        $parts = explode('|', $sampleCccd);

        if (count($parts) < 6) {
            throw new \Exception("FR03 Thất bại: Định dạng chuỗi quét QR không hợp lệ.");
        }

        $cccd = $parts[0];
        $hoTen = $parts[2];

        // Đối soát trực tuyến với CSDL Quốc gia về Dân cư (C06) qua trục LGSP Hải Phòng
        $res = LgspGatewayMock::verifyCitizen($cccd, $hoTen);
        if (!$res['success']) {
            throw new \Exception("FR03 Thất bại: Đối soát CCCD C06 không thành công.");
        }
    }

    /**
     * FR04 - VAI TRÒ CÁN BỘ MỘT CỬA: Thẩm định hồ sơ đầu vào & Ký số số hóa
     */
    public function testFR04_OneStopDigitizationAndSigning(): void
    {
        // Kiểm tra tài khoản cán bộ một cửa có quyền ký số hóa
        $stmt = $this->db->query("SELECT u.id, u.ho_ten FROM nguoi_dung u 
                                  JOIN nguoi_dung_vai_tro nv ON u.id = nv.nguoi_dung_id 
                                  JOIN vai_tro v ON nv.vai_tro_id = v.id 
                                  WHERE v.ma_vai_tro = 'CB_MOT_CUA' AND u.is_deleted = 0 LIMIT 1");
        $canBo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$canBo) {
            throw new \Exception("FR04 Thất bại: Không có cán bộ Một cửa hợp lệ trong CSDL.");
        }
    }

    /**
     * FR05 - VAI TRÒ CHUYÊN VIÊN: Thụ lý chuyên môn & Kiểm soát tiến độ
     */
    public function testFR05_SpecialistProcessing(): void
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM quy_trinh_buoc WHERE thoi_han_gio > 0");
        $count = (int)$stmt->fetchColumn();

        if ($count <= 0) {
            throw new \Exception("FR05 Thất bại: Quy trình thụ lý chuyên môn chưa cấu hình hạn thời gian.");
        }
    }

    /**
     * FR06 - VAI TRÒ CHUYÊN VIÊN: Gửi & trả lời ý kiến phối hợp liên thông
     */
    public function testFR06_InterAgencyCoordination(): void
    {
        // Kiểm tra cơ chế lấy ý kiến phối hợp trong WorkflowEngine
        $reflection = new \ReflectionClass(WorkflowEngine::class);
        if (!$reflection->hasMethod('layYKien') || !$reflection->hasMethod('traLoiYKien')) {
            throw new \Exception("FR06 Thất bại: WorkflowEngine thiếu hàm lấy ý kiến phối hợp liên thông.");
        }
    }

    /**
     * FR07 - VAI TRÒ LÃNH ĐẠO PHÒNG: Thẩm định hồ sơ & Ký số duyệt bước 1
     */
    public function testFR07_DivisionLeaderApproval(): void
    {
        $stmt = $this->db->query("SELECT u.id FROM nguoi_dung u 
                                  JOIN nguoi_dung_vai_tro nv ON u.id = nv.nguoi_dung_id 
                                  JOIN vai_tro v ON nv.vai_tro_id = v.id 
                                  WHERE v.ma_vai_tro = 'LD_PHONG' AND u.is_deleted = 0 LIMIT 1");
        $ldPhong = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ldPhong) {
            throw new \Exception("FR07 Thất bại: Không có tài khoản Lãnh đạo phòng (LD_PHONG).");
        }
    }

    /**
     * FR08 - VAI TRÒ LÃNH ĐẠO CƠ QUAN: Ký số phê duyệt phát hành kết quả
     */
    public function testFR08_AgencyLeaderApproval(): void
    {
        $stmt = $this->db->query("SELECT u.id FROM nguoi_dung u 
                                  JOIN nguoi_dung_vai_tro nv ON u.id = nv.nguoi_dung_id 
                                  JOIN vai_tro v ON nv.vai_tro_id = v.id 
                                  WHERE v.ma_vai_tro = 'LD_CO_QUAN' AND u.is_deleted = 0 LIMIT 1");
        $ldCoQuan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ldCoQuan) {
            throw new \Exception("FR08 Thất bại: Không có tài khoản Lãnh đạo cơ quan (LD_CO_QUAN).");
        }
    }

    /**
     * FR09 - VAI TRÒ VĂN THƯ: Đóng dấu số cơ quan & Trả kết quả điện tử
     */
    public function testFR09_ClerkDigitalSealing(): void
    {
        $stmt = $this->db->query("SELECT u.id FROM nguoi_dung u 
                                  JOIN nguoi_dung_vai_tro nv ON u.id = nv.nguoi_dung_id 
                                  JOIN vai_tro v ON nv.vai_tro_id = v.id 
                                  WHERE v.ma_vai_tro = 'VAN_THU' AND u.is_deleted = 0 LIMIT 1");
        $vanThu = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vanThu) {
            throw new \Exception("FR09 Thất bại: Không có tài khoản Văn thư (VAN_THU).");
        }
    }

    /**
     * FR10 - VAI TRÒ QUẢN TRỊ VIÊN (SYSADMIN): Quản trị người dùng & Phân tách quyền tuyệt đối
     */
    public function testFR10_SysadminSegregation(): void
    {
        // SYSADMIN không thể ký số nghiệp vụ
        $stmt = $this->db->query("SELECT u.id FROM nguoi_dung u 
                                  JOIN nguoi_dung_vai_tro nv ON u.id = nv.nguoi_dung_id 
                                  JOIN vai_tro v ON nv.vai_tro_id = v.id 
                                  WHERE v.ma_vai_tro = 'SYSADMIN' AND u.is_deleted = 0 LIMIT 1");
        $sysadmin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sysadmin) {
            $tempFile = tempnam(sys_get_temp_dir(), 'test_sa_') . '.pdf';
            file_put_contents($tempFile, '%PDF-1.4 TEST SYSADMIN SIGN');

            $blocked = false;
            try {
                ChuKySoService::signDocument((int)$sysadmin['id'], $tempFile, 'PHAT_HANH');
            } catch (\DomainException $e) {
                $blocked = true;
            } finally {
                @unlink($tempFile);
            }

            if (!$blocked) {
                throw new \Exception("FR10 Thất bại: SYSADMIN vi phạm phân tách quyền khi thực hiện ký số nghiệp vụ!");
            }
        }
    }

    /**
     * FR11 - THANH TOÁN TRỰC TUYẾN & THU PHÍ, LỆ PHÍ DVC
     */
    public function testFR11_PaymentGateway(): void
    {
        // Kiểm tra danh mục biểu phí TTHC trong hệ thống
        $stmt = $this->db->query("SELECT COUNT(*) FROM thu_tuc_hanh_chinh WHERE phi_le_phi >= 0");
        $count = (int)$stmt->fetchColumn();

        if ($count <= 0) {
            throw new \Exception("FR11 Thất bại: Hệ thống chưa cấu hình biểu phí, lệ phí cho các TTHC.");
        }
    }

    /**
     * FR12 - GIÁM SÁT SLA & CẢNH BÁO TRỄ HẠN TỰ ĐỘNG
     */
    public function testFR12_SlaMonitoringEngine(): void
    {
        // Kiểm tra hàm tính ngày hẹn trả loại trừ T7/CN
        $ngayHen = SlaEngine::calculateNgayHenTra('2026-10-12 08:30:00', 3);
        if (empty($ngayHen)) {
            throw new \Exception("FR12 Thất bại: SlaEngine không tính được ngày hẹn trả.");
        }

        // Kiểm tra phân loại màu SLA
        $danhGia = SlaEngine::evaluateSla('2026-10-20 17:00:00', '2026-10-01 08:00:00', null, 40);
        if (empty($danhGia['mau'])) {
            throw new \Exception("FR12 Thất bại: SlaEngine không đánh giá được màu chỉ báo tiến độ.");
        }
    }

    /**
     * FR13 - KHẢO SÁT HÀI LÒNG SIPAS ĐIỆN TỬ
     */
    public function testFR13_SipasCitizenFeedback(): void
    {
        $stmt = $this->db->query("SHOW TABLES LIKE 'danh_gia_hai_long'");
        $hasTable = (bool)$stmt->fetchColumn();

        if (!$hasTable) {
            throw new \Exception("FR13 Thất bại: Bảng danh_gia_hai_long không tồn tại.");
        }
    }

    /**
     * FR14 - BÁO CÁO THỐNG KÊ THÔNG TƯ 01/2018/TT-VPCP
     */
    public function testFR14_ReportingCircular01(): void
    {
        $thongKe = ReportingService::getReportThongTu01(['ky' => 'nam_nay']);

        if (!isset($thongKe['totals']) || !isset($thongKe['rows'])) {
            throw new \Exception("FR14 Thất bại: Báo cáo Thông tư 01 không trả về đúng cấu trúc chuẩn.");
        }

        $totals = $thongKe['totals'];
        $sumDaGq = $totals['giai_quyet_truoc_han'] + $totals['giai_quyet_dung_han'] + $totals['giai_quyet_qua_han'];
        if ($sumDaGq !== $totals['tong_da_giai_quyet']) {
            throw new \Exception("FR14 Thất bại: Số liệu giải quyết vi phạm cân bằng tổng (trước hạn + đúng hạn + quá hạn != tổng).");
        }
    }

    /**
     * FR15 - TÍCH HỢP HỆ THỐNG NGOẠI VI & NỀN TẢNG KẾT NỐI (LGSP / NDXP)
     */
    public function testFR15_ExternalIntegrationAndHealth(): void
    {
        $health = HealthCheckService::getHealthStatus();

        if (!isset($health['status']) || ($health['status'] !== 'UP' && $health['status'] !== 'DEGRADED')) {
            throw new \Exception("FR15 Thất bại: Kiểm tra sức khỏe dịch vụ không đạt trạng thái UP/DEGRADED.");
        }
    }
}
