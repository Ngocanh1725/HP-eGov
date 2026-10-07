<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\ErrorHandler;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Class CsrfMiddleware
 * Kiểm tra tính hợp lệ của mã thông báo CSRF Token đối với các phương thức ghi dữ liệu (POST, PUT, DELETE, PATCH).
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /** @var array<int, string> Các tuyến đường ngoại lệ (ví dụ: webhook từ hệ thống ngoài) */
    protected array $except = [
        '/api/*',
    ];

    public function handle(Request $request, Closure $next, mixed ...$args): Response
    {
        $method = $request->method();

        // Các phương thức an toàn (Safe HTTP methods) không cần kiểm tra CSRF
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // Bỏ qua các URL ngoại lệ
        if ($this->isExcepted($request)) {
            return $next($request);
        }

        // Lấy token từ POST body hoặc Header X-CSRF-TOKEN
        $token = $request->post('_csrf') ??
            $request->header('X-CSRF-TOKEN') ??
            $request->header('X-XSRF-TOKEN');

        if (!Csrf::validate($token)) {
            return ErrorHandler::renderError(
                $request,
                419,
                "Phiên làm việc hoặc mã xác thực biểu mẫu (CSRF Token) đã hết hạn. Vui lòng tải lại trang và thử lại."
            );
        }

        return $next($request);
    }

    /**
     * Kiểm tra xem đường dẫn có nằm trong danh sách ngoại lệ hay không
     */
    protected function isExcepted(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->except as $pattern) {
            $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#';
            if (preg_match($regex, $path)) {
                return true;
            }
        }

        return false;
    }
}
