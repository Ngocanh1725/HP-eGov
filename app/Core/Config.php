<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Class Config
 * Quản lý và truy xuất cấu hình theo cú pháp dot-notation (ví dụ: 'app.name', 'database.connections.mysql')
 */
class Config
{
    /** @var array<string, mixed> Bộ nhớ cache cấu hình */
    private static array $items = [];

    /** @var string Thư mục chứa các file cấu hình */
    private static string $configPath = '';

    /**
     * Khởi tạo đường dẫn cấu hình
     *
     * @param string $path Đường dẫn thư mục config
     */
    public static function setPath(string $path): void
    {
        self::$configPath = rtrim($path, '/\\');
    }

    /**
     * Lấy giá trị cấu hình theo cú pháp dot-notation
     *
     * @param string $key Khóa truy xuất (ví dụ 'app.timezone')
     * @param mixed $default Giá trị trả về nếu không tìm thấy
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $file = $parts[0];

        // Tải file cấu hình nếu chưa có trong cache
        if (!isset(self::$items[$file])) {
            $filePath = self::$configPath . DIRECTORY_SEPARATOR . $file . '.php';
            if (file_exists($filePath)) {
                $content = require $filePath;
                if (is_array($content)) {
                    self::$items[$file] = $content;
                } else {
                    self::$items[$file] = [];
                }
            } else {
                return $default;
            }
        }

        // Nếu chỉ hỏi tên file (ví dụ: Config::get('app'))
        if (count($parts) === 1) {
            return self::$items[$file];
        }

        // Duyệt theo từng cấp dot-notation
        $current = self::$items[$file];
        array_shift($parts);

        foreach ($parts as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                return $default;
            }
        }

        return $current;
    }

    /**
     * Thiết lập hoặc ghi đè giá trị cấu hình runtime
     *
     * @param string $key Khóa truy xuất
     * @param mixed $value Giá trị cần thiết lập
     */
    public static function set(string $key, mixed $value): void
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset(self::$items[$file])) {
            self::$items[$file] = [];
        }

        if (empty($parts)) {
            self::$items[$file] = $value;
            return;
        }

        $current = &self::$items[$file];
        foreach ($parts as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current = $value;
    }
}
