<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

/**
 * Interface MiddlewareInterface
 * Chuẩn hóa các bộ lọc trung gian xử lý HTTP Request
 */
interface MiddlewareInterface
{
    /**
     * Xử lý request và chuyển tiếp đến middleware tiếp theo
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @param mixed ...$args Các tham số bổ sung (ví dụ quyền hạn)
     * @return Response
     */
    public function handle(Request $request, Closure $next, mixed ...$args): Response;
}
