<?php

declare(strict_types=1);

/**
 * -----------------------------------------------------------------------------
 * HP-eGov: Test Runner Tối Giản (CLI)
 * Chạy kiểm thử đơn vị độc lập không cần Composer / PHPUnit
 * -----------------------------------------------------------------------------
 */

// Đảm bảo chạy từ CLI
if (php_sapi_name() !== 'cli') {
    die("Test runner chỉ có thể chạy từ Command Line Interface (CLI).\n");
}

$baseDir = dirname(__DIR__);
require_once $baseDir . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register($baseDir);
require_once $baseDir . '/app/Helpers/functions.php';

// Khởi tạo môi trường test
\App\Core\App::setBasePath($baseDir);
\App\Core\App::boot();

class TestRunner
{
    private static int $passed = 0;
    private static int $failed = 0;
    private static array $failures = [];
    private static string $currentTest = '';

    public static function run(string|array $testDirs): void
    {
        $startTime = microtime(true);
        self::printHeader("KHỞI CHẠY KIỂM THỬ HỆ THỐNG HP-eGov (UNIT & INTEGRATION)");

        $dirs = is_array($testDirs) ? $testDirs : [$testDirs];
        $files = [];
        foreach ($dirs as $dir) {
            $matched = glob($dir . '/*Test.php') ?: [];
            $files = array_merge($files, $matched);
        }

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $className = '';
            if (preg_match('/namespace\s+([^;]+);/', $content, $m)) {
                $className = trim($m[1]) . '\\' . basename($file, '.php');
            } else {
                $className = 'Tests\\Unit\\' . basename($file, '.php');
            }

            require_once $file;

            if (class_exists($className)) {
                $instance = new $className();
                $methods = get_class_methods($instance);

                self::printSuite(basename($file) . " ({$className})");

                foreach ($methods as $method) {
                    if (str_starts_with($method, 'test')) {
                        self::$currentTest = basename($file) . '::' . $method;

                        try {
                            // Gọi setUp nếu có
                            if (method_exists($instance, 'setUp')) {
                                $instance->setUp();
                            }

                            $instance->$method();
                            self::$passed++;
                            echo "  \033[32m✔\033[0m {$method}\n";
                        } catch (Throwable $e) {
                            self::$failed++;
                            self::$failures[] = [
                                'test'    => self::$currentTest,
                                'message' => $e->getMessage(),
                                'file'    => $e->getFile(),
                                'line'    => $e->getLine(),
                            ];
                            echo "  \033[31m✖\033[0m {$method} - \033[31m" . $e->getMessage() . "\033[0m\n";
                        } finally {
                            if (method_exists($instance, 'tearDown')) {
                                $instance->tearDown();
                            }
                        }
                    }
                }
                echo "\n";
            }
        }

        $elapsed = round(microtime(true) - $startTime, 4);
        self::printSummary($elapsed);

