<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\Database;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use Closure;
use PDO;
use Throwable;

/**
 * Class RateLimitMiddleware
 * Giới hạn tần suất truy cập <= 60 req/phút/IP và chặn IP vi phạm dựa trên bảng gioi_han_truy_cap & ip_chan
 * Chuẩn an toàn thông tin Cấp độ 3 (§3.3 & Master Rules)
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next, mixed ...$args): Response
    {
        $ip = $request->ip();
        $endpoint = substr($request->path(), 0, 100);

        try {
            // 1. Kiểm tra IP có nằm trong danh sách đen (ip_chan) hay không
            if ($this->isIpBlocked($ip)) {
                return ErrorHandler::renderError(
                    $request,
                    403,
                    "Địa chỉ IP ({$ip}) của bạn đã bị tạm khóa do nghi vấn tấn công hoặc vi phạm an toàn thông tin."
                );
            }

            // 2. Kiểm tra Rate Limiting (Mặc định 60 requests / 60 giây)
            $maxRequests = (int) Config::get('security.rate_limit.max_requests', 60);
            $window = (int) Config::get('security.rate_limit.decay_seconds', 60);

            $limitInfo = $this->checkRateLimit($ip, $endpoint, $maxRequests, $window);

            if (!$limitInfo['allowed']) {
                $retryAfter = $limitInfo['retry_after'];
                $response = ErrorHandler::renderError(
                    $request,
                    429,
                    "Quá giới hạn số lượng yêu cầu cho phép ({$maxRequests} yêu cầu/phút). Vui lòng thử lại sau {$retryAfter} giây."
                );
                $response->setHeader('Retry-After', (string)$retryAfter);
                $response->setHeader('X-RateLimit-Limit', (string)$maxRequests);
                $response->setHeader('X-RateLimit-Remaining', '0');
                return $response;
            }

            $response = $next($request);
            $response->setHeader('X-RateLimit-Limit', (string)$maxRequests);
            $response->setHeader('X-RateLimit-Remaining', (string)$limitInfo['remaining']);
            return $response;

        } catch (Throwable $e) {
            // Trường hợp CSDL có sự cố, không ngắt luồng request nhưng ghi log cảnh báo
            Logger::warning("Lỗi kiểm tra Rate Limit: " . $e->getMessage());
            return $next($request);
        }
    }

    /**
     * Kiểm tra xem địa chỉ IP có bị chặn hay không
     */
    private function isIpBlocked(string $ip): bool
    {
        $sql = "SELECT id FROM `ip_chan` 
                WHERE `ip_address` = :ip 
                  AND (`chan_den` IS NULL OR `chan_den` > NOW()) 
                LIMIT 1";

        $stmt = Database::getReadPdo()->prepare($sql);
        $stmt->execute([':ip' => $ip]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Kiểm tra và cập nhật bộ đếm rate limit
     *
     * @return array{allowed: bool, remaining: int, retry_after: int}
     */
    private function checkRateLimit(string $ip, string $endpoint, int $maxRequests, int $window): array
    {
        $now = time();
        $pdo = Database::getWritePdo();

        // Đọc bản ghi hiện tại
        $sqlSelect = "SELECT `so_request`, `thoi_gian_bat_dau` 
                      FROM `gioi_han_truy_cap` 
                      WHERE `ip_address` = :ip AND `endpoint` = :endpoint 
                      FOR UPDATE";

        $stmt = $pdo->prepare($sqlSelect);
        $stmt->execute([':ip' => $ip, ':endpoint' => $endpoint]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            // Bản ghi mới
            $sqlInsert = "INSERT INTO `gioi_han_truy_cap` (`ip_address`, `endpoint`, `so_request`, `thoi_gian_bat_dau`) 
                          VALUES (:ip, :endpoint, 1, :now)
                          ON DUPLICATE KEY UPDATE `so_request` = 1, `thoi_gian_bat_dau` = :now_dup";
            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->execute([':ip' => $ip, ':endpoint' => $endpoint, ':now' => $now, ':now_dup' => $now]);

            return ['allowed' => true, 'remaining' => $maxRequests - 1, 'retry_after' => 0];
        }

        $count = (int)$row['so_request'];
        $startTime = (int)$row['thoi_gian_bat_dau'];

        // Kiểm tra xem đã hết cửa sổ thời gian (window) chưa
        if (($now - $startTime) >= $window) {
            // Đặt lại bộ đếm cho chu kỳ mới
            $sqlUpdate = "UPDATE `gioi_han_truy_cap` 
                          SET `so_request` = 1, `thoi_gian_bat_dau` = :now 
                          WHERE `ip_address` = :ip AND `endpoint` = :endpoint";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([':now' => $now, ':ip' => $ip, ':endpoint' => $endpoint]);

            return ['allowed' => true, 'remaining' => $maxRequests - 1, 'retry_after' => 0];
        }

        // Vẫn trong chu kỳ hiện tại
        if ($count >= $maxRequests) {
            $retryAfter = max(1, $window - ($now - $startTime));
            return ['allowed' => false, 'remaining' => 0, 'retry_after' => $retryAfter];
        }

        // Tăng bộ đếm
        $sqlUpdate = "UPDATE `gioi_han_truy_cap` 
                      SET `so_request` = `so_request` + 1 
                      WHERE `ip_address` = :ip AND `endpoint` = :endpoint";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([':ip' => $ip, ':endpoint' => $endpoint]);

        return ['allowed' => true, 'remaining' => max(0, $maxRequests - ($count + 1)), 'retry_after' => 0];
    }
}
