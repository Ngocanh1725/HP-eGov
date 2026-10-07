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
 * ProfileController – Hồ sơ cá nhân cán bộ (Đổi mật khẩu, xem lịch sử đăng nhập)
 */
class ProfileController extends Controller
{
    /**
     * Xem thông tin cá nhân và lịch sử đăng nhập (GET /admin/ho-so-ca-nhan)
     */
    public function index(Request $request): Response
    {
        $userId = AuthService::id();
        $db = Database::connection();

        // 1. Lấy thông tin cán bộ
        $stmt = $db->prepare("SELECT u.*, dv.ten_don_vi, cd.ten_chuc_danh 
                              FROM nguoi_dung u 
                              LEFT JOIN don_vi dv ON u.don_vi_chinh_id = dv.id 
                              LEFT JOIN chuc_danh cd ON u.chuc_danh_id = cd.id 
                              WHERE u.id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Lấy 20 lần đăng nhập gần nhất của tài khoản này
        $logStmt = $db->prepare("SELECT * FROM nhat_ky_dang_nhap 
                                 WHERE ten_dang_nhap = :u 
                                 ORDER BY id DESC LIMIT 20");
        $logStmt->execute([':u' => $user['ten_dang_nhap']]);
        $loginHistory = $logStmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/profile/index', [
            'pageTitle' => 'Hồ sơ cá nhân & Lịch sử đăng nhập | HP-eGov',
            'user' => $user,
            'loginHistory' => $loginHistory,
            'roles' => AuthService::roles(),
            'currentDonVi' => AuthService::currentDonVi(),
            'donViList' => AuthService::donViList(),
            'errors' => Session::getFlash('errors', []),
        ]);
    }

    /**
     * Xử lý đổi mật khẩu tự nguyện (POST /admin/doi-mat-khau)
     */
    public function changePassword(Request $request): Response
    {
        $userId = AuthService::id();
        $db = Database::connection();

        $matKhauCu = (string)$request->input('mat_khau_cu', '');
        $matKhauMoi = (string)$request->input('mat_khau_moi', '');
        $xacNhan = (string)$request->input('xac_nhan_mat_khau', '');

        $errors = [];

        // 1. Kiểm tra mật khẩu cũ
        $stmt = $db->prepare("SELECT mat_khau, ten_dang_nhap FROM nguoi_dung WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($matKhauCu, $user['mat_khau'])) {
            $errors['mat_khau_cu'] = 'Mật khẩu hiện tại không chính xác.';
        }

        // 2. Kiểm tra chính sách mật khẩu mới
        if (strlen($matKhauMoi) < 10) {
            $errors['mat_khau_moi'] = 'Mật khẩu mới phải có ít nhất 10 ký tự.';
        } elseif (!preg_match('/[A-Z]/', $matKhauMoi)) {
            $errors['mat_khau_moi'] = 'Mật khẩu mới phải chứa ít nhất một chữ hoa (A-Z).';
        } elseif (!preg_match('/[a-z]/', $matKhauMoi)) {
            $errors['mat_khau_moi'] = 'Mật khẩu mới phải chứa ít nhất một chữ thường (a-z).';
        } elseif (!preg_match('/[0-9]/', $matKhauMoi)) {
            $errors['mat_khau_moi'] = 'Mật khẩu mới phải chứa ít nhất một số (0-9).';
        } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>_\-]/', $matKhauMoi)) {
            $errors['mat_khau_moi'] = 'Mật khẩu mới phải chứa ít nhất một ký tự đặc biệt.';
        }

        if ($matKhauMoi !== $xacNhan) {
            $errors['xac_nhan_mat_khau'] = 'Xác nhận mật khẩu mới không trùng khớp.';
        }

        if (!empty($errors)) {
            Session::flash('errors', $errors);
            return $this->redirect('/admin/ho-so-ca-nhan');
        }

        // 3. Cập nhật mật khẩu
        $newHash = password_hash($matKhauMoi, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare("UPDATE nguoi_dung SET mat_khau = :mk WHERE id = :id")->execute([
            ':mk' => $newHash,
            ':id' => $userId,
        ]);

        AuditService::log(
            $userId,
            'DOI_MAT_KHAU_CA_NHAN',
            'nguoi_dung',
            $userId,
            null,
            "Cán bộ {$user['ten_dang_nhap']} đã chủ động thay đổi mật khẩu định kỳ"
        );

        Session::flash('success', 'Đổi mật khẩu thành công! Vui lòng ghi nhớ mật khẩu mới.');
        return $this->redirect('/admin/ho-so-ca-nhan');
    }
}
