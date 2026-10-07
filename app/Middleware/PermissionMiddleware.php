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
 * Class PermissionMiddleware
 * Kiểm tra phân quyền RBAC của cán bộ đối với từng tuyến đường cụ thể (§4.1 & Master Rules)
 */
class PermissionMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next, mixed ...$args): Response
    {
        // Kiểm tra xem đã đăng nhập chưa
        if (!Session::has('user_id')) {
            return ErrorHandler::renderError($request, 401, "Yêu cầu đăng nhập tài khoản cán bộ để tiếp tục.");
        }

        // Nếu không yêu cầu quyền cụ thể
        if (empty($args)) {
            return $next($request);
        }

        $userPermissions = Session::get('user_permissions', []);

        // Superadmin có quyền '*'
        if (in_array('*', $userPermissions, true)) {
            return $next($request);
        }

        // Kiểm tra có ít nhất 1 quyền thỏa mãn trong danh sách quyền yêu cầu
        $hasPermission = false;
        foreach ($args as $requiredPermission) {
            if (in_array($requiredPermission, $userPermissions, true)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            $requiredStr = implode(', ', $args);
            return ErrorHandler::renderError(
                $request,
                403,
                "Bạn không được cấp quyền truy cập chức năng này. Quyền yêu cầu: [{$requiredStr}]."
            );
        }

        return $next($request);
    }
}
