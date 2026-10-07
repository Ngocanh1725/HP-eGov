<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;
use PDO;

/**
 * Class ThongBaoController – Quản trị Mẫu thông báo đa kênh & Hộp thư mô phỏng (§2.2 Báo cáo Kiến trúc)
 */
class ThongBaoController extends Controller
{
    /**
     * Màn hình Quản trị Mẫu Thông báo đa kênh
     */
    public function mauThongBao(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $roles = AuthService::userRoles($user['id']);
        if (!in_array('SYSADMIN', $roles, true) && !in_array('LD_CO_QUAN', $roles, true) && !in_array('LD_PHONG', $roles, true)) {
            Session::flash('error', 'Bạn không có quyền quản trị mẫu thông báo.');
            return $this->redirect('/admin/dashboard');
        }

        $db = Database::connection();
        $stmt = $db->query("SELECT * FROM mau_thong_bao ORDER BY id ASC");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/he_thong/mau_thong_bao', [
            'title' => 'Quản trị Mẫu Thông báo Đa kênh (SMS, Zalo ZNS, Email)',
            'user' => $user,
            'roles' => $roles,
            'templates' => $templates,
        ]);
    }

    /**
     * Cập nhật cấu hình & nội dung Mẫu thông báo
     */
    public function updateMauThongBao(Request $request, int $id): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Phiên làm việc đã hết hạn.'], 401);
        }

        $roles = AuthService::userRoles($user['id']);
        if (!in_array('SYSADMIN', $roles, true) && !in_array('LD_CO_QUAN', $roles, true)) {
            return $this->json(['success' => false, 'message' => 'Từ chối quyền truy cập.'], 403);
        }

        $db = Database::connection();
        $stmt = $db->prepare("SELECT id FROM mau_thong_bao WHERE id = :id");
        $stmt->execute([':id' => $id]);
        if (!$stmt->fetch()) {
            return $this->json(['success' => false, 'message' => 'Không tìm thấy mẫu thông báo.'], 404);
        }

        $tieuDe = trim((string)$request->post('tieu_de_mau', ''));
        $smsContent = trim((string)$request->post('noi_dung_sms', ''));
        $zaloContent = trim((string)$request->post('noi_dung_zalo', ''));
        $emailContent = trim((string)$request->post('noi_dung_email', ''));
        $inappContent = trim((string)$request->post('noi_dung_inapp', ''));

        $smsActive = $request->post('kenh_sms_active') ? 1 : 0;
        $zaloActive = $request->post('kenh_zalo_active') ? 1 : 0;
        $emailActive = $request->post('kenh_email_active') ? 1 : 0;
        $inappActive = $request->post('kenh_inapp_active') ? 1 : 0;

        $updateStmt = $db->prepare("UPDATE mau_thong_bao 
            SET tieu_de_mau = :td,
                noi_dung_sms = :sms,
                noi_dung_zalo = :zalo,
                noi_dung_email = :email,
                noi_dung_inapp = :inapp,
                kenh_sms_active = :sms_act,
                kenh_zalo_active = :zalo_act,
                kenh_email_active = :email_act,
                kenh_inapp_active = :inapp_act,
                updated_at = NOW()
            WHERE id = :id");

        $updateStmt->execute([
            ':td' => $tieuDe,
            ':sms' => $smsContent,
            ':zalo' => $zaloContent,
            ':email' => $emailContent,
            ':inapp' => $inappContent,
            ':sms_act' => $smsActive,
            ':zalo_act' => $zaloActive,
            ':email_act' => $emailActive,
            ':inapp_act' => $inappActive,
            ':id' => $id,
        ]);

        return $this->json(['success' => true, 'message' => 'Cập nhật mẫu thông báo thành công!']);
    }

    /**
     * Màn hình Hộp thư mô phỏng (Giả lập Gateway SMS Brandname & Zalo ZNS)
     */
    public function hopThuMoPhong(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $db = Database::connection();

        // 1. Lấy danh sách tin trong hàng đợi Outbox
        $sql = "SELECT q.*, h.ma_ho_so, h.nguoi_nop_ho_ten 
                FROM hang_doi_thong_bao q 
                LEFT JOIN ho_so h ON q.ho_so_id = h.id 
                ORDER BY q.id DESC LIMIT 100";
        $messages = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        // 2. Thống kê
        $totalSms = 0;
        $totalZalo = 0;
        $totalEmail = 0;
        $totalChoGui = 0;

        foreach ($messages as $m) {
            if ($m['kenh'] === 'SMS') $totalSms++;
            if ($m['kenh'] === 'ZALO') $totalZalo++;
            if ($m['kenh'] === 'EMAIL') $totalEmail++;
            if ($m['trang_thai'] === 'CHO_GUI') $totalChoGui++;
        }

        return $this->view('admin/he_thong/hop_thu_mo_phong', [
            'title' => 'Hộp thư Mô phỏng SMS Brandname & Zalo ZNS',
            'user' => $user,
            'messages' => $messages,
            'totalSms' => $totalSms,
            'totalZalo' => $totalZalo,
            'totalEmail' => $totalEmail,
            'totalChoGui' => $totalChoGui,
        ]);
    }
}
