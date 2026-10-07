<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\ReportingService;
use PDO;

/**
 * Class DashboardController – Phân hệ Dashboard chỉ đạo điều hành & IOC (PH08)
 * Phục vụ các vai trò: CHUYEN_VIEN, LD_PHONG, LD_CO_QUAN, Lãnh đạo TP Hải Phòng (IOC)
 */
class DashboardController extends Controller
{
    /**
     * Bàn làm việc thông minh phân luồng theo vai trò người dùng
     */
    public function index(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $roles = AuthService::userRoles($user['id']);
        $donViId = (int)($user['don_vi_hien_tai_id'] ?? $user['don_vi_chinh_id'] ?? 0);
        $donVi = AuthService::currentDonVi();
        $isTpNode = ($donVi['loai_don_vi'] ?? '') === 'UBND_TP';

        // Cho phép chuyển đổi tab hiển thị nếu có tham số view
        $viewType = $request->get('view');

        // 1. Nếu là Lãnh đạo UBND Thành phố hoặc có cờ xem IOC
        if ($viewType === 'ioc' || ($isTpNode && in_array('LD_CO_QUAN', $roles, true))) {
            return $this->redirect('/admin/ioc');
        }

        // 2. Nếu là Lãnh đạo cơ quan (Sở / UBND Quận/Huyện)
        if ($viewType === 'co_quan' || in_array('LD_CO_QUAN', $roles, true)) {
            $data = ReportingService::getStatsLdCoQuan($donViId);
            return $this->view('admin/dashboard/ld_co_quan', array_merge($data, [
                'title' => 'Dashboard Lãnh đạo Cơ quan',
                'user' => $user,
                'don_vi' => $donVi,
                'roles' => $roles,
            ]));
        }

        // 3. Nếu là Lãnh đạo phòng
        if ($viewType === 'phong' || in_array('LD_PHONG', $roles, true)) {
            $data = ReportingService::getStatsLdPhong($donViId);
            return $this->view('admin/dashboard/ld_phong', array_merge($data, [
                'title' => 'Dashboard Lãnh đạo Phòng',
                'user' => $user,
                'don_vi' => $donVi,
                'roles' => $roles,
            ]));
        }

        // 4. Mặc định là Chuyên viên thụ lý
        $data = ReportingService::getStatsChuyenVien((int)$user['id'], $donViId);
        return $this->view('admin/dashboard/chuyen_vien', array_merge($data, [
            'title' => 'Bàn làm việc Chuyên viên',
            'user' => $user,
            'don_vi' => $donVi,
            'roles' => $roles,
        ]));
    }

    /**
     * Màn hình Trung tâm Chỉ đạo & Điều hành Thông minh (IOC) Hải Phòng
     * 4 Thẻ KPI (§2), Heatmap, Bản đồ số SVG 15 quận/huyện, Bảng xếp hạng thi đua, Bottleneck Detector
     */
    public function ioc(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $forceRefresh = $request->get('refresh') === '1' || $request->get('nocache') === '1';
        $data = ReportingService::getStatsIoc($forceRefresh);

        // Danh sách đơn vị để hỗ trợ tra cứu nhanh
        $db = Database::connection();
        $donViList = $db->query("SELECT id, ten_don_vi, loai_don_vi FROM don_vi WHERE loai_don_vi IN ('SO_NGANH', 'UBND_HUYEN') ORDER BY loai_don_vi, id")->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/dashboard/ioc', array_merge($data, [
            'title' => 'Trung tâm IOC – Chỉ đạo Điều hành Dịch vụ công TP. Hải Phòng',
            'user' => $user,
            'don_vi_list' => $donViList,
            'is_fullscreen' => $request->get('tv') === '1',
        ]));
    }

    /**
     * API JSON trả về số liệu IOC (hỗ trợ Auto-refresh 60 giây và TV Wall không nhấp nháy)
     */
    public function apiIocStats(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $forceRefresh = $request->get('refresh') === '1';
        $data = ReportingService::getStatsIoc($forceRefresh);

        return $this->json($data);
    }

    /**
     * API JSON chi tiết một quận/huyện khi click trên bản đồ SVG
     */
    public function apiDistrictDetail(Request $request, int $id): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $db = Database::connection();
        $stmt = $db->prepare("SELECT * FROM v_ty_le_dung_han_don_vi WHERE don_vi_id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $this->json(['error' => 'Không tìm thấy đơn vị'], 404);
        }

        // Lấy top 5 hồ sơ đang quá hạn của đơn vị
        $stmtHs = $db->prepare("SELECT id, ma_ho_so, nguoi_nop_ho_ten, ngay_hen_tra, sla_mau 
                                FROM ho_so 
                                WHERE don_vi_tiep_nhan_id = :id AND is_deleted = 0 AND sla_mau = 'DO'
                                LIMIT 5");
        $stmtHs->execute([':id' => $id]);
        $overdueSamples = $stmtHs->fetchAll(PDO::FETCH_ASSOC);

        return $this->json([
            'unit' => $row,
            'overdue_samples' => $overdueSamples,
        ]);
    }
}
