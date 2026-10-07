<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use PDO;
use Throwable;

/**
 * AuthService – Dịch vụ Quản lý Định danh, Xác thực & Phân quyền RBAC đa cấp
 * 
 * Hỗ trợ 2 guard:
 * 1. 'can_bo': Dành cho cán bộ các cơ quan hành chính (/admin/dang-nhap)
 * 2. 'cong_dan': Dành cho công dân (VNeID) và doanh nghiệp (MST) (/dang-nhap)
 */
class AuthService
{
    public const GUARD_CAN_BO = 'can_bo';
    public const GUARD_CONG_DAN = 'cong_dan';

    // Số lần thử sai tối đa trước khi khóa 15 phút
    public const MAX_FAILED_ATTEMPTS = 5;
    public const LOCKOUT_MINUTES = 15;
    // Số lần sai kích hoạt Captcha
    public const CAPTCHA_TRIGGER_ATTEMPTS = 3;

    // Keys lưu session
    private const SESSION_GUARD = 'auth_guard';
    private const SESSION_USER = 'auth_user';
    private const SESSION_ROLES = 'auth_roles';
    private const SESSION_PERMISSIONS = 'auth_permissions';
    private const SESSION_DON_VI_LIST = 'auth_don_vi_list';
    private const SESSION_CURRENT_DON_VI = 'auth_current_don_vi';
    private const SESSION_MUST_CHANGE_PASSWORD = 'auth_must_change_password';

