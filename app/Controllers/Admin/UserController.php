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
use PDO;

/**
 * UserController – Phân hệ PH11 Quản trị người dùng & Tài khoản cán bộ / công dân
 * Chỉ dành cho người dùng có quyền admin.nguoi_dung
 */
class UserController extends Controller
{
    /**
     * Danh sách cán bộ công chức (GET /admin/he-thong/nguoi-dung)
     */
    public function index(Request $request): Response
    {
        $db = Database::connection();
        $q = trim((string)$request->query('q', ''));
        $donViId = (int)$request->query('don_vi_id', 0);
        $status = $request->query('status', '');

        $sql = "SELECT u.*, dv.ten_don_vi, cd.ten_chuc_danh,
                GROUP_CONCAT(DISTINCT v.ten_vai_tro SEPARATOR ', ') AS ds_vai_tro
                FROM nguoi_dung u
                LEFT JOIN don_vi dv ON u.don_vi_chinh_id = dv.id
                LEFT JOIN chuc_danh cd ON u.chuc_danh_id = cd.id
                LEFT JOIN nguoi_dung_vai_tro ndvt ON u.id = ndvt.nguoi_dung_id
                LEFT JOIN vai_tro v ON ndvt.vai_tro_id = v.id
                WHERE u.is_deleted = 0";

        $params = [];
        if (!empty($q)) {
            $sql .= " AND (u.ten_dang_nhap LIKE :q OR u.ho_ten LIKE :q OR u.email LIKE :q OR u.so_cccd LIKE :q)";
            $params[':q'] = "%{$q}%";
        }
        if ($donViId > 0) {
            $sql .= " AND u.don_vi_chinh_id = :dvid";
            $params[':dvid'] = $donViId;
        }
        if ($status !== '' && is_numeric($status)) {
            $sql .= " AND u.trang_thai = :st";
            $params[':st'] = (int)$status;
        }

        $sql .= " GROUP BY u.id ORDER BY u.id ASC LIMIT 100";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy danh sách đơn vị để lọc
        $donViList = $db->query("SELECT id, ten_don_vi FROM don_vi WHERE is_deleted = 0 ORDER BY ten_don_vi ASC")->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/users/index', [
            'pageTitle' => 'Quản trị Cán bộ & Tài khoản | HP-eGov',
            'users' => $users,
            'donViList' => $donViList,
            'filters' => [
                'q' => $q,
                'don_vi_id' => $donViId,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Mở khóa tài khoản bị khóa do 5 lần sai (POST /admin/he-thong/nguoi-dung/{id}/mo-khoa)
     */
    public function unlock(Request $request): Response
    {
        $id = (int)$request->routeParam('id', 0);
        $db = Database::connection();

        $stmt = $db->prepare("SELECT * FROM nguoi_dung WHERE id = :id AND is_deleted = 0");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Session::flash('error', 'Không tìm thấy tài khoản cán bộ.');
            return $this->redirect('/admin/he-thong/nguoi-dung');
        }

        $oldData = ['so_lan_sai' => $user['so_lan_sai'], 'khoa_den' => $user['khoa_den']];
        $db->prepare("UPDATE nguoi_dung SET so_lan_sai = 0, khoa_den = NULL WHERE id = :id")->execute([':id' => $id]);
        $newData = ['so_lan_sai' => 0, 'khoa_den' => null];

        AuditService::log(
            AuthService::id(),
            'MO_KHOA_CAN_BO',
            'nguoi_dung',
            $id,
            $oldData,
            $newData
        );

        Session::flash('success', "Đã mở khóa đăng nhập thành công cho tài khoản {$user['ten_dang_nhap']}.");
        return $this->redirect('/admin/he-thong/nguoi-dung');
    }

    /**
     * Khóa / Vô hiệu hóa hoặc Kích hoạt tài khoản (POST /admin/he-thong/nguoi-dung/{id}/trang-thai)
     */
    public function toggleStatus(Request $request): Response
    {
        $id = (int)$request->routeParam('id', 0);
        if ($id === AuthService::id()) {
            Session::flash('error', 'Không thể tự vô hiệu hóa tài khoản của chính mình.');
            return $this->redirect('/admin/he-thong/nguoi-dung');
        }

        $db = Database::connection();
        $stmt = $db->prepare("SELECT id, ten_dang_nhap, trang_thai FROM nguoi_dung WHERE id = :id AND is_deleted = 0");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Session::flash('error', 'Không tìm thấy cán bộ.');
            return $this->redirect('/admin/he-thong/nguoi-dung');
        }

        $newStatus = ((int)$user['trang_thai'] === 1) ? 0 : 1;
        $db->prepare("UPDATE nguoi_dung SET trang_thai = :st WHERE id = :id")->execute([
            ':st' => $newStatus,
            ':id' => $id,
        ]);

        AuditService::log(
            AuthService::id(),
            'DOI_TRANG_THAI_CAN_BO',
            'nguoi_dung',
            $id,
            ['trang_thai' => $user['trang_thai']],
            ['trang_thai' => $newStatus]
        );

        $msg = $newStatus === 1 ? "Đã kích hoạt lại tài khoản {$user['ten_dang_nhap']}." : "Đã vô hiệu hóa tài khoản {$user['ten_dang_nhap']}.";
        Session::flash('success', $msg);
        return $this->redirect('/admin/he-thong/nguoi-dung');
    }

