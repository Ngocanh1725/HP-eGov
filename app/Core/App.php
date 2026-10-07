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
            $response = Router::dispatch($request);
            $response->send();
        } catch (Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }
}