    /**
     * Xác thực cán bộ công chức (/admin/dang-nhap)
     *
     * @return array{success: bool, message: string, require_captcha: bool, must_change_pw: bool, user: ?array}
     */
    public static function attemptCanBo(string $username, string $password, ?string $captchaCode = null): array
    {
        $db = Database::connection();
        $ip = self::getClientIp();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System';
        $trimmedUsername = trim($username);

        // 1. Tìm thông tin cán bộ
        $stmt = $db->prepare("SELECT * FROM nguoi_dung WHERE (ten_dang_nhap = :u OR email = :email) AND is_deleted = 0 LIMIT 1");
        $stmt->execute([':u' => $trimmedUsername, ':email' => $trimmedUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            self::logLogin($trimmedUsername, 'UNKNOWN', 'THAT_BAI', 'Tài khoản không tồn tại', $ip, $ua);
            return [
                'success' => false,
                'message' => 'Tên đăng nhập hoặc mật khẩu không chính xác.',
                'require_captcha' => false,
                'must_change_pw' => false,
                'user' => null,
            ];
        }

        $userId = (int)$user['id'];
        $soLanSai = (int)$user['so_lan_sai'];
        $khoaDen = $user['khoa_den'];

        // 2. Kiểm tra nếu tài khoản bị khóa tạm thời do quá số lần thử sai
        if (!empty($khoaDen)) {
            $lockUntil = strtotime($khoaDen);
            $now = time();
            if ($lockUntil > $now) {
                $conLaiPhut = (int)ceil(($lockUntil - $now) / 60);
                self::logLogin($user['ten_dang_nhap'], 'CAN_BO', 'KHOA', "Tài khoản đang bị khóa tạm thời đến {$khoaDen}", $ip, $ua);
                return [
                    'success' => false,
                    'message' => "Tài khoản tạm thời bị khóa do nhập sai quá " . self::MAX_FAILED_ATTEMPTS . " lần. Vui lòng thử lại sau {$conLaiPhut} phút.",
                    'require_captcha' => true,
                    'must_change_pw' => false,
                    'user' => null,
                ];
            } else {
                // Đã hết thời gian khóa, tự động mở khóa
                $db->prepare("UPDATE nguoi_dung SET khoa_den = NULL, so_lan_sai = 0 WHERE id = :id")->execute([':id' => $userId]);
                $soLanSai = 0;
            }
        }

        // 3. Kiểm tra trạng thái hoạt động của tài khoản
        if ((int)$user['trang_thai'] !== 1) {
            self::logLogin($user['ten_dang_nhap'], 'CAN_BO', 'THAT_BAI', 'Tài khoản đã bị vô hiệu hóa', $ip, $ua);
            return [
                'success' => false,
                'message' => 'Tài khoản đã bị vô hiệu hóa. Vui lòng liên hệ Quản trị viên hệ thống.',
                'require_captcha' => false,
                'must_change_pw' => false,
                'user' => null,
            ];
        }

        // 4. Nếu số lần sai >= 3, bắt buộc phải nhập đúng Captcha
        $needsCaptcha = ($soLanSai >= self::CAPTCHA_TRIGGER_ATTEMPTS);
        if ($needsCaptcha) {
            if (empty($captchaCode) || !CaptchaService::verify($captchaCode)) {
                return [
                    'success' => false,
                    'message' => 'Mã xác nhận bảo mật (Captcha) không chính xác hoặc đã hết hạn.',
                    'require_captcha' => true,
                    'must_change_pw' => false,
                    'user' => null,
                ];
            }
        }

        // 5. Kiểm tra mật khẩu
        if (!password_verify($password, $user['mat_khau'])) {
            $newFailedCount = $soLanSai + 1;

            if ($newFailedCount >= self::MAX_FAILED_ATTEMPTS) {
                // Khóa 15 phút
                $lockUntilStr = date('Y-m-d H:i:s', time() + (self::LOCKOUT_MINUTES * 60));
                $stmt = $db->prepare("UPDATE nguoi_dung SET so_lan_sai = :cnt, khoa_den = :kd WHERE id = :id");
                $stmt->execute([':cnt' => $newFailedCount, ':kd' => $lockUntilStr, ':id' => $userId]);

                self::logLogin($user['ten_dang_nhap'], 'CAN_BO', 'KHOA', "Sai mật khẩu {$newFailedCount} lần, khóa 15 phút", $ip, $ua);
                AuditService::log($userId, 'TAI_KHOAN_BI_KHOA', 'nguoi_dung', $userId, null, "Khóa tài khoản 15 phút do nhập sai {$newFailedCount} lần", null, $ip, $ua);

                return [
                    'success' => false,
                    'message' => "Bạn đã nhập sai mật khẩu {$newFailedCount} lần. Tài khoản bị tạm khóa " . self::LOCKOUT_MINUTES . " phút.",
                    'require_captcha' => true,
                    'must_change_pw' => false,
                    'user' => null,
                ];
            } else {
                $stmt = $db->prepare("UPDATE nguoi_dung SET so_lan_sai = :cnt WHERE id = :id");
                $stmt->execute([':cnt' => $newFailedCount, ':id' => $userId]);

                self::logLogin($user['ten_dang_nhap'], 'CAN_BO', 'THAT_BAI', "Sai mật khẩu lần {$newFailedCount}", $ip, $ua);

                return [
                    'success' => false,
                    'message' => 'Tên đăng nhập hoặc mật khẩu không chính xác.',
                    'require_captcha' => ($newFailedCount >= self::CAPTCHA_TRIGGER_ATTEMPTS),
                    'must_change_pw' => false,
                    'user' => null,
                ];
            }
        }

        // 6. Đăng nhập thành công -> Reset số lần sai và thời gian khóa
        $db->prepare("UPDATE nguoi_dung SET so_lan_sai = 0, khoa_den = NULL WHERE id = :id")->execute([':id' => $userId]);

        // 7. Lấy danh sách vai trò, quyền, đơn vị công tác & kiêm nhiệm
        $rolesData = self::loadRolesAndPermissions($userId);

        // 8. Xác định vai trò chính và ghi log đăng nhập thành công
        $primaryRole = $rolesData['roles'][0] ?? 'CAN_BO';
        self::logLogin($user['ten_dang_nhap'], $primaryRole, 'THANH_CONG', 'Đăng nhập thành công', $ip, $ua);
        AuditService::log($userId, 'DANG_NHAP_THANH_CONG', 'nguoi_dung', $userId, null, "Cán bộ {$user['ten_dang_nhap']} đăng nhập thành công", null, $ip, $ua);

        // 9. Khởi tạo phiên làm việc bảo mật
        Session::regenerate(true);
        Session::set(self::SESSION_GUARD, self::GUARD_CAN_BO);
        Session::set(self::SESSION_USER, [
            'id' => $userId,
            'ten_dang_nhap' => $user['ten_dang_nhap'],
            'ho_ten' => $user['ho_ten'],
            'email' => $user['email'],
            'so_dien_thoai' => $user['so_dien_thoai'],
            'so_cccd' => $user['so_cccd'],
            'don_vi_chinh_id' => (int)$user['don_vi_chinh_id'],
        ]);
        Session::set(self::SESSION_ROLES, $rolesData['roles']);
        Session::set(self::SESSION_PERMISSIONS, $rolesData['permissions']);
        Session::set(self::SESSION_DON_VI_LIST, $rolesData['don_vi_list']);
        Session::set(self::SESSION_CURRENT_DON_VI, $rolesData['current_don_vi']);

        $mustChangePw = ((int)$user['yeu_cau_doi_mk'] === 1);
        Session::set(self::SESSION_MUST_CHANGE_PASSWORD, $mustChangePw);

        return [
            'success' => true,
            'message' => 'Đăng nhập thành công.',
            'require_captcha' => false,
            'must_change_pw' => $mustChangePw,
            'user' => $user,
        ];
    }

    /**
     * Xác thực tài khoản Doanh nghiệp bằng Mã số thuế & Mật khẩu
     *
     * @return array{success: bool, message: string, user: ?array}
     */
    public static function attemptDoanhNghiep(string $mst, string $password): array
    {
        $db = Database::connection();
        $ip = self::getClientIp();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System';
        $trimmedMst = trim($mst);

        $stmt = $db->prepare("SELECT * FROM cong_dan WHERE ma_so_thue = :mst AND loai_chu_the = 'TO_CHUC' LIMIT 1");
        $stmt->execute([':mst' => $trimmedMst]);
        $dn = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dn || empty($dn['mat_khau']) || !password_verify($password, $dn['mat_khau'])) {
            self::logLogin($trimmedMst, 'CONG_DAN', 'THAT_BAI', 'Mã số thuế hoặc mật khẩu không chính xác', $ip, $ua);
            return [
                'success' => false,
                'message' => 'Mã số thuế hoặc mật khẩu không chính xác.',
                'user' => null,
            ];
        }

        if ((int)$dn['trang_thai'] !== 1) {
            self::logLogin($trimmedMst, 'CONG_DAN', 'THAT_BAI', 'Tài khoản doanh nghiệp đã bị vô hiệu hóa', $ip, $ua);
            return [
                'success' => false,
                'message' => 'Tài khoản doanh nghiệp đã bị khóa hoặc ngừng hoạt động.',
                'user' => null,
            ];
        }

        $dnId = (int)$dn['id'];
        self::logLogin($trimmedMst, 'CONG_DAN', 'THANH_CONG', 'Doanh nghiệp đăng nhập thành công', $ip, $ua);
        AuditService::log(null, 'DN_DANG_NHAP_THANH_CONG', 'cong_dan', $dnId, null, "Doanh nghiệp MST {$trimmedMst} đăng nhập thành công", $dnId, $ip, $ua);

        Session::regenerate(true);
        Session::set(self::SESSION_GUARD, self::GUARD_CONG_DAN);
        Session::set(self::SESSION_USER, [
            'id' => $dnId,
            'loai_chu_the' => 'TO_CHUC',
            'ma_so_thue' => $dn['ma_so_thue'],
            'ten_doanh_nghiep' => $dn['ten_doanh_nghiep'],
            'ho_ten' => $dn['ten_doanh_nghiep'],
            'nguoi_dai_dien' => $dn['nguoi_dai_dien'],
            'email' => $dn['email'],
            'so_dien_thoai' => $dn['so_dien_thoai'],
            'dia_chi' => $dn['dia_chi_thuong_tru'],
        ]);
        Session::set(self::SESSION_ROLES, ['CONG_DAN']);
        Session::set(self::SESSION_PERMISSIONS, [
            'portal.nop_ho_so',
            'portal.tra_cuu',
            'portal.bo_sung',
            'portal.danh_gia'
        ]);

        return [
            'success' => true,
            'message' => 'Đăng nhập thành công.',
            'user' => $dn,
        ];
    }

