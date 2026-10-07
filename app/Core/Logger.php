<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Class Logger
 * Ghi nhật ký hệ thống đơn giản theo chuẩn PSR-3, tự động phân tách tệp theo ngày trong storage/logs/
 */
class Logger
{
    private static string $logPath = '';

    public const EMERGENCY = 'EMERGENCY';
    public const ALERT     = 'ALERT';
    public const CRITICAL  = 'CRITICAL';
    public const ERROR     = 'ERROR';
    public const WARNING   = 'WARNING';
    public const NOTICE    = 'NOTICE';
    public const INFO      = 'INFO';
    public const DEBUG     = 'DEBUG';

    /**
     * Thiết lập đường dẫn thư mục logs
     */
    public static function setLogPath(string $path): void
    {
        self::$logPath = rtrim($path, '/\\');
    }

    public static function emergency(string $message, array $context = []): void
    {
        self::log(self::EMERGENCY, $message, $context);
    }

    public static function alert(string $message, array $context = []): void
    {
        self::log(self::ALERT, $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log(self::CRITICAL, $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log(self::ERROR, $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log(self::WARNING, $message, $context);
    }

    public static function notice(string $message, array $context = []): void
    {
        self::log(self::NOTICE, $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log(self::INFO, $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log(self::DEBUG, $message, $context);
    }

    /**
     * Ghi log với cấp độ chỉ định
     *
     * @param string $level Cấp độ log
     * @param string $message Thông điệp
     * @param array<string, mixed> $context Bối cảnh đi kèm
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        if (empty(self::$logPath)) {
            $baseDir = dirname(__DIR__, 2);
            self::$logPath = $baseDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        }

        if (!is_dir(self::$logPath)) {
            @mkdir(self::$logPath, 0755, true);
        }

        $date = date('Y-m-d');
        $file = self::$logPath . DIRECTORY_SEPARATOR . "hp-egov-{$date}.log";

        $time = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $entry = sprintf("[%s] [%s] %s%s%s", $time, strtoupper($level), $message, $contextStr, PHP_EOL);

        @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
    }
}
