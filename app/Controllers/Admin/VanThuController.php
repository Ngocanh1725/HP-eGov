<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\VanThuService;
use PDO;

/**
 * Class VanThuController – Phân hệ Quản trị Văn thư số & Phát hành (PH05)
 * Phục vụ vai trò: VAN_THU (Quản lý sổ văn bản, Cấp số tự động, Đóng dấu số cơ quan, Xuất CSV)
 */
class VanThuController extends Controller
{
    /**
     * Danh sách hồ sơ chờ Văn thư đóng dấu số cơ quan
     */
    public function index(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $roles = AuthService::userRoles($user['id']);
        if (!in_array('VAN_THU', $roles, true) && !in_array('SYSADMIN', $roles, true)) {
            Session::flash('error', 'Bạn không có quyền truy cập phân hệ Văn thư số.');
            return $this->redirect('/admin/thu-ly');
        }

        $db = Database::connection();
        $donViId = (int)($user['don_vi_hien_tai_id'] ?? $user['don_vi_chinh_id'] ?? 0);
        $q = trim((string)$request->get('q', ''));

        // Lọc các hồ sơ đang ở bước DONG_DAU hoặc đã có chữ ký PHAT_HANH của Lãnh đạo
        $sql = "SELECT h.*, t.ten_tthc, d.ten_don_vi, qb.ten_buoc,
                       u.ho_ten as ten_chuyen_vien,
                       (SELECT c.thoi_gian_ky FROM chu_ky_so c WHERE c.ho_so_tai_lieu_id IN 
                           (SELECT id FROM ho_so_tai_lieu WHERE ho_so_id = h.id)
                           AND c.loai_chu_ky = 'PHAT_HANH' ORDER BY c.id DESC LIMIT 1) as thoi_gian_ld_ky
                FROM ho_so h
                JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
                JOIN don_vi d ON h.don_vi_tiep_nhan_id = d.id
                LEFT JOIN quy_trinh_buoc qb ON h.buoc_hien_tai_id = qb.id
                LEFT JOIN nguoi_dung u ON h.chuyen_vien_thu_ly_id = u.id
                WHERE h.is_deleted = 0
                  AND h.trang_thai = 'DANG_XU_LY'
                  AND (qb.loai_buoc = 'DONG_DAU' OR qb.vai_tro_xu_ly = 'VAN_THU')";

        $params = [];
        if (!empty($q)) {
            $sql .= " AND (h.ma_ho_so LIKE :q OR h.nguoi_nop_ho_ten LIKE :q OR t.ten_tthc LIKE :q)";
            $params[':q'] = "%{$q}%";
        }

        $sql .= " ORDER BY h.ngay_hen_tra ASC LIMIT 50";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $hoSoList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/van_thu/index', [
            'user'     => $user,
            'hoSoList' => $hoSoList,
            'q'        => $q,
            'title'    => 'Văn thư số - Danh sách hồ sơ chờ đóng dấu phát hành',
        ]);
    }

    /**
     * Màn hình đóng dấu số cơ quan cho hồ sơ
     */
    public function dongDau(Request $request, int $id): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $db = Database::connection();
        $stmt = $db->prepare("SELECT h.*, t.ten_tthc, t.ma_tthc, d.ten_don_vi, qb.ten_buoc,
                                     u.ho_ten as ten_chuyen_vien
                              FROM ho_so h
                              JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
                              JOIN don_vi d ON h.don_vi_tiep_nhan_id = d.id
                              LEFT JOIN quy_trinh_buoc qb ON h.buoc_hien_tai_id = qb.id
                              LEFT JOIN nguoi_dung u ON h.chuyen_vien_thu_ly_id = u.id
                              WHERE h.id = :id AND h.is_deleted = 0");
        $stmt->execute([':id' => $id]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            Session::flash('error', 'Hồ sơ không tồn tại.');
            return $this->redirect('/admin/van-thu');
        }

        // Danh sách sổ văn bản đi của đơn vị
        $donViId = (int)$hoSo['don_vi_tiep_nhan_id'];
        $soVanBanList = VanThuService::getDanhSachSoVanBan($donViId, (int)date('Y'), 'DI');

        // Lấy danh sách chữ ký số đã có trên hồ sơ
        $sigStmt = $db->prepare("SELECT c.*, cts.ten_chu_the, cts.so_serial, cts.nha_cung_cap
                                 FROM chu_ky_so c
                                 JOIN chung_thu_so cts ON c.chung_thu_so_id = cts.id
                                 WHERE c.ho_so_tai_lieu_id IN (SELECT id FROM ho_so_tai_lieu WHERE ho_so_id = :hid)
                                 ORDER BY c.id ASC");
        $sigStmt->execute([':hid' => $id]);
        $chuKyList = $sigStmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/van_thu/dong_dau', [
            'user'         => $user,
            'hoSo'         => $hoSo,
            'soVanBanList' => $soVanBanList,
            'chuKyList'    => $chuKyList,
            'title'        => "Đóng dấu phát hành hồ sơ {$hoSo['ma_ho_so']}",
        ]);
    }

    /**
     * Xử lý đóng dấu số cơ quan & hoàn tất hồ sơ
     */
    public function xuLyDongDau(Request $request, int $id): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $soVanBanId = (int)$request->post('so_van_ban_id', 0);
        $trichYeu = trim((string)$request->post('trich_yeu', ''));
        $nguoiKy = trim((string)$request->post('nguoi_ky', 'Lãnh đạo cơ quan'));

        if ($soVanBanId <= 0) {
            Session::flash('error', 'Vui lòng chọn sổ văn bản để vào số phát hành.');
            return $this->redirect("/admin/van-thu/dong-dau/{$id}");
        }

        try {
            $result = VanThuService::dongDauVaHoanThanh($id, (int)$user['id'], $soVanBanId, [
                'trich_yeu' => $trichYeu,
                'nguoi_ky'  => $nguoiKy,
            ]);

            Session::flash('success', "Đã cấp số và đóng dấu số cơ quan thành công: Số {$result['so_ky_hieu']}. Hồ sơ đã chuyển sang trạng thái HOÀN THÀNH và đồng bộ vào Kho số hóa công dân.");
            return $this->redirect('/admin/van-thu');
        } catch (\Throwable $e) {
            Session::flash('error', "Đóng dấu số thất bại: " . $e->getMessage());
            return $this->redirect("/admin/van-thu/dong-dau/{$id}");
        }
    }

    /**
     * Quản lý danh mục sổ văn bản đi/đến
     */
    public function soVanBan(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $donViId = (int)($user['don_vi_hien_tai_id'] ?? $user['don_vi_chinh_id'] ?? 1);
        $nam = (int)$request->get('nam', (int)date('Y'));
        $loaiSo = $request->get('loai_so', null);

        $soVanBanList = VanThuService::getDanhSachSoVanBan($donViId, $nam, $loaiSo ? (string)$loaiSo : null);

        return $this->view('admin/van_thu/so_van_ban', [
            'user'         => $user,
            'soVanBanList' => $soVanBanList,
            'nam'          => $nam,
            'loaiSo'       => $loaiSo,
            'title'        => 'Quản lý sổ văn bản điện tử',
        ]);
    }

    /**
     * Tạo mới sổ văn bản
     */
    public function taoSoVanBan(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $donViId = (int)($user['don_vi_hien_tai_id'] ?? $user['don_vi_chinh_id'] ?? 1);
        $tenSo = trim((string)$request->post('ten_so', ''));
        $loaiSo = (string)$request->post('loai_so', 'DI');
        $nam = (int)$request->post('nam', (int)date('Y'));
        $kyHieuMau = trim((string)$request->post('ky_hieu_mau', '/QĐ-UBND'));
        $soHienTai = (int)$request->post('so_hien_tai', 0);

        if (empty($tenSo)) {
            Session::flash('error', 'Vui lòng nhập tên sổ văn bản.');
            return $this->redirect('/admin/van-thu/so-van-ban');
        }

        try {
            VanThuService::taoSoVanBan([
                'don_vi_id'   => $donViId,
                'nam'         => $nam,
                'ten_so'      => $tenSo,
                'loai_so'     => $loaiSo,
                'ky_hieu_mau' => $kyHieuMau,
                'so_hien_tai' => $soHienTai,
            ]);

            Session::flash('success', "Đã tạo sổ văn bản '{$tenSo}' thành công.");
            return $this->redirect('/admin/van-thu/so-van-ban');
        } catch (\Throwable $e) {
            Session::flash('error', "Lỗi tạo sổ văn bản: " . $e->getMessage());
            return $this->redirect('/admin/van-thu/so-van-ban');
        }
    }

    /**
     * Quản lý công văn đến/đi gắn với hồ sơ
     */
    public function congVan(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $db = Database::connection();
        $donViId = (int)($user['don_vi_hien_tai_id'] ?? $user['don_vi_chinh_id'] ?? 1);
        $soId = (int)$request->get('so_id', 0);
        $q = trim((string)$request->get('q', ''));

        // Lấy danh sách sổ để làm bộ lọc
        $soList = VanThuService::getDanhSachSoVanBan($donViId);

        $sql = "SELECT vb.*, s.ten_so, s.loai_so, h.ma_ho_so
                FROM van_ban_den_di vb
                JOIN so_van_ban s ON vb.so_van_ban_id = s.id
                LEFT JOIN ho_so h ON vb.ho_so_id = h.id
                WHERE s.don_vi_id = :dvid";

        $params = [':dvid' => $donViId];
        if ($soId > 0) {
            $sql .= " AND vb.so_van_ban_id = :soid";
            $params[':soid'] = $soId;
        }
        if (!empty($q)) {
            $sql .= " AND (vb.so_ky_hieu LIKE :q OR vb.trich_yeu LIKE :q OR h.ma_ho_so LIKE :q)";
            $params[':q'] = "%{$q}%";
        }

        $sql .= " ORDER BY vb.ngay_vao_so DESC, vb.id DESC LIMIT 100";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $congVanList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/van_thu/cong_van', [
            'user'        => $user,
            'congVanList' => $congVanList,
            'soList'      => $soList,
            'soId'        => $soId,
            'q'           => $q,
            'title'       => 'Theo dõi công văn đến và đi',
        ]);
    }

    /**
     * Xuất CSV danh sách công văn theo sổ văn bản (UTF-8 BOM)
     */
    public function xuatCsv(Request $request, int $soId): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return Response::json(['error' => 'Từ chối truy cập'], 403);
        }

        $csv = VanThuService::xuatCsvSoVanBan($soId);
        $filename = "so_van_ban_{$soId}_" . date('Ymd_His') . ".csv";

        return Response::downloadContent($csv, $filename, 'text/csv; charset=utf-8');
    }
}
