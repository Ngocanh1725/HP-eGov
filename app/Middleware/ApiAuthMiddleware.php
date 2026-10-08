<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\ApiTokenService;
use Closure;

/**
 * Class ApiAuthMiddleware
 * Xác thực Bearer Token cho các endpoint REST API /api/v1
 * Hỗ trợ kiểm tra scopes: api_auth, api_auth:cong_dan, api_auth:lanh_dao
 */
class ApiAuthMiddleware implements MiddlewareInterface
{
    private ApiTokenService $tokenService;

    public function __construct()
    {
        $this->tokenService = new ApiTokenService();
    }

    public function handle(Request $request, Closure $next, mixed ...$args): Response
    {
        // 1. Lấy Authorization Header
        $authHeader = $request->header('Authorization') ?: $request->header('HTTP_AUTHORIZATION');

        if (!$authHeader || !preg_match('/^Bearer\s+(.+)$/i', trim($authHeader), $matches)) {
            return Response::json([
                'status'    => 'error',
                'code'      => 401,
                'message'   => 'Thiếu Bearer Token xác thực trong tiêu đề Authorization',
                'timestamp' => date('c'),
            ], 401);
        }

        $rawToken = trim($matches[1]);

        // 2. Kiểm tra token hợp lệ trong CSDL
        $tokenInfo = $this->tokenService->verifyToken($rawToken);
        if (!$tokenInfo) {
            return Response::json([
                'status'    => 'error',
                'code'      => 401,
                'message'   => 'Bearer Token không hợp lệ hoặc đã hết hạn',
                'timestamp' => date('c'),
            ], 401);
        }

        // 3. Kiểm tra Scope yêu cầu (nếu middleware có tham số, ví dụ api_auth:lanh_dao)
        $requiredScope = $args[0] ?? null;
        if ($requiredScope && !$this->tokenService->hasScope($tokenInfo, (string)$requiredScope)) {
            return Response::json([
                'status'    => 'error',
                'code'      => 403,
                'message'   => "Token không có quyền truy cập phạm vi này (Yêu cầu scope: '{$requiredScope}')",
                'timestamp' => date('c'),
            ], 403);
        }

        // 4. Lưu thông tin xác thực vào thuộc tính Request
        $request->setAttribute('api_token', $tokenInfo);
        $request->setAttribute('api_user_id', $tokenInfo['nguoi_dung_id'] ? (int)$tokenInfo['nguoi_dung_id'] : null);
        $request->setAttribute('api_citizen_id', $tokenInfo['cong_dan_id'] ? (int)$tokenInfo['cong_dan_id'] : null);

        return $next($request);
    }
}
