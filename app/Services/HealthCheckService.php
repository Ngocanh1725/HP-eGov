<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Database;
use PDO;
use Throwable;

/**
 * Class HealthCheckService
 * Giám sát sức khỏe hạ tầng & hệ thống HP-eGov thời gian thực
 * Phục vụ Dashboard Admin và Endpoint RESTful `/api/v1/health` cho Prometheus/Zabbix
 */
class HealthCheckService
{
    /**
     * Thu thập toàn bộ chỉ số sức khỏe của hệ thống
     *
     * @return array<string, mixed>
     */
    public static function getHealthStatus(): array
    {
        $dbStatus = self::checkDatabase();
        $diskStatus = self::checkDiskSpace();
        $storageStatus = self::checkStorageSize();
        $queueStatus = self::checkNotificationQueue();
        $cronStatus = self::checkCronStatus();
        $sessionStatus = self::checkActiveSessions();
        $environment = self::getEnvironmentInfo();

        $overallStatus = ($dbStatus['status'] === 'UP' && $diskStatus['used_percent'] < 95.0) ? 'UP' : 'DEGRADED';

        return [
            'status'            => $overallStatus,
            'timestamp'         => date('c'),
            'app_name'          => 'HP-eGov Hải Phòng',
            'app_env'           => getenv('APP_ENV') ?: 'production',
            'database'          => $dbStatus,
            'disk'              => $diskStatus,
            'storage'           => $storageStatus,
            'queue'             => $queueStatus,
            'cron'              => $cronStatus,
            'active_sessions'   => $sessionStatus,
            'environment'       => $environment,
        ];
    }

