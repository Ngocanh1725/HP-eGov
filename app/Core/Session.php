<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Class Session
 * Quản lý phiên làm việc an toàn: Cookie flags (HttpOnly, SameSite, Secure),
 * kiểm soát thời gian hết hạn (Timeout), xoay vòng session ID và cơ chế Flash message.
 */
class Session
{
    private static bool $started = false;

    /**
     * Khởi động phiên làm việc với cờ bảo mật theo chuẩn An toàn cấp độ 3
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE || headers_sent() || php_sapi_name() === 'cli') {
            self::$started = true;
            return;
        }

        $lifetime = (int) Config::get('session.lifetime', 1800);
        $cookieName = (string) Config::get('session.name', 'hpegov_session');
        $secure = (bool) Config::get('session.secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
        $samesite = (string) Config::get('session.samesite', 'Lax');

        session_name($cookieName);

        // Thiết lập cờ bảo mật cho Cookie
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => $samesite,
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_start();
        self::$started = true;

        // Kiểm tra thời gian hết hạn (Idle Timeout 30 phút)
        self::checkTimeout($lifetime);

        // Chuyển đổi dữ liệu flash của request trước sang trạng thái sẵn sàng xóa
        self::ageFlashData();
    }

    /**
     * Kiểm tra thời gian bất hoạt (Timeout)
     */
    private static function checkTimeout(int $lifetime): void
    {
        $now = time();
        $lastActivity = $_SESSION['_last_activity'] ?? null;

        if ($lastActivity !== null && ($now - $lastActivity) > $lifetime) {
            self::destroy();
            self::start();
            self::set('session_expired', true);
            return;
        }

        $_SESSION['_last_activity'] = $now;
    }

    /**
     * Lấy giá trị từ session
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Gán giá trị vào session
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Kiểm tra khóa có tồn tại trong session
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Xóa một khóa khỏi session
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Tái tạo Session ID chống tấn công Session Fixation
     */
    public static function regenerate(bool $deleteOldSession = true): void
    {
        self::start();
        if (!headers_sent()) {
            session_regenerate_id($deleteOldSession);
        }
    }

    /**
     * Hủy toàn bộ phiên làm việc
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_destroy();
            self::$started = false;
        }
    }

    /**
     * Lưu thông báo Flash (chỉ tồn tại trong 1 request kế tiếp)
     */
    public static function flash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['_flash']['new'][$key] = $value;
    }

    /**
     * Lấy giá trị thông báo Flash
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION['_flash']['old'][$key] ?? $_SESSION['_flash']['new'][$key] ?? $default;
    }

    /**
     * Quản lý vòng đời dữ liệu Flash qua các request
     */
    private static function ageFlashData(): void
    {
        // Xóa flash cũ của request trước
        if (isset($_SESSION['_flash']['old'])) {
            unset($_SESSION['_flash']['old']);
        }

        // Chuyển flash mới thành cũ
        if (isset($_SESSION['_flash']['new'])) {
            $_SESSION['_flash']['old'] = $_SESSION['_flash']['new'];
            unset($_SESSION['_flash']['new']);
        }
    }
}
