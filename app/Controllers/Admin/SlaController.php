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
use App\Services\ChuKySoService;
use App\Services\SlaEngine;
use App\Services\ThongBaoService;
use PDO;

/**
 * Class SlaController – Giám sát SLA & Xử lý Văn bản xin lỗi Mẫu số 04 (TT 01/2018/TT-VPCP)
 * Vai trò thẩm quyền ký Mẫu 04: LD_CO_QUAN (Lãnh đạo Sở / Lãnh đạo UBND Quận/Huyện / Lãnh đạo TP)
 */
class SlaController extends Controller
{
    /**
     * Danh sách hồ sơ quá hạn SLA (màu ĐỎ) cần ban hành Văn bản xin lỗi
     */
    public function danhSachQuaHan(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $roles = AuthService::userRoles($user['id']);
        if (!in_array('LD_CO_QUAN', $roles, true) && !in_array('LD_PHONG', $roles, true) && !in_array('CHUYEN_VIEN', $roles, true)) {
            Session::flash('error', 'Bạn không có quyền truy cập màn hình giám sát SLA.');
            return $this->redirect('/admin/thu-ly');
        }

        $db = Database::connection();
        $donViId = (int)($user['don_vi_hien_tai_id'] ?? $user['don_vi_chinh_id'] ?? 0);
        $q = trim((string)$request->get('q', ''));

        // Query hồ sơ có SLA ĐỎ và chưa hoàn thành
        $sql = "SELECT h.*, h.nguoi_nop_ho_ten as ho_ten_nguoi_nop, h.nguoi_nop_sdt as so_dien_thoai_nguoi_nop,
                       t.ten_tthc, d.ten_don_vi, qb.ten_buoc,
                       cv.ho_ten as ten_chuyen_vien,
                       xl.id as vbxl_id, xl.so_van_ban as vbxl_so, xl.trang_thai as vbxl_trang_thai, xl.ngay_ky as vbxl_ngay_ky
                FROM ho_so h
                JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
                JOIN don_vi d ON h.don_vi_tiep_nhan_id = d.id
                LEFT JOIN quy_trinh_buoc qb ON h.buoc_hien_tai_id = qb.id
                LEFT JOIN nguoi_dung cv ON h.chuyen_vien_thu_ly_id = cv.id
                LEFT JOIN van_ban_xin_loi xl ON h.id = xl.ho_so_id
                WHERE h.is_deleted = 0
                  AND h.sla_mau = 'DO'
                  AND h.trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA', 'TU_CHOI', 'DA_HUY')";

        $params = [];
        if (!in_array('LD_CO_QUAN', $roles, true) && !in_array('SYSADMIN', $roles, true)) {
            $sql .= " AND h.don_vi_hien_tai_id = :dvid";
            $params[':dvid'] = $donViId;
        }

        if (!empty($q)) {
            $sql .= " AND (h.ma_ho_so LIKE :q OR h.nguoi_nop_ho_ten LIKE :q OR t.ten_tthc LIKE :q)";
            $params[':q'] = "%{$q}%";
        }

        $sql .= " ORDER BY h.ngay_hen_tra ASC LIMIT 100";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $danhSach = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Đếm thống kê
        $totalQuaHan = count($danhSach);
        $daCoMau04 = 0;
        $chuaCoMau04 = 0;

        foreach ($danhSach as $row) {
            if (!empty($row['vbxl_id']) && $row['vbxl_trang_thai'] === 'DA_KY') {
                $daCoMau04++;
            } else {
                $chuaCoMau04++;
            }
        }

        return $this->view('admin/sla/danh_sach_qua_han', [
            'title' => 'Giám sát Hồ sơ Quá hạn & Lập Văn bản xin lỗi Mẫu 04',
            'user' => $user,
            'roles' => $roles,
            'danhSach' => $danhSach,
            'totalQuaHan' => $totalQuaHan,
            'daCoMau04' => $daCoMau04,
            'chuaCoMau04' => $chuaCoMau04,
            'q' => $q,
        ]);
    }

    /**
     * Màn hình soạn thảo Văn bản xin lỗi Mẫu 04 (Thông tư 01/2018/TT-VPCP)
     */
    public function formMau04(Request $request, int $hoSoId): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $roles = AuthService::userRoles($user['id']);
        if (!in_array('LD_CO_QUAN', $roles, true) && !in_array('LD_PHONG', $roles, true) && !in_array('CHUYEN_VIEN', $roles, true)) {
            Session::flash('error', 'Bạn không có quyền thực hiện chức năng này.');
            return $this->redirect('/admin/sla/danh-sach-qua-han');
        }

