<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ErrorHandler;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

/**
 * Class AuthMiddleware
 * Xác thực phiên đăng nhập của người dùng (khung cơ sở cho Phiên 5)
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next, mixed ...$args): Response
    {
        $guard = $args[0] ?? 'can_bo'; // 'can_bo' hoặc 'cong_dan'

        $isLoggedIn = false;
        if ($guard === 'cong_dan') {
            $isLoggedIn = Session::has('cong_dan_id');
        } else {
            $isLoggedIn = Session::has('user_id');
        }

        if (!$isLoggedIn) {
            if ($request->wantsJson()) {
                return ErrorHandler::renderError(
                    $request,
                    401,
                    "Yêu cầu xác thực danh tính để truy cập tài nguyên này."
                );
            }

            // Lưu đường dẫn hiện tại để chuyển hướng lại sau khi đăng nhập
            Session::set('url.intended', $request->path());

            $loginUrl = ($guard === 'cong_dan') ? '/cong-dan/dang-nhap' : '/admin/dang-nhap';
            return Response::redirect(url($loginUrl));
        }

        return $next($request);
    }
}