    /**
     * Xác thực công dân qua tài khoản VNeID Sandbox (OIDC Authorization Code Flow)
     * Nhận thuộc tính: Số CCCD, Họ tên, Ngày sinh, Giới tính, Quê quán, Địa chỉ thường trú
     * Tự động tạo mới hoặc cập nhật thông tin trong bảng cong_dan
     *
     * @param array $vneidProfile
     * @return array{success: bool, message: string, user: ?array}
     */
    public static function loginFromVNeID(array $vneidProfile): array
    {
        $db = Database::connection();
        $ip = self::getClientIp();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System';

        $cccd = trim($vneidProfile['so_cccd'] ?? '');
        $vneidSub = trim($vneidProfile['vneid_sub'] ?? ('vneid_sub_' . $cccd));
        $hoTen = trim($vneidProfile['ho_ten'] ?? 'Công dân');
        $ngaySinh = !empty($vneidProfile['ngay_sinh']) ? $vneidProfile['ngay_sinh'] : null;
        $gioiTinh = in_array($vneidProfile['gioi_tinh'] ?? '', ['NAM', 'NU', 'KHAC'], true) ? $vneidProfile['gioi_tinh'] : 'NAM';
        $queQuan = trim($vneidProfile['que_quan'] ?? '');
        $diaChiThuongTru = trim($vneidProfile['dia_chi_thuong_tru'] ?? '');
        $soDienThoai = trim($vneidProfile['so_dien_thoai'] ?? '');
        $email = trim($vneidProfile['email'] ?? '');

        if (empty($cccd)) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy Số CCCD từ phản hồi định danh VNeID.',
                'user' => null,
            ];
        }

        // Tìm trong bảng cong_dan
        $stmt = $db->prepare("SELECT * FROM cong_dan WHERE so_cccd = :cccd OR vneid_sub = :sub LIMIT 1");
        $stmt->execute([':cccd' => $cccd, ':sub' => $vneidSub]);
        $citizen = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($citizen) {
            $citizenId = (int)$citizen['id'];
            if ((int)$citizen['trang_thai'] !== 1) {
                return [
                    'success' => false,
                    'message' => 'Tài khoản công dân này đã bị khóa trên hệ thống HP-eGov.',
                    'user' => null,
                ];
            }

            // Cập nhật thông tin mới nhất từ VNeID
            $updateSql = "UPDATE cong_dan SET 
                ho_ten = :ho_ten, 
                ngay_sinh = COALESCE(:ngay_sinh, ngay_sinh),
                gioi_tinh = COALESCE(:gioi_tinh, gioi_tinh),
                que_quan = COALESCE(:que_quan, que_quan),
                dia_chi_thuong_tru = COALESCE(:dia_chi, dia_chi_thuong_tru),
                vneid_sub = :sub,
                updated_at = NOW()
                WHERE id = :id";
            $db->prepare($updateSql)->execute([
                ':ho_ten' => $hoTen,
                ':ngay_sinh' => $ngaySinh,
                ':gioi_tinh' => $gioiTinh,
                ':que_quan' => $queQuan,
                ':dia_chi' => $diaChiThuongTru,
                ':sub' => $vneidSub,
                ':id' => $citizenId,
            ]);
        } else {
            // Tạo mới công dân từ VNeID
            $insertSql = "INSERT INTO cong_dan 
                (loai_chu_the, so_cccd, vneid_sub, ho_ten, ngay_sinh, gioi_tinh, que_quan, dia_chi_thuong_tru, email, so_dien_thoai, trang_thai, created_at) 
                VALUES 
                ('CA_NHAN', :cccd, :sub, :ho_ten, :ngay_sinh, :gioi_tinh, :que_quan, :dia_chi, :email, :sdt, 1, NOW())";
            $stmt = $db->prepare($insertSql);
            $stmt->execute([
                ':cccd' => $cccd,
                ':sub' => $vneidSub,
                ':ho_ten' => $hoTen,
                ':ngay_sinh' => $ngaySinh,
                ':gioi_tinh' => $gioiTinh,
                ':que_quan' => $queQuan,
                ':dia_chi' => $diaChiThuongTru,
                ':email' => $email ?: null,
                ':sdt' => $soDienThoai ?: null,
            ]);
            $citizenId = (int)$db->lastInsertId();
        }

        // Lấy thông tin hoàn chỉnh
        $stmt = $db->prepare("SELECT * FROM cong_dan WHERE id = :id");
        $stmt->execute([':id' => $citizenId]);
        $citizen = $stmt->fetch(PDO::FETCH_ASSOC);

        self::logLogin($cccd, 'CONG_DAN', 'THANH_CONG', 'Đăng nhập VNeID thành công', $ip, $ua);
        AuditService::log(null, 'VNEID_DANG_NHAP_THANH_CONG', 'cong_dan', $citizenId, null, "Công dân CCCD {$cccd} đăng nhập qua VNeID OIDC", $citizenId, $ip, $ua);

        Session::regenerate(true);
        Session::set(self::SESSION_GUARD, self::GUARD_CONG_DAN);
        Session::set(self::SESSION_USER, [
            'id' => $citizenId,
            'loai_chu_the' => 'CA_NHAN',
            'so_cccd' => $citizen['so_cccd'],
            'vneid_sub' => $citizen['vneid_sub'],
            'ho_ten' => $citizen['ho_ten'],
            'ngay_sinh' => $citizen['ngay_sinh'],
            'gioi_tinh' => $citizen['gioi_tinh'],
            'que_quan' => $citizen['que_quan'],
            'dia_chi_thuong_tru' => $citizen['dia_chi_thuong_tru'],
            'email' => $citizen['email'],
            'so_dien_thoai' => $citizen['so_dien_thoai'],
        ]);
        Session::set(self::SESSION_ROLES, ['CONG_DAN']);
        Session::set(self::SESSION_PERMISSIONS, [
            'portal.nop_ho_so',
            'portal.tra_cuu',
            'portal.bo_sung',
            'portal.danh_gia'
        ]);

        return [
            'success' => true,
            'message' => 'Đăng nhập qua VNeID thành công.',
            'user' => $citizen,
        ];
    }

    /**
     * Nạp danh sách vai trò, quyền hạn và cây đơn vị cho một cán bộ
     */
    public static function loadRolesAndPermissions(int $userId): array
    {
        $db = Database::connection();

        // 1. Lấy vai trò và đơn vị tương ứng
        $roleSql = "SELECT v.ma_vai_tro, v.ten_vai_tro, ndvt.don_vi_id, dv.ten_don_vi, dv.ma_dinh_danh, dv.loai_don_vi, dv.path AS duong_dan_node 
                    FROM nguoi_dung_vai_tro ndvt
                    JOIN vai_tro v ON ndvt.vai_tro_id = v.id
                    JOIN don_vi dv ON ndvt.don_vi_id = dv.id
                    WHERE ndvt.nguoi_dung_id = :uid";
        $stmt = $db->prepare($roleSql);
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $roles = [];
        $donViMap = [];

        foreach ($rows as $row) {
            if (!in_array($row['ma_vai_tro'], $roles, true)) {
                $roles[] = $row['ma_vai_tro'];
            }
            $donViId = (int)$row['don_vi_id'];
            if (!isset($donViMap[$donViId])) {
                $donViMap[$donViId] = [
                    'id' => $donViId,
                    'ten_don_vi' => $row['ten_don_vi'],
                    'ma_dinh_danh' => $row['ma_dinh_danh'],
                    'loai_don_vi' => $row['loai_don_vi'],
                    'duong_dan_node' => $row['duong_dan_node'],
                    'roles' => [$row['ma_vai_tro']],
                ];
            } else {
                if (!in_array($row['ma_vai_tro'], $donViMap[$donViId]['roles'], true)) {
                    $donViMap[$donViId]['roles'][] = $row['ma_vai_tro'];
                }
            }
        }

        // Lấy thông tin đơn vị chính
        $uStmt = $db->prepare("SELECT u.don_vi_chinh_id, dv.ten_don_vi, dv.ma_dinh_danh, dv.loai_don_vi, dv.path AS duong_dan_node 
                               FROM nguoi_dung u 
                               JOIN don_vi dv ON u.don_vi_chinh_id = dv.id 
                               WHERE u.id = :uid");
        $uStmt->execute([':uid' => $userId]);
        $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);

        $currentDonVi = null;
        if ($uRow) {
            $mainDvId = (int)$uRow['don_vi_chinh_id'];
            $currentDonVi = [
                'id' => $mainDvId,
                'ten_don_vi' => $uRow['ten_don_vi'],
                'ma_dinh_danh' => $uRow['ma_dinh_danh'],
                'loai_don_vi' => $uRow['loai_don_vi'],
                'duong_dan_node' => $uRow['duong_dan_node'],
                'roles' => $donViMap[$mainDvId]['roles'] ?? $roles,
            ];
            // Đảm bảo đơn vị chính có trong danh sách
            if (!isset($donViMap[$mainDvId])) {
                $donViMap[$mainDvId] = $currentDonVi;
            }
        } elseif (!empty($donViMap)) {
            $currentDonVi = reset($donViMap);
        }

        // 2. Lấy toàn bộ quyền theo các vai trò
        $permissions = [];
        if (!empty($roles)) {
            $inRoles = implode(',', array_fill(0, count($roles), '?'));
            $permSql = "SELECT DISTINCT q.ma_quyen 
                        FROM quyen q
                        JOIN vai_tro_quyen vtq ON q.id = vtq.quyen_id
                        JOIN vai_tro v ON vtq.vai_tro_id = v.id
                        WHERE v.ma_vai_tro IN ({$inRoles})";
            $pStmt = $db->prepare($permSql);
            $pStmt->execute($roles);
            $permissions = $pStmt->fetchAll(PDO::FETCH_COLUMN);
        }

        // CHẶN CỨNG BẢO MẬT BẤT BIẾN:
        // Nếu chỉ có vai trò SYSADMIN, tuyệt đối loại bỏ mọi quyền hoso.* và kyso.*
        if (in_array('SYSADMIN', $roles, true) && count($roles) === 1) {
            $permissions = array_filter($permissions, function ($p) {
                return !str_starts_with($p, 'hoso.') && !str_starts_with($p, 'kyso.') && !str_starts_with($p, 'thu_ly.');
            });
            $permissions = array_values($permissions);
        }

        return [
            'roles' => $roles,
            'permissions' => $permissions,
            'don_vi_list' => array_values($donViMap),
            'current_don_vi' => $currentDonVi,
        ];
    }

    /**
     * Chuyển đổi đơn vị công tác hiện tại (dành cho cán bộ kiêm nhiệm nhiều đơn vị)
     */
    public static function switchDonVi(int $donViId): bool
    {
        $donViList = Session::get(self::SESSION_DON_VI_LIST, []);
        foreach ($donViList as $dv) {
            if ((int)$dv['id'] === $donViId) {
                Session::set(self::SESSION_CURRENT_DON_VI, $dv);
                // Làm mới danh sách quyền theo vai trò tại node này
                self::refresh();
                return true;
            }
        }
        return false;
    }

    /**
     * Làm mới cache session thông tin, quyền và vai trò từ CSDL
     */
    public static function refresh(): void
    {
        $user = Session::get(self::SESSION_USER);
        $guard = Session::get(self::SESSION_GUARD);

        if ($guard === self::GUARD_CAN_BO && !empty($user['id'])) {
            $data = self::loadRolesAndPermissions((int)$user['id']);
            Session::set(self::SESSION_ROLES, $data['roles']);
            Session::set(self::SESSION_PERMISSIONS, $data['permissions']);
            Session::set(self::SESSION_DON_VI_LIST, $data['don_vi_list']);

            // Giữ nguyên current don vi nếu vẫn còn trong danh sách
            $current = Session::get(self::SESSION_CURRENT_DON_VI);
            if ($current) {
                $exists = false;
                foreach ($data['don_vi_list'] as $dv) {
                    if ((int)$dv['id'] === (int)$current['id']) {
                        Session::set(self::SESSION_CURRENT_DON_VI, $dv);
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    Session::set(self::SESSION_CURRENT_DON_VI, $data['current_don_vi']);
                }
            } else {
                Session::set(self::SESSION_CURRENT_DON_VI, $data['current_don_vi']);
            }
        }
    }

    /**
     * Kiểm tra trạng thái đã đăng nhập
     */
    public static function check(?string $guard = null): bool
    {
        $currentGuard = Session::get(self::SESSION_GUARD);
        if (empty($currentGuard) || empty(Session::get(self::SESSION_USER))) {
            return false;
        }
        if ($guard !== null && $currentGuard !== $guard) {
            return false;
        }
        return true;
    }

    /**
     * Lấy guard hiện tại
     */
    public static function guard(): ?string
    {
        return Session::get(self::SESSION_GUARD);
    }

    /**
     * Lấy thông tin người dùng hiện tại từ session
     */
    public static function user(): ?array
    {
        return Session::get(self::SESSION_USER);
    }

    /**
     * Lấy ID người dùng hiện tại
     */
    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    /**
     * Lấy danh sách vai trò hiện tại
     */
    public static function roles(): array
    {
        return Session::get(self::SESSION_ROLES, []);
    }

    /**
     * Lấy danh sách mã vai trò của một người dùng cụ thể
     *
     * @param int|null $userId
     * @return array<string>
     */
    public static function userRoles(?int $userId = null): array
    {
        if ($userId === null) {
            return self::roles();
        }
        $data = self::loadRolesAndPermissions($userId);
        return $data['roles'] ?? [];
    }

    /**
     * Kiểm tra người dùng có một hoặc nhiều vai trò cụ thể
     *
     * @param string|array $role
     */
    public static function hasRole(string|array $role): bool
    {
        $userRoles = self::roles();
        if (is_array($role)) {
            foreach ($role as $r) {
                if (in_array($r, $userRoles, true)) {
                    return true;
                }
            }
            return false;
        }
        return in_array($role, $userRoles, true);
    }

    /**
     * Lấy danh sách quyền hạn hiện tại
     */
    public static function permissions(): array
    {
        return Session::get(self::SESSION_PERMISSIONS, []);
    }

    /**
     * Kiểm tra quyền thao tác (RBAC Permission Check)
     */
    public static function can(string $permissionCode): bool
    {
        // Kiểm tra quyền từ cache session
        $perms = self::permissions();
        return in_array($permissionCode, $perms, true);
    }

    /**
     * Lấy đơn vị công tác hiện tại của cán bộ
     */
    public static function currentDonVi(): ?array
    {
        return Session::get(self::SESSION_CURRENT_DON_VI);
    }

    /**
     * Lấy danh sách tất cả các đơn vị cán bộ được phân công
     */
    public static function donViList(): array
    {
        return Session::get(self::SESSION_DON_VI_LIST, []);
    }

    /**
     * Kiểm tra có bắt buộc phải đổi mật khẩu lần đầu không
     */
    public static function mustChangePassword(): bool
    {
        return (bool)Session::get(self::SESSION_MUST_CHANGE_PASSWORD, false);
    }

    /**
     * Đánh dấu đã hoàn thành đổi mật khẩu lần đầu
     */
    public static function markPasswordChanged(): void
    {
        Session::set(self::SESSION_MUST_CHANGE_PASSWORD, false);
        $userId = self::id();
        if ($userId) {
            Database::connection()->prepare("UPDATE nguoi_dung SET yeu_cau_doi_mk = 0 WHERE id = :id")->execute([':id' => $userId]);
        }
    }

    /**
     * Đăng xuất khỏi hệ thống
     */
    public static function logout(): void
    {
        $userId = self::id();
        $guard = self::guard();

        if ($userId) {
            AuditService::log(
                $guard === self::GUARD_CAN_BO ? $userId : null,
                'DANG_XUAT',
                $guard === self::GUARD_CAN_BO ? 'nguoi_dung' : 'cong_dan',
                $userId,
                null,
                "Người dùng đăng xuất khỏi hệ thống"
            );
        }

        Session::remove(self::SESSION_GUARD);
        Session::remove(self::SESSION_USER);
        Session::remove(self::SESSION_ROLES);
        Session::remove(self::SESSION_PERMISSIONS);
        Session::remove(self::SESSION_DON_VI_LIST);
        Session::remove(self::SESSION_CURRENT_DON_VI);
        Session::remove(self::SESSION_MUST_CHANGE_PASSWORD);
        Session::regenerate(true);
    }

    /**
     * Ghi nhật ký đăng nhập vào bảng nhat_ky_dang_nhap
     */
    private static function logLogin(
        string $username,
        string $role,
        string $status,
        string $reason,
        string $ip,
        string $userAgent
    ): void {
        try {
            $db = Database::connection();
            $sql = "INSERT INTO nhat_ky_dang_nhap (ten_dang_nhap, vai_tro, dia_chi_ip, user_agent, trang_thai, ly_do, created_at) 
                    VALUES (:u, :r, :ip, :ua, :status, :reason, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':u' => $username,
                ':r' => $role,
                ':ip' => $ip,
                ':ua' => $userAgent,
                ':status' => $status,
                ':reason' => $reason,
            ]);
        } catch (Throwable $e) {
            error_log("[AuthService logLogin Error] " . $e->getMessage());
        }
    }

    /**
     * Lấy IP người dùng an toàn
     */
    private static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
