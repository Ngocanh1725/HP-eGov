<?php

declare(strict_types=1);

use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\CategoryController;
use App\Controllers\Admin\ConfigController;
use App\Controllers\Admin\DonViController;
use App\Controllers\Admin\DvhcController;
use App\Controllers\Admin\EformController;
use App\Controllers\Admin\ProfileController;
use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\TthcController;
use App\Controllers\Admin\UiKitController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\WorkflowController;
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

        // ====================================================================
        // PH09: Quản trị Danh mục dùng chung (MDM) (Quyền admin.danh_muc)
        // ====================================================================
        Router::group(['middleware' => ['can:admin.danh_muc']], function () {
            // 1. Đơn vị Hành chính (ĐVHC 3 cấp, sáp nhập/chia tách)
            Router::get('/danh-muc/dvhc', [DvhcController::class, 'index'])->name('admin.dvhc');
            Router::post('/danh-muc/dvhc', [DvhcController::class, 'store'])->name('admin.dvhc.store');
            Router::post('/danh-muc/dvhc/sap-nhap', [DvhcController::class, 'merge'])->name('admin.dvhc.merge');
            Router::post('/danh-muc/dvhc/{id:\d+}/ngung-hieu-luc', [DvhcController::class, 'toggleActive'])->name('admin.dvhc.toggle');
            Router::get('/danh-muc/dvhc/export', [DvhcController::class, 'export'])->name('admin.dvhc.export');

            // 2. Cây tổ chức Đơn vị / Phòng ban (QCVN 102)
            Router::get('/danh-muc/don-vi', [DonViController::class, 'index'])->name('admin.don_vi');
            Router::post('/danh-muc/don-vi', [DonViController::class, 'store'])->name('admin.don_vi.store');
            Router::post('/danh-muc/don-vi/{id:\d+}/ngung-hieu-luc', [DonViController::class, 'toggleActive'])->name('admin.don_vi.toggle');
            Router::get('/danh-muc/don-vi/suggest-code', [DonViController::class, 'suggestCode'])->name('admin.don_vi.suggest_code');
            Router::get('/danh-muc/don-vi/export', [DonViController::class, 'export'])->name('admin.don_vi.export');

            // 3. Lĩnh vực & Chức danh
            Router::get('/danh-muc/linh-vuc', [CategoryController::class, 'linhVuc'])->name('admin.linh_vuc');
            Router::post('/danh-muc/linh-vuc', [CategoryController::class, 'storeLinhVuc'])->name('admin.linh_vuc.store');
            Router::post('/danh-muc/linh-vuc/{id:\d+}/ngung-hieu-luc', [CategoryController::class, 'toggleLinhVuc'])->name('admin.linh_vuc.toggle');
            Router::get('/danh-muc/linh-vuc/export', [CategoryController::class, 'exportLinhVuc'])->name('admin.linh_vuc.export');

            Router::get('/danh-muc/chuc-danh', [CategoryController::class, 'chucDanh'])->name('admin.chuc_danh');
            Router::post('/danh-muc/chuc-danh', [CategoryController::class, 'storeChucDanh'])->name('admin.chuc_danh.store');
            Router::post('/danh-muc/chuc-danh/{id:\d+}/ngung-hieu-luc', [CategoryController::class, 'toggleChucDanh'])->name('admin.chuc_danh.toggle');
            Router::get('/danh-muc/chuc-danh/export', [CategoryController::class, 'exportChucDanh'])->name('admin.chuc_danh.export');

            // 4. Thủ tục Hành chính (TTHC) & Đồng bộ CSDL TTHC Quốc gia
            Router::get('/danh-muc/tthc', [TthcController::class, 'index'])->name('admin.tthc');
            Router::get('/danh-muc/tthc/dong-bo', [TthcController::class, 'syncPreview'])->name('admin.tthc.sync_preview');
            Router::post('/danh-muc/tthc/dong-bo/ap-dung', [TthcController::class, 'applySync'])->name('admin.tthc.apply_sync');
            Router::get('/danh-muc/tthc/export', [TthcController::class, 'export'])->name('admin.tthc.export');

            // 5. Trình thiết kế E-Form
            Router::get('/danh-muc/eform', [EformController::class, 'index'])->name('admin.eform');
            Router::post('/danh-muc/eform/luu', [EformController::class, 'save'])->name('admin.eform.save');

            // 6. Ngày nghỉ lễ, Giờ làm việc, Tham số hệ thống & Trạng thái TT 01/2018
            Router::get('/danh-muc/ngay-nghi', [ConfigController::class, 'holidays'])->name('admin.holidays');
            Router::post('/danh-muc/ngay-nghi', [ConfigController::class, 'storeHoliday'])->name('admin.holidays.store');
            Router::post('/danh-muc/ngay-nghi/nhan-ban', [ConfigController::class, 'cloneHolidays'])->name('admin.holidays.clone');

            Router::get('/danh-muc/cau-hinh', [ConfigController::class, 'parameters'])->name('admin.parameters');
            Router::post('/danh-muc/cau-hinh/{id:\d+}', [ConfigController::class, 'updateParameter'])->name('admin.parameters.update');

            Router::get('/danh-muc/trang-thai', [ConfigController::class, 'statuses'])->name('admin.statuses');
        });

        // ====================================================================
        // PH10: Quản trị Quy trình xử lý BPMN-lite (Quyền admin.quy_trinh)
        // ====================================================================
        Router::group(['middleware' => ['can:admin.quy_trinh']], function () {
            Router::get('/quy-trinh', [WorkflowController::class, 'index'])->name('admin.workflow');
            Router::get('/quy-trinh/{id:\d+}', [WorkflowController::class, 'detail'])->name('admin.workflow.detail');
            Router::post('/quy-trinh/{id:\d+}/nhan-ban-phien-ban', [WorkflowController::class, 'createVersion'])->name('admin.workflow.clone_version');
            Router::post('/quy-trinh/{id:\d+}/buoc', [WorkflowController::class, 'addStep'])->name('admin.workflow.add_step');
            Router::post('/quy-trinh/{id:\d+}/chuyen-tiep', [WorkflowController::class, 'addTransition'])->name('admin.workflow.add_transition');
        });
    });
});
