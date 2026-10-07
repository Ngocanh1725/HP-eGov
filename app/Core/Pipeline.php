<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

/**
 * Class Pipeline
 * Thực thi chuỗi các Middleware theo mô hình củ hành (Onion architecture)
 */
class Pipeline
{
    private Request $passable;
    private array $pipes = [];

    /** @var array<string, class-string<MiddlewareInterface>> Bản đồ ánh xạ alias middleware sang class */
    private static array $aliases = [
        'security_headers' => \App\Middleware\SecurityHeadersMiddleware::class,
        'csrf'             => \App\Middleware\CsrfMiddleware::class,
        'rate_limit'       => \App\Middleware\RateLimitMiddleware::class,
        'auth'             => \App\Middleware\AuthMiddleware::class,
        'guest'            => \App\Middleware\GuestMiddleware::class,
        'permission'       => \App\Middleware\PermissionMiddleware::class,
        'can'              => \App\Middleware\PermissionMiddleware::class,
        'data_scope'       => \App\Middleware\DataScopeMiddleware::class,
    ];

    /**
     * Đăng ký alias cho Middleware
     */
    public static function alias(string $name, string $class): void
    {
        self::$aliases[$name] = $class;
    }

    /**
     * Thiết lập đối tượng truyền qua pipeline
     */
    public function send(Request $passable): static
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * Thiết lập danh sách các Middleware
     */
    public function through(array $pipes): static
    {
        $this->pipes = $pipes;
        return $this;
    }

    /**
     * Thực thi pipeline và kết thúc tại đích đến (destination)
     *
     * @param Closure(Request): Response $destination
     * @return Response
     */
    public function then(Closure $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            function (Closure $next, mixed $pipe): Closure {
                return function (Request $request) use ($next, $pipe): Response {
                    return $this->resolvePipe($pipe, $request, $next);
                };
            },
            $destination
        );

        return $pipeline($this->passable);
    }

    /**
     * Khởi tạo và thực thi một Middleware
     */
    private function resolvePipe(mixed $pipe, Request $request, Closure $next): Response
    {
        $args = [];

        if (is_string($pipe)) {
            // Tách tham số nếu có dạng 'permission:hoso.view,hoso.edit'
            if (str_contains($pipe, ':')) {
                [$pipeName, $argStr] = explode(':', $pipe, 2);
                $args = explode(',', $argStr);
                $pipe = $pipeName;
            }

            if (isset(self::$aliases[$pipe])) {
                $className = self::$aliases[$pipe];
            } else {
                $className = $pipe;
            }

            if (!class_exists($className)) {
                throw new RuntimeException("Lớp Middleware không tồn tại: {$className}");
            }

            $instance = new $className();
            if (!$instance instanceof MiddlewareInterface) {
                throw new RuntimeException("Lớp {$className} phải triển khai MiddlewareInterface.");
            }

            return $instance->handle($request, $next, ...$args);
        }

        if ($pipe instanceof Closure) {
            return $pipe($request, $next);
        }

        throw new RuntimeException("Middleware không hợp lệ.");
    }
}
