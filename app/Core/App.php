<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Class App
 * Điều phối vòng đời ứng dụng HP-eGov (Application Lifecycle)
 */
class App
{
    private static string $basePath = '';
    private static bool $booted = false;

    /**
     * Khởi tạo đường dẫn gốc của ứng dụng
     */
    public static function setBasePath(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/\\');
    }

    /**
     * Lấy đường dẫn gốc của dự án
     */
    public static function basePath(string $path = ''): string
    {
        return self::$basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    /**
     * Khởi động các thành phần cốt lõi của hệ thống
     */
    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        // 1. Nạp file môi trường .env
        $envPath = self::basePath('.env');
        if (file_exists($envPath)) {
            Env::load($envPath);
        }

        // 2. Thiết lập đường dẫn cho Config, View và Logger
        Config::setPath(self::basePath('app/Config'));
        View::setViewsPath(self::basePath('app/Views'));
        Logger::setLogPath(self::basePath('storage/logs'));

        // 3. Cấu hình Timezone mặc định
        $timezone = (string) Config::get('app.timezone', 'Asia/Ho_Chi_Minh');
        date_default_timezone_set($timezone);

        // 4. Khởi động Error Handler toàn cục
        ErrorHandler::register();

        // 5. Khởi động Session an toàn
        Session::start();

        // 6. Nạp danh sách routes
        self::loadRoutes();

        self::$booted = true;
    }

    /**
     * Nạp các tệp khai báo Route
     */
    private static function loadRoutes(): void
    {
        $webRoutes = self::basePath('routes/web.php');
        if (file_exists($webRoutes)) {
            require_once $webRoutes;
        }

        $apiRoutes = self::basePath('routes/api.php');
        if (file_exists($apiRoutes)) {
            require_once $apiRoutes;
        }
    }

    /**
     * Tiếp nhận HTTP Request và phản hồi kết quả
     */
    public static function run(): void
    {
        try {
            self::boot();

            $request = Request::capture();

            // Kiểm tra Chế độ bảo trì hệ thống (Static Maintenance Site)
            if (self::isUnderMaintenance($request)) {
                self::serveMaintenanceResponse();
                return;
            }

            $response = Router::dispatch($request);
            $response->send();
        } catch (Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

    /**
     * Kiểm tra xem hệ thống có đang ở chế độ bảo trì hay không
     */
    private static function isUnderMaintenance(Request $request): bool
    {
        $flagFile = self::basePath('storage/maintenance.flag');
        if (!file_exists($flagFile)) {
            return false;
        }

        $path = $request->path();

        // Bỏ qua các đường dẫn kỹ thuật và đăng nhập của Quản trị viên
        $bypassPrefixes = [
            '/admin/dang-nhap',
            '/admin/he-thong',
            '/admin/thoat',
            '/api/v1/health',
            '/assets',
            '/css',
            '/js',
            '/vendor',
        ];

        foreach ($bypassPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        // Cho phép SYSADMIN đã đăng nhập tiếp tục truy cập
        if (Session::has('auth_can_bo')) {
            $user = Session::get('auth_can_bo');
            if (is_array($user) && ($user['vai_tro'] ?? '') === 'SYSADMIN') {
                return false;
            }
        }

        return true;
    }

    /**
     * Trả về trang tĩnh bảo trì (Static Maintenance Site) với mã 503
     */
    private static function serveMaintenanceResponse(): void
    {
        http_response_code(503);
        header('Retry-After: 3600');
        header('Content-Type: text/html; charset=UTF-8');

        $maintenanceFile = self::basePath('public/maintenance.html');
        if (file_exists($maintenanceFile)) {
            readfile($maintenanceFile);
        } else {
            echo '<h1>503 - Hệ thống HP-eGov đang tạm dừng bảo trì nâng cấp</h1>';
        }
        exit;
    }
}

