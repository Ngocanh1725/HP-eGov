<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Class SecurityHeadersMiddleware
 * Bổ sung các HTTP Security Headers theo chuẩn An toàn thông tin Cấp độ 3 (§3.3 & Master Rules)
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next, mixed ...$args): Response
    {
        $response = $next($request);

        $headers = Config::get('security.headers', [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(self), microphone=(), geolocation=()',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self';",
        ]);

        foreach ($headers as $name => $value) {
            $response->setHeader($name, $value);
        }

        return $response;
    }
}
