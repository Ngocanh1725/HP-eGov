<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ErrorHandler;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;
use Closure;

/**
 * Class AuthMiddleware
 * Xác thực phiên đăng nhập của người dùng theo guard (can_bo hoặc cong_dan)
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next, mixed ...$args): Response
    {
        $guard = $args[0] ?? AuthService::GUARD_CAN_BO; // 'can_bo' hoặc 'cong_dan'

        if (!AuthService::check($guard)) {
            if ($request->wantsJson()) {
                return ErrorHandler::renderError(
                    $request,
                    401,
                    "Yêu cầu đăng nhập để truy cập tài nguyên này."
                );
            }

            Session::set('url.intended', $request->path());
            $loginUrl = ($guard === AuthService::GUARD_CONG_DAN) ? '/dang-nhap' : '/admin/dang-nhap';
            return Response::redirect(url($loginUrl));
        }

        // Nếu là cán bộ và đang bị cờ bắt buộc đổi mật khẩu lần đầu
        if ($guard === AuthService::GUARD_CAN_BO && AuthService::mustChangePassword()) {
            $path = $request->path();
            if ($path !== '/admin/doi-mat-khau-lan-dau' && $path !== '/admin/dang-xuat') {
                return Response::redirect(url('/admin/doi-mat-khau-lan-dau'));
            }
        }

        return $next($request);
    }
}
