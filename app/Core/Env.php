<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Class Env
 * Đọc và phân tích cú pháp tệp cấu hình môi trường .env
 */
class Env
{
    /** @var array<string, mixed> Mảng lưu trữ các biến môi trường */
    private static array $variables = [];

    /** @var bool Trạng thái đã tải hay chưa */
    private static bool $loaded = false;

    /**
     * Nạp file .env từ đường dẫn chỉ định
     *
     * @param string $path Đường dẫn tệp .env
     */
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Bỏ qua dòng comment hoặc dòng trống
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Tách Key=Value
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $val = trim($parts[1]);

            // Xử lý giá trị có dấu ngoặc kép hoặc đơn
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            } else {
                // Cắt bỏ comment ở cuối dòng nếu không nằm trong ngoặc
                $commentPos = strpos($val, ' #');
                if ($commentPos !== false) {
                    $val = trim(substr($val, 0, $commentPos));
                }
            }

            // Chuyển đổi kiểu dữ liệu
            $parsedVal = match (strtolower($val)) {
                'true', '(true)' => true,
                'false', '(false)' => false,
                'empty', '(empty)', '""', "''" => '',
                'null', '(null)' => null,
                default => $val,
            };

            self::$variables[$key] = $parsedVal;
            $_ENV[$key] = $parsedVal;
            $_SERVER[$key] = $parsedVal;
            putenv("{$key}={$val}");
        }

        self::$loaded = true;
    }

    /**
     * Lấy giá trị biến môi trường
     *
     * @param string $key Tên biến môi trường
     * @param mixed $default Giá trị mặc định nếu không tồn tại
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$variables)) {
            return self::$variables[$key];
        }

        $envVal = getenv($key);
        if ($envVal !== false) {
            return match (strtolower($envVal)) {
                'true' => true,
                'false' => false,
                'null' => null,
                'empty' => '',
                default => $envVal,
            };
        }

        return $default;
    }
}
