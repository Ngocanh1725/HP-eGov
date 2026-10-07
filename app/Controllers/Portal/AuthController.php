<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\AuthService;

/**
 * AuthController (Portal) – Xác thực Công dân (VNeID SSO Mock) & Doanh nghiệp (MST)
 */
class AuthController extends Controller
{
    /**
     * Hiển thị trang đăng nhập dành cho Công dân và Doanh nghiệp (GET /dang-nhap)
     */
    public function showLogin(Request $request): Response
    {
        if (AuthService::check(AuthService::GUARD_CONG_DAN)) {
            return $this->redirect('/cong-dan/ho-so');
        }

        return $this->view('portal/auth/login', [
            'pageTitle' => 'Đăng nhập Cổng DVC Hải Phòng | VNeID & Doanh nghiệp',
            'errors' => Session::getFlash('errors', []),
            'old' => Session::getFlash('old', []),
        ]);
    }

    /**
     * Bắt đầu luồng VNeID OIDC Authorization Code (GET /auth/vneid/login)
     */
    public function vneidLogin(Request $request): Response
    {
        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));

        Session::set('vneid_oauth_state', $state);
        Session::set('vneid_oauth_nonce', $nonce);

        $params = http_build_query([
            'client_id' => 'hp_egov_portal',
            'response_type' => 'code',
            'scope' => 'openid profile id_card',
            'redirect_uri' => url('/auth/vneid/callback'),
            'state' => $state,
            'nonce' => $nonce,
        ]);

        return $this->redirect('/auth/vneid/sandbox?' . $params);
    }

    /**
     * Màn hình VNeID Sandbox mô phỏng C06 Bộ Công an (GET /auth/vneid/sandbox)
     */
    public function vneidSandbox(Request $request): Response
    {
        $state = (string)$request->query('state', '');
        $storedState = Session::get('vneid_oauth_state');

        if (empty($state) || $state !== $storedState) {
            return new Response('403 Forbidden: Trạng thái phiên xác thực VNeID không hợp lệ hoặc đã hết hạn.', 403);
        }

        // Danh sách công dân mẫu phục vụ kiểm thử tiện lợi
        $mockCitizens = [
            [
                'so_cccd' => '031090001234',
                'ho_ten' => 'Nguyễn Văn An',
                'ngay_sinh' => '1990-05-15',
                'gioi_tinh' => 'NAM',
                'que_quan' => 'Phường Hoàng Văn Thụ, Quận Hồng Bàng, Hải Phòng',
                'dia_chi_thuong_tru' => 'Số 12 phố Đinh Tiên Hoàng, Phường Hoàng Văn Thụ, Quận Hồng Bàng, Hải Phòng',
                'so_dien_thoai' => '0912345678',
                'email' => 'nguyenvanan.hp@gmail.com',
            ],
            [
                'so_cccd' => '031195005678',
                'ho_ten' => 'Trần Thị Mai',
                'ngay_sinh' => '1995-10-20',
                'gioi_tinh' => 'NU',
                'que_quan' => 'Phường Lạc Viên, Quận Ngô Quyền, Hải Phòng',
                'dia_chi_thuong_tru' => 'Số 45 đường Lê Lợi, Phường Lạc Viên, Quận Ngô Quyền, Hải Phòng',
                'so_dien_thoai' => '0987654321',
                'email' => 'tranmai.hp95@gmail.com',
            ],
            [
                'so_cccd' => '031088009999',
                'ho_ten' => 'Lê Hoàng Nam',
                'ngay_sinh' => '1988-03-08',
                'gioi_tinh' => 'NAM',
                'que_quan' => 'Xã Thủy Đường, Huyện Thủy Nguyên, Hải Phòng',
                'dia_chi_thuong_tru' => 'Thôn Đông, Xã Thủy Đường, Huyện Thủy Nguyên, Hải Phòng',
                'so_dien_thoai' => '0903222888',
                'email' => 'namle.thuynguyen@gmail.com',
            ],
        ];

        return $this->view('portal/auth/vneid_sandbox', [
            'pageTitle' => 'VNeID Sandbox – Cổng Định danh Điện tử Quốc gia',
            'state' => $state,
            'nonce' => Session::get('vneid_oauth_nonce', ''),
            'mockCitizens' => $mockCitizens,
        ]);
    }

    /**
     * Xử lý chấp thuận ủy quyền từ VNeID Sandbox (POST /auth/vneid/consent)
     */
    public function vneidConsent(Request $request): Response
    {
        $state = (string)$request->input('state', '');
        $storedState = Session::get('vneid_oauth_state');

        if (empty($state) || $state !== $storedState) {
            AuditService::log(null, 'CSRF_ATTACK_VNEID', 'vneid_oauth', null, null, "Sai lệch state token trong luồng VNeID");
            return new Response('403 Forbidden: Token bảo vệ chống CSRF không khớp.', 403);
        }

        $cccd = (string)$request->input('so_cccd', '');
        $hoTen = (string)$request->input('ho_ten', '');
        $ngaySinh = (string)$request->input('ngay_sinh', '');
        $gioiTinh = (string)$request->input('gioi_tinh', 'NAM');
        $queQuan = (string)$request->input('que_quan', '');
        $diaChi = (string)$request->input('dia_chi_thuong_tru', '');
        $sdt = (string)$request->input('so_dien_thoai', '');
        $email = (string)$request->input('email', '');

        if (empty(trim($cccd)) || strlen(trim($cccd)) < 9) {
            Session::flash('error', 'Vui lòng cung cấp Số CCCD hợp lệ.');
            return $this->redirect('/auth/vneid/sandbox?state=' . urlencode($state));
        }

        // Tạo mã authorization_code tạm thời
        $authCode = 'vneid_auth_code_' . bin2hex(random_bytes(16));

        // Lưu profile tương ứng với authCode trong session
        $profile = [
            'so_cccd' => trim($cccd),
            'vneid_sub' => 'c06_vneid_' . trim($cccd),
            'ho_ten' => trim($hoTen) ?: 'Công dân Hải Phòng',
            'ngay_sinh' => trim($ngaySinh) ?: null,
            'gioi_tinh' => in_array($gioiTinh, ['NAM', 'NU', 'KHAC'], true) ? $gioiTinh : 'NAM',
            'que_quan' => trim($queQuan),
            'dia_chi_thuong_tru' => trim($diaChi),
            'so_dien_thoai' => trim($sdt),
            'email' => trim($email),
        ];

        Session::set('vneid_payload_' . $authCode, $profile);

        // Redirect về callback với code và state
        return $this->redirect('/auth/vneid/callback?code=' . urlencode($authCode) . '&state=' . urlencode($state));
    }

    /**
     * Tiếp nhận chuyển tiếp từ VNeID (GET /auth/vneid/callback)
     */
    public function vneidCallback(Request $request): Response
    {
        $state = (string)$request->query('state', '');
        $code = (string)$request->query('code', '');
        $storedState = Session::get('vneid_oauth_state');

        // 1. Kiểm tra CSRF qua state
        if (empty($state) || $state !== $storedState) {
            AuditService::log(null, 'CSRF_DETECTED_VNEID_CALLBACK', 'vneid_oauth', null, null, "Phát hiện giả mạo state tại VNeID Callback");
            Session::flash('error', 'Lỗi xác thực bảo mật VNeID (State Token không hợp lệ).');
            return $this->redirect('/dang-nhap');
        }

        // Xóa state để chống Replay
        Session::remove('vneid_oauth_state');
        Session::remove('vneid_oauth_nonce');

        // 2. Tra cứu payload theo code
        $payloadKey = 'vneid_payload_' . $code;
        $profile = Session::get($payloadKey);
        Session::remove($payloadKey);

        if (empty($profile) || empty($profile['so_cccd'])) {
            Session::flash('error', 'Mã xác thực ủy quyền VNeID đã hết hạn hoặc không tồn tại.');
            return $this->redirect('/dang-nhap');
        }

        // 3. Đăng nhập / đồng bộ thông tin công dân
        $result = AuthService::loginFromVNeID($profile);

        if (!$result['success']) {
            Session::flash('error', $result['message']);
            return $this->redirect('/dang-nhap');
        }

        Session::flash('success', 'Xác thực tài khoản VNeID thành công! Xin chào ' . $profile['ho_ten']);
        return $this->redirect('/cong-dan/ho-so');
    }

    /**
     * Xử lý đăng nhập Doanh nghiệp bằng MST (POST /dang-nhap-doanh-nghiep)
     */
    public function loginDoanhNghiep(Request $request): Response
    {
        $mst = (string)$request->input('ma_so_thue', '');
        $password = (string)$request->input('mat_khau', '');

        if (empty(trim($mst)) || empty(trim($password))) {
            Session::flash('errors', ['ma_so_thue' => 'Vui lòng nhập đầy đủ Mã số thuế và mật khẩu.']);
            Session::flash('old', ['ma_so_thue' => $mst, 'tab' => 'doanh-nghiep']);
            return $this->redirect('/dang-nhap');
        }

        $result = AuthService::attemptDoanhNghiep($mst, $password);

        if (!$result['success']) {
            Session::flash('error', $result['message']);
            Session::flash('old', ['ma_so_thue' => $mst, 'tab' => 'doanh-nghiep']);
            return $this->redirect('/dang-nhap');
        }

        Session::flash('success', 'Doanh nghiệp đăng nhập thành công.');
        return $this->redirect('/cong-dan/ho-so');
    }

    /**
     * Đăng xuất Công dân / Doanh nghiệp (GET /dang-xuat)
     */
    public function logout(Request $request): Response
    {
        AuthService::logout();
        Session::flash('info', 'Quý khách đã đăng xuất an toàn khỏi Cổng Dịch vụ công.');
        return $this->redirect('/');
    }
}
