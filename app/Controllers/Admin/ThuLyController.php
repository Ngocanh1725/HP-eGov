<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Integrations\Mock\LgspGatewayMock;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\WorkflowEngine;
use PDO;

/**
 * ThuLyController – Quản lý phân hệ Thụ lý, Thẩm định & Phối hợp Liên thông (PH03)
 * Phục vụ các vai trò: CHUYEN_VIEN, LD_PHONG, LD_CO_QUAN
 */
class ThuLyController extends Controller
{
    /**
     * Hộp việc (Inbox) cán bộ - Đa tab, lọc, tìm kiếm, hiển thị SLA
     */
    public function inbox(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $db = Database::connection();
        $donViId = (int)($user['don_vi_hien_tai_id'] ?? $user['don_vi_chinh_id'] ?? 0);
        $userId = (int)$user['id'];
        $tab = (string)$request->get('tab', 'cho_xu_ly');
        $q = trim((string)$request->get('q', ''));
        $tthcId = (int)$request->get('tthc_id', 0);

        // Đếm số lượng hồ sơ cho từng tab
        $counts = [
            'cho_xu_ly' => 0,
            'dang_xu_ly' => 0,
            'da_chuyen' => 0,
            'phoi_hop' => 0,
            'sap_qua_han' => 0,
            'qua_han' => 0,
        ];

        // 1. Chờ xử lý: Hồ sơ tại đơn vị hiện tại, trạng thái DANG_XU_LY hoặc MOI_TIEP_NHAN, chưa có chuyên viên hoặc mới chuyển đến
        $cntCho = $db->query("SELECT COUNT(*) FROM ho_so 
                              WHERE don_vi_hien_tai_id = {$donViId} 
                                AND trang_thai IN ('MOI_TIEP_NHAN', 'DANG_XU_LY') 
                                AND (chuyen_vien_thu_ly_id IS NULL OR chuyen_vien_thu_ly_id = {$userId})
                                AND is_deleted = 0")->fetchColumn();
        $counts['cho_xu_ly'] = (int)$cntCho;

        // 2. Đang xử lý: Hồ sơ đang được chuyên viên thụ lý
        $cntDang = $db->query("SELECT COUNT(*) FROM ho_so 
                               WHERE don_vi_hien_tai_id = {$donViId} 
                                 AND trang_thai = 'DANG_XU_LY' 
                                 AND chuyen_vien_thu_ly_id = {$userId} 
                                 AND is_deleted = 0")->fetchColumn();
        $counts['dang_xu_ly'] = (int)$cntDang;

        // 3. Đã chuyển: Hồ sơ đơn vị mình từng xử lý nhưng hiện tại đang ở đơn vị khác
        $cntChuyen = $db->query("SELECT COUNT(DISTINCT hs.id) 
                                 FROM ho_so hs 
                                 JOIN ho_so_luan_chuyen lc ON hs.id = lc.ho_so_id 
                                 WHERE lc.tu_don_vi_id = {$donViId} 
                                   AND hs.don_vi_hien_tai_id != {$donViId} 
                                   AND hs.is_deleted = 0")->fetchColumn();
        $counts['da_chuyen'] = (int)$cntChuyen;

        // 4. Phối hợp: Hồ sơ đơn vị khác gửi yêu cầu lấy ý kiến tới đơn vị mình
        $cntPh = $db->query("SELECT COUNT(DISTINCT ho_so_id) FROM ho_so_y_kien_phoi_hop 
                             WHERE don_vi_phoi_hop_id = {$donViId} AND trang_thai = 'CHO_Y_KIEN'")->fetchColumn();
        $counts['phoi_hop'] = (int)$cntPh;

        // 5. Sắp quá hạn (Vàng) & Quá hạn (Đỏ) tại đơn vị
        $cntVang = $db->query("SELECT COUNT(*) FROM ho_so 
                               WHERE don_vi_hien_tai_id = {$donViId} 
                                 AND sla_mau = 'VANG' 
                                 AND trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA', 'TU_CHOI', 'DA_HUY') 
                                 AND is_deleted = 0")->fetchColumn();
        $counts['sap_qua_han'] = (int)$cntVang;

        $cntDo = $db->query("SELECT COUNT(*) FROM ho_so 
                             WHERE don_vi_hien_tai_id = {$donViId} 
                               AND sla_mau = 'DO' 
                               AND trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA', 'TU_CHOI', 'DA_HUY') 
                               AND is_deleted = 0")->fetchColumn();
        $counts['qua_han'] = (int)$cntDo;

        // Xây dựng truy vấn danh sách theo tab
        $whereClauses = ["hs.is_deleted = 0"];
        $params = [];

        switch ($tab) {
            case 'dang_xu_ly':
                $whereClauses[] = "hs.don_vi_hien_tai_id = :dvid AND hs.trang_thai = 'DANG_XU_LY' AND hs.chuyen_vien_thu_ly_id = :uid";
                $params[':dvid'] = $donViId;
                $params[':uid'] = $userId;
                break;

            case 'da_chuyen':
                $whereClauses[] = "hs.id IN (SELECT ho_so_id FROM ho_so_luan_chuyen WHERE tu_don_vi_id = :tudv) AND hs.don_vi_hien_tai_id != :dvid";
                $params[':tudv'] = $donViId;
                $params[':dvid'] = $donViId;
                break;

            case 'phoi_hop':
                $whereClauses[] = "hs.id IN (SELECT ho_so_id FROM ho_so_y_kien_phoi_hop WHERE don_vi_phoi_hop_id = :phdv AND trang_thai = 'CHO_Y_KIEN')";
                $params[':phdv'] = $donViId;
                break;

            case 'sap_qua_han':
                $whereClauses[] = "hs.don_vi_hien_tai_id = :dvid AND hs.sla_mau = 'VANG' AND hs.trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA', 'TU_CHOI', 'DA_HUY')";
                $params[':dvid'] = $donViId;
                break;

            case 'qua_han':
                $whereClauses[] = "hs.don_vi_hien_tai_id = :dvid AND hs.sla_mau = 'DO' AND hs.trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA', 'TU_CHOI', 'DA_HUY')";
                $params[':dvid'] = $donViId;
                break;

            case 'cho_xu_ly':
            default:
                $whereClauses[] = "hs.don_vi_hien_tai_id = :dvid AND hs.trang_thai IN ('MOI_TIEP_NHAN', 'DANG_XU_LY') AND (hs.chuyen_vien_thu_ly_id IS NULL OR hs.chuyen_vien_thu_ly_id = :uid)";
                $params[':dvid'] = $donViId;
                $params[':uid'] = $userId;
                break;
        }

        if (!empty($q)) {
            $whereClauses[] = "(hs.ma_ho_so LIKE :q OR hs.nguoi_nop_ho_ten LIKE :q OR hs.nguoi_nop_cccd LIKE :q)";
            $params[':q'] = "%{$q}%";
        }

        if ($tthcId > 0) {
            $whereClauses[] = "hs.tthc_id = :tid";
            $params[':tid'] = $tthcId;
        }

        $whereSql = implode(' AND ', $whereClauses);
        $sql = "SELECT hs.*, tt.ten_tthc, tt.ma_tthc, qb.ten_buoc, qb.loai_buoc, 
                       cv.ho_ten AS ten_chuyen_vien, dv.ten_don_vi AS ten_don_vi_hien_tai
                FROM ho_so hs
                LEFT JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id
                LEFT JOIN quy_trinh_buoc qb ON hs.buoc_hien_tai_id = qb.id
                LEFT JOIN nguoi_dung cv ON hs.chuyen_vien_thu_ly_id = cv.id
                LEFT JOIN don_vi dv ON hs.don_vi_hien_tai_id = dv.id
                WHERE {$whereSql}
                ORDER BY 
                    CASE hs.sla_mau WHEN 'DO' THEN 1 WHEN 'VANG' THEN 2 ELSE 3 END, 
                    hs.ngay_hen_tra ASC 
                LIMIT 50";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $danhSachHoSo = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Danh mục TTHC để lọc
        $tthcList = $db->query("SELECT id, ma_tthc, ten_tthc FROM thu_tuc_hanh_chinh WHERE trang_thai = 1 ORDER BY ma_tthc ASC")->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/thu_ly/inbox', [
            'pageTitle' => 'Hộp việc Thụ lý & Phối hợp Liên thông',
            'tab' => $tab,
            'counts' => $counts,
            'danhSachHoSo' => $danhSachHoSo,
            'tthcList' => $tthcList,
            'q' => $q,
            'tthcId' => $tthcId,
            'currentUser' => $user,
        ]);
    }

    /**
     * Màn hình thẩm định chi tiết hồ sơ (Dành cho Chuyên viên & Lãnh đạo phòng)
     */
    public function detail(Request $request, string|int $id): Response
    {
        $id = (int)$id;
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $db = Database::connection();
        $stmtHs = $db->prepare("SELECT hs.*, tt.ten_tthc, tt.ma_tthc, tt.thoi_han_giai_quyet_ngay, tt.phi_le_phi,
                                       qb.ten_buoc, qb.loai_buoc, qb.vai_tro_xu_ly, qb.thoi_han_gio as buoc_thoi_han_gio,
                                       dv_tn.ten_don_vi as ten_don_vi_tiep_nhan,
                                       dv_ht.ten_don_vi as ten_don_vi_hien_tai,
                                       cv.ho_ten as ten_chuyen_vien, cv.email as email_chuyen_vien
                                FROM ho_so hs
                                LEFT JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id
                                LEFT JOIN quy_trinh_buoc qb ON hs.buoc_hien_tai_id = qb.id
                                LEFT JOIN don_vi dv_tn ON hs.don_vi_tiep_nhan_id = dv_tn.id
                                LEFT JOIN don_vi dv_ht ON hs.don_vi_hien_tai_id = dv_ht.id
                                LEFT JOIN nguoi_dung cv ON hs.chuyen_vien_thu_ly_id = cv.id
                                WHERE hs.id = :id AND hs.is_deleted = 0");
        $stmtHs->execute([':id' => $id]);
        $hoSo = $stmtHs->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            Session::flash('flash_error', 'Không tìm thấy thông tin hồ sơ.');
            return $this->redirect('/admin/thu-ly');
        }

        // 1. Danh sách tệp đính kèm
        $taiLieuList = $db->query("SELECT tl.*, nd.ho_ten as ten_nguoi_so_hoa, cks.so_serial, cks.thoi_gian_ky
                                   FROM ho_so_tai_lieu tl
                                   LEFT JOIN nguoi_dung nd ON tl.so_hoa_boi_id = nd.id
                                   LEFT JOIN (
                                       SELECT ck.id, ck.thoi_gian_ky, ct.so_serial 
                                       FROM chu_ky_so ck 
                                       LEFT JOIN chung_thu_so ct ON ck.chung_thu_so_id = ct.id
                                   ) cks ON tl.chu_ky_so_id = cks.id
                                   WHERE tl.ho_so_id = {$id}
                                   ORDER BY tl.id ASC")->fetchAll(PDO::FETCH_ASSOC);

        // 2. Timeline lịch sử luân chuyển
        $timeline = $db->query("SELECT lc.*, ndc.ho_ten as ten_nguoi_chuyen, ndn.ho_ten as ten_nguoi_nhan,
                                       tudv.ten_don_vi as ten_tu_don_vi, dendv.ten_don_vi as ten_den_don_vi,
                                       tub.ten_buoc as ten_tu_buoc, denb.ten_buoc as ten_den_buoc
                                FROM ho_so_luan_chuyen lc
                                LEFT JOIN nguoi_dung ndc ON lc.nguoi_chuyen_id = ndc.id
                                LEFT JOIN nguoi_dung ndn ON lc.nguoi_nhan_id = ndn.id
                                LEFT JOIN don_vi tudv ON lc.tu_don_vi_id = tudv.id
                                LEFT JOIN don_vi dendv ON lc.den_don_vi_id = dendv.id
                                LEFT JOIN quy_trinh_buoc tub ON lc.tu_buoc_id = tub.id
                                LEFT JOIN quy_trinh_buoc denb ON lc.den_buoc_id = denb.id
                                WHERE lc.ho_so_id = {$id}
                                ORDER BY lc.thoi_gian_chuyen DESC, lc.id DESC")->fetchAll(PDO::FETCH_ASSOC);

        // 3. Danh sách ý kiến phối hợp liên phòng / liên sở
        $yKienPhoiHop = $db->query("SELECT yk.*, dvyc.ten_don_vi as ten_don_vi_yeu_cau, dvph.ten_don_vi as ten_don_vi_phoi_hop,
                                           ndyc.ho_ten as ten_nguoi_yeu_cau, ndtl.ho_ten as ten_nguoi_tra_loi
                                    FROM ho_so_y_kien_phoi_hop yk
                                    LEFT JOIN don_vi dvyc ON yk.don_vi_yeu_cau_id = dvyc.id
                                    LEFT JOIN don_vi dvph ON yk.don_vi_phoi_hop_id = dvph.id
                                    LEFT JOIN nguoi_dung ndyc ON yk.nguoi_yeu_cau_id = ndyc.id
                                    LEFT JOIN nguoi_dung ndtl ON yk.nguoi_tra_loi_id = ndtl.id
                                    WHERE yk.ho_so_id = {$id}
                                    ORDER BY yk.id DESC")->fetchAll(PDO::FETCH_ASSOC);

        // 4. Lịch sử yêu cầu bổ sung
        $boSungList = $db->query("SELECT bs.*, nd.ho_ten as ten_nguoi_yeu_cau
                                  FROM ho_so_bo_sung bs
                                  LEFT JOIN nguoi_dung nd ON bs.nguoi_yeu_cau_id = nd.id
                                  WHERE bs.ho_so_id = {$id}
                                  ORDER BY bs.id DESC")->fetchAll(PDO::FETCH_ASSOC);

        // 5. Danh sách chuyên viên cùng đơn vị (phục vụ LD_PHONG phân công kèm số lượng việc đang làm)
        $userDonViId = (int)($user['don_vi_hien_tai_id'] ?? $user['don_vi_chinh_id'] ?? 0);
        $chuyenVienList = $db->query("SELECT u.id, u.ho_ten, u.chuc_danh_id, 
                                             COUNT(hs.id) as so_ho_so_dang_xu_ly
                                      FROM nguoi_dung u
                                      LEFT JOIN ho_so hs ON u.id = hs.chuyen_vien_thu_ly_id AND hs.trang_thai = 'DANG_XU_LY' AND hs.is_deleted = 0
                                      WHERE u.don_vi_chinh_id = {$userDonViId} AND u.trang_thai = 1
                                      GROUP BY u.id, u.ho_ten, u.chuc_danh_id
                                      ORDER BY so_ho_so_dang_xu_ly ASC, u.ho_ten ASC")->fetchAll(PDO::FETCH_ASSOC);

        // 6. Danh sách các bước tiếp theo trong quy trình
        $nextSteps = [];
        if (!empty($hoSo['quy_trinh_id'])) {
            $currStepId = $hoSo['buoc_hien_tai_id'] ? (int)$hoSo['buoc_hien_tai_id'] : 0;
            $nextSteps = $db->query("SELECT qb.*, dv.ten_don_vi as ten_don_vi_xu_ly
                                     FROM quy_trinh_chuyen_tiep ct
                                     JOIN quy_trinh_buoc qb ON ct.den_buoc_id = qb.id
                                     LEFT JOIN don_vi dv ON qb.don_vi_xu_ly_id = dv.id
                                     WHERE ct.quy_trinh_id = {$hoSo['quy_trinh_id']} AND ct.tu_buoc_id = {$currStepId}
                                     ORDER BY qb.stt_buoc ASC")->fetchAll(PDO::FETCH_ASSOC);

            if (empty($nextSteps)) {
                $currStt = (int)$db->query("SELECT stt_buoc FROM quy_trinh_buoc WHERE id = {$currStepId}")->fetchColumn();
                $nextSteps = $db->query("SELECT qb.*, dv.ten_don_vi as ten_don_vi_xu_ly
                                         FROM quy_trinh_buoc qb
                                         LEFT JOIN don_vi dv ON qb.don_vi_xu_ly_id = dv.id
                                         WHERE qb.quy_trinh_id = {$hoSo['quy_trinh_id']} AND qb.stt_buoc > {$currStt}
                                         ORDER BY qb.stt_buoc ASC LIMIT 1")->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        // 7. Danh sách cơ quan / đơn vị để chọn khi gửi lấy ý kiến phối hợp
        $donViList = $db->query("SELECT id, ten_don_vi, ma_dinh_danh, loai_don_vi 
                                 FROM don_vi 
                                 WHERE id != {$userDonViId} AND trang_thai = 1 
                                 ORDER BY loai_don_vi ASC, ten_don_vi ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Parse dữ liệu E-form
        $eformData = [];
        if (!empty($hoSo['du_lieu_eform'])) {
            $eformData = json_decode($hoSo['du_lieu_eform'], true) ?: [];
        }

        // Kiểm tra toàn vẹn file
        $fileIntegrity = WorkflowEngine::verifyFileIntegrity($id);

        $isReadOnly = ((int)$hoSo['don_vi_hien_tai_id'] !== $userDonViId);

        return $this->view('admin/thu_ly/detail', [
            'pageTitle' => "Thụ lý hồ sơ: {$hoSo['ma_ho_so']}",
            'hoSo' => $hoSo,
            'taiLieuList' => $taiLieuList,
            'timeline' => $timeline,
            'yKienPhoiHop' => $yKienPhoiHop,
            'boSungList' => $boSungList,
            'chuyenVienList' => $chuyenVienList,
            'nextSteps' => $nextSteps,
            'donViList' => $donViList,
            'eformData' => $eformData,
            'fileIntegrity' => $fileIntegrity,
            'isReadOnly' => $isReadOnly,
            'currentUser' => $user,
        ]);
    }

    /**
     * Phân công chuyên viên thụ lý (Lãnh đạo phòng)
     */
    public function phanCong(Request $request, string|int $id): Response
    {
        $id = (int)$id;
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        $chuyenVienId = (int)$request->input('chuyen_vien_id');
        $yKien = trim((string)$request->input('y_kien', ''));

        if ($chuyenVienId <= 0) {
            Session::flash('flash_error', 'Vui lòng chọn chuyên viên thụ lý.');
            return $this->redirect("/admin/thu-ly/{$id}");
        }

        $res = WorkflowEngine::phanCong($id, (int)$user['id'], $chuyenVienId, $yKien);

        if ($res['success']) {
            Session::flash('flash_success', $res['message']);
        } else {
            Session::flash('flash_error', $res['message']);
        }

        return $this->redirect("/admin/thu-ly/{$id}");
    }

    /**
     * Chuyển bước kế tiếp trong quy trình
     */
    public function chuyenTiep(Request $request, string|int $id): Response
    {
        $id = (int)$id;
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        $denBuocId = $request->input('den_buoc_id') ? (int)$request->input('den_buoc_id') : null;
        $yKien = trim((string)$request->input('noi_dung_y_kien', ''));

        $res = WorkflowEngine::chuyenTiep($id, (int)$user['id'], $denBuocId, $yKien);

        if ($res['success']) {
            Session::flash('flash_success', $res['message']);
        } else {
            Session::flash('flash_error', $res['message']);
        }

        return $this->redirect("/admin/thu-ly/{$id}");
    }

    /**
     * Trả lại hồ sơ cho bước trước đó
     */
    public function traLai(Request $request, string|int $id): Response
    {
        $id = (int)$id;
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        $lyDo = trim((string)$request->input('ly_do', ''));
        $veBuocId = $request->input('ve_buoc_id') ? (int)$request->input('ve_buoc_id') : null;

        if (empty($lyDo)) {
            Session::flash('flash_error', 'Vui lòng nhập lý do trả lại hồ sơ.');
            return $this->redirect("/admin/thu-ly/{$id}");
        }

        $res = WorkflowEngine::traLai($id, (int)$user['id'], $lyDo, $veBuocId);

        if ($res['success']) {
            Session::flash('flash_success', $res['message']);
        } else {
            Session::flash('flash_error', $res['message']);
        }

        return $this->redirect("/admin/thu-ly/{$id}");
    }

    /**
     * Yêu cầu công dân bổ sung giấy tờ (Tạm dừng SLA)
     */
    public function yeuCauBoSung(Request $request, string|int $id): Response
    {
        $id = (int)$id;
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        $lyDo = trim((string)$request->input('ly_do_bo_sung', ''));
        $danhSach = trim((string)$request->input('danh_sach_giay_to', ''));
        $hanNgay = (int)$request->input('han_ngay', 30);

        if (empty($lyDo)) {
            Session::flash('flash_error', 'Vui lòng nhập lý do yêu cầu bổ sung.');
            return $this->redirect("/admin/thu-ly/{$id}");
        }

        $res = WorkflowEngine::yeuCauBoSung($id, (int)$user['id'], $lyDo, $danhSach, $hanNgay);

        if ($res['success']) {
            Session::flash('flash_success', $res['message']);
        } else {
            Session::flash('flash_error', $res['message']);
        }

        return $this->redirect("/admin/thu-ly/{$id}");
    }

    /**
     * Khôi phục thụ lý sau khi công dân nộp bổ sung
     */
    public function tiepTucSauBoSung(Request $request, string|int $id): Response
    {
        $id = (int)$id;
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        $ghiChu = trim((string)$request->input('ghi_chu', ''));

        $res = WorkflowEngine::tiepTucSauBoSung($id, (int)$user['id'], $ghiChu);

        if ($res['success']) {
            Session::flash('flash_success', $res['message']);
        } else {
            Session::flash('flash_error', $res['message']);
        }

        return $this->redirect("/admin/thu-ly/{$id}");
    }

    /**
     * Gửi yêu cầu lấy ý kiến phối hợp liên phòng / liên sở
     */
    public function layYKien(Request $request, string|int $id): Response
    {
        $id = (int)$id;
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        $donViPhoiHopId = (int)$request->input('don_vi_phoi_hop_id');
        $noiDung = trim((string)$request->input('noi_dung_yeu_cau', ''));
        $hanTraLoi = trim((string)$request->input('han_tra_loi', ''));

        if ($donViPhoiHopId <= 0 || empty($noiDung) || empty($hanTraLoi)) {
            Session::flash('flash_error', 'Vui lòng điền đầy đủ đơn vị phối hợp, nội dung và hạn trả lời.');
            return $this->redirect("/admin/thu-ly/{$id}");
        }

        $res = WorkflowEngine::layYKien($id, (int)$user['id'], $donViPhoiHopId, $noiDung, $hanTraLoi . ' 17:00:00');

        if ($res['success']) {
            Session::flash('flash_success', $res['message']);
        } else {
            Session::flash('flash_error', $res['message']);
        }

        return $this->redirect("/admin/thu-ly/{$id}");
    }

    /**
     * Đơn vị phối hợp phản hồi ý kiến
     */
    public function traLoiYKien(Request $request, string|int $id): Response
    {
        $id = (int)$id;
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        $yKienId = (int)$request->input('y_kien_id');
        $noiDungTraLoi = trim((string)$request->input('noi_dung_tra_loi', ''));

        if ($yKienId <= 0 || empty($noiDungTraLoi)) {
            Session::flash('flash_error', 'Vui lòng nhập nội dung phản hồi ý kiến.');
            return $this->redirect("/admin/thu-ly/{$id}");
        }

        $res = WorkflowEngine::traLoiYKien($yKienId, (int)$user['id'], $noiDungTraLoi);

        if ($res['success']) {
            Session::flash('flash_success', $res['message']);
        } else {
            Session::flash('flash_error', $res['message']);
        }

        return $this->redirect("/admin/thu-ly/{$id}");
    }

    /**
     * Thu hồi hồ sơ đã chuyển
     */
    public function thuHoi(Request $request, string|int $id): Response
    {
        $id = (int)$id;
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        $lyDo = trim((string)$request->input('ly_do_thu_hoi', ''));

        $res = WorkflowEngine::thuHoi($id, (int)$user['id'], $lyDo);

        if ($res['success']) {
            Session::flash('flash_success', $res['message']);
        } else {
            Session::flash('flash_error', $res['message']);
        }

        return $this->redirect("/admin/thu-ly/{$id}");
    }

    /**
     * Lưu dự thảo kết quả / biên bản thẩm định (từ template mẫu có biến)
     */
    public function luuDuThao(Request $request, string|int $id): Response
    {
        $id = (int)$id;
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        $db = Database::connection();

        $tieuDe = trim((string)$request->input('tieu_de_du_thao', 'Tờ trình thẩm định hồ sơ'));
        $noiDungHtml = (string)$request->input('noi_dung_du_thao', '');

        if (empty($noiDungHtml)) {
            Session::flash('flash_error', 'Nội dung dự thảo không được để trống.');
            return $this->redirect("/admin/thu-ly/{$id}");
        }

        // Lưu vào ho_so_tai_lieu với loại DU_THAO
        $fileName = 'du_thao_' . uniqid() . '.html';
        $dir = dirname(__DIR__, 3) . '/storage/uploads/du_thao';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filePath = "{$dir}/{$fileName}";
        file_put_contents($filePath, $noiDungHtml);
        $sha256 = hash_file('sha256', $filePath);

        $stmt = $db->prepare("INSERT INTO ho_so_tai_lieu (
            ho_so_id, ten_goc, ten_luu, duong_dan, mime_type, kich_thuoc_byte, sha256, loai_tai_lieu, so_hoa_boi_id, ngay_so_hoa, created_at
        ) VALUES (
            :hid, :tg, :tl, :dd, 'text/html', :size, :sha, 'DU_THAO', :uid, NOW(), NOW()
        )");

        $stmt->execute([
            ':hid' => $id,
            ':tg' => $tieuDe . '.html',
            ':tl' => $fileName,
            ':dd' => $filePath,
            ':size' => filesize($filePath),
            ':sha' => $sha256,
            ':uid' => $user['id'],
        ]);

        AuditService::log((int)$user['id'], 'TAO_DU_THAO', 'ho_so_tai_lieu', (int)$db->lastInsertId(), [], ['tieu_de' => $tieuDe]);
        Session::flash('flash_success', "Đã lưu bản dự thảo [{$tieuDe}] thành công.");

        return $this->redirect("/admin/thu-ly/{$id}");
    }

    /**
     * API AJAX đối soát C06 & Đất đai / Đăng kiểm qua LgspGatewayMock
     */
    public function apiVerifyLgsp(Request $request): Response
    {
        $loai = strtolower((string)$request->input('loai', $request->input('type', 'c06')));

        if ($loai === 'c06') {
            $cccd = (string)$request->input('so_cccd', '');
            $hoTen = (string)$request->input('ho_ten', '');
            $res = LgspGatewayMock::verifyCitizen($cccd, $hoTen);
            return $this->json($res);
        }

        if ($loai === 'land') {
            $soThua = (string)$request->input('so_thua', '');
            $soTo = (string)$request->input('so_to', '');
            $chuSuDung = (string)$request->input('chu_su_dung', '');
            $res = LgspGatewayMock::verifyLandData($soThua, $soTo, $chuSuDung);
            return $this->json($res);
        }

        if ($loai === 'vehicle') {
            $bienSo = (string)$request->input('bien_so', '');
            $chuXe = (string)$request->input('chu_xe', '');
            $res = LgspGatewayMock::verifyVehicleRegistration($bienSo, $chuXe);
            return $this->json($res);
        }

        return $this->json(['success' => false, 'message' => 'Loại đối soát không hợp lệ.']);
    }
}
