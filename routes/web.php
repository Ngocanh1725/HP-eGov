<?php

declare(strict_types=1);

use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\BaoCaoController;
use App\Controllers\Admin\CategoryController;
use App\Controllers\Admin\ConfigController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\DonViController;
use App\Controllers\Admin\DvhcController;
use App\Controllers\Admin\EformController;
use App\Controllers\Admin\MotCuaController;
use App\Controllers\Admin\PheDuyetController;
use App\Controllers\Admin\ProfileController;
use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\SlaController;
use App\Controllers\Admin\ThongBaoController;
use App\Controllers\Admin\ThuLyController;
use App\Controllers\Admin\TthcController;
use App\Controllers\Admin\UiKitController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\VanThuController;
use App\Controllers\Admin\WorkflowController;
use App\Controllers\CaptchaController;
use App\Controllers\Portal\AuthController as PortalAuthController;
use App\Controllers\Portal\DvcController;
use App\Controllers\Portal\HelpController;
use App\Controllers\Portal\HomeController;
use App\Controllers\Portal\HoSoCaNhanController;
use App\Controllers\Portal\KiemTraChuKyController;
use App\Controllers\Portal\NopHoSoController;
use App\Controllers\Portal\NotificationPortalController;
use App\Controllers\Portal\TraCuuController;
use App\Core\Response;
use App\Core\Router;

// ============================================================================
// 1. TUYẾN ĐƯỜNG CHUNG & CAPTCHA
// ============================================================================
Router::get('/', [HomeController::class, 'index'])->name('home');
Router::get('/ping', [HomeController::class, 'ping'])->name('ping');
Router::get('/captcha', [CaptchaController::class, 'render'])->name('captcha');

// Tuyến đường công khai DVC, Tra cứu & Trợ giúp
Router::get('/dvc/thu-tuc', [DvcController::class, 'index'])->name('portal.dvc');
Router::get('/thu-tuc-hanh-chinh', [DvcController::class, 'index']);
Router::get('/dvc/thu-tuc/{ma}', [DvcController::class, 'detail'])->name('portal.dvc.detail');

Router::get('/tra-cuu', [TraCuuController::class, 'index'])->name('portal.tra_cuu');
Router::get('/kiem-tra-chu-ky', [KiemTraChuKyController::class, 'index'])->name('portal.kiem_tra_chu_ky');
Router::post('/kiem-tra-chu-ky/verify-file', [KiemTraChuKyController::class, 'verifyFile'])->name('portal.kiem_tra_chu_ky.verify_file');
Router::get('/tro-giup', [HelpController::class, 'index'])->name('portal.help');
Router::get('/ho-tro', [HelpController::class, 'index']);
Router::get('/nop-ho-so', function () {
    return Response::redirect(url('/dvc/thu-tuc'));
});

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

