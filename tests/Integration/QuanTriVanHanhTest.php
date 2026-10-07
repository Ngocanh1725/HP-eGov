<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Admin\HeThongController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\BackupService;
use App\Services\HealthCheckService;
use App\Services\IncidentService;
use App\Services\SecurityPolicyService;
use App\Services\TrainingService;
use PDO;

/**
 * Class QuanTriVanHanhTest
 * Kiểm thử Tích hợp Quản trị & Vận hành Hệ thống HP-eGov (Phiên 13/16)
 * Kiểm tra: PH12 (WORM, Security), PH13 (Backup, Restore test vào hp_egov_restore_test, RTO/RPO), PH15 (Đào tạo 4 nhóm)
 */
class QuanTriVanHanhTest
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
     * Test 1: Luồng Sao lưu Full Backup & Phục hồi thử nghiệm (Test Restore) vào CSDL hp_egov_restore_test
     * Cam kết RTO ≤ 4 giờ, RPO ≤ 2 giờ
     */
    public function testFullBackupAndRestoreFlow(): void
    {
        // 1. Chạy Full Backup
        $backupRes = BackupService::runFullBackup("Kiểm thử Tích hợp Full Backup");
        $this->assert($backupRes['success'], 'Full Backup phải thực thi thành công');
        $this->assert(!empty($backupRes['file']), 'Tệp sao lưu không được rỗng');
        $this->assert(!empty($backupRes['sha256']), 'Mã băm SHA-256 phải được tạo');
        $this->assert($backupRes['size'] > 0, 'Dung lượng tệp sao lưu phải lớn hơn 0 bytes');

        $backupId = $backupRes['backup_id'];

        // 2. Chạy Phục hồi thử nghiệm (Test Restore) vào CSDL hp_egov_restore_test
        $restoreRes = BackupService::testRestore($backupId);
        $this->assert($restoreRes['success'], 'Phục hồi thử nghiệm vào hp_egov_restore_test phải thành công: ' . ($restoreRes['message'] ?? ''));
        $this->assert($restoreRes['tables_count'] >= 50, 'Số lượng bảng phục hồi phải đạt tối thiểu 50 bảng');
        $this->assert($restoreRes['rto_seconds'] < 14400, 'Thời gian phục hồi thực tế phải nhỏ hơn 4 giờ (14,400 giây)');

        // 3. Kiểm tra tự động ghi nhận biên bản diễn tập DRP vào bảng dien_tap
        $db = Database::connection();
        $stmt = $db->query("SELECT * FROM dien_tap WHERE loai_dien_tap = 'CHUYEN_DOI_THAM_HOA_DRP' ORDER BY id DESC LIMIT 1");
        $drill = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assert(!empty($drill), 'Bảng dien_tap phải tự động ghi nhận biên bản diễn tập sau test restore');
        $this->assertEquals('DAT', $drill['ket_qua'], 'Kết quả diễn tập phục hồi phải ĐẠT');
    }

    /**
     * Test 2: Luồng Sao lưu Differential Backup (hằng ngày) và Transaction Log (2h/lần)
     */
    public function testDifferentialAndLogBackup(): void
    {
        // 1. Chạy Differential Backup
        $diffRes = BackupService::runDiffBackup("Kiểm thử Differential Backup");
        $this->assert($diffRes['success'], 'Differential Backup phải chạy thành công');
        $this->assert(!empty($diffRes['sha256']), 'Differential backup phải có checksum SHA-256');

        // 2. Chạy Transaction Log Backup
        $logRes = BackupService::runLogBackup("Kiểm thử Transaction Log Backup");
        $this->assert($logRes['success'], 'Transaction Log Backup phải chạy thành công');
        $this->assert(!empty($logRes['sha256']), 'Transaction log backup phải có checksum SHA-256');
    }

    /**
     * Test 3: Kiểm tra tính toàn vẹn của chuỗi băm bất biến WORM (Audit Trail)
     */
    public function testWormAuditChainIntegrity(): void
    {
        $res = AuditService::verifyChain(1000);
        $this->assert($res['valid'], 'Chuỗi băm WORM Audit Trail phải hợp lệ và toàn vẹn 100%');
        $this->assert($res['broken_at_id'] === null, 'Không được có điểm đứt gãy trong chuỗi kiểm toán WORM');
        $this->assert($res['total_verified'] > 0, 'Phải có ít nhất 1 bản ghi kiểm toán được đối soát');
    }

    /**
     * Test 4: Giám sát sức khỏe hệ thống (HealthCheckService)
     */
    public function testHealthCheckService(): void
    {
        $health = HealthCheckService::getHealthStatus();
        $this->assert(in_array($health['status'], ['UP', 'DEGRADED'], true), 'Trạng thái hệ thống phải là UP hoặc DEGRADED');
        $this->assertEquals('UP', $health['database']['status'], 'Trạng thái CSDL phải là UP');
        $this->assert($health['database']['latency_ms'] >= 0, 'Độ trễ CSDL phải lớn hơn hoặc bằng 0 ms');
        $this->assert($health['disk']['total_gb'] > 0, 'Tổng dung lượng ổ đĩa phải lớn hơn 0 GB');
        $this->assert(!empty($health['environment']['php_version']), 'Phiên bản PHP phải có giá trị');
        $this->assert(!empty($health['environment']['mysql_version']), 'Phiên bản MySQL phải có giá trị');
    }

    /**
     * Test 5: Chính sách bảo mật & Quản lý danh sách IP bị chặn
     */
    public function testSecurityPolicyAndIpBlock(): void
    {
        $testIp = '192.168.99.99';

        // 1. Chặn IP
        $blockOk = SecurityPolicyService::blockIp($testIp, 'IP vi phạm thử nghiệm unit test', null);
        $this->assert($blockOk, 'Khóa IP vi phạm phải thành công');

        // 2. Kiểm tra tồn tại trong blacklist
        $list = SecurityPolicyService::getBlockedIps();
        $found = false;
        $foundId = 0;
        foreach ($list as $item) {
            if ($item['ip_address'] === $testIp) {
                $found = true;
                $foundId = (int)$item['id'];
                break;
            }
        }
        $this->assert($found, 'IP bị chặn phải xuất hiện trong danh sách blacklist');

        // 3. Mở khóa IP
        $unblockOk = SecurityPolicyService::unblockIp($foundId);
        $this->assert($unblockOk, 'Mở khóa IP phải thành công');

        // 4. Cập nhật chính sách an toàn
        $policies = SecurityPolicyService::getPolicies();
        $this->assert(isset($policies['mat_khau_do_dai_toi_thieu']), 'Phải có cấu hình độ dài mật khẩu tối thiểu');
        $this->assert(isset($policies['so_lan_dang_nhap_sai_khoa']), 'Phải có cấu hình số lần đăng nhập sai');
    }

    /**
     * Test 6: Kiểm tra bảo vệ phân quyền RBAC phân hệ Quản trị hệ thống (Chỉ SYSADMIN)
     */
    public function testSysAdminRbacProtection(): void
    {
        $controller = new HeThongController();

        // 1. Đăng nhập bằng Chuyên viên thông thường -> Phải bị từ chối 403 Forbidden
        AuthService::attemptCanBo('chuyenvien.hongbang', 'Hpegov@2026');
        $req = new Request();
        $res = $controller->saoLuu($req);
        $this->assertEquals(403, $res->getStatusCode(), 'Cán bộ không phải SYSADMIN khi truy cập /admin/he-thong/sao-luu phải bị trả về HTTP 403');

        // 2. Đăng nhập bằng tài khoản SYSADMIN -> Phải được cấp quyền truy cập 200 OK
        AuthService::attemptCanBo('sysadmin', 'Hpegov@2026');
        $resSys = $controller->saoLuu($req);
        $this->assertEquals(200, $resSys->getStatusCode(), 'SYSADMIN truy cập /admin/he-thong/sao-luu phải trả về HTTP 200 OK');

        $resHealth = $controller->health($req);
        $this->assertEquals(200, $resHealth->getStatusCode(), 'SYSADMIN truy cập /admin/he-thong/health phải trả về HTTP 200 OK');
    }

    /**
     * Test 7: Phân hệ Đào tạo & Chuyển giao 4 nhóm cán bộ (§1.4)
     */
    public function testTrainingServiceAndGrading(): void
    {
        TrainingService::ensureDefaultCourses();
        $courses = TrainingService::getCoursesWithStats();
        $this->assert(count($courses) >= 4, 'Hệ thống phải có ít nhất 4 khóa đào tạo cho 4 nhóm đối tượng (§1.4)');

        // Kiểm tra nhóm đối tượng
        $groups = array_column($courses, 'nhom_doi_tuong');
        $this->assert(in_array('MOT_CUA', $groups, true), 'Phải có khóa cho Cán bộ Một cửa');
        $this->assert(in_array('CHUYEN_VIEN', $groups, true), 'Phải có khóa cho Chuyên viên thụ lý');
        $this->assert(in_array('LANH_DAO', $groups, true), 'Phải có khóa cho Lãnh đạo ký số');
        $this->assert(in_array('SYSADMIN', $groups, true), 'Phải có khóa cho Quản trị viên SYSADMIN');
    }
}