    /**
     * Đặt lại mật khẩu về mặc định (POST /admin/he-thong/nguoi-dung/{id}/reset-mat-khau)
     */
    public function resetPassword(Request $request): Response
    {
        $id = (int)$request->routeParam('id', 0);
        $db = Database::connection();

        $stmt = $db->prepare("SELECT id, ten_dang_nhap FROM nguoi_dung WHERE id = :id AND is_deleted = 0");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Session::flash('error', 'Không tìm thấy cán bộ.');
            return $this->redirect('/admin/he-thong/nguoi-dung');
        }

        $defaultHash = password_hash('Hpegov@2026', PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare("UPDATE nguoi_dung SET mat_khau = :mk, so_lan_sai = 0, khoa_den = NULL, yeu_cau_doi_mk = 1 WHERE id = :id")->execute([
            ':mk' => $defaultHash,
            ':id' => $id,
        ]);

        AuditService::log(
            AuthService::id(),
            'RESET_MAT_KHAU_CAN_BO',
            'nguoi_dung',
            $id,
            null,
            "Reset mật khẩu về mặc định Hpegov@2026 và yêu cầu đổi mật khẩu lần đầu"
        );

        Session::flash('success', "Đã đặt lại mật khẩu của {$user['ten_dang_nhap']} về 'Hpegov@2026' và bật cờ bắt đổi mật khẩu lần đầu.");
        return $this->redirect('/admin/he-thong/nguoi-dung');
    }

    /**
     * Danh sách tài khoản công dân & doanh nghiệp (GET /admin/he-thong/cong-dan)
     */
    public function citizens(Request $request): Response
    {
        $db = Database::connection();
        $q = trim((string)$request->query('q', ''));
        $type = $request->query('type', '');

        $sql = "SELECT * FROM cong_dan WHERE 1=1";
        $params = [];
        if (!empty($q)) {
            $sql .= " AND (so_cccd LIKE :q OR ma_so_thue LIKE :q OR ho_ten LIKE :q OR ten_doanh_nghiep LIKE :q OR so_dien_thoai LIKE :q)";
            $params[':q'] = "%{$q}%";
        }
        if (!empty($type)) {
            $sql .= " AND loai_chu_the = :tp";
            $params[':tp'] = $type;
        }

        $sql .= " ORDER BY id DESC LIMIT 100";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $citizens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/users/citizens', [
            'pageTitle' => 'Danh sách Tài khoản Công dân & Doanh nghiệp | HP-eGov',
            'citizens' => $citizens,
            'filters' => ['q' => $q, 'type' => $type],
        ]);
    }

    /**
     * Khóa / Mở khóa tài khoản công dân (POST /admin/he-thong/cong-dan/{id}/trang-thai)
     */
    public function toggleCitizenStatus(Request $request): Response
    {
        $id = (int)$request->routeParam('id', 0);
        $db = Database::connection();

        $stmt = $db->prepare("SELECT id, ho_ten, trang_thai FROM cong_dan WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$c) {
            Session::flash('error', 'Không tìm thấy tài khoản công dân.');
            return $this->redirect('/admin/he-thong/cong-dan');
        }

        $newSt = ((int)$c['trang_thai'] === 1) ? 0 : 1;
        $db->prepare("UPDATE cong_dan SET trang_thai = :st WHERE id = :id")->execute([':st' => $newSt, ':id' => $id]);

        AuditService::log(
            AuthService::id(),
            'DOI_TRANG_THAI_CONG_DAN',
            'cong_dan',
            $id,
            ['trang_thai' => $c['trang_thai']],
            ['trang_thai' => $newSt],
            $id
        );

        $msg = $newSt === 1 ? "Đã mở khóa tài khoản {$c['ho_ten']}." : "Đã khóa tài khoản {$c['ho_ten']}.";
        Session::flash('success', $msg);
        return $this->redirect('/admin/he-thong/cong-dan');
    }
}