        $db = Database::connection();
        $stmt = $db->prepare("SELECT h.*, h.nguoi_nop_ho_ten as ho_ten_nguoi_nop, h.nguoi_nop_sdt as so_dien_thoai_nguoi_nop,
                                     h.nguoi_nop_cccd as so_cccd_nguoi_nop, h.nguoi_nop_dia_chi as dia_chi_nguoi_nop,
                                     t.ten_tthc, t.ma_tthc, d.ten_don_vi, d.ma_dinh_danh,
                                     cv.ho_ten as ten_chuyen_vien,
                                     xl.id as vbxl_id, xl.so_van_ban as vbxl_so, xl.ly_do_qua_han as vbxl_ly_do,
                                     xl.ngay_hen_moi as vbxl_ngay_hen_moi, xl.trang_thai as vbxl_trang_thai
                              FROM ho_so h
                              JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
                              JOIN don_vi d ON h.don_vi_tiep_nhan_id = d.id
                              LEFT JOIN nguoi_dung cv ON h.chuyen_vien_thu_ly_id = cv.id
                              LEFT JOIN van_ban_xin_loi xl ON h.id = xl.ho_so_id
                              WHERE h.id = :id AND h.is_deleted = 0");
        $stmt->execute([':id' => $hoSoId]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            Session::flash('error', 'Không tìm thấy hồ sơ yêu cầu.');
            return $this->redirect('/admin/sla/danh-sach-qua-han');
        }

        // Gợi ý ngày hẹn mới: +3 ngày làm việc kể từ thời điểm hiện tại
        $ngayHenMoiGoiY = SlaEngine::calculateNgayHenTra(date('Y-m-d H:i:s'), 3);

        // Gợi ý số văn bản
        $namHienTai = date('Y');
        $soGoiY = "XL-" . date('md') . "/UBND-VP";

        return $this->view('admin/sla/form_mau_04', [
            'title' => 'Lập Phiếu Xin lỗi & Hẹn lại Ngày trả kết quả (Mẫu số 04)',
            'user' => $user,
            'roles' => $roles,
            'hoSo' => $hoSo,
            'ngayHenMoiGoiY' => $ngayHenMoiGoiY,
            'soGoiY' => $soGoiY,
        ]);
    }

