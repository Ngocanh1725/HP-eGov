<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;
use ErrorException;

/**
 * Class ErrorHandler
 * Xử lý lỗi và ngoại lệ toàn cục, hiển thị trang lỗi thân thiện (403, 404, 419, 429, 500),
 * che giấu thông tin nhạy cảm ở môi trường production theo chuẩn An toàn cấp độ 3.
 */
class ErrorHandler
{
    /**
     * Đăng ký các bộ xử lý lỗi toàn cục với PHP
     */
    public static function register(): void
    {
        error_reporting(E_ALL);

        set_error_handler(function (int $level, string $message, string $file = '', int $line = 0): bool {
            if (!(error_reporting() & $level)) {
                return false;
            }
            throw new ErrorException($message, 0, $level, $file, $line);
        });

        set_exception_handler(function (Throwable $e): void {
            self::handleException($e);
        });

        register_shutdown_function(function (): void {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::handleException(new ErrorException(
                    $error['message'],
                    0,
                    $error['type'],
                    $error['file'],
                    $error['line']
                ));
            }
        });
    }

    /**
     * Xử lý ngoại lệ Throwable
     */
    public static function handleException(Throwable $e): void
    {
        $statusCode = 500;
        $code = (int)$e->getCode();
        if ($code >= 400 && $code < 600) {
            $statusCode = $code;
        }

        // Ghi log hệ thống
        Logger::error(
            sprintf("%s: %s in %s:%d", get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()),
            ['trace' => $e->getTraceAsString()]
        );

        $request = Request::capture();
        self::renderError($request, $statusCode, $e->getMessage(), $e);
    }

    /**
     * Hiển thị trang 404 Không tìm thấy trang
     */
    public static function renderNotFound(Request $request): Response
    {
        return self::renderError($request, 404, "Không tìm thấy trang hoặc tài nguyên yêu cầu.");
    }

    /**
     * Hiển thị trang lỗi tương ứng theo chuẩn mã HTTP
     */
    public static function renderError(
        Request $request,
        int $statusCode,
        string $message,
        ?Throwable $exception = null
    ): Response {
        $isDebug = (bool)Config::get('app.debug', false);

        $titles = [
            400 => '400 - Yêu cầu không hợp lệ',
            401 => '401 - Chưa xác thực danh tính',
            403 => '403 - Truy cập bị từ chối',
            404 => '404 - Không tìm thấy trang',
            405 => '405 - Phương thức không được phép',
            419 => '419 - Phiên làm việc đã hết hạn (CSRF)',
            429 => '429 - Quá số lượng yêu cầu cho phép',
            500 => '500 - Lỗi máy chủ nội bộ',
        ];

        $title = $titles[$statusCode] ?? "{$statusCode} - Lỗi hệ thống";

        // Nếu client yêu cầu JSON hoặc là AJAX request
        if ($request->wantsJson()) {
            $safeMessage = ($isDebug || $statusCode < 500) ? $message : 'Đã xảy ra sự cố kỹ thuật nội bộ.';
            $data = [
                'status'  => 'error',
                'code'    => $statusCode,
                'message' => $safeMessage,
            ];

            if ($isDebug && $exception !== null) {
                $data['debug'] = [
                    'exception' => get_class($exception),
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                    'trace'     => explode("\n", $exception->getTraceAsString()),
                ];
            }

            $res = Response::json($data, $statusCode);
            if (php_sapi_name() !== 'cli') {
                $res->send();
                exit;
            }
            return $res;
        }

        // Kiểm tra xem có view template chuyên dụng trong app/Views/errors/{code}.php hay không
        $viewFile = "errors/{$statusCode}";
        $baseDir = dirname(__DIR__, 2);
        $fullPath = $baseDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Views' .
            DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . "{$statusCode}.php";

        if (file_exists($fullPath)) {
            $html = View::render($viewFile, [
                'title'     => $title,
                'message'   => $message,
                'code'      => $statusCode,
                'exception' => $isDebug ? $exception : null,
                'isDebug'   => $isDebug,
            ]);
            $res = Response::html($html, $statusCode);
            if (php_sapi_name() !== 'cli') {
                $res->send();
                exit;
            }
            return $res;
        }

        // Giao diện fallback mặc định trang nhã với tone màu Navy #0B2E59
        $debugInfoHtml = '';
        if ($isDebug && $exception !== null) {
            $trace = htmlspecialchars($exception->getTraceAsString());
            $file = htmlspecialchars($exception->getFile());
            $line = $exception->getLine();
            $debugInfoHtml = "
                <div style='margin-top:20px;text-align:left;background:#f8fafc;padding:16px;border-radius:8px;border:1px solid #cbd5e1;font-family:Consolas,monospace;font-size:13px;max-height:300px;overflow:auto;'>
                    <strong>Ngoại lệ:</strong> " . get_class($exception) . "<br>
                    <strong>Tệp:</strong> {$file} : <strong>Dòng {$line}</strong><br><br>
                    <strong>Chi tiết Stack Trace:</strong><br>
                    <pre style='margin:0;white-space:pre-wrap;'>{$trace}</pre>
                </div>
            ";
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} | HP-eGov Hải Phòng</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f6fa;
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .error-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            max-width: 650px;
            width: 100%;
            padding: 40px;
            text-align: center;
            border-top: 5px solid #0B2E59;
        }
        .code-badge {
            display: inline-block;
            background: #e2e8f0;
            color: #0B2E59;
            font-size: 14px;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 9999px;
            margin-bottom: 16px;
        }
        h1 { font-size: 24px; color: #0B2E59; margin-bottom: 12px; }
        p { color: #64748b; font-size: 15px; line-height: 1.6; margin-bottom: 24px; }
        .btn-home {
            display: inline-block;
            background: #0B2E59;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-home:hover { background: #082242; }
    </style>
</head>
<body>
    <div class="error-card">
        <span class="code-badge">MÃ LỖI: {$statusCode}</span>
        <h1>{$title}</h1>
        <p>{$message}</p>
        <a href="/HP-eGov/" class="btn-home">Về Trang chủ HP-eGov</a>
        {$debugInfoHtml}
    </div>
</body>
</html>
HTML;

        $res = Response::html($html, $statusCode);
        if (php_sapi_name() !== 'cli') {
            $res->send();
            exit;
        }
        return $res;
    }
}
