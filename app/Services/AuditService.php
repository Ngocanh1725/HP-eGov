<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;

/**
 * AuditService – Dịch vụ Nhật ký kiểm toán WORM (Write Once Read Many)
 * 
 * Tuân thủ quy định tại Mục 7 & 13 (.agents/rules/hp-egov.md):
 * - Ghi nhat_ky_kiem_toan với chuỗi băm WORM SHA-256 (hash = SHA256(prev_hash | data))
 * - Cung cấp hàm verifyChain() phát hiện can thiệp giả mạo dữ liệu
 */
class AuditService
{
    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /**
     * Ghi một sự kiện kiểm toán vào CSDL với băm chuỗi WORM bảo mật
     *
     * @param int|null $userId ID cán bộ thao tác (hoặc null nếu là khách / công dân / hệ thống)
     * @param string $action Hành động (Vd: DANG_NHAP, CAP_QUYEN, TAO_HO_SO, IDOR_VIOLATION...)
     * @param string $table Bảng CSDL bị tác động
     * @param int|null $recordId ID của bản ghi bị tác động
     * @param array|string|null $oldData Dữ liệu cũ trước khi thay đổi
     * @param array|string|null $newData Dữ liệu mới sau khi thay đổi
     * @param int|null $congDanId ID công dân (nếu có)
     * @param string|null $ip Địa chỉ IP (tự động lấy nếu để null)
     * @param string|null $userAgent User Agent trình duyệt
     * @return int ID của bản ghi nhật ký kiểm toán vừa tạo
     */
    public static function log(
        ?int $userId,
        string $action,
        string $table,
        ?int $recordId = null,
        array|string|null $oldData = null,
        array|string|null $newData = null,
        ?int $congDanId = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): int {
        try {
            $db = Database::connection();

            // 1. Lấy hash của bản ghi gần nhất
            $stmt = $db->query("SELECT id, hash FROM nhat_ky_kiem_toan ORDER BY id DESC LIMIT 1");
            $lastRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            $prevHash = $lastRecord['hash'] ?? self::GENESIS_HASH;

            // 2. Chuẩn bị thông tin IP và User Agent
            $ipAddress = $ip ?? self::getClientIp();
            $ua = $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System');

            $oldStr = is_array($oldData) ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : (string)($oldData ?? '');
            $newStr = is_array($newData) ? json_encode($newData, JSON_UNESCAPED_UNICODE) : (string)($newData ?? '');

            // Nếu newData rỗng, tạo chuỗi tóm tắt hành động
            if ($newStr === '') {
                $newStr = "Action {$action} on {$table}" . ($recordId !== null ? " ID {$recordId}" : '');
            }

            // 3. Tính toán hash cho bản ghi hiện tại
            $hash = self::computeHash($prevHash, $newStr);

            // 4. Chèn vào CSDL
            $insertSql = "INSERT INTO nhat_ky_kiem_toan 
                (nguoi_dung_id, cong_dan_id, hanh_dong, bang_tac_dong, ban_ghi_id, du_lieu_cu, du_lieu_moi, dia_chi_ip, user_agent, prev_hash, hash, created_at) 
                VALUES 
                (:nguoi_dung_id, :cong_dan_id, :hanh_dong, :bang_tac_dong, :ban_ghi_id, :du_lieu_cu, :du_lieu_moi, :dia_chi_ip, :user_agent, :prev_hash, :hash, NOW())";

            $stmt = $db->prepare($insertSql);
            $stmt->execute([
                ':nguoi_dung_id' => $userId,
                ':cong_dan_id' => $congDanId,
                ':hanh_dong' => $action,
                ':bang_tac_dong' => $table,
                ':ban_ghi_id' => $recordId,
                ':du_lieu_cu' => $oldStr !== '' ? $oldStr : null,
                ':du_lieu_moi' => $newStr,
                ':dia_chi_ip' => $ipAddress,
                ':user_agent' => $ua,
                ':prev_hash' => $prevHash,
                ':hash' => $hash,
            ]);

            return (int) $db->lastInsertId();
        } catch (Throwable $e) {
            error_log("[AuditService Error] " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Tính toán chuỗi băm SHA-256 xác thực bản ghi theo công thức chuẩn:
     * hash = SHA256(prev_hash | du_lieu_moi)
     */
    public static function computeHash(string $prevHash, string $newDataStr): string
    {
        return hash('sha256', $prevHash . '|' . $newDataStr);
    }

    /**
     * Kiểm tra tính toàn vẹn của chuỗi băm kiểm toán (Audit Hash Chain Verification)
     *
     * @param int|null $limit Giới hạn số lượng bản ghi kiểm tra (mặc định 1000 bản ghi)
     * @param int $offset Bản ghi bắt đầu
     * @return array{
     *   valid: bool,
     *   total_verified: int,
     *   broken_at_id: int|null,
     *   reason: string|null,
     *   details: array|null
     * }
     */
    public static function verifyChain(?int $limit = 1000, int $offset = 0): array
    {
        $db = Database::connection();
        $query = "SELECT id, nguoi_dung_id, cong_dan_id, hanh_dong, bang_tac_dong, ban_ghi_id, du_lieu_cu, du_lieu_moi, dia_chi_ip, user_agent, prev_hash, hash, created_at 
                  FROM nhat_ky_kiem_toan ORDER BY id ASC";

        if ($limit !== null) {
            $query .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $db->query($query);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($records)) {
            return [
                'valid' => true,
                'total_verified' => 0,
                'broken_at_id' => null,
                'reason' => 'Không có bản ghi nhật ký kiểm toán nào.',
                'details' => null
            ];
        }

        $expectedPrevHash = null;

        // Nếu bắt đầu từ offset > 0, lấy hash của bản ghi trước đó
        if ($offset > 0) {
            $firstId = (int)$records[0]['id'];
            $prevStmt = $db->prepare("SELECT hash FROM nhat_ky_kiem_toan WHERE id < :id ORDER BY id DESC LIMIT 1");
            $prevStmt->execute([':id' => $firstId]);
            $prevRow = $prevStmt->fetch(PDO::FETCH_ASSOC);
            $expectedPrevHash = $prevRow['hash'] ?? self::GENESIS_HASH;
        }

        $count = 0;
        foreach ($records as $index => $row) {
            $id = (int)$row['id'];
            $prevHash = $row['prev_hash'];
            $currentHash = $row['hash'];

            // Xác định expectedPrevHash cho bản ghi đầu tiên
            if ($index === 0 && $expectedPrevHash === null) {
                $expectedPrevHash = self::GENESIS_HASH;
            }

            // 1. Kiểm tra tính liên tục của chuỗi liên kết (Chain Linkage)
            if ($prevHash !== $expectedPrevHash) {
                return [
                    'valid' => false,
                    'total_verified' => $count,
                    'broken_at_id' => $id,
                    'reason' => "Đứt gãy chuỗi liên kết tại bản ghi #{$id}: prev_hash ({$prevHash}) không khớp hash trước ({$expectedPrevHash})",
                    'details' => [
                        'record_id' => $id,
                        'found_prev_hash' => $prevHash,
                        'expected_prev_hash' => $expectedPrevHash,
                    ]
                ];
            }

            // 2. Tính lại hash của bản ghi hiện tại để kiểm tra tính toàn vẹn dữ liệu
            $recomputedHash = self::computeHash($prevHash, (string)$row['du_lieu_moi']);

            if ($currentHash !== $recomputedHash) {
                return [
                    'valid' => false,
                    'total_verified' => $count,
                    'broken_at_id' => $id,
                    'reason' => "Dữ liệu bản ghi #{$id} bị can thiệp trái phép! Hash lưu ({$currentHash}) khác hash tính lại ({$recomputedHash})",
                    'details' => [
                        'record_id' => $id,
                        'stored_hash' => $currentHash,
                        'computed_hash' => $recomputedHash,
                    ]
                ];
            }

            // Cập nhật expectedPrevHash cho vòng lặp tiếp theo
            $expectedPrevHash = $currentHash;
            $count++;
        }

        return [
            'valid' => true,
            'total_verified' => $count,
            'broken_at_id' => null,
            'reason' => "Chuỗi kiểm toán hợp lệ toàn vẹn. Đã kiểm tra {$count} bản ghi.",
            'details' => null
        ];
    }

    /**
     * Lấy IP của client an toàn
     */
    private static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
