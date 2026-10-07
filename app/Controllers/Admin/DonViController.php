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
use App\Services\MdmService;
use PDO;

/**
 * DonViController – Quản lý Cây tổ chức Đơn vị / Phòng ban / Một cửa
 * Tự động gợi ý mã định danh cơ quan nhà nước theo chuẩn QCVN 102:2016/BTTTT
 */
class DonViController extends Controller
{
    /**
     * Danh sách cây đơn vị (GET /admin/danh-muc/don-vi)
     */
    public function index(Request $request): Response
    {
        $db = Database::connection();
        $q = trim((string)$request->query('q', ''));
        $loai = $request->query('loai', '');

        $sql = "SELECT dv.*, p.ten_don_vi AS ten_cha, dvhc.ten_dvhc 
                FROM don_vi dv
                LEFT JOIN don_vi p ON dv.parent_id = p.id
                LEFT JOIN dm_dvhc dvhc ON dv.dvhc_id = dvhc.id
                WHERE dv.is_deleted = 0";
        $params = [];

        if (!empty($q)) {
            $sql .= " AND (dv.ma_dinh_danh LIKE :q OR dv.ten_don_vi LIKE :q OR dv.ten_viet_tat LIKE :q)";
            $params[':q'] = "%{$q}%";
        }
        if (!empty($loai)) {
            $sql .= " AND dv.loai_don_vi = :loai";
            $params[':loai'] = $loai;
        }

        $sql .= " ORDER BY dv.cap_do ASC, dv.thu_tu ASC, dv.id ASC LIMIT 200";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $donViList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Danh sách đơn vị cha để lựa chọn
        $parents = $db->query("SELECT id, ten_don_vi, ma_dinh_danh, loai_don_vi FROM don_vi WHERE is_deleted = 0 AND loai_don_vi IN ('UBND_TP', 'SO_NGANH', 'UBND_HUYEN') ORDER BY ten_don_vi ASC")->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/don_vi/index', [
            'pageTitle' => 'Cây Tổ chức Đơn vị & Phòng ban | HP-eGov',
            'donViList' => $donViList,
            'parents' => $parents,
            'filters' => ['q' => $q, 'loai' => $loai],
        ]);
    }

    /**
     * Tự động gợi ý Mã định danh QCVN 102 (GET /admin/danh-muc/don-vi/goi-y-ma?parent_id=...)
     */
    public function suggestCode(Request $request): Response
    {
        $parentId = (int)$request->query('parent_id', 0);
        $loai = (string)$request->query('loai', 'PHONG_BAN');
        $db = Database::connection();

        $prefix = '000.04.15.H21'; // Mặc định cấp Thành phố Hải Phòng
        if ($parentId > 0) {
            $parent = $db->query("SELECT ma_dinh_danh FROM don_vi WHERE id = {$parentId}")->fetch(PDO::FETCH_ASSOC);
            if (!empty($parent['ma_dinh_danh'])) {
                $prefix = $parent['ma_dinh_danh'];
            }
        }

        // Đếm số đơn vị con hiện có để cấp số tiếp theo
        $stmt = $db->prepare("SELECT COUNT(*) FROM don_vi WHERE parent_id = :pid");
        $stmt->execute([':pid' => $parentId ?: null]);
        $count = (int)$stmt->fetchColumn() + 1;

        $suffix = str_pad((string)$count, 2, '0', STR_PAD_LEFT);
        $suggestedCode = "{$prefix}.{$suffix}";

        return $this->json(['suggested_code' => $suggestedCode, 'prefix' => $prefix]);
    }

    /**
     * Thêm mới đơn vị (POST /admin/danh-muc/don-vi)
     */
    public function store(Request $request): Response
    {
        $ma = trim((string)$request->input('ma_dinh_danh', ''));
        $ten = trim((string)$request->input('ten_don_vi', ''));
        $loai = (string)$request->input('loai_don_vi', 'PHONG_BAN');
        $parentId = (int)$request->input('parent_id', 0) ?: null;
        $dvhcId = (int)$request->input('dvhc_id', 1) ?: 1;
        $diaChi = trim((string)$request->input('dia_chi', ''));

        if (empty($ma) || empty($ten)) {
            Session::flash('error', 'Vui lòng nhập Mã định danh và Tên cơ quan.');
            return $this->redirect('/admin/danh-muc/don-vi');
        }

        $db = Database::connection();

        // Tính toán path
        $path = $ma;
        $capDo = 1;
        if ($parentId) {
            $p = $db->query("SELECT path, cap_do FROM don_vi WHERE id = {$parentId}")->fetch(PDO::FETCH_ASSOC);
            if ($p) {
                $path = ($p['path'] ?? '') . '/' . $ma;
                $capDo = ((int)$p['cap_do']) + 1;
            }
        }

        $stmt = $db->prepare("INSERT INTO don_vi (ma_dinh_danh, ten_don_vi, loai_don_vi, parent_id, dvhc_id, path, cap_do, dia_chi, trang_thai) 
                              VALUES (:ma, :ten, :loai, :pid, :dvhc, :path, :cap, :dc, 1)");
        $stmt->execute([
            ':ma' => $ma,
            ':ten' => $ten,
            ':loai' => $loai,
            ':pid' => $parentId,
            ':dvhc' => $dvhcId,
            ':path' => $path,
            ':cap' => $capDo,
            ':dc' => $diaChi,
        ]);

        $id = (int)$db->lastInsertId();
        AuditService::log(AuthService::id(), 'THEM_DON_VI', 'don_vi', $id, null, ['ma' => $ma, 'ten' => $ten]);

        Session::flash('success', "Thêm cơ quan/đơn vị {$ten} thành công.");
        return $this->redirect('/admin/danh-muc/don-vi');
    }

    /**
     * Bật / Tắt trạng thái (POST /admin/danh-muc/don-vi/{id}/trang-thai)
     */
    public function toggleStatus(Request $request): Response
    {
        $id = (int)$request->routeParam('id', 0);
        $db = Database::connection();

        $stmt = $db->prepare("SELECT id, ten_don_vi, trang_thai FROM don_vi WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            Session::flash('error', 'Không tìm thấy cơ quan đơn vị.');
            return $this->redirect('/admin/danh-muc/don-vi');
        }

        $newSt = ((int)$row['trang_thai'] === 1) ? 0 : 1;
        $db->prepare("UPDATE don_vi SET trang_thai = :st WHERE id = :id")->execute([':st' => $newSt, ':id' => $id]);

        AuditService::log(AuthService::id(), 'DOI_TRANG_THAI_DON_VI', 'don_vi', $id, ['trang_thai' => $row['trang_thai']], ['trang_thai' => $newSt]);

        $msg = $newSt === 1 ? "Đã kích hoạt cơ quan {$row['ten_don_vi']}." : "Đã ngừng hiệu lực cơ quan {$row['ten_don_vi']}.";
        Session::flash('success', $msg);
        return $this->redirect('/admin/danh-muc/don-vi');
    }

    /**
     * Xuất CSV cây tổ chức (GET /admin/danh-muc/don-vi/export)
     */
    public function export(Request $request): Response
    {
        $db = Database::connection();
        $rows = $db->query("SELECT dv.ma_dinh_danh, dv.ten_don_vi, dv.loai_don_vi, COALESCE(p.ten_don_vi, 'UBND TP Hải Phòng') as don_vi_cha, dv.cap_do, COALESCE(dv.dia_chi, '') as dia_chi, CASE WHEN dv.trang_thai = 1 THEN 'Hoạt động' ELSE 'Ngừng' END as trang_thai 
                            FROM don_vi dv LEFT JOIN don_vi p ON dv.parent_id = p.id WHERE dv.is_deleted = 0 ORDER BY dv.cap_do ASC, dv.id ASC")->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Mã định danh (QCVN 102)', 'Tên cơ quan/đơn vị', 'Loại hình', 'Cơ quan cấp trên', 'Cấp bậc', 'Địa chỉ trụ sở', 'Trạng thái'];
        return MdmService::exportCsv('cay_to_chuc_co_quan_haiphong.csv', $headers, $rows);
    }
}
