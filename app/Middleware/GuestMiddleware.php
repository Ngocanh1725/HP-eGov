<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

/**
 * Class GuestMiddleware
 * Chặn người dùng đã đăng nhập quay lại các trang đăng ký / đăng nhập
 */
class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next, mixed ...$args): Response
    {
        if (Session::has('user_id')) {
            return Response::redirect(url('/admin/dashboard'));
        }

        if (Session::has('cong_dan_id')) {
            return Response::redirect(url('/cong-dan/ho-so-cua-toi'));
        }

        return $next($request);
    }
}
