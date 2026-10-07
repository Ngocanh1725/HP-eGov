<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Database;
use PDO;
use Throwable;

/**
 * Class SecurityPolicyService
 * Quản lý chính sách bảo mật, danh sách IP bị chặn, Rate limit và Chế độ bảo trì hệ thống
 */
class SecurityPolicyService
{
    /**
     * Lấy các tham số chính sách bảo mật từ bảng cau_hinh_he_thong
     *
     * @return array<string, mixed>
     */
    public static function getPolicies(): array
    {
        $db = Database::connection();
        $stmt = $db->query("SELECT khoa, gia_tri FROM cau_hinh_he_thong WHERE nhom = 'BAO_MAT' OR khoa IN ('maintenance_mode', 'maintenance_message', 'mat_khau_do_dai_toi_thieu')");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'mat_khau_do_dai_toi_thieu'     => (int)($rows['mat_khau_do_dai_toi_thieu'] ?? 8),
            'so_lan_dang_nhap_sai_khoa'     => (int)($rows['so_lan_dang_nhap_sai_khoa'] ?? 5),
            'thoi_gian_khoa_tai_khoan_phut' => (int)($rows['thoi_gian_khoa_tai_khoan_phut'] ?? 15),
            'session_lifetime_phut'         => (int)($rows['session_lifetime_phut'] ?? 30),
            'rate_limit_public'             => (int)($rows['rate_limit_public'] ?? 60),
            'maintenance_mode'              => (int)($rows['maintenance_mode'] ?? (self::isMaintenanceFlagSet() ? 1 : 0)),
            'maintenance_message'           => $rows['maintenance_message'] ?? 'Hệ thống đang tạm ngừng để nâng cấp kỹ thuật theo kế hoạch định kỳ. Trân trọng cảm ơn sự thông cảm của Quý công dân và Doanh nghiệp.',
        ];
    }

    /**
     * Cập nhật các tham số chính sách bảo mật
     *
     * @param array<string, mixed> $data
     * @param int $userId ID người thực hiện
     * @return bool
     */
    public static function updatePolicies(array $data, int $userId): bool
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $allowedKeys = [
                'mat_khau_do_dai_toi_thieu'     => ['type' => 'int', 'desc' => 'Độ dài tối thiểu của mật khẩu người dùng'],
                'so_lan_dang_nhap_sai_khoa'     => ['type' => 'int', 'desc' => 'Số lần đăng nhập sai tối đa trước khi khóa tài khoản'],
                'thoi_gian_khoa_tai_khoan_phut' => ['type' => 'int', 'desc' => 'Thời gian tạm khóa tài khoản khi brute-force (phút)'],
                'session_lifetime_phut'         => ['type' => 'int', 'desc' => 'Thời gian hết hạn phiên làm việc không hoạt động (phút)'],
                'rate_limit_public'             => ['type' => 'int', 'desc' => 'Số request tối đa trong 1 phút cho 1 địa chỉ IP'],
                'maintenance_mode'              => ['type' => 'int', 'desc' => 'Chế độ bảo trì hệ thống (0: Tắt, 1: Bật)'],
                'maintenance_message'           => ['type' => 'string', 'desc' => 'Thông điệp bảo trì hiển thị trên trang tĩnh'],
            ];

            $checkStmt = $db->prepare("SELECT id, gia_tri FROM cau_hinh_he_thong WHERE khoa = :khoa");
            $insertStmt = $db->prepare("INSERT INTO cau_hinh_he_thong (khoa, gia_tri, kieu_du_lieu, nhom, mo_ta) VALUES (:khoa, :gia_tri, :kieu_du_lieu, 'BAO_MAT', :mo_ta)");
            $updateStmt = $db->prepare("UPDATE cau_hinh_he_thong SET gia_tri = :gia_tri WHERE khoa = :khoa");
            $versionStmt = $db->prepare("INSERT INTO phien_ban_cau_hinh (khoa_cau_hinh, gia_tri_cu, gia_tri_moi, nguoi_thay_doi_id) VALUES (:khoa, :cu, :moi, :uid)");

            foreach ($allowedKeys as $key => $meta) {
                if (isset($data[$key])) {
                    $val = (string)$data[$key];
                    $checkStmt->execute(['khoa' => $key]);
                    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

                    if ($existing) {
                        $oldVal = $existing['gia_tri'];
                        if ($oldVal !== $val) {
                            $updateStmt->execute(['gia_tri' => $val, 'khoa' => $key]);
                            $versionStmt->execute([
                                'khoa' => $key,
                                'cu'   => $oldVal,
                                'moi'  => $val,
                                'uid'  => $userId,
                            ]);
                        }
                    } else {
                        $insertStmt->execute([
                            'khoa'          => $key,
                            'gia_tri'       => $val,
                            'kieu_du_lieu'  => $meta['type'],
                            'mo_ta'         => $meta['desc'],
                        ]);
                        $versionStmt->execute([
                            'khoa' => $key,
                            'cu'   => null,
                            'moi'  => $val,
                            'uid'  => $userId,
                        ]);
                    }
                }
            }

            // Đồng bộ trạng thái bảo trì ra cờ file vật lý để đáp ứng Static Maintenance Site tức thì
            if (isset($data['maintenance_mode'])) {
                self::syncMaintenanceFlag((int)$data['maintenance_mode'] === 1, (string)($data['maintenance_message'] ?? ''));
            }

            $db->commit();
            return true;
        } catch (Throwable $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Lấy danh sách IP bị chặn
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getBlockedIps(): array
    {
        $db = Database::connection();
        $stmt = $db->query("SELECT * FROM ip_chan ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Thêm IP vào danh sách chặn
     */
    public static function blockIp(string $ip, string $reason, ?string $blockedUntil = null): bool
    {
        $db = Database::connection();
        try {
            $stmt = $db->prepare("INSERT INTO ip_chan (ip_address, ly_do, chan_den) 
                                  VALUES (:ip, :ly_do, :chan_den) 
                                  ON DUPLICATE KEY UPDATE ly_do = :ly_do_up, chan_den = :chan_den_up");
            return $stmt->execute([
                'ip'          => trim($ip),
                'ly_do'       => $reason,
                'chan_den'    => $blockedUntil,
                'ly_do_up'    => $reason,
                'chan_den_up' => $blockedUntil,
            ]);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Mở khóa IP (xóa khỏi danh sách chặn)
     */
    public static function unblockIp(int $id): bool
    {
        $db = Database::connection();
        $stmt = $db->prepare("DELETE FROM ip_chan WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Lấy danh sách IP đang bị giới hạn truy cập (rate limit)
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getRateLimitedList(): array
    {
        $db = Database::connection();
        $stmt = $db->query("SELECT * FROM gioi_han_truy_cap ORDER BY so_request DESC LIMIT 50");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Làm sạch / reset rate limit cho một IP hoặc tất cả
     */
    public static function clearRateLimit(?string $ip = null): bool
    {
        $db = Database::connection();
        if ($ip !== null) {
            $stmt = $db->prepare("DELETE FROM gioi_han_truy_cap WHERE ip_address = :ip");
            return $stmt->execute(['ip' => $ip]);
        }
        return (bool)$db->exec("TRUNCATE TABLE gioi_han_truy_cap");
    }

    /**
     * Kiểm tra trạng thái cờ bảo trì file vật lý
     */
    public static function isMaintenanceFlagSet(): bool
    {
        $flagFile = App::basePath('storage/maintenance.flag');
        return file_exists($flagFile);
    }

    /**
     * Đồng bộ tệp cờ bảo trì
     */
    public static function syncMaintenanceFlag(bool $enabled, string $message = ''): void
    {
        $flagFile = App::basePath('storage/maintenance.flag');
        if ($enabled) {
            $content = json_encode([
                'enabled'   => true,
                'timestamp' => date('c'),
                'message'   => $message,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            file_put_contents($flagFile, $content);
        } else {
            if (file_exists($flagFile)) {
                @unlink($flagFile);
            }
        }
    }
}
