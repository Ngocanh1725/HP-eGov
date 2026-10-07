<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Services\AuthService;
use PDO;

/**
 * DataScopeService – Dịch vụ áp dụng phạm vi dữ liệu phân cấp (Data Scope)
 * Chống IDOR và lọc truy vấn hồ sơ theo đơn vị / vai trò
 */
class DataScopeService
{
    /**
     * Xác định phạm vi truy cập hồ sơ của người dùng hiện tại
     *
     * @return array{
     *   type: string, // 'CONG_DAN', 'ALL_TP', 'CO_QUAN', 'PHONG_BAN', 'PHAN_CONG', 'DON_VI', 'NONE'
     *   conditions: string,
     *   params: array
     * }
     */
    public static function getScope(): array
    {
        $guard = AuthService::guard();

        // 1. Phía công dân: Chỉ thấy hồ sơ của chính mình
        if ($guard === AuthService::GUARD_CONG_DAN) {
            $citizenId = AuthService::id();
            return [
                'type' => 'CONG_DAN',
                'conditions' => 'ho_so.cong_dan_id = :scope_cong_dan_id',
                'params' => [':scope_cong_dan_id' => $citizenId],
            ];
        }

        // 2. Phía Cán bộ: Phân tích vai trò và đơn vị làm việc
        $roles = AuthService::roles();
        $currentDonVi = AuthService::currentDonVi();
        $userId = AuthService::id();
        $donViId = $currentDonVi ? (int)$currentDonVi['id'] : 0;
        $nodePath = $currentDonVi['duong_dan_node'] ?? '';

        // SYSADMIN: Tuyệt đối không can thiệp hồ sơ nghiệp vụ
        if (in_array('SYSADMIN', $roles, true) && count($roles) === 1) {
            return [
                'type' => 'NONE',
                'conditions' => '1 = 0', // Luôn trả về rỗng
                'params' => [],
            ];
        }

        // Lãnh đạo cơ quan UBND TP: Toàn thành phố
        if (in_array('LD_CO_QUAN', $roles, true) && ($currentDonVi['loai_don_vi'] ?? '') === 'UBND_TP') {
            return [
                'type' => 'ALL_TP',
                'conditions' => '1 = 1',
                'params' => [],
            ];
        }

        // Lãnh đạo cơ quan (Sở / UBND Quận/Huyện): Toàn bộ hồ sơ thuộc cây đơn vị của mình
        if (in_array('LD_CO_QUAN', $roles, true)) {
            return [
                'type' => 'CO_QUAN',
                'conditions' => '(ho_so.don_vi_tiep_nhan_id = :scope_don_vi_id OR ho_so.don_vi_hien_tai_id = :scope_don_vi_id)',
                'params' => [':scope_don_vi_id' => $donViId],
            ];
        }

        // Lãnh đạo phòng: Hồ sơ thuộc phạm vi phòng ban mình
        if (in_array('LD_PHONG', $roles, true)) {
            return [
                'type' => 'PHONG_BAN',
                'conditions' => '(ho_so.don_vi_hien_tai_id = :scope_dv_id OR ho_so.phong_ban_xu_ly_id = :scope_dv_id)',
                'params' => [':scope_dv_id' => $donViId],
            ];
        }

        // Chuyên viên: Hồ sơ được phân công trực tiếp hoặc phối hợp
        if (in_array('CHUYEN_VIEN', $roles, true)) {
            return [
                'type' => 'PHAN_CONG',
                'conditions' => '(ho_so.chuyen_vien_thu_ly_id = :scope_user_id OR EXISTS (SELECT 1 FROM ho_so_y_kien WHERE ho_so_y_kien.ho_so_id = ho_so.id AND ho_so_y_kien.nguoi_duoc_hoi_id = :scope_user_id))',
                'params' => [':scope_user_id' => $userId],
            ];
        }

        // Cán bộ Một cửa & Văn thư: Hồ sơ tại đơn vị mình công tác
        if (in_array('CB_MOT_CUA', $roles, true) || in_array('VAN_THU', $roles, true)) {
            return [
                'type' => 'DON_VI',
                'conditions' => 'ho_so.don_vi_tiep_nhan_id = :scope_dv_mot_cua',
                'params' => [':scope_dv_mot_cua' => $donViId],
            ];
        }

        // Mặc định: theo đơn vị
        return [
            'type' => 'DON_VI',
            'conditions' => 'ho_so.don_vi_tiep_nhan_id = :scope_default_dv',
            'params' => [':scope_default_dv' => $donViId],
        ];
    }

    /**
     * Kiểm tra một hồ sơ cụ thể có nằm trong phạm vi quyền hạn của người dùng không (chống IDOR)
     */
    public static function checkHoSoAccess(int $hoSoId): bool
    {
        $db = Database::connection();
        $scope = self::getScope();

        if ($scope['type'] === 'NONE') {
            return false;
        }

        $sql = "SELECT id FROM ho_so WHERE id = :hs_id AND ({$scope['conditions']}) LIMIT 1";
        $params = array_merge([':hs_id' => $hoSoId], $scope['params']);

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }
}
