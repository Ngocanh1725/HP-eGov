<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\Router;
use App\Core\Session;

if (!function_exists('e')) {
    /**
     * Escape chuỗi HTML an toàn chống tấn công XSS
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('env')) {
    /**
     * Lấy giá trị biến môi trường
     */
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    /**
     * Lấy giá trị cấu hình theo dot-notation
     */
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('url')) {
    /**
     * Tạo đường dẫn URL đầy đủ
     */
    function url(string $path = '', array $params = []): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $base = $path;
        } else {
            $appUrl = rtrim((string)Config::get('app.url', '/HP-eGov'), '/');
            $cleanPath = ltrim($path, '/');
            $base = empty($cleanPath) ? $appUrl . '/' : "{$appUrl}/{$cleanPath}";
        }

        if (!empty($params)) {
            $query = http_build_query($params);
            $base .= (str_contains($base, '?') ? '&' : '?') . $query;
        }

        return $base;
    }
}

if (!function_exists('asset')) {
    /**
     * Tạo đường dẫn URL đến tệp tĩnh (CSS, JS, Fonts, Img)
     */
    function asset(string $path): string
    {
        $cleanPath = ltrim($path, '/');
        // Nếu path chưa có assets/ ở đầu thì tự thêm
        if (!str_starts_with($cleanPath, 'assets/')) {
            $cleanPath = 'assets/' . $cleanPath;
        }
        return url($cleanPath);
    }
}

if (!function_exists('route')) {
    /**
     * Tạo URL từ tên Route đã đăng ký
     */
    function route(string $name, array $params = []): string
    {
        return Router::route($name, $params);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Lấy token CSRF hiện tại
     */
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Sinh trường input ẩn chứa CSRF token cho HTML Form
     */
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('method_field')) {
    /**
     * Sinh trường ẩn giả lập phương thức HTTP (Method Spoofing: PUT, DELETE, PATCH)
     */
    function method_field(string $method): string
    {
        $m = htmlspecialchars(strtoupper($method), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_method" value="' . $m . '">';
    }
}

if (!function_exists('old')) {
    /**
     * Lấy lại giá trị dữ liệu form đã nhập ở request trước
     */
    function old(string $key, mixed $default = null): mixed
    {
        $oldData = Session::getFlash('_old_input', []);
        return $oldData[$key] ?? $default;
    }
}

if (!function_exists('errors')) {
    /**
     * Lấy danh sách thông báo lỗi xác thực từ request trước
     *
     * @return mixed Mảng lỗi hoặc lỗi đầu tiên của một trường cụ thể
     */
    function errors(?string $key = null): mixed
    {
        $allErrors = Session::getFlash('_errors', []);
        if ($key === null) {
            return $allErrors;
        }
        return $allErrors[$key][0] ?? null;
    }
}

if (!function_exists('flash')) {
    /**
     * Thiết lập hoặc lấy thông báo nhanh (Flash message)
     */
    function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            Session::flash($key, $value);
            return null;
        }
        return Session::getFlash($key);
    }
}

if (!function_exists('date_vn')) {
    /**
     * Định dạng ngày tháng theo phong cách tiếng Việt
     */
    function date_vn(mixed $date, string $format = 'd/m/Y'): string
    {
        if (empty($date)) {
            return '';
        }

        if ($date instanceof DateTimeInterface) {
            return $date->format($format);
        }

        $timestamp = is_numeric($date) ? (int)$date : strtotime((string)$date);
        if ($timestamp === false) {
            return (string)$date;
        }

        return date($format, $timestamp);
    }
}

if (!function_exists('auth')) {
    /**
     * Lấy thông tin người dùng đang đăng nhập hoặc kiểm tra guard
     *
     * @param string|null $guard 'can_bo' hoặc 'cong_dan'
     * @return array<string, mixed>|null
     */
    function auth(?string $guard = null): ?array
    {
        if ($guard !== null && \App\Services\AuthService::guard() !== $guard) {
            return null;
        }
        return \App\Services\AuthService::user();
    }
}

if (!function_exists('can')) {
    /**
     * Kiểm tra quyền của người dùng hiện tại
     */
    function can(string $permissionCode): bool
    {
        return \App\Services\AuthService::can($permissionCode);
    }
}

if (!function_exists('has_role')) {
    /**
     * Kiểm tra vai trò của người dùng hiện tại
     *
     * @param string|array $role
     */
    function has_role(string|array $role): bool
    {
        return \App\Services\AuthService::hasRole($role);
    }
}

if (!function_exists('current_don_vi')) {
    /**
     * Lấy thông tin đơn vị làm việc hiện tại của cán bộ
     */
    function current_don_vi(): ?array
    {
        return \App\Services\AuthService::currentDonVi();
    }
}

