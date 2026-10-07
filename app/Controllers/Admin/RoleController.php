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
use PDO;

/**
 * RoleController – Phân hệ PH11 Quản trị Vai trò & Ma trận phân quyền RBAC
 * Chặn cứng: Tuyệt đối không cho phép gán quyền hoso.* và kyso.* cho vai trò SYSADMIN
 */
class RoleController extends Controller
{
    /**
     * Màn hình MA TRẬN PHÂN QUYỀN dạng lưới Checkbox (GET /admin/he-thong/vai-tro)
     */
    public function matrix(Request $request): Response
    {
        $db = Database::connection();

        // 1. Lấy danh sách 7 vai trò
        $roles = $db->query("SELECT * FROM vai_tro ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

        // 2. Lấy danh sách tất cả các quyền, gom theo nhóm
        $permissions = $db->query("SELECT * FROM quyen ORDER BY nhom_quyen ASC, ma_quyen ASC")->fetchAll(PDO::FETCH_ASSOC);
        $groupedPermissions = [];
        foreach ($permissions as $p) {
            $group = $p['nhom_quyen'] ?? 'KHAC';
            $groupedPermissions[$group][] = $p;
        }

        // 3. Lấy ma trận gán quyền hiện tại (vai_tro_quyen)
        $pivot = $db->query("SELECT vai_tro_id, quyen_id FROM vai_tro_quyen")->fetchAll(PDO::FETCH_ASSOC);
        $rolePermMatrix = [];
        foreach ($pivot as $row) {
            $rolePermMatrix[$row['vai_tro_id']][$row['quyen_id']] = true;
        }

        return $this->view('admin/roles/matrix', [
            'pageTitle' => 'Ma trận Phân quyền RBAC | HP-eGov',
            'roles' => $roles,
            'groupedPermissions' => $groupedPermissions,
            'matrix' => $rolePermMatrix,
        ]);
    }

    /**
     * Cập nhật toàn bộ ma trận quyền (POST /admin/he-thong/vai-tro/ma-tran)
     */
    public function updateMatrix(Request $request): Response
    {
        $db = Database::connection();
        $submittedMatrix = (array)$request->input('matrix', []); // role_id => [perm_id => 1]

        // Lấy thông tin các vai trò và quyền để kiểm tra chặn cứng
        $roles = $db->query("SELECT id, ma_vai_tro FROM vai_tro")->fetchAll(PDO::FETCH_KEY_PAIR);
        $perms = $db->query("SELECT id, ma_quyen FROM quyen")->fetchAll(PDO::FETCH_KEY_PAIR);

        // Lấy dữ liệu cũ để ghi audit
        $oldPivot = $db->query("SELECT vai_tro_id, quyen_id FROM vai_tro_quyen")->fetchAll(PDO::FETCH_ASSOC);

        $insertedCount = 0;
        $blockedCount = 0;

        $db->beginTransaction();
        try {
            // Xóa sạch bảng liên kết cũ
            $db->exec("TRUNCATE TABLE vai_tro_quyen");

            $insertStmt = $db->prepare("INSERT INTO vai_tro_quyen (vai_tro_id, quyen_id) VALUES (:vid, :qid)");

            foreach ($submittedMatrix as $roleId => $permList) {
                $rId = (int)$roleId;
                $roleCode = $roles[$rId] ?? '';

                if (!is_array($permList)) {
                    continue;
                }

                foreach (array_keys($permList) as $permId) {
                    $pId = (int)$permId;
                    $permCode = $perms[$pId] ?? '';

                    // CHẶN CỨNG BẤT BIẾN:
                    // Tuyệt đối không cho phép gán quyền hoso.* hoặc kyso.* cho SYSADMIN!
                    if ($roleCode === 'SYSADMIN') {
                        if (str_starts_with($permCode, 'hoso.') || str_starts_with($permCode, 'kyso.') || str_starts_with($permCode, 'thu_ly.')) {
                            $blockedCount++;
                            continue; // Bỏ qua, không lưu
                        }
                    }

                    $insertStmt->execute([':vid' => $rId, ':qid' => $pId]);
                    $insertedCount++;
                }
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Lỗi khi cập nhật ma trận quyền: ' . $e->getMessage());
            return $this->redirect('/admin/he-thong/vai-tro');
        }

        // Làm mới cache session cho user hiện tại
        AuthService::refresh();

        // Ghi Audit log
        AuditService::log(
            AuthService::id(),
            'CAP_NHAT_MA_TRAN_QUYEN',
            'vai_tro_quyen',
            null,
            ['total_old' => count($oldPivot)],
            ['total_new' => $insertedCount, 'blocked_sysadmin_perms' => $blockedCount]
        );

        $msg = "Đã cập nhật thành công Ma trận phân quyền ({$insertedCount} liên kết).";
        if ($blockedCount > 0) {
            $msg .= " Đã tự động kích hoạt luật bảo mật chặn {$blockedCount} quyền nghiệp vụ (hoso/kyso) khỏi SYSADMIN.";
        }

        Session::flash('success', $msg);
        return $this->redirect('/admin/he-thong/vai-tro');
    }
}
