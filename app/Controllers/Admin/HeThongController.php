<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\BackupService;
use App\Services\HealthCheckService;
use App\Services\IncidentService;
use App\Services\SecurityPolicyService;
use App\Services\TrainingService;
use PDO;
use Throwable;

/**
 * Class HeThongController
 * Phân hệ Quản trị & Vận hành Hệ thống HP-eGov (PH12, PH13, PH15 – §1.4, §2.3, §4.3, §4.4)
 * Dành riêng cho Quản trị viên hệ thống (SYSADMIN)
 */
class HeThongController extends Controller
{
    /**
     * Kiểm tra quyền SYSADMIN trước khi thực hiện bất kỳ hành động nào
     */
    private function checkSysAdmin(): ?Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $roles = AuthService::userRoles((int)$user['id']);
        if (!in_array('SYSADMIN', $roles, true) && ($user['vai_tro'] ?? '') !== 'SYSADMIN') {
            return new Response("Truy cập bị từ chối: Phân hệ kỹ thuật dành riêng cho Quản trị viên hệ thống (SYSADMIN).", 403);
        }

        return null;
    }

    // =========================================================================
    // 1. PH12: AN TOÀN THÔNG TIN & NHẬT KÝ KIỂM TOÁN WORM
    // =========================================================================

    /**
     * Tra cứu Nhật ký kiểm toán (Audit Trail)
     */
    public function audit(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $db = Database::connection();
        $page = max(1, (int)$request->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $userId = $request->get('user_id');
        $action = $request->get('action');
        $table = $request->get('table');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $where = ["1=1"];
        $params = [];

        if (!empty($userId)) {
            $where[] = "k.nguoi_dung_id = :uid";
            $params['uid'] = (int)$userId;
        }
        if (!empty($action)) {
            $where[] = "k.hanh_dong = :action";
            $params['action'] = trim((string)$action);
        }
        if (!empty($table)) {
            $where[] = "k.bang_tac_dong = :table";
            $params['table'] = trim((string)$table);
        }
        if (!empty($fromDate)) {
            $where[] = "k.created_at >= :from_date";
            $params['from_date'] = $fromDate . ' 00:00:00';
        }
        if (!empty($toDate)) {
            $where[] = "k.created_at <= :to_date";
            $params['to_date'] = $toDate . ' 23:59:59';
        }

        $whereSql = implode(' AND ', $where);

        // Đếm tổng số
        $countStmt = $db->prepare("SELECT COUNT(*) FROM nhat_ky_kiem_toan k WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Lấy danh sách
        $sql = "SELECT k.*, u.ho_ten, u.ten_dang_nhap, 
                (SELECT v.ma_vai_tro FROM nguoi_dung_vai_tro ndvt JOIN vai_tro v ON ndvt.vai_tro_id = v.id WHERE ndvt.nguoi_dung_id = u.id LIMIT 1) AS vai_tro 
                FROM nhat_ky_kiem_toan k
                LEFT JOIN nguoi_dung u ON k.nguoi_dung_id = u.id
                WHERE {$whereSql}
                ORDER BY k.id DESC
                LIMIT :lim OFFSET :off";

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy danh sách người dùng để lọc
        $users = $db->query("SELECT id, ho_ten, ten_dang_nhap FROM nguoi_dung WHERE is_deleted = 0 ORDER BY ho_ten ASC")->fetchAll(PDO::FETCH_ASSOC);


        return $this->view('admin/he_thong/audit', [
            'logs'        => $logs,
            'total'       => $total,
            'page'        => $page,
            'totalPages'  => (int)ceil($total / $limit),
            'users'       => $users,
            'filters'     => [
                'user_id'   => $userId,
                'action'    => $action,
                'table'     => $table,
                'from_date' => $fromDate,
                'to_date'   => $toDate,
            ],
            'title'       => 'Tra cứu Nhật ký kiểm toán WORM (Audit Trail)',
            'breadcrumbs' => [
                ['label' => 'Bàn làm việc', 'url' => '/admin/dashboard'],
                ['label' => 'Quản trị hệ thống'],
                ['label' => 'Nhật ký kiểm toán WORM'],
            ],
        ]);
    }

    /**
     * Kiểm tra tính toàn vẹn của chuỗi băm WORM (AJAX)
     */
    public function verifyChain(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $result = AuditService::verifyChain(2000);
        return Response::json($result);
    }

    /**
     * Nhật ký đăng nhập hệ thống
     */
    public function nhatKyDangNhap(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $db = Database::connection();
        $page = max(1, (int)$request->get('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $status = $request->get('status');
        $ip = $request->get('ip');
        $username = $request->get('username');

        $where = ["1=1"];
        $params = [];

        if (!empty($status)) {
            $where[] = "trang_thai = :status";
            $params['status'] = $status;
        }
        if (!empty($ip)) {
            $where[] = "dia_chi_ip LIKE :ip";
            $params['ip'] = '%' . trim((string)$ip) . '%';
        }
        if (!empty($username)) {
            $where[] = "ten_dang_nhap LIKE :user";
            $params['user'] = '%' . trim((string)$username) . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM nhat_ky_dang_nhap WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("SELECT * FROM nhat_ky_dang_nhap WHERE {$whereSql} ORDER BY id DESC LIMIT :lim OFFSET :off");
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/he_thong/nhat_ky_dang_nhap', [
            'logs'        => $logs,
            'total'       => $total,
            'page'        => $page,
            'totalPages'  => (int)ceil($total / $limit),
            'filters'     => ['status' => $status, 'ip' => $ip, 'username' => $username],
            'title'       => 'Nhật ký đăng nhập & An toàn phiên',
            'breadcrumbs' => [
                ['label' => 'Bàn làm việc', 'url' => '/admin/dashboard'],
                ['label' => 'Quản trị hệ thống'],
                ['label' => 'Nhật ký đăng nhập'],
            ],
        ]);
    }

    /**
     * Nhật ký kết nối tích hợp hệ thống ngoài (LGSP, C06, SMS, DVCQG...)
     */
    public function nhatKyTichHop(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $db = Database::connection();
        $page = max(1, (int)$request->get('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $heThong = $request->get('he_thong');
        $where = ["1=1"];
        $params = [];

        if (!empty($heThong)) {
            $where[] = "dich_vu = :ht";
            $params['ht'] = $heThong;
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM nhat_ky_tich_hop WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("SELECT * FROM nhat_ky_tich_hop WHERE {$whereSql} ORDER BY id DESC LIMIT :lim OFFSET :off");
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/he_thong/nhat_ky_tich_hop', [
            'logs'        => $logs,
            'total'       => $total,
            'page'        => $page,
            'totalPages'  => (int)ceil($total / $limit),
            'filters'     => ['he_thong' => $heThong],
            'title'       => 'Nhật ký kết nối tích hợp (LGSP / C06 / DVCQG)',
            'breadcrumbs' => [
                ['label' => 'Bàn làm việc', 'url' => '/admin/dashboard'],
                ['label' => 'Quản trị hệ thống'],
                ['label' => 'Nhật ký tích hợp'],
            ],
        ]);
    }

    /**
     * Quản lý IP bị chặn & Rate limit
     */
    public function ipChan(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $blockedIps = SecurityPolicyService::getBlockedIps();
        $rateLimits = SecurityPolicyService::getRateLimitedList();

        return $this->view('admin/he_thong/ip_chan', [
            'blockedIps'  => $blockedIps,
            'rateLimits'  => $rateLimits,
            'title'       => 'Quản lý IP bị chặn & Giới hạn Rate Limit',
            'breadcrumbs' => [
                ['label' => 'Bàn làm việc', 'url' => '/admin/dashboard'],
                ['label' => 'Quản trị hệ thống'],
                ['label' => 'Danh sách IP chặn & Rate limit'],
            ],
        ]);
    }

    /**
     * Thêm IP vào danh sách chặn
     */
    public function blockIpAction(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $ip = trim((string)$request->post('ip_address'));
        $reason = trim((string)$request->post('ly_do'));
        $days = (int)$request->post('days', 0);

        if (empty($ip) || empty($reason)) {
            Session::flash('error', 'Vui lòng nhập địa chỉ IP và lý do chặn.');
            return $this->redirect('/admin/he-thong/ip-chan');
        }

        $blockedUntil = $days > 0 ? date('Y-m-d H:i:s', strtotime("+{$days} days")) : null;
        if (SecurityPolicyService::blockIp($ip, $reason, $blockedUntil)) {
            Session::flash('success', "Đã khóa địa chỉ IP [{$ip}] thành công.");
        } else {
            Session::flash('error', 'Có lỗi xảy ra khi khóa IP.');
        }

        return $this->redirect('/admin/he-thong/ip-chan');
    }

    /**
     * Mở khóa IP
     */
    public function unblockIpAction(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $id = (int)$request->post('id');
        if (SecurityPolicyService::unblockIp($id)) {
            Session::flash('success', 'Đã mở khóa địa chỉ IP.');
        } else {
            Session::flash('error', 'Không thể mở khóa IP.');
        }

        return $this->redirect('/admin/he-thong/ip-chan');
    }

    /**
     * Làm sạch Rate limit
     */
    public function clearRateLimitAction(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $ip = $request->post('ip');
        SecurityPolicyService::clearRateLimit($ip ? trim((string)$ip) : null);
        Session::flash('success', 'Đã làm sạch bộ đếm Rate limit.');

        return $this->redirect('/admin/he-thong/ip-chan');
    }

    /**
     * Cấu hình chính sách bảo mật & Chế độ bảo trì
     */
    public function chinhSachBaoMat(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $policies = SecurityPolicyService::getPolicies();

        return $this->view('admin/he_thong/chinh_sach_bao_mat', [
            'policies'    => $policies,
            'title'       => 'Cấu hình Chính sách Bảo mật & Chế độ Bảo trì',
            'breadcrumbs' => [
                ['label' => 'Bàn làm việc', 'url' => '/admin/dashboard'],
                ['label' => 'Quản trị hệ thống'],
                ['label' => 'Chính sách bảo mật'],
            ],
        ]);
    }

    /**
     * Cập nhật chính sách bảo mật
     */
    public function updateChinhSachBaoMat(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        $data = [
            'mat_khau_do_dai_toi_thieu'     => (int)$request->post('mat_khau_do_dai_toi_thieu', 8),
            'so_lan_dang_nhap_sai_khoa'     => (int)$request->post('so_lan_dang_nhap_sai_khoa', 5),
            'thoi_gian_khoa_tai_khoan_phut' => (int)$request->post('thoi_gian_khoa_tai_khoan_phut', 15),
            'session_lifetime_phut'         => (int)$request->post('session_lifetime_phut', 30),
            'rate_limit_public'             => (int)$request->post('rate_limit_public', 60),
            'maintenance_mode'              => (int)$request->post('maintenance_mode', 0),
            'maintenance_message'           => trim((string)$request->post('maintenance_message')),
        ];

        if (SecurityPolicyService::updatePolicies($data, (int)$user['id'])) {
            Session::flash('success', 'Đã cập nhật chính sách an toàn thông tin & thông số hệ thống thành công.');
        } else {
            Session::flash('error', 'Có lỗi xảy ra khi cập nhật chính sách.');
        }

        return $this->redirect('/admin/he-thong/chinh-sach-bao-mat');
    }

    // =========================================================================
    // 2. PH13: QUẢN TRỊ SAO LƯU & PHỤC HỒI THẢM HỌA (RPO <= 2h, RTO <= 4h)
    // =========================================================================

    /**
     * Trang chủ quản trị Sao lưu & Phục hồi CSDL
     */
    public function saoLuu(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $history = BackupService::getHistory(50);
        $db = Database::connection();

        // Lấy thống kê
        $fullCount = (int)$db->query("SELECT COUNT(*) FROM sao_luu WHERE loai_sao_luu = 'FULL' AND trang_thai = 'THANH_CONG'")->fetchColumn();
        $diffCount = (int)$db->query("SELECT COUNT(*) FROM sao_luu WHERE loai_sao_luu = 'DIFFERENTIAL' AND trang_thai = 'THANH_CONG'")->fetchColumn();
        $logCount = (int)$db->query("SELECT COUNT(*) FROM sao_luu WHERE loai_sao_luu = 'LOG' AND trang_thai = 'THANH_CONG'")->fetchColumn();
        $lastFull = $db->query("SELECT thoi_gian_hoan_thanh FROM sao_luu WHERE loai_sao_luu = 'FULL' AND trang_thai = 'THANH_CONG' ORDER BY id DESC LIMIT 1")->fetchColumn();

        return $this->view('admin/he_thong/sao_luu', [
            'history'     => $history,
            'stats'       => [
                'full_count' => $fullCount,
                'diff_count' => $diffCount,
                'log_count'  => $logCount,
                'last_full'  => $lastFull ?: 'Chưa sao lưu',
            ],
            'title'       => 'Quản trị Sao lưu & Phục hồi thảm họa (RPO ≤ 2h, RTO ≤ 4h)',
            'breadcrumbs' => [
                ['label' => 'Bàn làm việc', 'url' => '/admin/dashboard'],
                ['label' => 'Quản trị hệ thống'],
                ['label' => 'Sao lưu & Phục hồi CSDL'],
            ],
        ]);
    }

    /**
     * Kích hoạt chạy sao lưu tức thời
     */
    public function runBackupAction(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $type = strtoupper((string)$request->post('type', 'FULL'));
        $note = trim((string)$request->post('ghi_chu', ''));

        if ($type === 'FULL') {
            $result = BackupService::runFullBackup($note ?: 'Full Backup kích hoạt thủ công');
        } elseif ($type === 'DIFFERENTIAL') {
            $result = BackupService::runDiffBackup($note ?: 'Differential Backup kích hoạt thủ công');
        } else {
            $result = BackupService::runLogBackup($note ?: 'Transaction Log Backup kích hoạt thủ công');
        }

        if ($result['success']) {
            Session::flash('success', "{$result['message']} - Checksum SHA-256: " . substr($result['sha256'] ?? '', 0, 16) . "... (Thời gian: {$result['elapsed_seconds']}s)");
        } else {
            Session::flash('error', $result['message']);
        }

        return $this->redirect('/admin/he-thong/sao-luu');
    }

    /**
     * Thực hiện PHỤC HỒI THỬ NGHIỆM vào CSDL hp_egov_restore_test
     */
    public function testRestoreAction(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $backupId = (int)$request->post('backup_id');
        if ($backupId <= 0) {
            Session::flash('error', 'Mã bản sao lưu không hợp lệ.');
            return $this->redirect('/admin/he-thong/sao-luu');
        }

        $result = BackupService::testRestore($backupId);

        if ($result['success']) {
            Session::flash('success', "PHỤC HỒI THỬ NGHIỆM THÀNH CÔNG: {$result['message']} | Kết quả diễn tập DRP đã được tự động lưu vào Sổ diễn tập.");
        } else {
            Session::flash('error', "Phục hồi thử nghiệm thất bại: {$result['message']}");
        }

        return $this->redirect('/admin/he-thong/sao-luu');
    }

    /**
     * Tải về tệp bản sao lưu (bảo mật tuyệt đối, chỉ SYSADMIN)
     */
    public function downloadBackupAction(Request $request, int $id): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $backup = BackupService::getById($id);
        if (!$backup || empty($backup['duong_dan']) || !file_exists($backup['duong_dan'])) {
            Session::flash('error', 'Tệp tin sao lưu không tồn tại trên hệ thống lưu trữ.');
            return $this->redirect('/admin/he-thong/sao-luu');
        }

        $filePath = $backup['duong_dan'];
        $fileName = $backup['ten_file'];

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    // =========================================================================
    // 3. GIÁM SÁT SỨC KHỎE HẠ TẦNG (HEALTH DASHBOARD & API)
    // =========================================================================

    /**
     * Trang Dashboard Sức khỏe hệ thống
     */
    public function health(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $health = HealthCheckService::getHealthStatus();

        return $this->view('admin/he_thong/health', [
            'health'      => $health,
            'title'       => 'Giám sát Sức khỏe Hạ tầng & Dịch vụ Thời gian thực',
            'breadcrumbs' => [
                ['label' => 'Bàn làm việc', 'url' => '/admin/dashboard'],
                ['label' => 'Quản trị hệ thống'],
                ['label' => 'Giám sát sức khỏe hệ thống'],
            ],
        ]);
    }

    // =========================================================================
    // 4. QUẢN LÝ SỰ CỐ AN TOÀN THÔNG TIN & KẾ HOẠCH BCP/DRP (§4.3, §4.4)
    // =========================================================================

    /**
     * Trang Quản lý sự cố & Biên bản diễn tập DRP
     */
    public function suCo(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $status = $request->get('status');
        $incidents = IncidentService::getIncidents($status ?: null);
        $drills = IncidentService::getDrills();
        $schedule = IncidentService::checkDrillSchedule();

        return $this->view('admin/he_thong/su_co', [
            'incidents'   => $incidents,
            'drills'      => $drills,
            'schedule'    => $schedule,
            'filterStatus'=> $status,
            'title'       => 'Quản lý Sự cố An toàn thông tin & Kế hoạch BCP/DRP',
            'breadcrumbs' => [
                ['label' => 'Bàn làm việc', 'url' => '/admin/dashboard'],
                ['label' => 'Quản trị hệ thống'],
                ['label' => 'Quản lý sự cố & BCP/DRP'],
            ],
        ]);
    }

    /**
     * Thêm hoặc cập nhật sự cố
     */
    public function luuSuCoAction(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        $id = (int)$request->post('id', 0);
        $data = [
            'loai_su_co'          => $request->post('loai_su_co'),
            'muc_do'              => $request->post('muc_do'),
            'tieu_de'             => trim((string)$request->post('tieu_de')),
            'mo_ta'               => trim((string)$request->post('mo_ta')),
            'thoi_gian_phat_hien' => $request->post('thoi_gian_phat_hien') ?: date('Y-m-d H:i:s'),
            'raci_trach_nhiem'    => trim((string)$request->post('raci_trach_nhiem')),
            'bien_phap_khac_phuc' => trim((string)$request->post('bien_phap_khac_phuc')),
            'trang_thai'          => $request->post('trang_thai'),
            'thoi_gian_khac_phuc' => $request->post('thoi_gian_khac_phuc') ?: null,
        ];

        if ($id > 0) {
            $ok = IncidentService::updateIncident($id, $data);
            $msg = 'Đã cập nhật tiến độ xử lý sự cố.';
        } else {
            $ok = IncidentService::createIncident($data, (int)$user['id']);
            $msg = 'Đã tiếp nhận và ghi nhận sự cố mới vào hệ thống.';
        }

        if ($ok) {
            Session::flash('success', $msg);
        } else {
            Session::flash('error', 'Có lỗi xảy ra khi lưu thông tin sự cố.');
        }

        return $this->redirect('/admin/he-thong/su-co');
    }

    /**
     * Thêm biên bản diễn tập
     */
    public function luuDienTapAction(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        $data = [
            'loai_dien_tap'      => $request->post('loai_dien_tap'),
            'tieu_de'            => trim((string)$request->post('tieu_de')),
            'kich_ban'           => trim((string)$request->post('kich_ban')),
            'thoi_gian_dien_tap' => $request->post('thoi_gian_dien_tap') ?: date('Y-m-d H:i:s'),
            'rto_thuc_te_phut'   => $request->post('rto_thuc_te_phut'),
            'ket_qua'            => $request->post('ket_qua'),
            'bien_ban_danh_gia'  => trim((string)$request->post('bien_ban_danh_gia')),
        ];

        if (IncidentService::recordDrill($data, (int)$user['id'])) {
            Session::flash('success', 'Đã ghi nhận biên bản diễn tập vào hồ sơ kiểm toán.');
        } else {
            Session::flash('error', 'Có lỗi khi lưu biên bản diễn tập.');
        }

        return $this->redirect('/admin/he-thong/su-co');
    }

    // =========================================================================
    // 5. PH15: ĐÀO TẠO & CHUYỂN GIAO CÔNG NGHỆ 4 NHÓM CÁN BỘ (§1.4)
    // =========================================================================

    /**
     * Trang Quản trị đào tạo & Sát hạch cán bộ
     */
    public function daoTao(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        TrainingService::ensureDefaultCourses();
        TrainingService::seedSampleStudents();

        $courses = TrainingService::getCoursesWithStats();
        $selectedCourseId = (int)$request->get('khoa_id', $courses[0]['id'] ?? 0);

        $students = [];
        $selectedCourse = null;
        foreach ($courses as $c) {
            if ((int)$c['id'] === $selectedCourseId) {
                $selectedCourse = $c;
                break;
            }
        }

        if ($selectedCourseId > 0) {
            $students = TrainingService::getStudentsByCourse($selectedCourseId);
        }

        // Lấy danh sách cán bộ có thể ghi danh
        $db = Database::connection();
        $availableUsers = $db->query("SELECT u.id, u.ho_ten, u.ten_dang_nhap, 
            COALESCE((SELECT v.ma_vai_tro FROM nguoi_dung_vai_tro ndvt JOIN vai_tro v ON ndvt.vai_tro_id = v.id WHERE ndvt.nguoi_dung_id = u.id LIMIT 1), 'CAN_BO') AS vai_tro 
            FROM nguoi_dung u WHERE u.is_deleted = 0 ORDER BY u.ho_ten ASC")->fetchAll(PDO::FETCH_ASSOC);


        return $this->view('admin/he_thong/dao_tao', [
            'courses'        => $courses,
            'selectedCourse' => $selectedCourse,
            'students'       => $students,
            'availableUsers' => $availableUsers,
            'title'          => 'Đào tạo & Sát hạch chuyển giao 4 nhóm cán bộ (§1.4)',
            'breadcrumbs'    => [
                ['label' => 'Bàn làm việc', 'url' => '/admin/dashboard'],
                ['label' => 'Quản trị hệ thống'],
                ['label' => 'Đào tạo & Chuyển giao công nghệ'],
            ],
        ]);
    }

    /**
     * Ghi danh cán bộ vào khóa đào tạo
     */
    public function ghiDanhHocVienAction(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $courseId = (int)$request->post('khoa_id');
        $userId = (int)$request->post('nguoi_dung_id');

        if ($courseId <= 0 || $userId <= 0) {
            Session::flash('error', 'Dữ liệu ghi danh không hợp lệ.');
            return $this->redirect('/admin/he-thong/dao-tao');
        }

        if (TrainingService::enrollStudent($courseId, $userId)) {
            Session::flash('success', 'Đã ghi danh cán bộ vào khóa đào tạo thành công.');
        } else {
            Session::flash('error', 'Có lỗi xảy ra hoặc cán bộ đã được ghi danh trước đó.');
        }

        return $this->redirect('/admin/he-thong/dao-tao?khoa_id=' . $courseId);
    }

    /**
     * Nhập điểm và đánh giá sát hạch
     */
    public function nhapDiemHocVienAction(Request $request): Response
    {
        if ($resp = $this->checkSysAdmin()) {
            return $resp;
        }

        $courseId = (int)$request->post('khoa_id');
        $studentId = (int)$request->post('id');
        $diemSo = $request->post('diem_so') !== '' ? (float)$request->post('diem_so') : null;
        $soHoSo = (int)$request->post('so_ho_so_thuc_hanh', 0);
        $nhanXet = trim((string)$request->post('nhan_xet'));

        if (TrainingService::gradeStudent($studentId, $diemSo, $soHoSo, $nhanXet)) {
            Session::flash('success', 'Đã cập nhật kết quả sát hạch học viên thành công theo tiêu chuẩn §1.4.');
        } else {
            Session::flash('error', 'Không thể cập nhật kết quả sát hạch.');
        }

        return $this->redirect('/admin/he-thong/dao-tao?khoa_id=' . $courseId);
    }
}