    /**
     * Xử lý ký số Văn bản xin lỗi Mẫu 04 (Lãnh đạo cơ quan thực hiện)
     */
    public function kySoMau04(Request $request, int $hoSoId): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Phiên làm việc đã hết hạn.'], 401);
        }

        // Tuân thủ triệt để Rule #6 & #12: Chỉ LD_CO_QUAN ký số, SYSADMIN bị chặn 100%
        $roles = AuthService::userRoles($user['id']);
        if (!in_array('LD_CO_QUAN', $roles, true)) {
            return $this->json([
                'success' => false,
                'message' => 'Từ chối quyền: Chỉ Lãnh đạo cơ quan (LD_CO_QUAN) mới có thẩm quyền ký ban hành Văn bản xin lỗi Mẫu số 04 theo quy định.'
            ], 403);
        }

        $pin = trim((string)$request->post('pin', ''));
        $soVanBan = trim((string)$request->post('so_van_ban', ''));
        $lyDoQuaHan = trim((string)$request->post('ly_do_qua_han', ''));
        $ngayHenMoi = trim((string)$request->post('ngay_hen_moi', ''));

        if (empty($pin)) {
            return $this->json(['success' => false, 'message' => 'Vui lòng nhập mã PIN SmartCA.'], 422);
        }
        if (empty($soVanBan) || empty($lyDoQuaHan) || empty($ngayHenMoi)) {
            return $this->json(['success' => false, 'message' => 'Vui lòng điền đầy đủ số văn bản, lý do quá hạn và ngày hẹn mới.'], 422);
        }

        // Mô phỏng xác thực PIN SmartCA (Mã PIN chuẩn demo: 123456)
        if ($pin !== '123456') {
            return $this->json(['success' => false, 'message' => 'Mã PIN SmartCA không chính xác.'], 422);
        }

        $db = Database::connection();
        $stmtHs = $db->prepare("SELECT h.*, t.ten_tthc, d.ten_don_vi 
                                FROM ho_so h 
                                JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id 
                                JOIN don_vi d ON h.don_vi_tiep_nhan_id = d.id 
                                WHERE h.id = :id AND h.is_deleted = 0");
        $stmtHs->execute([':id' => $hoSoId]);
        $hoSo = $stmtHs->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            return $this->json(['success' => false, 'message' => 'Không tìm thấy hồ sơ.'], 404);
        }

        $db->beginTransaction();
        try {
            // 1. Tạo hoặc cập nhật bản ghi van_ban_xin_loi
            $stmtCheck = $db->prepare("SELECT id FROM van_ban_xin_loi WHERE ho_so_id = :id");
            $stmtCheck->execute([':id' => $hoSoId]);
            $existingId = $stmtCheck->fetchColumn();

            if ($existingId) {
                $stmtUpdate = $db->prepare("UPDATE van_ban_xin_loi 
                                            SET so_van_ban = :so, 
                                                nguoi_ky_id = :uid, 
                                                ly_do_qua_han = :lydo, 
                                                ngay_hen_moi = :henmoi, 
                                                ngay_ky = NOW(), 
                                                trang_thai = 'DA_KY' 
                                            WHERE id = :id");
                $stmtUpdate->execute([
                    ':so' => $soVanBan,
                    ':uid' => $user['id'],
                    ':lydo' => $lyDoQuaHan,
                    ':henmoi' => $ngayHenMoi,
                    ':id' => $existingId,
                ]);
                $vbxlId = (int)$existingId;
            } else {
                $stmtInsert = $db->prepare("INSERT INTO van_ban_xin_loi 
                    (ho_so_id, so_van_ban, nguoi_ky_id, ly_do_qua_han, ngay_hen_moi, ngay_ky, trang_thai) 
                    VALUES (:hsid, :so, :uid, :lydo, :henmoi, NOW(), 'DA_KY')");
                $stmtInsert->execute([
                    ':hsid' => $hoSoId,
                    ':so' => $soVanBan,
                    ':uid' => $user['id'],
                    ':lydo' => $lyDoQuaHan,
                    ':henmoi' => $ngayHenMoi,
                ]);
                $vbxlId = (int)$db->lastInsertId();
            }

            // 2. Cập nhật ngày hẹn trả mới cho hồ sơ
            $stmtUpdateHs = $db->prepare("UPDATE ho_so SET ngay_hen_tra = :henmoi, updated_at = NOW() WHERE id = :id");
            $stmtUpdateHs->execute([
                ':henmoi' => $ngayHenMoi,
                ':id' => $hoSoId,
            ]);

            // 3. Ghi vết kiểm toán (Audit Trail)
            AuditService::log(
                action: 'SIGN_MAU_04',
                table: 'van_ban_xin_loi',
                recordId: $vbxlId,
                oldData: null,
                newData: [
                    'ho_so_id' => $hoSoId,
                    'ma_ho_so' => $hoSo['ma_ho_so'],
                    'so_van_ban' => $soVanBan,
                    'ngay_hen_moi' => $ngayHenMoi,
                    'nguoi_ky' => $user['ho_ten'],
                ],
                userId: (int)$user['id']
            );

            // 4. Phát sự kiện thông báo đa kênh Outbox (SMS Brandname, Zalo ZNS, Email, In-app)
            ThongBaoService::guiSuKien($hoSo, 'XIN_LOI_QUA_HAN', [
                'so_van_ban' => $soVanBan,
                'ngay_hen_moi' => $ngayHenMoi,
            ]);

            $db->commit();

            return $this->json([
                'success' => true,
                'message' => "Đã ký số thành công Văn bản xin lỗi Mẫu số 04 ({$soVanBan}). Hồ sơ đã được mở khóa để tiếp tục giải quyết.",
                'redirect' => "/admin/sla/in-mau-04/{$hoSoId}",
            ]);
        } catch (\Throwable $e) {
            $db->rollBack();
            return $this->json([
                'success' => false,
                'message' => 'Lỗi khi ký số văn bản xin lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bản in chuẩn A4 Văn bản xin lỗi Mẫu số 04 (Thông tư 01/2018/TT-VPCP)
     */
    public function inMau04(Request $request, int $hoSoId): Response
    {
        $db = Database::connection();
        $stmt = $db->prepare("SELECT h.*, h.nguoi_nop_ho_ten as ho_ten_nguoi_nop, h.nguoi_nop_sdt as so_dien_thoai_nguoi_nop,
                                     h.nguoi_nop_cccd as so_cccd_nguoi_nop, h.nguoi_nop_dia_chi as dia_chi_nguoi_nop,
                                     t.ten_tthc, t.ma_tthc, d.ten_don_vi, d.ma_dinh_danh,
                                     xl.so_van_ban as vbxl_so, xl.ly_do_qua_han as vbxl_ly_do,
                                     xl.ngay_hen_moi as vbxl_ngay_hen_moi, xl.ngay_ky as vbxl_ngay_ky,
                                     u.ho_ten as ten_nguoi_ky, cd.ten_chuc_danh
                              FROM ho_so h
                              JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
                              JOIN don_vi d ON h.don_vi_tiep_nhan_id = d.id
                              JOIN van_ban_xin_loi xl ON h.id = xl.ho_so_id
                              LEFT JOIN nguoi_dung u ON xl.nguoi_ky_id = u.id
                              LEFT JOIN chuc_danh cd ON u.chuc_danh_id = cd.id
                              WHERE h.id = :id AND xl.trang_thai = 'DA_KY'");
        $stmt->execute([':id' => $hoSoId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            Session::flash('error', 'Hồ sơ chưa có Văn bản xin lỗi được ký số.');
            return $this->redirect("/admin/sla/van-ban-xin-loi/{$hoSoId}");
        }

        return $this->view('admin/sla/in_mau_04', [
            'title' => 'Phiếu xin lỗi và hẹn lại ngày trả kết quả (Mẫu số 04)',
            'data' => $data,
        ]);
    }
}
