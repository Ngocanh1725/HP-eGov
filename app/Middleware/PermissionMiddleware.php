<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ErrorHandler;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuditService;
use App\Services\AuthService;
use Closure;

/**
 * Class PermissionMiddleware
 * Kiểm tra quyền hạn thao tác (RBAC) của cán bộ theo mã quyền (can:quyen.ma)
 */
class PermissionMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next, mixed ...$args): Response
    {
        $permissionCode = $args[0] ?? null;

        if (empty($permissionCode)) {
            return $next($request);
        }

        // Kiểm tra quyền từ AuthService
        if (!AuthService::can($permissionCode)) {
            $userId = AuthService::id();
            $roles = implode(',', AuthService::roles());

            // Ghi nhận nhật ký kiểm toán hành vi truy cập trái phép
            AuditService::log(
                $userId,
                'TRUY_CAP_TRAI_PHEP_403',
                'phan_quyen',
                null,
                null,
                "Truy cập trái phép tính năng yêu cầu quyền [{$permissionCode}]. Vai trò hiện tại: [{$roles}], URL: " . $request->path()
            );

            if ($request->wantsJson()) {
                return ErrorHandler::renderError(
                    $request,
                    403,
                    "Bạn không có quyền [{$permissionCode}] để thực hiện thao tác này."
                );
            }

            return ErrorHandler::renderError(
                $request,
                403,
                "Từ chối truy cập (403 Forbidden): Đồng chí không được phân quyền [{$permissionCode}] để sử dụng chức năng này."
            );
        }

        return $next($request);
    }
}