    /**
     * Kiểm tra kết nối CSDL và đo độ trễ (latency ms)
     *
     * @return array{status: string, latency_ms: float, error?: string}
     */
    public static function checkDatabase(): array
    {
        $start = microtime(true);
        try {
            $db = Database::connection();
            $stmt = $db->query("SELECT 1");
            $stmt->fetchColumn();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status'     => 'UP',
                'latency_ms' => $latency,
            ];
        } catch (Throwable $e) {
            return [
                'status'     => 'DOWN',
                'latency_ms' => -1,
                'error'      => $e->getMessage(),
            ];
        }
    }

    /**
     * Kiểm tra dung lượng ổ đĩa nơi ứng dụng đang chạy
     *
     * @return array{free_gb: float, total_gb: float, used_gb: float, used_percent: float}
     */
    public static function checkDiskSpace(): array
    {
        $basePath = App::basePath();
        $freeBytes = disk_free_space($basePath);
        $totalBytes = disk_total_space($basePath);

        if ($freeBytes === false || $totalBytes === false || $totalBytes <= 0) {
            return [
                'free_gb'      => 0.0,
                'total_gb'     => 0.0,
                'used_gb'      => 0.0,
                'used_percent' => 0.0,
            ];
        }

        $usedBytes = $totalBytes - $freeBytes;
        $usedPercent = round(($usedBytes / $totalBytes) * 100, 2);

        return [
            'free_gb'      => round($freeBytes / (1024 * 1024 * 1024), 2),
            'total_gb'     => round($totalBytes / (1024 * 1024 * 1024), 2),
            'used_gb'      => round($usedBytes / (1024 * 1024 * 1024), 2),
            'used_percent' => $usedPercent,
        ];
    }

    /**
     * Tính tổng kích thước thư mục lưu trữ storage/
     *
     * @return array{size_mb: float, formatted: string}
     */
    public static function checkStorageSize(): array
    {
        $storageDir = App::basePath('storage');
        $totalBytes = 0;

        if (is_dir($storageDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($storageDir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $totalBytes += $file->getSize();
                }
            }
        }

        $sizeMb = round($totalBytes / (1024 * 1024), 2);

        return [
            'size_bytes' => $totalBytes,
            'size_mb'    => $sizeMb,
            'formatted'  => $sizeMb > 1024 ? round($sizeMb / 1024, 2) . ' GB' : $sizeMb . ' MB',
        ];
    }

    /**
     * Kiểm tra hàng đợi thông báo chờ gửi
     *
     * @return array{pending_count: int, failed_count: int}
     */
    public static function checkNotificationQueue(): array
    {
        try {
            $db = Database::connection();
            // Kiểm tra bảng hang_doi_thong_bao nếu tồn tại
            $stmt = $db->query("SELECT 
                SUM(CASE WHEN trang_thai = 'CHO_GUI' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN trang_thai = 'THAT_BAI' THEN 1 ELSE 0 END) as failed
                FROM hang_doi_thong_bao");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'pending_count' => (int)($row['pending'] ?? 0),
                'failed_count'  => (int)($row['failed'] ?? 0),
            ];
        } catch (Throwable) {
            return [
                'pending_count' => 0,
                'failed_count'  => 0,
            ];
        }
    }

    /**
     * Kiểm tra thời gian chạy gần nhất của các tác vụ Cron/SLA
     *
     * @return array{last_sla_scan: string|null, last_backup: string|null}
     */
    public static function checkCronStatus(): array
    {
        $lastSla = null;
        $lastBackup = null;

        try {
            $db = Database::connection();
            // Lấy thời gian từ cau_hinh_he_thong hoặc nhat_ky_kiem_toan
            $stmt = $db->query("SELECT gia_tri FROM cau_hinh_he_thong WHERE khoa = 'cron_sla_last_run'");
            $val = $stmt->fetchColumn();
            if ($val) {
                $lastSla = (string)$val;
            } else {
                // Thử lấy từ bảng ho_so_log gần nhất
                $stmt2 = $db->query("SELECT MAX(created_at) FROM nhat_ky_kiem_toan WHERE hanh_dong = 'SLA_SCAN'");
                $lastSla = $stmt2->fetchColumn() ?: null;
            }

            // Lấy thời gian backup gần nhất
            $stmt3 = $db->query("SELECT MAX(thoi_gian_hoan_thanh) FROM sao_luu WHERE trang_thai = 'THANH_CONG'");
            $lastBackup = $stmt3->fetchColumn() ?: null;
        } catch (Throwable) {
            // bỏ qua
        }

        return [
            'last_sla_scan' => $lastSla ?: 'Chưa ghi nhận',
            'last_backup'   => $lastBackup ?: 'Chưa ghi nhận',
        ];
    }

    /**
     * Đếm số lượng phiên làm việc đang hoạt động (trong vòng 30 phút qua)
     *
     * @return int
     */
    public static function checkActiveSessions(): int
    {
        try {
            $db = Database::connection();
            $since = date('Y-m-d H:i:s', time() - 1800); // 30 phút trước
            $stmt = $db->prepare("SELECT COUNT(DISTINCT ten_dang_nhap) FROM nhat_ky_dang_nhap WHERE trang_thai = 'THANH_CONG' AND created_at >= :since");
            $stmt->execute(['since' => $since]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable) {
            return 1;
        }
    }

    /**
     * Lấy thông tin phiên bản PHP, MySQL, Web server và OS
     *
     * @return array{php_version: string, mysql_version: string, web_server: string, os: string}
     */
    public static function getEnvironmentInfo(): array
    {
        $mysqlVersion = 'Không rõ';
        try {
            $db = Database::connection();
            $mysqlVersion = (string)$db->query("SELECT VERSION()")->fetchColumn();
        } catch (Throwable) {
            // bỏ qua
        }

        return [
            'php_version'   => PHP_VERSION,
            'mysql_version' => $mysqlVersion,
            'web_server'    => $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/2.4 (XAMPP)',
            'os'            => PHP_OS_FAMILY . ' (' . php_uname('s') . ' ' . php_uname('r') . ')',
        ];
    }
}
