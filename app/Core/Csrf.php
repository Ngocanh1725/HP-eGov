<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Class Csrf
 * Quản lý Token chống tấn công Cross-Site Request Forgery (CSRF)
 */
class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    /**
     * Lấy token hiện tại hoặc sinh mới nếu chưa có
     *
     * @return string
     */
    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);
        if (empty($token) || !is_string($token)) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }
        return $token;
    }

    /**
     * Tái tạo CSRF token mới
     *
     * @return string
     */
    public static function regenerate(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set(self::SESSION_KEY, $token);
        return $token;
    }

    /**
     * Xác thực token người dùng gửi lên
     *
     * @param string|null $token
     * @return bool
     */
    public static function validate(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        $sessionToken = Session::get(self::SESSION_KEY);
        if (empty($sessionToken) || !is_string($sessionToken)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Sinh trường input ẩn cho HTML Form
     *
     * @return string
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }
}
