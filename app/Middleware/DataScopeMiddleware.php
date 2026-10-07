<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ErrorHandler;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\DataScopeService;
use Closure;

/**
 * Class DataScopeMiddleware
 * Tự động xác định và ràng buộc phạm vi truy cập hồ sơ (Data Scope)
 * Ngăn chặn SYSADMIN can thiệp trái phép hồ sơ nghiệp vụ
 */
class DataScopeMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next, mixed ...$args): Response
    {
        $scope = DataScopeService::getScope();

        // Chặn cứng: SYSADMIN tuyệt đối không có quyền thao tác trực tiếp trên hồ sơ nghiệp vụ
        if ($scope['type'] === 'NONE' && AuthService::hasRole('SYSADMIN')) {
            AuditService::log(
                AuthService::id(),
                'SYSADMIN_CAN_THIEP_HO_SO_403',
                'ho_so',
                null,
                null,
                "Tài khoản Quản trị viên cố gắng truy cập dữ liệu hồ sơ nghiệp vụ tại: " . $request->path()
            );

            if ($request->wantsJson()) {
                return ErrorHandler::renderError(
                    $request,
                    403,
                    "Tài khoản Quản trị hệ thống (SYSADMIN) không được phép truy cập hoặc can thiệp dữ liệu hồ sơ nghiệp vụ."
                );
            }

            return ErrorHandler::renderError(
                $request,
                403,
                "Từ chối quyền (403 Forbidden): Theo quy định an toàn hệ thống, Quản trị viên không được can thiệp vào hồ sơ hành chính nghiệp vụ."
            );
        }

        return $next($request);
    }
}
