<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

/**
 * DvcController – Phân hệ Tra cứu Danh mục Dịch vụ công & Chi tiết TTHC
 */
class DvcController extends Controller
{
    /**
     * Danh mục Dịch vụ công trực tuyến (GET /dvc/thu-tuc)
     */
    public function index(Request $request): Response
    {
        $db = Database::connection();
        $q = trim((string)$request->query('q', ''));
        $linhVucId = (int)$request->query('linh_vuc_id', 0);
        $cap = trim((string)$request->query('cap', ''));
        $mucDo = trim((string)$request->query('muc_do', ''));

        $sql = "SELECT tt.*, lv.ten_linh_vuc 
                FROM thu_tuc_hanh_chinh tt
                LEFT JOIN dm_linh_vuc lv ON tt.linh_vuc_id = lv.id
                WHERE tt.trang_thai = 1";
        $params = [];

        if (!empty($q)) {
            $sql .= " AND (tt.ten_tthc LIKE :q OR tt.ma_rut_gon LIKE :q OR tt.ma_tthc LIKE :q)";
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

        $sql .= " ORDER BY tt.muc_do_dvc DESC, tt.id ASC LIMIT 60";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $tthcList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $linhVucList = $db->query("SELECT id, ten_linh_vuc FROM dm_linh_vuc ORDER BY ten_linh_vuc ASC")->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('portal/dvc/index', [
            'pageTitle' => 'Danh mục Dịch vụ công trực tuyến | Cổng DVC Hải Phòng',
            'tthcList' => $tthcList,
            'linhVucList' => $linhVucList,
            'filters' => [
                'q' => $q,
                'linh_vuc_id' => $linhVucId,
                'cap' => $cap,
                'muc_do' => $mucDo,
            ],
        ]);
    }

    /**
     * Chi tiết Thủ tục hành chính (GET /dvc/thu-tuc/{ma})
     */
    public function detail(Request $request): Response
    {
        $ma = (string)$request->routeParam('ma', '');
        $db = Database::connection();

        // Tìm theo mã rút gọn hoặc id
        $stmt = $db->prepare("SELECT tt.*, lv.ten_linh_vuc 
                              FROM thu_tuc_hanh_chinh tt 
                              LEFT JOIN dm_linh_vuc lv ON tt.linh_vuc_id = lv.id 
                              WHERE tt.ma_rut_gon = :ma OR tt.id = :id 
                              LIMIT 1");
        $stmt->execute([':ma' => $ma, ':id' => is_numeric($ma) ? (int)$ma : 0]);
        $tthc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tthc) {
            return $this->redirect('/dvc/thu-tuc');
        }

        // Lấy thành phần hồ sơ
        $tpStmt = $db->prepare("SELECT * FROM thanh_phan_ho_so WHERE tthc_id = :tid ORDER BY thu_tu ASC, id ASC");
        $tpStmt->execute([':tid' => $tthc['id']]);
        $thanhPhanHoSo = $tpStmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy thông tin quy trình xử lý gắn kèm
        $qtStmt = $db->prepare("SELECT * FROM quy_trinh WHERE tthc_id = :tid AND is_active = 1 ORDER BY phien_ban DESC LIMIT 1");
        $qtStmt->execute([':tid' => $tthc['id']]);
        $quyTrinh = $qtStmt->fetch(PDO::FETCH_ASSOC);

        // Lấy các bước quy trình nếu có
        $buocList = [];
        if ($quyTrinh) {
            $bStmt = $db->prepare("SELECT b.*, dv.ten_don_vi FROM quy_trinh_buoc b LEFT JOIN don_vi dv ON b.don_vi_xu_ly_id = dv.id WHERE b.quy_trinh_id = :qid ORDER BY b.stt_buoc ASC");
            $bStmt->execute([':qid' => $quyTrinh['id']]);
            $buocList = $bStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $this->view('portal/dvc/detail', [
            'pageTitle' => "{$tthc['ten_tthc']} | Cổng DVC Hải Phòng",
            'tthc' => $tthc,
            'thanhPhanHoSo' => $thanhPhanHoSo,
            'quyTrinh' => $quyTrinh,
            'buocList' => $buocList,
        ]);
    }
}
