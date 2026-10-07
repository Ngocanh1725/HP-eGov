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
use App\Services\WorkflowEngine;
use PDO;

/**
 * WorkflowController – Phân hệ PH10 Quản trị Quy trình BPMN-lite
 * Quản lý phiên bản quy trình, thiết kế bước/luồng và hiển thị sơ đồ trực quan
 */
class WorkflowController extends Controller
{
    /**
     * Danh sách quy trình theo TTHC (GET /admin/quy-trinh)
     */
    public function index(Request $request): Response
    {
        $db = Database::connection();
        $q = trim((string)$request->query('q', ''));

        $sql = "SELECT qt.*, tt.ten_tthc, tt.ma_rut_gon, tt.thoi_han_giai_quyet_ngay,
                COUNT(b.id) AS so_buoc
                FROM quy_trinh qt
                LEFT JOIN thu_tuc_hanh_chinh tt ON qt.tthc_id = tt.id
                LEFT JOIN quy_trinh_buoc b ON qt.id = b.quy_trinh_id
                WHERE 1=1";
        $params = [];
        if (!empty($q)) {
            $sql .= " AND (qt.ten_quy_trinh LIKE :q OR tt.ten_tthc LIKE :q OR tt.ma_rut_gon LIKE :q)";
            $params[':q'] = "%{$q}%";
        }

        $sql .= " GROUP BY qt.id ORDER BY qt.id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $workflows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/workflow/index', [
            'pageTitle' => 'Quản trị Quy trình Xử lý (BPMN-lite) | HP-eGov',
            'workflows' => $workflows,
            'q' => $q,
        ]);
    }

    /**
     * Xem chi tiết thiết kế quy trình và sơ đồ (GET /admin/quy-trinh/{id})
     */
    public function detail(Request $request): Response
    {
        $id = (int)$request->routeParam('id', 0);
        $db = Database::connection();

        // 1. Lấy thông tin quy trình
        $stmt = $db->prepare("SELECT qt.*, tt.ten_tthc, tt.ma_rut_gon, tt.thoi_han_giai_quyet_ngay 
                              FROM quy_trinh qt 
                              LEFT JOIN thu_tuc_hanh_chinh tt ON qt.tthc_id = tt.id 
                              WHERE qt.id = :id");
        $stmt->execute([':id' => $id]);
        $workflow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$workflow) {
            Session::flash('error', 'Không tìm thấy quy trình.');
            return $this->redirect('/admin/quy-trinh');
        }

        // 2. Lấy danh sách các bước
        $stepStmt = $db->prepare("SELECT b.*, dv.ten_don_vi 
                                  FROM quy_trinh_buoc b 
                                  LEFT JOIN don_vi dv ON b.don_vi_xu_ly_id = dv.id 
                                  WHERE b.quy_trinh_id = :id 
                                  ORDER BY b.stt_buoc ASC");
        $stepStmt->execute([':id' => $id]);
        $steps = $stepStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Lấy luồng chuyển tiếp
        $trStmt = $db->prepare("SELECT ct.*, b1.ten_buoc as tu_ten, b2.ten_buoc as den_ten 
                                FROM quy_trinh_chuyen_tiep ct 
                                LEFT JOIN quy_trinh_buoc b1 ON ct.tu_buoc_id = b1.id 
                                LEFT JOIN quy_trinh_buoc b2 ON ct.den_buoc_id = b2.id 
                                WHERE ct.quy_trinh_id = :id");
        $trStmt->execute([':id' => $id]);
        $transitions = $trStmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Thẩm định quy trình (Workflow Validator)
        $validation = WorkflowEngine::validateWorkflow($id);

        // 5. Sinh mã Mermaid
        $mermaid = WorkflowEngine::generateMermaidDiagram($id);

        // Danh sách các cơ quan để chọn đơn vị xử lý
        $donViList = $db->query("SELECT id, ten_don_vi, loai_don_vi FROM don_vi WHERE trang_thai = 1 ORDER BY cap_do ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/workflow/detail', [
            'pageTitle' => "Quy trình: {$workflow['ten_quy_trinh']} (v{$workflow['phien_ban']}) | HP-eGov",
            'workflow' => $workflow,
            'steps' => $steps,
            'transitions' => $transitions,
            'validation' => $validation,
            'mermaid' => $mermaid,
            'donViList' => $donViList,
        ]);
    }

    /**
     * Tạo phiên bản mới của quy trình (POST /admin/quy-trinh/{id}/nhan-ban-phien-ban)
     * Sao chép toàn bộ các bước và chuyển tiếp, các hồ sơ đang chạy giữ nguyên phiên bản cũ
     */
    public function createVersion(Request $request): Response
    {
        $id = (int)$request->routeParam('id', 0);
        $db = Database::connection();

        $orig = $db->query("SELECT * FROM quy_trinh WHERE id = {$id}")->fetch(PDO::FETCH_ASSOC);
        if (!$orig) {
            Session::flash('error', 'Không tìm thấy quy trình gốc.');
            return $this->redirect('/admin/quy-trinh');
        }

        $nextVer = ((int)$orig['phien_ban']) + 1;

        $db->beginTransaction();
        try {
            // 1. Tạo bản ghi quy trình phiên bản mới
            $insQt = $db->prepare("INSERT INTO quy_trinh (tthc_id, ten_quy_trinh, phien_ban, tong_thoi_han_gio, is_active, created_at) 
                                   VALUES (:tid, :ten, :ver, :gio, 1, NOW())");
            $insQt->execute([
                ':tid' => $orig['tthc_id'],
                ':ten' => $orig['ten_quy_trinh'],
                ':ver' => $nextVer,
                ':gio' => $orig['tong_thoi_han_gio'],
            ]);
            $newQtId = (int)$db->lastInsertId();

            // 2. Sao chép các bước
            $oldSteps = $db->query("SELECT * FROM quy_trinh_buoc WHERE quy_trinh_id = {$id} ORDER BY stt_buoc ASC")->fetchAll(PDO::FETCH_ASSOC);
            $stepIdMap = []; // old_step_id => new_step_id

            $insStep = $db->prepare("INSERT INTO quy_trinh_buoc 
                (quy_trinh_id, stt_buoc, ten_buoc, loai_buoc, vai_tro_xu_ly, don_vi_xu_ly_id, thoi_han_gio, cho_phep_tra_lai, ghi_chu) 
                VALUES 
                (:qid, :stt, :ten, :loai, :vt, :dvid, :gio, :tl, :gc)");

            foreach ($oldSteps as $s) {
                $insStep->execute([
                    ':qid' => $newQtId,
                    ':stt' => $s['stt_buoc'],
                    ':ten' => $s['ten_buoc'],
                    ':loai' => $s['loai_buoc'],
                    ':vt' => $s['vai_tro_xu_ly'],
                    ':dvid' => $s['don_vi_xu_ly_id'],
                    ':gio' => $s['thoi_han_gio'],
                    ':tl' => $s['cho_phep_tra_lai'],
                    ':gc' => $s['ghi_chu'],
                ]);
                $stepIdMap[$s['id']] = (int)$db->lastInsertId();
            }

            // 3. Sao chép luồng chuyển tiếp
            $oldTransitions = $db->query("SELECT * FROM quy_trinh_chuyen_tiep WHERE quy_trinh_id = {$id}")->fetchAll(PDO::FETCH_ASSOC);
            $insTr = $db->prepare("INSERT INTO quy_trinh_chuyen_tiep (quy_trinh_id, tu_buoc_id, den_buoc_id, hanh_dong, dieu_kien) 
                                   VALUES (:qid, :tu, :den, :hd, :dk)");

            foreach ($oldTransitions as $tr) {
                $newTu = $stepIdMap[$tr['tu_buoc_id']] ?? null;
                $newDen = $stepIdMap[$tr['den_buoc_id']] ?? null;
                if ($newTu && $newDen) {
                    $insTr->execute([
                        ':qid' => $newQtId,
                        ':tu' => $newTu,
                        ':den' => $newDen,
                        ':hd' => $tr['hanh_dong'],
                        ':dk' => $tr['dieu_kien'],
                    ]);
                }
            }

            $db->commit();

            AuditService::log(
                AuthService::id(),
                'TAO_PHIEN_BAN_QUY_TRINH',
                'quy_trinh',
                $newQtId,
                ['goc_id' => $id, 'phien_ban_cu' => $orig['phien_ban']],
                ['moi_id' => $newQtId, 'phien_ban_moi' => $nextVer]
            );

            Session::flash('success', "Đã tạo thành công phiên bản mới v{$nextVer} cho quy trình! Hồ sơ đang xử lý vẫn giữ phiên bản cũ an toàn.");
            return $this->redirect("/admin/quy-trinh/{$newQtId}");
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Lỗi khi tạo phiên bản mới: ' . $e->getMessage());
            return $this->redirect("/admin/quy-trinh/{$id}");
        }
    }

    /**
     * Thêm bước mới vào quy trình (POST /admin/quy-trinh/{id}/buoc)
     */
    public function addStep(Request $request): Response
    {
        $id = (int)$request->routeParam('id', 0);
        $stt = (int)$request->input('stt_buoc', 1);
        $ten = trim((string)$request->input('ten_buoc', ''));
        $loai = (string)$request->input('loai_buoc', 'THU_LY');
        $vaiTro = (string)$request->input('vai_tro_xu_ly', 'CHUYEN_VIEN');
        $donViId = (int)$request->input('don_vi_xu_ly_id', 1);
        $thoiHan = (int)$request->input('thoi_han_gio', 8);
        $choPhepTraLai = (int)$request->input('cho_phep_tra_lai', 0);
        $ghiChu = trim((string)$request->input('ghi_chu', ''));

        if (empty($ten)) {
            Session::flash('error', 'Tên bước không được để trống.');
            return $this->redirect("/admin/quy-trinh/{$id}");
        }

        $db = Database::connection();
        $stmt = $db->prepare("INSERT INTO quy_trinh_buoc 
            (quy_trinh_id, stt_buoc, ten_buoc, loai_buoc, vai_tro_xu_ly, don_vi_xu_ly_id, thoi_han_gio, cho_phep_tra_lai, ghi_chu) 
            VALUES (:qid, :stt, :ten, :loai, :vt, :dvid, :gio, :tl, :gc)");
        $stmt->execute([
            ':qid' => $id,
            ':stt' => $stt,
            ':ten' => $ten,
            ':loai' => $loai,
            ':vt' => $vaiTro,
            ':dvid' => $donViId,
            ':gio' => $thoiHan,
            ':tl' => $choPhepTraLai,
            ':gc' => $ghiChu,
        ]);

        AuditService::log(AuthService::id(), 'THEM_BUOC_QUY_TRINH', 'quy_trinh_buoc', (int)$db->lastInsertId(), null, ['quy_trinh_id' => $id, 'ten' => $ten]);

        Session::flash('success', "Thêm bước [{$ten}] thành công.");
        return $this->redirect("/admin/quy-trinh/{$id}");
    }

    /**
     * Thêm luồng chuyển tiếp giữa các bước (POST /admin/quy-trinh/{id}/chuyen-tiep)
     */
    public function addTransition(Request $request): Response
    {
        $id = (int)$request->routeParam('id', 0);
        $tuBuoc = (int)$request->input('tu_buoc_id', 0);
        $denBuoc = (int)$request->input('den_buoc_id', 0);
        $hanhDong = (string)$request->input('hanh_dong', 'CHUYEN');
        $dieuKien = trim((string)$request->input('dieu_kien', ''));

        if ($tuBuoc <= 0 || $denBuoc <= 0) {
            Session::flash('error', 'Vui lòng chọn bước đi và bước đến hợp lệ.');
            return $this->redirect("/admin/quy-trinh/{$id}");
        }

        $db = Database::connection();
        $stmt = $db->prepare("INSERT INTO quy_trinh_chuyen_tiep (quy_trinh_id, tu_buoc_id, den_buoc_id, hanh_dong, dieu_kien) 
                              VALUES (:qid, :tu, :den, :hd, :dk)");
        $stmt->execute([
            ':qid' => $id,
            ':tu' => $tuBuoc,
            ':den' => $denBuoc,
            ':hd' => $hanhDong,
            ':dk' => $dieuKien,
        ]);

        AuditService::log(AuthService::id(), 'THEM_CHUYEN_TIEP_QUY_TRINH', 'quy_trinh_chuyen_tiep', (int)$db->lastInsertId(), null, ['tu' => $tuBuoc, 'den' => $denBuoc, 'hanh_dong' => $hanhDong]);

        Session::flash('success', 'Thêm chuyển tiếp luồng xử lý thành công.');
        return $this->redirect("/admin/quy-trinh/{$id}");
    }
}