        // Thoát mã lỗi nếu có bài test hỏng
        exit(self::$failed > 0 ? 1 : 0);
    }

    public static function assertEquals(mixed $expected, mixed $actual, string $msg = ''): void
    {
        if ($expected !== $actual) {
            $expStr = var_export($expected, true);
            $actStr = var_export($actual, true);
            throw new RuntimeException($msg ?: "Kỳ vọng: {$expStr}, nhưng thực tế nhận được: {$actStr}");
        }
    }

    public static function assertTrue(mixed $condition, string $msg = ''): void
    {
        if ($condition !== true) {
            throw new RuntimeException($msg ?: "Kỳ vọng giá trị TRUE nhưng nhận được FALSE.");
        }
    }

    public static function assertFalse(mixed $condition, string $msg = ''): void
    {
        if ($condition !== false) {
            throw new RuntimeException($msg ?: "Kỳ vọng giá trị FALSE nhưng nhận được TRUE.");
        }
    }

    public static function assertNull(mixed $actual, string $msg = ''): void
    {
        if ($actual !== null) {
            throw new RuntimeException($msg ?: "Kỳ vọng giá trị NULL nhưng nhận được: " . var_export($actual, true));
        }
    }

    public static function assertNotNull(mixed $actual, string $msg = ''): void
    {
        if ($actual === null) {
            throw new RuntimeException($msg ?: "Kỳ vọng giá trị KHÁC NULL nhưng nhận được NULL.");
        }
    }

    public static function assertGreaterThan(int|float $expected, int|float $actual, string $msg = ''): void
    {
        if ($actual <= $expected) {
            throw new RuntimeException($msg ?: "Kỳ vọng {$actual} lớn hơn {$expected}.");
        }
    }

    public static function assertGreaterThanOrEqual(int|float $expected, int|float $actual, string $msg = ''): void
    {
        if ($actual < $expected) {
            throw new RuntimeException($msg ?: "Kỳ vọng {$actual} lớn hơn hoặc bằng {$expected}.");
        }
    }

    public static function assertContains(mixed $needle, array|string $haystack, string $msg = ''): void
    {
        if (is_string($haystack)) {
            if (!str_contains($haystack, (string)$needle)) {
                throw new RuntimeException($msg ?: "Chuỗi '{$haystack}' không chứa '{$needle}'.");
            }
        } elseif (is_array($haystack)) {
            if (!in_array($needle, $haystack, true)) {
                throw new RuntimeException($msg ?: "Mảng không chứa phần tử: " . var_export($needle, true));
            }
        }
    }

    public static function assertThrows(string $expectedException, callable $callback, string $msg = ''): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            if ($e instanceof $expectedException) {
                return; // Đạt kỳ vọng
            }
            throw new RuntimeException("Kỳ vọng ngoại lệ [{$expectedException}], nhưng nhận được [" . get_class($e) . "]: " . $e->getMessage());
        }

        throw new RuntimeException($msg ?: "Kỳ vọng ngoại lệ [{$expectedException}] nhưng khối lệnh không ném ngoại lệ nào.");
    }

    private static function printHeader(string $title): void
    {
        echo "\033[36m======================================================================\033[0m\n";
        echo "\033[1;36m {$title}\033[0m\n";
        echo "\033[36m======================================================================\033[0m\n\n";
    }

    private static function printSuite(string $suite): void
    {
        echo "\033[1;33m► Tập kiểm thử: {$suite}\033[0m\n";
    }

    private static function printSummary(float $elapsed): void
    {
        $total = self::$passed + self::$failed;
        echo "\033[36m----------------------------------------------------------------------\033[0m\n";
        echo "Tổng số test: \033[1m{$total}\033[0m | ";
        echo "Thành công: \033[1;32m" . self::$passed . " passed\033[0m | ";

        if (self::$failed > 0) {
            echo "Thất bại: \033[1;31m" . self::$failed . " failed\033[0m\n";
            echo "\n\033[1;31mDANH SÁCH LỖI CHI TIẾT:\033[0m\n";
            foreach (self::$failures as $idx => $fail) {
                echo ($idx + 1) . ") {$fail['test']}\n";
                echo "   {$fail['message']}\n";
                echo "   Tại: {$fail['file']}:{$fail['line']}\n\n";
            }
        } else {
            echo "Thất bại: \033[1;32m0 failed\033[0m\n";
            echo "\033[1;32m🎉 TẤT CẢ CÁC BÀI TEST ĐỀU VƯỢT QUA XUẤT SẮC!\033[0m\n";
        }

        echo "Thời gian thực thi: {$elapsed}s\n";
        echo "\033[36m======================================================================\033[0m\n";
    }
}

// Chạy các bài test trong thư mục tests/Unit, tests/Integration, tests/Security, tests/Functional, tests/uat
TestRunner::run([
    __DIR__ . '/Unit',
    __DIR__ . '/Integration',
    __DIR__ . '/Security',
    __DIR__ . '/Functional',
    __DIR__ . '/uat',
]);
