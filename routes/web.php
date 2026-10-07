<?php

declare(strict_types=1);

use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\ProfileController;
use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\UiKitController;
use App\Controllers\Admin\UserController;
use App\Controllers\CaptchaController;
use App\Controllers\Portal\AuthController as PortalAuthController;
use App\Controllers\Portal\HomeController;
use App\Core\Response;
use App\Core\Router;

// ============================================================================
// 1. TUYẾN ĐƯỜNG CHUNG & CAPTCHA
// ============================================================================
Router::get('/', [HomeController::class, 'index'])->name('home');
Router::get('/ping', [HomeController::class, 'ping'])->name('ping');
Router::get('/captcha', [CaptchaController::class, 'render'])->name('captcha');

// ============================================================================
// 2. CỔNG XÁC THỰC CÔNG DÂN & DOANH NGHIỆP (PORTAL AUTH)
// ============================================================================
Router::get('/dang-nhap', [PortalAuthController::class, 'showLogin'])->name('portal.login');
Router::post('/dang-nhap-doanh-nghiep', [PortalAuthController::class, 'loginDoanhNghiep'])->name('portal.login_doanh_nghiep');
Router::get('/dang-xuat', [PortalAuthController::class, 'logout'])->name('portal.logout');

// Luồng SSO VNeID Sandbox (OIDC Authorization Code Flow)
Router::get('/auth/vneid/login', [PortalAuthController::class, 'vneidLogin'])->name('portal.vneid_login');
Router::get('/auth/vneid/sandbox', [PortalAuthController::class, 'vneidSandbox'])->name('portal.vneid_sandbox');
Router::post('/auth/vneid/consent', [PortalAuthController::class, 'vneidConsent'])->name('portal.vneid_consent');
Router::get('/auth/vneid/callback', [PortalAuthController::class, 'vneidCallback'])->name('portal.vneid_callback');

// Khu vực DVC Công dân đã đăng nhập
Router::group(['prefix' => '/cong-dan', 'middleware' => ['security_headers', 'auth:cong_dan']], function () {
    Router::get('/ho-so', function () {
        return Response::html("<h1>Hồ sơ của tôi</h1><p>Chào mừng công dân " . e(auth('cong_dan')['ho_ten'] ?? '') . " đã đăng nhập qua VNeID.</p><p><a href='" . url('/dang-xuat') . "'>Đăng xuất</a></p>");
    })->name('portal.ho_so');
});

// ============================================================================
// 3. KHU VỰC CÁN BỘ CÔNG CHỨC (ADMIN AREA)
// ============================================================================
Router::group(['prefix' => '/admin', 'middleware' => ['security_headers']], function () {
    // Chuyển hướng gốc
    Router::get('/', function () {
        return Response::redirect(url('/admin/dang-nhap'));
    });

    // Cổng đăng nhập & đăng xuất cán bộ
    Router::get('/dang-nhap', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Router::post('/dang-nhap', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Router::get('/dang-xuat', [AdminAuthController::class, 'logout'])->name('admin.logout');

    // Bắt buộc đổi mật khẩu lần đầu
    Router::get('/doi-mat-khau-lan-dau', [AdminAuthController::class, 'showForceChangePassword'])->name('admin.force_change_pwd');
    Router::post('/doi-mat-khau-lan-dau', [AdminAuthController::class, 'forceChangePassword'])->name('admin.force_change_pwd.submit');

    // Tuyến đường yêu cầu đã đăng nhập Cán bộ
    Router::group(['middleware' => ['auth:can_bo']], function () {
        // Bàn làm việc & UI Kit
        Router::get('/dashboard', function () {
            return Response::redirect(url('/admin/ui-kit'));
        })->name('admin.dashboard');

        Router::get('/ui-kit', [UiKitController::class, 'index'])->name('admin.ui_kit');

        // Chuyển đơn vị làm việc kiêm nhiệm
        Router::get('/chuyen-don-vi/{id:\d+}', [AdminAuthController::class, 'switchDonVi'])->name('admin.switch_don_vi');

        // Hồ sơ cá nhân cán bộ
        Router::get('/ho-so-ca-nhan', [ProfileController::class, 'index'])->name('admin.profile');
        Router::post('/doi-mat-khau', [ProfileController::class, 'changePassword'])->name('admin.change_pwd');

        // PH11: Quản trị người dùng & Tài khoản
        Router::group(['middleware' => ['can:admin.nguoi_dung']], function () {
            Router::get('/he-thong/nguoi-dung', [UserController::class, 'index'])->name('admin.users');
            Router::post('/he-thong/nguoi-dung/{id:\d+}/mo-khoa', [UserController::class, 'unlock'])->name('admin.users.unlock');
            Router::post('/he-thong/nguoi-dung/{id:\d+}/trang-thai', [UserController::class, 'toggleStatus'])->name('admin.users.toggle_status');
            Router::post('/he-thong/nguoi-dung/{id:\d+}/reset-mat-khau', [UserController::class, 'resetPassword'])->name('admin.users.reset_pwd');

            Router::get('/he-thong/cong-dan', [UserController::class, 'citizens'])->name('admin.citizens');
            Router::post('/he-thong/cong-dan/{id:\d+}/trang-thai', [UserController::class, 'toggleCitizenStatus'])->name('admin.citizens.toggle_status');
        });

        // PH11: Quản trị Vai trò & Ma trận quyền RBAC
        Router::group(['middleware' => ['can:admin.vai_tro_quyen']], function () {
            Router::get('/he-thong/vai-tro', [RoleController::class, 'matrix'])->name('admin.roles.matrix');
            Router::post('/he-thong/vai-tro/ma-tran', [RoleController::class, 'updateMatrix'])->name('admin.roles.update_matrix');
        });
    });
});
