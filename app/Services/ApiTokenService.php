<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use PDO;
use Throwable;

/**
 * Class ApiTokenService
 * Quản lý xác thực Bearer Token cho REST API /api/v1 (§1.1, §3.3)
 */
class ApiTokenService
{
    /**
     * Xác thực Bearer Token từ chuỗi raw token gửi lên
     *
     * @param string $rawToken Chuỗi token thuần (không kèm prefix 'Bearer ')
     * @return array<string, mixed>|null Trả về thông tin token nếu hợp lệ, ngược lại null
     */
    public function verifyToken(string $rawToken): ?array
    {
        $rawToken = trim($rawToken);
        if ($rawToken === '') {
            return null;
        }

        $tokenHash = hash('sha256', $rawToken);
        $pdo = Database::pdo();

        try {
            $stmt = $pdo->prepare("
                SELECT * FROM api_tokens
                WHERE token_hash = :hash
                  AND trang_thai = 1
                  AND expires_at > NOW()
                LIMIT 1
            ");
            $stmt->execute(['hash' => $tokenHash]);
            $token = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$token) {
                return null;
            }

            // Cập nhật thời điểm sử dụng cuối cùng (không làm gián đoạn nếu lỗi)
            $updateStmt = $pdo->prepare("UPDATE api_tokens SET last_used_at = NOW() WHERE id = :id");
            $updateStmt->execute(['id' => $token['id']]);

            return $token;
        } catch (Throwable $e) {
            Logger::error("API Token verify error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Kiểm tra xem Token có quyền truy cập scope yêu cầu hay không
     *
     * @param array<string, mixed> $tokenInfo
     * @param string $requiredScope (ví dụ: 'tra_cuu', 'thu_tuc', 'cong_dan', 'lanh_dao', 'webhook')
     * @return bool
     */
    public function hasScope(array $tokenInfo, string $requiredScope): bool
    {
        $scopesStr = (string)($tokenInfo['scopes'] ?? '');
        if ($scopesStr === '*' || $scopesStr === '') {
            return true;
        }

        $scopes = array_map('trim', explode(',', $scopesStr));
        return in_array($requiredScope, $scopes, true) || in_array('*', $scopes, true);
    }

    /**
     * Tạo mới một API Token
     *
     * @param int|null $nguoiDungId ID Cán bộ (nếu là token nội bộ)
     * @param int|null $congDanId ID Công dân (nếu là token công dân)
     * @param string $tenToken Tên gợi nhớ của token
     * @param string $scopes Phạm vi quyền phân cách bởi dấu phẩy ('*' hoặc 'tra_cuu,cong_dan')
     * @param int $expireDays Số ngày có hiệu lực (mặc định 365 ngày)
     * @return array{raw_token: string, token_id: int, expires_at: string}
     */
    public function createToken(
        ?int $nguoiDungId,
        ?int $congDanId,
        string $tenToken,
        string $scopes = '*',
        int $expireDays = 365
    ): array {
        $rawToken = bin2hex(random_bytes(32)); // 64 ký tự hex ngẫu nhiên an toàn
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expireDays} days"));

        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            INSERT INTO api_tokens (
                nguoi_dung_id, cong_dan_id, ten_token, token_hash, scopes, expires_at, trang_thai
            ) VALUES (
                :uid, :cid, :ten, :hash, :scopes, :exp, 1
            )
        ");
        $stmt->execute([
            'uid'    => $nguoiDungId,
            'cid'    => $congDanId,
            'ten'    => $tenToken,
            'hash'   => $tokenHash,
            'scopes' => $scopes,
            'exp'    => $expiresAt,
        ]);

        return [
            'raw_token'  => $rawToken,
            'token_id'   => (int)$pdo->lastInsertId(),
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Vô hiệu hóa một API Token
     */
    public function revokeToken(int $tokenId): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("UPDATE api_tokens SET trang_thai = 0 WHERE id = :id");
        return $stmt->execute(['id' => $tokenId]);
    }
}
