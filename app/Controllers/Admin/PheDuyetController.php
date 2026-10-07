<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\ChuKySoService;
use App\Services\WorkflowEngine;
use PDO;

/**
 * Class PheDuyetController – Phân hệ Phê duyệt & Ký số điện tử (PH04)
 * Phục vụ các vai trò: LD_PHONG (Duyệt bước 1), LD_CO_QUAN (Ký phát hành, Batch signing, Duyệt hủy)
 */
class PheDuyetController extends Controller
{
    /**
     * Danh sách hồ sơ chờ Lãnh đạo phòng duyệt bước 1
     */
    public function indexPhong(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $roles = AuthService::userRoles($user['id']);
        if (!in_array('LD_PHONG', $roles, true) && !in_array('LD_CO_QUAN', $roles, true)) {
            Session::flash('error', 'Bạn không có quyền truy cập khu vực phê duyệt của Lãnh đạo phòng.');
            return $this->redirect('/admin/thu-ly');
        }

        $db = Database::connection();
        $donViId = (int)($user['don_vi_hien_tai_id'] ?? $user['don_vi_chinh_id'] ?? 0);
        $q = trim((string)$request->get('q', ''));

        $sql = "SELECT h.*, h.nguoi_nop_ho_ten as ho_ten_nguoi_nop, h.nguoi_nop_cccd as so_cccd_nguoi_nop, h.nguoi_nop_sdt as so_dien_thoai_nguoi_nop,
                       t.ten_tthc, d.ten_don_vi, qb.ten_buoc, qb.loai_buoc,
                       u.ho_ten as ten_chuyen_vien,
                       (SELECT COUNT(*) FROM chu_ky_so c WHERE c.ho_so_tai_lieu_id IN 
                           (SELECT id FROM ho_so_tai_lieu WHERE ho_so_id = h.id)) as da_ky_so
                FROM ho_so h
                JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
                JOIN don_vi d ON h.don_vi_tiep_nhan_id = d.id
                LEFT JOIN quy_trinh_buoc qb ON h.buoc_hien_tai_id = qb.id
                LEFT JOIN nguoi_dung u ON h.chuyen_vien_thu_ly_id = u.id
                WHERE h.is_deleted = 0
                  AND h.trang_thai = 'DANG_XU_LY'
                  AND (qb.loai_buoc = 'THAM_TRA' OR qb.vai_tro_xu_ly = 'LD_PHONG')";

        $params = [];
        if (!empty($q)) {
            $sql .= " AND (h.ma_ho_so LIKE :q OR h.nguoi_nop_ho_ten LIKE :q OR t.ten_tthc LIKE :q)";
            $params[':q'] = "%{$q}%";
        }

        $sql .= " ORDER BY h.ngay_hen_tra ASC LIMIT 50";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $hoSoList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/phe_duyet/phong', [
            'user'     => $user,
            'hoSoList' => $hoSoList,
            'q'        => $q,
            'title'    => 'Lãnh đạo phòng - Thẩm tra & Ký số duyệt bước 1',
        ]);
    }

    /**
     * Chi tiết hồ sơ và dự thảo kết quả chờ duyệt bước 1
     */
    public function chiTietPhong(Request $request, int $id): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $db = Database::connection();
        $stmt = $db->prepare("SELECT h.*, h.nguoi_nop_ho_ten as ho_ten_nguoi_nop, h.nguoi_nop_cccd as so_cccd_nguoi_nop, h.nguoi_nop_sdt as so_dien_thoai_nguoi_nop,
                                     t.ten_tthc, t.ma_tthc, d.ten_don_vi, qb.ten_buoc,
                                     u.ho_ten as ten_chuyen_vien, u.email as email_chuyen_vien
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
            return $this->redirect('/admin/phe-duyet/phong');
        }

        // Danh sách tài liệu đính kèm & dự thảo
        $docsStmt = $db->prepare("SELECT * FROM ho_so_tai_lieu WHERE ho_so_id = :hid ORDER BY id ASC");
        $docsStmt->execute([':hid' => $id]);
        $taiLieuList = $docsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Lịch sử luân chuyển
        $lcStmt = $db->prepare("SELECT l.*, u.ho_ten as nguoi_thao_tac, d.ten_don_vi 
                                FROM ho_so_luan_chuyen l
                                LEFT JOIN nguoi_dung u ON l.nguoi_gui_id = u.id
                                LEFT JOIN don_vi d ON l.don_vi_gui_id = d.id
                                WHERE l.ho_so_id = :hid
                                ORDER BY l.id ASC");
        $lcStmt->execute([':hid' => $id]);
        $luanChuyen = $lcStmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/phe_duyet/chi_tiet_phong', [
            'user'        => $user,
            'hoSo'        => $hoSo,
            'taiLieuList' => $taiLieuList,
            'luanChuyen'  => $luanChuyen,
            'title'       => "Thẩm tra hồ sơ {$hoSo['ma_ho_so']}",
        ]);
    }

    /**
     * Ký số duyệt bước 1 (LD_PHONG)
     */
    public function kySoPhong(Request $request, int $id): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $pin = trim((string)$request->post('pin', ''));
        $yKien = trim((string)$request->post('y_kien', 'Duyệt bước 1 - Kính trình Lãnh đạo cơ quan xem xét phê duyệt'));

        if (empty($pin)) {
            Session::flash('error', 'Vui lòng nhập mã PIN SmartCA để ký số.');
            return $this->redirect("/admin/phe-duyet/phong/{$id}");
        }

        $db = Database::connection();
        $tlStmt = $db->prepare("SELECT * FROM ho_so_tai_lieu WHERE ho_so_id = :hid ORDER BY id DESC LIMIT 1");
        $tlStmt->execute([':hid' => $id]);
        $taiLieu = $tlStmt->fetch(PDO::FETCH_ASSOC);

        $filePath = '';
        $tlId = null;
        if ($taiLieu && !empty($taiLieu['duong_dan']) && file_exists($taiLieu['duong_dan'])) {
            $filePath = $taiLieu['duong_dan'];
            $tlId = (int)$taiLieu['id'];
        } else {
            $docDir = dirname(__DIR__, 3) . '/storage/uploads/results';
            if (!is_dir($docDir)) {
                mkdir($docDir, 0755, true);
            }
            $filePath = "{$docDir}/to_trinh_hs_{$id}.pdf";
            if (!file_exists($filePath)) {
                file_put_contents($filePath, "%PDF-1.4\nTo trinh phe duyet ho so #{$id}\nTime: " . date('Y-m-d H:i:s'));
            }
        }

        try {
            $signResult = ChuKySoService::signDocument((int)$user['id'], $filePath, 'DUYET_BUOC_1', $tlId, [
                'pin'     => $pin,
                'ghi_chu' => $yKien,
            ]);

            // Chuyển bước sang Lãnh đạo cơ quan
            WorkflowEngine::chuyenTiep($id, (int)$user['id'], "Lãnh đạo phòng đã ký số duyệt bước 1: {$yKien} (Serial: {$signResult['serial']})");

            Session::flash('success', "Đã ký số duyệt bước 1 thành công (Serial: {$signResult['serial']}). Hồ sơ đã được chuyển trình Lãnh đạo cơ quan.");
            return $this->redirect('/admin/phe-duyet/phong');
        } catch (\Throwable $e) {
            Session::flash('error', "Ký số duyệt bước 1 thất bại: " . $e->getMessage());
            return $this->redirect("/admin/phe-duyet/phong/{$id}");
        }
    }

    /**
     * Trả lại hồ sơ từ Lãnh đạo phòng về chuyên viên
     */
    public function traLaiPhong(Request $request, int $id): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $lyDo = trim((string)$request->post('ly_do_tra_lai', ''));
        if (empty($lyDo)) {
            Session::flash('error', 'Vui lòng nhập lý do trả lại hồ sơ.');
            return $this->redirect("/admin/phe-duyet/phong/{$id}");
        }

        try {
            WorkflowEngine::traLai($id, (int)$user['id'], "Lãnh đạo phòng trả lại: {$lyDo}");
            Session::flash('success', 'Đã trả lại hồ sơ về cho chuyên viên hoàn thiện.');
            return $this->redirect('/admin/phe-duyet/phong');
        } catch (\Throwable $e) {
            Session::flash('error', "Lỗi trả lại hồ sơ: " . $e->getMessage());
            return $this->redirect("/admin/phe-duyet/phong/{$id}");
        }
    }

    /**
     * Danh sách hồ sơ chờ Lãnh đạo cơ quan ký phát hành
     */
    public function indexCoQuan(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $roles = AuthService::userRoles($user['id']);
        if (!in_array('LD_CO_QUAN', $roles, true)) {
            Session::flash('error', 'Bạn không có quyền truy cập khu vực phê duyệt của Lãnh đạo cơ quan.');
            return $this->redirect('/admin/thu-ly');
        }

        $db = Database::connection();
        $q = trim((string)$request->get('q', ''));

        $sql = "SELECT h.*, h.nguoi_nop_ho_ten as ho_ten_nguoi_nop, h.nguoi_nop_cccd as so_cccd_nguoi_nop, h.nguoi_nop_sdt as so_dien_thoai_nguoi_nop,
                       t.ten_tthc, d.ten_don_vi, qb.ten_buoc, qb.loai_buoc,
                       u.ho_ten as ten_chuyen_vien,
                       (SELECT COUNT(*) FROM chu_ky_so c WHERE c.ho_so_tai_lieu_id IN 
                           (SELECT id FROM ho_so_tai_lieu WHERE ho_so_id = h.id)
                           AND c.loai_chu_ky = 'DUYET_BUOC_1') as phong_da_duyet
                FROM ho_so h
                JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
                JOIN don_vi d ON h.don_vi_tiep_nhan_id = d.id
                LEFT JOIN quy_trinh_buoc qb ON h.buoc_hien_tai_id = qb.id
                LEFT JOIN nguoi_dung u ON h.chuyen_vien_thu_ly_id = u.id
                WHERE h.is_deleted = 0
                  AND h.trang_thai = 'DANG_XU_LY'
                  AND (qb.loai_buoc = 'PHE_DUYET' OR qb.vai_tro_xu_ly = 'LD_CO_QUAN')";

        $params = [];
        if (!empty($q)) {
            $sql .= " AND (h.ma_ho_so LIKE :q OR h.nguoi_nop_ho_ten LIKE :q OR t.ten_tthc LIKE :q)";
            $params[':q'] = "%{$q}%";
        }

        $sql .= " ORDER BY h.ngay_hen_tra ASC LIMIT 50";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $hoSoList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/phe_duyet/co_quan', [
            'user'     => $user,
            'hoSoList' => $hoSoList,
            'q'        => $q,
            'title'    => 'Lãnh đạo cơ quan - Phê duyệt & Ký số phát hành',
        ]);
    }

    /**
     * Chi tiết hồ sơ và văn bản kết quả chờ Lãnh đạo cơ quan ký
     */
    public function chiTietCoQuan(Request $request, int $id): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $db = Database::connection();
        $stmt = $db->prepare("SELECT h.*, h.nguoi_nop_ho_ten as ho_ten_nguoi_nop, h.nguoi_nop_cccd as so_cccd_nguoi_nop, h.nguoi_nop_sdt as so_dien_thoai_nguoi_nop,
                                     t.ten_tthc, t.ma_tthc, d.ten_don_vi, qb.ten_buoc,
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
            return $this->redirect('/admin/phe-duyet/co-quan');
        }

        $docsStmt = $db->prepare("SELECT * FROM ho_so_tai_lieu WHERE ho_so_id = :hid ORDER BY id ASC");
        $docsStmt->execute([':hid' => $id]);
        $taiLieuList = $docsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Chữ ký của phòng
        $sigStmt = $db->prepare("SELECT c.*, cts.ten_chu_the, cts.so_serial, cts.nha_cung_cap
                                 FROM chu_ky_so c
                                 JOIN chung_thu_so cts ON c.chung_thu_so_id = cts.id
                                 WHERE c.ho_so_tai_lieu_id IN (SELECT id FROM ho_so_tai_lieu WHERE ho_so_id = :hid)
                                 ORDER BY c.id ASC");
        $sigStmt->execute([':hid' => $id]);
        $chuKyList = $sigStmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/phe_duyet/chi_tiet_co_quan', [
            'user'        => $user,
            'hoSo'        => $hoSo,
            'taiLieuList' => $taiLieuList,
            'chuKyList'   => $chuKyList,
            'title'       => "Phê duyệt hồ sơ {$hoSo['ma_ho_so']}",
        ]);
    }

    /**
     * Ký số phát hành hồ sơ đơn lẻ (LD_CO_QUAN)
     */
    public function kySoCoQuan(Request $request, int $id): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $pin = trim((string)$request->post('pin', ''));
        $yKien = trim((string)$request->post('y_kien', 'Phê duyệt ban hành kết quả thủ tục hành chính'));

        if (empty($pin)) {
            Session::flash('error', 'Vui lòng nhập mã PIN SmartCA để ký phát hành.');
            return $this->redirect("/admin/phe-duyet/co-quan/{$id}");
        }

        try {
            $batchResult = ChuKySoService::batchSign((int)$user['id'], [$id], $pin, 'PHAT_HANH', $yKien);
            if ($batchResult['success_count'] > 0) {
                Session::flash('success', "Đã ký số phát hành thành công hồ sơ. Hồ sơ đã chuyển Văn thư số đóng dấu.");
                return $this->redirect('/admin/phe-duyet/co-quan');
            } else {
                $err = $batchResult['results'][0]['error_message'] ?? 'Ký số thất bại';
                Session::flash('error', "Ký số phát hành thất bại: {$err}");
                return $this->redirect("/admin/phe-duyet/co-quan/{$id}");
            }
        } catch (\Throwable $e) {
            Session::flash('error', "Lỗi ký số phát hành: " . $e->getMessage());
            return $this->redirect("/admin/phe-duyet/co-quan/{$id}");
        }
    }

    /**
     * KÝ SỐ HÀNG LOẠT (Batch Signing) cho Lãnh đạo cơ quan
     */
    public function batchSignAction(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Chưa đăng nhập'], 401);
        }

        $hoSoIds = $request->post('ho_so_ids', []);
        $pin = trim((string)$request->post('pin', ''));
        $yKien = trim((string)$request->post('y_kien', 'Phê duyệt ban hành hàng loạt'));

        if (!is_array($hoSoIds) || empty($hoSoIds)) {
            return $this->json(['success' => false, 'message' => 'Vui lòng chọn ít nhất một hồ sơ để ký số hàng loạt.'], 400);
        }

        if (empty($pin)) {
            return $this->json(['success' => false, 'message' => 'Vui lòng nhập mã PIN SmartCA.'], 400);
        }

        try {
            $ids = array_map('intval', $hoSoIds);
            $batchResult = ChuKySoService::batchSign((int)$user['id'], $ids, $pin, 'PHAT_HANH', $yKien);

            return $this->json([
                'success' => true,
                'data'    => $batchResult,
                'message' => "Đã hoàn thành ký số {$batchResult['success_count']}/{$batchResult['total']} hồ sơ trong {$batchResult['duration_total']}s (Trung bình {$batchResult['duration_avg']}s/hồ sơ).",
            ]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Danh sách đề xuất hủy hồ sơ (CHO_DUYET_HUY)
     */
    public function danhSachChoHuy(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $db = Database::connection();
        $sql = "SELECT h.*, t.ten_tthc, d.ten_don_vi
                FROM ho_so h
                JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
                JOIN don_vi d ON h.don_vi_tiep_nhan_id = d.id
                WHERE h.trang_thai = 'CHO_DUYET_HUY' AND h.is_deleted = 0
                ORDER BY h.updated_at DESC";
        $stmt = $db->query($sql);
        $hoSoList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/phe_duyet/danh_sach_huy', [
            'user'     => $user,
            'hoSoList' => $hoSoList,
            'title'    => 'Phê duyệt đề xuất hủy hồ sơ TTHC',
        ]);
    }

    /**
     * Phê duyệt hủy logic hồ sơ (DA_HUY)
     */
    public function duyetHuy(Request $request, int $id): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $roles = AuthService::userRoles($user['id']);
        if (!in_array('LD_CO_QUAN', $roles, true)) {
            Session::flash('error', 'Chỉ Lãnh đạo cơ quan mới có thẩm quyền phê duyệt hủy hồ sơ.');
            return $this->redirect('/admin/phe-duyet/cho-huy');
        }

        $yKien = trim((string)$request->post('y_kien_duyet_huy', 'Đồng ý hủy hồ sơ theo biên bản giải trình'));

        $db = Database::connection();
        try {
            $upd = $db->prepare("UPDATE ho_so SET 
                trang_thai = 'DA_HUY', is_deleted = 1, deleted_at = NOW(),
                ghi_chu = CONCAT(COALESCE(ghi_chu, ''), ' | [ĐÃ HỦY LOGIC bởi Lãnh đạo ID ', :uid, ': ', :yk, ']')
                WHERE id = :id AND trang_thai = 'CHO_DUYET_HUY'");
            $upd->execute([
                ':uid' => $user['id'],
                ':yk'  => $yKien,
                ':id'  => $id,
            ]);

            Session::flash('success', "Đã phê duyệt hủy logic hồ sơ #{$id} thành công (Bảo toàn dữ liệu WORM, không xóa vật lý).");
            return $this->redirect('/admin/phe-duyet/cho-huy');
        } catch (\Throwable $e) {
            Session::flash('error', "Lỗi duyệt hủy: " . $e->getMessage());
            return $this->redirect('/admin/phe-duyet/cho-huy');
        }
    }
}