// ============================================================================
// 3. KHU VỰC DỊCH VỤ CÔNG & HỒ SƠ CỦA CÔNG DÂN (YÊU CẦU ĐĂNG NHẬP)
// ============================================================================
Router::group(['middleware' => ['security_headers', 'auth:cong_dan']], function () {
    // Wizard 4 bước nộp hồ sơ trực tuyến
    Router::get('/dvc/nop-ho-so/{tthc_id:\d+}', [NopHoSoController::class, 'wizard'])->name('portal.nop_ho_so');
    Router::post('/dvc/nop-ho-so/{tthc_id:\d+}', [NopHoSoController::class, 'submit'])->name('portal.nop_ho_so.submit');
    Router::get('/dvc/nop-ho-so/thanh-cong/{ma_ho_so}', [NopHoSoController::class, 'success'])->name('portal.nop_ho_so.success');

    // Quản lý hồ sơ công dân & Kho dữ liệu
    Router::get('/cong-dan/ho-so', [HoSoCaNhanController::class, 'index'])->name('portal.ho_so');
    Router::get('/cong-dan/ho-so/{ma_ho_so}', [HoSoCaNhanController::class, 'show'])->name('portal.ho_so.detail');
    Router::get('/cong-dan/ho-so/{ma_ho_so}/bo-sung', [HoSoCaNhanController::class, 'boSungForm'])->name('portal.ho_so.bo_sung');
    Router::post('/cong-dan/ho-so/{ma_ho_so}/bo-sung', [HoSoCaNhanController::class, 'submitBoSung'])->name('portal.ho_so.bo_sung.submit');
    
    // Kho dữ liệu điện tử cá nhân / doanh nghiệp
    Router::get('/cong-dan/kho-du-lieu', [HoSoCaNhanController::class, 'khoDuLieu'])->name('portal.kho_du_lieu');

    // Khảo sát hài lòng SIPAS 5 tiêu chí
    Router::get('/cong-dan/ho-so/{ma_ho_so}/danh-gia-sipas', [HoSoCaNhanController::class, 'sipasForm'])->name('portal.sipas');
    Router::post('/cong-dan/ho-so/{ma_ho_so}/danh-gia-sipas', [HoSoCaNhanController::class, 'submitSipas'])->name('portal.sipas.submit');

    // Trung tâm thông báo
    Router::get('/cong-dan/thong-bao', [NotificationPortalController::class, 'index'])->name('portal.notifications');
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
        // PH08: Bàn làm việc & Trung tâm IOC Hải Phòng
        Router::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
        Router::get('/ioc', [DashboardController::class, 'ioc'])->name('admin.ioc');
        Router::get('/api/ioc-stats', [DashboardController::class, 'apiIocStats'])->name('admin.api.ioc_stats');
        Router::get('/api/ioc-district/{id:\d+}', [DashboardController::class, 'apiDistrictDetail'])->name('admin.api.ioc_district');

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

        // ====================================================================
        // PH02: Bộ phận Một cửa (CB_MOT_CUA)
        // ====================================================================
        Router::get('/mot-cua', [MotCuaController::class, 'dashboard'])->name('admin.motcua.dashboard');
        Router::get('/mot-cua/dashboard', [MotCuaController::class, 'dashboard']);

        // Tiếp nhận trực tiếp & Quét QR CCCD
        Router::get('/mot-cua/tiep-nhan', [MotCuaController::class, 'tiepNhanForm'])->name('admin.motcua.tiepNhanForm');
        Router::post('/mot-cua/api/verify-cccd', [MotCuaController::class, 'apiVerifyCccd'])->name('admin.motcua.verifyCccd');
        Router::post('/mot-cua/tiep-nhan', [MotCuaController::class, 'submitTiepNhan'])->name('admin.motcua.submitTiepNhan');
        Router::get('/mot-cua/in-giay-hen/{id:\d+}', [MotCuaController::class, 'inGiayHen'])->name('admin.motcua.inGiayHen');

        // Hồ sơ trực tuyến chờ tiếp nhận
        Router::get('/mot-cua/truc-tuyen', [MotCuaController::class, 'danhSachTrucTuyen'])->name('admin.motcua.hoSoTrucTuyen');
        Router::get('/mot-cua/ho-so-truc-tuyen', [MotCuaController::class, 'danhSachTrucTuyen']);
        Router::get('/mot-cua/truc-tuyen/{id:\d+}', [MotCuaController::class, 'chiTietTrucTuyen'])->name('admin.motcua.chiTietTrucTuyen');
        Router::post('/mot-cua/truc-tuyen/{id:\d+}/tiep-nhan', [MotCuaController::class, 'tiepNhanTrucTuyen'])->name('admin.motcua.tiepNhanTrucTuyen');
        Router::post('/mot-cua/truc-tuyen/{id:\d+}/yeu-cau-bo-sung', [MotCuaController::class, 'yeuCauBoSung'])->name('admin.motcua.yeuCauBoSung');
        Router::get('/mot-cua/truc-tuyen/{id:\d+}/in-phieu-bo-sung', [MotCuaController::class, 'inPhieuBoSung'])->name('admin.motcua.inPhieuBoSung');
        Router::post('/mot-cua/truc-tuyen/{id:\d+}/tu-choi', [MotCuaController::class, 'tuChoiTiepNhan'])->name('admin.motcua.tuChoiTiepNhan');
        Router::get('/mot-cua/truc-tuyen/{id:\d+}/in-phieu-tu-choi', [MotCuaController::class, 'inPhieuTuChoi'])->name('admin.motcua.inPhieuTuChoi');

        // Trả kết quả TTHC & Bưu chính VNPost
        Router::get('/mot-cua/tra-ket-qua', [MotCuaController::class, 'traKetQua'])->name('admin.motcua.traKetQua');
        Router::post('/mot-cua/tra-ket-qua', [MotCuaController::class, 'submitTraKetQua'])->name('admin.motcua.submitTraKetQua');
        Router::post('/mot-cua/de-xuat-huy', [MotCuaController::class, 'deXuatHuy'])->name('admin.motcua.deXuatHuy');

        // Sổ theo dõi hồ sơ Mẫu 06 TT 01/2018
        Router::get('/mot-cua/so-theo-doi', [MotCuaController::class, 'soTheoDoi'])->name('admin.motcua.soTheoDoi');
        Router::get('/mot-cua/so-theo-doi/export', [MotCuaController::class, 'exportSoTheoDoi'])->name('admin.motcua.exportSoTheoDoi');

        // ====================================================================
        // PH03: Phân hệ Thụ lý & Phối hợp Liên thông (CHUYEN_VIEN, LD_PHONG)
        // ====================================================================
        Router::get('/thu-ly', [ThuLyController::class, 'inbox'])->name('admin.thuly.inbox');
        Router::get('/thu-ly/danh-sach', [ThuLyController::class, 'inbox']);
        Router::get('/thu-ly/lien-thong', function () {
            return Response::redirect(url('/admin/thu-ly', ['tab' => 'phoi_hop']));
        });
        Router::get('/thu-ly/{id:\d+}', [ThuLyController::class, 'detail'])->name('admin.thuly.detail');
        Router::post('/thu-ly/{id:\d+}/phan-cong', [ThuLyController::class, 'phanCong'])->name('admin.thuly.phan_cong');
        Router::post('/thu-ly/{id:\d+}/chuyen-tiep', [ThuLyController::class, 'chuyenTiep'])->name('admin.thuly.chuyen_tiep');
        Router::post('/thu-ly/{id:\d+}/tra-lai', [ThuLyController::class, 'traLai'])->name('admin.thuly.tra_lai');
        Router::post('/thu-ly/{id:\d+}/yeu-cau-bo-sung', [ThuLyController::class, 'yeuCauBoSung'])->name('admin.thuly.yeu_cau_bo_sung');
        Router::post('/thu-ly/{id:\d+}/tiep-tuc-bo-sung', [ThuLyController::class, 'tiepTucSauBoSung'])->name('admin.thuly.tiep_tuc_bo_sung');
        Router::post('/thu-ly/{id:\d+}/lay-y-kien', [ThuLyController::class, 'layYKien'])->name('admin.thuly.lay_y_kien');
        Router::post('/thu-ly/y-kien/{id:\d+}/tra-loi', [ThuLyController::class, 'traLoiYKien'])->name('admin.thuly.tra_loi_y_kien');
        Router::post('/thu-ly/{id:\d+}/du-thao', [ThuLyController::class, 'luuDuThao'])->name('admin.thuly.luu_du_thao');
        Router::post('/thu-ly/{id:\d+}/thu-hoi', [ThuLyController::class, 'thuHoi'])->name('admin.thuly.thu_hoi');
        Router::post('/thu-ly/api/verify-lgsp', [ThuLyController::class, 'apiVerifyLgsp'])->name('admin.thuly.verify_lgsp');
        Router::post('/thu-ly/api/lgsp-verify', [ThuLyController::class, 'apiVerifyLgsp']);

        // ====================================================================
        // PH04: Phê duyệt & Ký số điện tử (LD_PHONG, LD_CO_QUAN)
        // ====================================================================
        Router::get('/phe-duyet/phong', [PheDuyetController::class, 'indexPhong'])->name('admin.phe_duyet.phong');
        Router::get('/phe-duyet/phong/{id:\d+}', [PheDuyetController::class, 'chiTietPhong'])->name('admin.phe_duyet.chi_tiet_phong');
        Router::post('/phe-duyet/phong/{id:\d+}/ky-so', [PheDuyetController::class, 'kySoPhong'])->name('admin.phe_duyet.ky_so_phong');
        Router::post('/phe-duyet/phong/{id:\d+}/tra-lai', [PheDuyetController::class, 'traLaiPhong'])->name('admin.phe_duyet.tra_lai_phong');

        Router::get('/phe-duyet/co-quan', [PheDuyetController::class, 'indexCoQuan'])->name('admin.phe_duyet.co_quan');
        Router::get('/phe-duyet/co-quan/{id:\d+}', [PheDuyetController::class, 'chiTietCoQuan'])->name('admin.phe_duyet.chi_tiet_co_quan');
        Router::post('/phe-duyet/co-quan/{id:\d+}/ky-so', [PheDuyetController::class, 'kySoCoQuan'])->name('admin.phe_duyet.ky_so_co_quan');
        Router::post('/phe-duyet/co-quan/batch-sign', [PheDuyetController::class, 'batchSignAction'])->name('admin.phe_duyet.batch_sign');
        Router::get('/phe-duyet/cho-huy', [PheDuyetController::class, 'danhSachChoHuy'])->name('admin.phe_duyet.cho_huy');
        Router::post('/phe-duyet/cho-huy/{id:\d+}/duyet', [PheDuyetController::class, 'duyetHuy'])->name('admin.phe_duyet.duyet_huy');

        // ====================================================================
        // PH05: Quản trị Văn thư số & Phát hành (VAN_THU)
        // ====================================================================
        Router::get('/van-thu', [VanThuController::class, 'index'])->name('admin.van_thu.index');
        Router::get('/van-thu/dong-dau/{id:\d+}', [VanThuController::class, 'dongDau'])->name('admin.van_thu.dong_dau');
        Router::post('/van-thu/dong-dau/{id:\d+}', [VanThuController::class, 'xuLyDongDau'])->name('admin.van_thu.xu_ly_dong_dau');
        Router::get('/van-thu/so-van-ban', [VanThuController::class, 'soVanBan'])->name('admin.van_thu.so_van_ban');
        Router::post('/van-thu/so-van-ban/tao', [VanThuController::class, 'taoSoVanBan'])->name('admin.van_thu.tao_so_van_ban');
        Router::get('/van-thu/cong-van', [VanThuController::class, 'congVan'])->name('admin.van_thu.cong_van');
        Router::get('/van-thu/cong-van/xuat-csv/{id:\d+}', [VanThuController::class, 'xuatCsv'])->name('admin.van_thu.xuat_csv');

        // ====================================================================
        // PH06: Giám sát SLA & Văn bản xin lỗi Mẫu 04 (TT 01/2018/TT-VPCP)
        // ====================================================================
        Router::get('/sla/danh-sach-qua-han', [SlaController::class, 'danhSachQuaHan'])->name('admin.sla.qua_han');
        Router::get('/sla/van-ban-xin-loi/{id:\d+}', [SlaController::class, 'formMau04'])->name('admin.sla.form_mau_04');
        Router::post('/sla/ky-so-mau-04/{id:\d+}', [SlaController::class, 'kySoMau04'])->name('admin.sla.ky_so_mau_04');
        Router::get('/sla/in-mau-04/{id:\d+}', [SlaController::class, 'inMau04'])->name('admin.sla.in_mau_04');

        // ====================================================================
        // PH07: Quản trị Mẫu Thông báo Đa kênh & Hộp thư mô phỏng
        // ====================================================================
        Router::get('/he-thong/mau-thong-bao', [ThongBaoController::class, 'mauThongBao'])->name('admin.mau_thong_bao');
        Router::post('/he-thong/mau-thong-bao/{id:\d+}', [ThongBaoController::class, 'updateMauThongBao'])->name('admin.mau_thong_bao.update');
        Router::get('/he-thong/hop-thu-mo-phong', [ThongBaoController::class, 'hopThuMoPhong'])->name('admin.hop_thu_mo_phong');

        // ====================================================================
        // PH08: Phân hệ Báo cáo Thống kê Hành chính Động (TT 01/2018, NĐ 107/2021)
        // ====================================================================
        Router::get('/bao-cao', [BaoCaoController::class, 'index'])->name('admin.bao_cao.index');
        Router::get('/bao-cao/thong-tu-01', [BaoCaoController::class, 'thongTu01'])->name('admin.bao_cao.thong_tu_01');
        Router::get('/bao-cao/so-hoa', [BaoCaoController::class, 'soHoa'])->name('admin.bao_cao.so_hoa');
        Router::get('/bao-cao/qua-han', [BaoCaoController::class, 'quaHan'])->name('admin.bao_cao.qua_han');
        Router::get('/bao-cao/hai-long', [BaoCaoController::class, 'haiLong'])->name('admin.bao_cao.hai_long');
        Router::get('/bao-cao/truc-tuyen', [BaoCaoController::class, 'trucTuyen'])->name('admin.bao_cao.truc_tuyen');
        Router::get('/bao-cao/lien-thong', [BaoCaoController::class, 'lienThong'])->name('admin.bao_cao.lien_thong');
    });
});
