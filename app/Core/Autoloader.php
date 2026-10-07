<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Class Autoloader
 * Tự động nạp lớp theo chuẩn PSR-4 cho namespace App\ trỏ vào thư mục app/
 * Không phụ thuộc vào Composer.
 */
class Autoloader
{
    /** @var string Thư mục gốc của project */
    private static string $baseDir;

    /**
     * Đăng ký autoloader với SPL
     *
     * @param string $baseDir Thư mục gốc chứa mã nguồn
     */
    public static function register(string $baseDir): void
    {
        self::$baseDir = rtrim($baseDir, '/\\');

        spl_autoload_register(function (string $class): void {
            $prefix = 'App\\';
            $len = strlen($prefix);

            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relativeClass = substr($class, $len);
            $file = self::$baseDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR .
                str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
            }
        });
    }
}
