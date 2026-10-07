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
use App\Services\CaptchaService;

/**
 * AuthController (Admin) – Xử lý Đăng nhập, Đổi mật khẩu bắt buộc và Đăng xuất cho Cán bộ
 */
class AuthController extends Controller
{
    /**
     * Hiển thị biểu mẫu đăng nhập Cán bộ (/admin/dang-nhap)
     */
    public function showLogin(Request $request): Response
    {
        if (AuthService::check(AuthService::GUARD_CAN_BO)) {
            if (AuthService::mustChangePassword()) {
                return $this->redirect('/admin/doi-mat-khau-lan-dau');
            }
            return $this->redirect('/admin/dashboard');
        }

        $requireCaptcha = (bool)Session::get('admin_login_require_captcha', false);

        return $this->view('admin/auth/login', [
            'pageTitle' => 'Đăng nhập Cán bộ | HP-eGov Hải Phòng',
            'requireCaptcha' => $requireCaptcha,
            'errors' => Session::getFlash('errors', []),
            'old' => Session::getFlash('old', []),
        ]);
    }

    /**
     * Xử lý đăng nhập cán bộ (POST /admin/dang-nhap)
     */
    public function login(Request $request): Response
    {
        $username = (string)$request->input('ten_dang_nhap', '');
        $password = (string)$request->input('mat_khau', '');
        $captcha = (string)$request->input('captcha_code', '');

        if (empty(trim($username)) || empty(trim($password))) {
            Session::flash('errors', ['ten_dang_nhap' => 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.']);
            Session::flash('old', ['ten_dang_nhap' => $username]);
            return $this->redirect('/admin/dang-nhap');
        }

        $result = AuthService::attemptCanBo($username, $password, $captcha);

        if (!$result['success']) {
            if ($result['require_captcha']) {
                Session::set('admin_login_require_captcha', true);
            }
            Session::flash('error', $result['message']);
            Session::flash('old', ['ten_dang_nhap' => $username]);
            return $this->redirect('/admin/dang-nhap');
        }

        // Đăng nhập thành công -> Xóa cờ captcha
        Session::remove('admin_login_require_captcha');

        if ($result['must_change_pw']) {
            Session::flash('warning', 'Đây là lần đầu đăng nhập hoặc tài khoản vừa được cấp lại. Vui lòng đổi mật khẩu để tiếp tục.');
            return $this->redirect('/admin/doi-mat-khau-lan-dau');
        }

        Session::flash('success', 'Đăng nhập thành công. Chào mừng đồng chí ' . ($result['user']['ho_ten'] ?? ''));
        return $this->redirect('/admin/dashboard');
    }

    /**
     * Hiển thị biểu mẫu bắt buộc đổi mật khẩu lần đầu (/admin/doi-mat-khau-lan-dau)
     */
    public function showForceChangePassword(Request $request): Response
    {
        if (!AuthService::check(AuthService::GUARD_CAN_BO)) {
            return $this->redirect('/admin/dang-nhap');
        }

        if (!AuthService::mustChangePassword()) {
            return $this->redirect('/admin/dashboard');
        }

        return $this->view('admin/auth/force_change_password', [
            'pageTitle' => 'Đổi mật khẩu lần đầu | HP-eGov Hải Phòng',
            'errors' => Session::getFlash('errors', []),
        ]);
    }

    /**
     * Xử lý đổi mật khẩu lần đầu (POST /admin/doi-mat-khau-lan-dau)
     */
    public function forceChangePassword(Request $request): Response
    {
        if (!AuthService::check(AuthService::GUARD_CAN_BO)) {
            return $this->redirect('/admin/dang-nhap');
        }

        $matKhauMoi = (string)$request->input('mat_khau_moi', '');
        $xacNhan = (string)$request->input('xac_nhan_mat_khau', '');

        $errors = [];

        // Kiểm tra chính sách mật khẩu: ≥ 10 ký tự, có hoa, thường, số, ký tự đặc biệt
        if (strlen($matKhauMoi) < 10) {
            $errors['mat_khau_moi'] = 'Mật khẩu phải có tối thiểu 10 ký tự.';
        } elseif (!preg_match('/[A-Z]/', $matKhauMoi)) {
            $errors['mat_khau_moi'] = 'Mật khẩu phải chứa ít nhất một chữ cái in hoa (A-Z).';
        } elseif (!preg_match('/[a-z]/', $matKhauMoi)) {
            $errors['mat_khau_moi'] = 'Mật khẩu phải chứa ít nhất một chữ cái in thường (a-z).';
        } elseif (!preg_match('/[0-9]/', $matKhauMoi)) {
            $errors['mat_khau_moi'] = 'Mật khẩu phải chứa ít nhất một chữ số (0-9).';
        } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>_\-]/', $matKhauMoi)) {
            $errors['mat_khau_moi'] = 'Mật khẩu phải chứa ít nhất một ký tự đặc biệt (!@#$%^&*...).';
        }

        if ($matKhauMoi !== $xacNhan) {
            $errors['xac_nhan_mat_khau'] = 'Xác nhận mật khẩu mới không trùng khớp.';
        }

        if (!empty($errors)) {
            Session::flash('errors', $errors);
            return $this->redirect('/admin/doi-mat-khau-lan-dau');
        }

        $userId = AuthService::id();
        $hashNew = password_hash($matKhauMoi, PASSWORD_BCRYPT, ['cost' => 12]);

        $db = Database::connection();
        $db->prepare("UPDATE nguoi_dung SET mat_khau = :mk, yeu_cau_doi_mk = 0 WHERE id = :id")->execute([
            ':mk' => $hashNew,
            ':id' => $userId,
        ]);

        AuthService::markPasswordChanged();

        AuditService::log(
            $userId,
            'DOI_MAT_KHAU_LAN_DAU',
            'nguoi_dung',
            $userId,
            null,
            "Cán bộ hoàn tất đổi mật khẩu lần đầu tuân thủ chính sách"
        );

        Session::flash('success', 'Đổi mật khẩu thành công! Mời đồng chí tiếp tục làm việc.');
        return $this->redirect('/admin/dashboard');
    }

    /**
     * Chuyển đổi đơn vị công tác cho cán bộ kiêm nhiệm (GET /admin/chuyen-don-vi/{id})
     */
    public function switchDonVi(Request $request): Response
    {
        if (!AuthService::check(AuthService::GUARD_CAN_BO)) {
            return $this->redirect('/admin/dang-nhap');
        }

        $id = (int)$request->routeParam('id', 0);
        if ($id > 0 && AuthService::switchDonVi($id)) {
            $current = AuthService::currentDonVi();
            AuditService::log(
                AuthService::id(),
                'CHUYEN_DON_VI_KIEM_NHIEM',
                'don_vi',
                $id,
                null,
                "Chuyển ngữ cảnh công tác sang đơn vị: " . ($current['ten_don_vi'] ?? (string)$id)
            );
            Session::flash('success', 'Đã chuyển sang làm việc tại: ' . ($current['ten_don_vi'] ?? ''));
        } else {
            Session::flash('error', 'Không tìm thấy đơn vị công tác được phân công hoặc không có thẩm quyền.');
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/admin/dashboard';
        return $this->redirect($referer);
    }

    /**
     * Đăng xuất cán bộ (GET /admin/dang-xuat)
     */
    public function logout(Request $request): Response
    {
        AuthService::logout();
        Session::flash('info', 'Đã đăng xuất an toàn khỏi hệ thống.');
        return $this->redirect('/admin/dang-nhap');
    }
}
