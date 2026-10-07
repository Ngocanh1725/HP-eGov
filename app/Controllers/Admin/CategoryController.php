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
 * CategoryController – Quản lý Danh mục Chức danh cán bộ & Lĩnh vực TTHC
 */
class CategoryController extends Controller
{
    /**
     * Danh sách Lĩnh vực TTHC (GET /admin/danh-muc/linh-vuc)
     */
    public function linhVuc(Request $request): Response
    {
        $db = Database::connection();
        $q = trim((string)$request->query('q', ''));

        $sql = "SELECT lv.*, COUNT(tt.id) AS so_luong_tthc 
                FROM dm_linh_vuc lv
                LEFT JOIN thu_tuc_hanh_chinh tt ON lv.id = tt.linh_vuc_id
                WHERE 1=1";
        $params = [];
        if (!empty($q)) {
            $sql .= " AND (lv.ma_linh_vuc LIKE :q OR lv.ten_linh_vuc LIKE :q)";
            $params[':q'] = "%{$q}%";
        }
        $sql .= " GROUP BY lv.id ORDER BY lv.id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/categories/linh_vuc', [
            'pageTitle' => 'Danh mục Lĩnh vực TTHC | HP-eGov',
            'items' => $items,
            'q' => $q,
        ]);
    }

    /**
     * Thêm Lĩnh vực mới (POST /admin/danh-muc/linh-vuc)
     */
    public function storeLinhVuc(Request $request): Response
    {
        $ma = trim((string)$request->input('ma_linh_vuc', ''));
        $ten = trim((string)$request->input('ten_linh_vuc', ''));
        $moTa = trim((string)$request->input('mo_ta', ''));

        if (empty($ma) || empty($ten)) {
            Session::flash('error', 'Vui lòng nhập Mã và Tên lĩnh vực.');
            return $this->redirect('/admin/danh-muc/linh-vuc');
        }

        $db = Database::connection();
        $stmt = $db->prepare("INSERT INTO dm_linh_vuc (ma_linh_vuc, ten_linh_vuc, mo_ta, trang_thai) VALUES (:ma, :ten, :mt, 1)");
        $stmt->execute([':ma' => $ma, ':ten' => $ten, ':mt' => $moTa]);

        AuditService::log(AuthService::id(), 'THEM_LINH_VUC', 'dm_linh_vuc', (int)$db->lastInsertId(), null, ['ma' => $ma, 'ten' => $ten]);

        Session::flash('success', "Thêm lĩnh vực {$ten} thành công.");
        return $this->redirect('/admin/danh-muc/linh-vuc');
    }

    /**
     * Danh sách Chức danh cán bộ (GET /admin/danh-muc/chuc-danh)
     */
    public function chucDanh(Request $request): Response
    {
        $db = Database::connection();
        $q = trim((string)$request->query('q', ''));

        $sql = "SELECT cd.*, COUNT(u.id) AS so_can_bo 
                FROM chuc_danh cd
                LEFT JOIN nguoi_dung u ON cd.id = u.chuc_danh_id AND u.is_deleted = 0
                WHERE 1=1";
        $params = [];
        if (!empty($q)) {
            $sql .= " AND (cd.ma_chuc_danh LIKE :q OR cd.ten_chuc_danh LIKE :q)";
            $params[':q'] = "%{$q}%";
        }
        $sql .= " GROUP BY cd.id ORDER BY cd.id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/categories/chuc_danh', [
            'pageTitle' => 'Danh mục Chức danh Cán bộ | HP-eGov',
            'items' => $items,
            'q' => $q,
        ]);
    }

    /**
     * Thêm Chức danh mới (POST /admin/danh-muc/chuc-danh)
     */
    public function storeChucDanh(Request $request): Response
    {
        $ma = trim((string)$request->input('ma_chuc_danh', ''));
        $ten = trim((string)$request->input('ten_chuc_danh', ''));
        $moTa = trim((string)$request->input('mo_ta', ''));

        if (empty($ma) || empty($ten)) {
            Session::flash('error', 'Vui lòng nhập Mã và Tên chức danh.');
            return $this->redirect('/admin/danh-muc/chuc-danh');
        }

        $db = Database::connection();
        $stmt = $db->prepare("INSERT INTO chuc_danh (ma_chuc_danh, ten_chuc_danh, mo_ta, trang_thai) VALUES (:ma, :ten, :mt, 1)");
        $stmt->execute([':ma' => $ma, ':ten' => $ten, ':mt' => $moTa]);

        AuditService::log(AuthService::id(), 'THEM_CHUC_DANH', 'chuc_danh', (int)$db->lastInsertId(), null, ['ma' => $ma, 'ten' => $ten]);

        Session::flash('success', "Thêm chức danh {$ten} thành công.");
        return $this->redirect('/admin/danh-muc/chuc-danh');
    }
}
