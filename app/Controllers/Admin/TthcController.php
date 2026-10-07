<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Integrations\Mock\CsdlTthcQuocGiaMock;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\MdmService;
use PDO;

/**
 * TthcController – Quản lý Danh mục Thủ tục Hành chính (TTHC)
 * Đồng bộ với CSDL Quốc gia về TTHC (Adapter Pattern), so sánh khác biệt và xuất CSV
 */
class TthcController extends Controller
{
    /**
     * Danh sách TTHC (GET /admin/danh-muc/tthc)
     */
    public function index(Request $request): Response
    {
        $db = Database::connection();
        $q = trim((string)$request->query('q', ''));
        $linhVucId = (int)$request->query('linh_vuc_id', 0);
        $cap = $request->query('cap', '');
        $mucDo = $request->query('muc_do', '');

        $sql = "SELECT tt.*, lv.ten_linh_vuc, COUNT(tp.id) AS so_thanh_phan 
                FROM thu_tuc_hanh_chinh tt
                LEFT JOIN dm_linh_vuc lv ON tt.linh_vuc_id = lv.id
                LEFT JOIN thanh_phan_ho_so tp ON tt.id = tp.tthc_id
                WHERE 1=1";
        $params = [];

        if (!empty($q)) {
            $sql .= " AND (tt.ma_tthc LIKE :q OR tt.ma_rut_gon LIKE :q OR tt.ten_tthc LIKE :q)";
            $params[':q'] = "%{$q}%";
        }
        if ($linhVucId > 0) {
            $sql .= " AND tt.linh_vuc_id = :lvid";
            $params[':lvid'] = $linhVucId;
        }
        if (!empty($cap)) {
            $sql .= " AND tt.cap_thuc_hien = :cap";
            $params[':cap'] = $cap;
        }
        if (!empty($mucDo)) {
            $sql .= " AND tt.muc_do_dvc = :md";
            $params[':md'] = $mucDo;
        }

        $sql .= " GROUP BY tt.id ORDER BY tt.id ASC LIMIT 100";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $tthcList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $linhVucList = $db->query("SELECT id, ten_linh_vuc FROM dm_linh_vuc ORDER BY ten_linh_vuc ASC")->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/tthc/index', [
            'pageTitle' => 'Danh mục Thủ tục Hành chính | HP-eGov',
            'tthcList' => $tthcList,
            'linhVucList' => $linhVucList,
            'filters' => ['q' => $q, 'linh_vuc_id' => $linhVucId, 'cap' => $cap, 'muc_do' => $mucDo],
        ]);
    }

    /**
     * Màn hình đối chiếu so sánh khác biệt đồng bộ Quốc gia (GET /admin/danh-muc/tthc/dong-bo)
     */
    public function syncPreview(Request $request): Response
    {
        $mockAdapter = new CsdlTthcQuocGiaMock();
        $syncData = $mockAdapter->sync();

        return $this->view('admin/tthc/sync_preview', [
            'pageTitle' => 'Đồng bộ CSDL TTHC Quốc gia | HP-eGov',
            'syncData' => $syncData,
        ]);
    }

    /**
     * Áp dụng kết quả đồng bộ vào CSDL HP-eGov (POST /admin/danh-muc/tthc/dong-bo/ap-dung)
     */
    public function applySync(Request $request): Response
    {
        $db = Database::connection();
        $mockAdapter = new CsdlTthcQuocGiaMock();
        $syncData = $mockAdapter->sync();

        $db->beginTransaction();
        try {
            $appliedCount = 0;

            // 1. Thêm TTHC mới
            $insertStmt = $db->prepare("INSERT INTO thu_tuc_hanh_chinh 
                (ma_tthc, ma_rut_gon, ten_tthc, linh_vuc_id, cap_thuc_hien, muc_do_dvc, thoi_han_giai_quyet_ngay, phi_le_phi, can_cu_phap_ly, trang_thai_cong_bo, ngay_cong_bo, ngay_dong_bo, trang_thai) 
                VALUES 
                (:ma, :rg, :ten, 1, :cap, :md, :th, :phi, :cc, 'DANG_CONG_BO', NOW(), NOW(), 1)");

            foreach ($syncData['added'] as $item) {
                // Kiểm tra xem đã có chưa
                $exists = $db->query("SELECT id FROM thu_tuc_hanh_chinh WHERE ma_tthc = '{$item['ma_tthc']}'")->fetchColumn();
                if (!$exists) {
                    $insertStmt->execute([
                        ':ma' => $item['ma_tthc'],
                        ':rg' => $item['ma_rut_gon'],
                        ':ten' => $item['ten_thu_tuc'],
                        ':cap' => $item['cap_thuc_hien'],
                        ':md' => $item['muc_do'],
                        ':th' => $item['thoi_han_ngay'],
                        ':phi' => $item['phi_le_phi'],
                        ':cc' => $item['can_cu_phap_ly'],
                    ]);
                    $appliedCount++;
                }
            }

            // 2. Cập nhật TTHC sửa đổi
            $updateStmt = $db->prepare("UPDATE thu_tuc_hanh_chinh SET 
                muc_do_dvc = :md, 
                thoi_han_giai_quyet_ngay = :th, 
                phi_le_phi = :phi, 
                ngay_dong_bo = NOW() 
                WHERE ma_tthc = :ma");

            foreach ($syncData['updated'] as $item) {
                $updateStmt->execute([
                    ':md' => $item['muc_do_moi'],
                    ':th' => $item['thoi_han_moi'],
                    ':phi' => $item['phi_moi'],
                    ':ma' => $item['ma_tthc'],
                ]);
                $appliedCount++;
            }

            $db->commit();

            AuditService::log(
                AuthService::id(),
                'DONG_BO_CSDL_TTHC_QUOC_GIA',
                'thu_tuc_hanh_chinh',
                null,
                null,
                "Áp dụng thành công {$appliedCount} thay đổi từ CSDL TTHC Quốc gia (VPCP)"
            );

            Session::flash('success', "Đồng bộ thành công! Đã cập nhật {$appliedCount} thủ tục hành chính từ CSDL Quốc gia.");
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Lỗi khi áp dụng đồng bộ: ' . $e->getMessage());
        }

        return $this->redirect('/admin/danh-muc/tthc');
    }

    /**
     * Xuất danh mục TTHC ra CSV UTF-8 có BOM (GET /admin/danh-muc/tthc/export)
     */
    public function export(Request $request): Response
    {
        $db = Database::connection();
        $rows = $db->query("SELECT tt.ma_tthc, tt.ma_rut_gon, tt.ten_tthc, lv.ten_linh_vuc, tt.cap_thuc_hien, tt.muc_do_dvc, tt.thoi_han_giai_quyet_ngay, tt.phi_le_phi, COALESCE(tt.can_cu_phap_ly, '') as can_cu 
                            FROM thu_tuc_hanh_chinh tt LEFT JOIN dm_linh_vuc lv ON tt.linh_vuc_id = lv.id ORDER BY tt.id ASC")->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Mã TTHC Quốc gia', 'Mã rút gọn', 'Tên TTHC', 'Lĩnh vực', 'Cấp thực hiện', 'Mức độ DVC', 'Thời hạn (ngày)', 'Phí / Lệ phí (VNĐ)', 'Căn cứ pháp lý'];
        return MdmService::exportCsv('danh_muc_tthc_haiphong.csv', $headers, $rows);
    }
}
