<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class Route
 * Đại diện cho một tuyến đường cụ thể
 */
class Route
{
    private string $method;
    private string $uri;
    private mixed $action;
    private array $middlewares = [];
    private ?string $name = null;
    private array $whereConditions = [];

    public function __construct(string $method, string $uri, mixed $action, array $middlewares = [])
    {
        $this->method = strtoupper($method);
        $this->uri = '/' . trim($uri, '/');
        $this->action = $action;
        $this->middlewares = $middlewares;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getAction(): mixed
    {
        return $this->action;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        Router::registerNamedRoute($name, $this);
        return $this;
    }

    public function middleware(string|array $middleware): static
    {
        $m = is_array($middleware) ? $middleware : [$middleware];
        $this->middlewares = array_merge($this->middlewares, $m);
        return $this;
    }

    public function where(string|array $name, ?string $expression = null): static
    {
        if (is_array($name)) {
            $this->whereConditions = array_merge($this->whereConditions, $name);
        } else {
            $this->whereConditions[$name] = $expression ?? '';
        }
        return $this;
    }

    /**
     * Kiểm tra và so khớp URI với Route, trích xuất tham số
     *
     * @param string $path Đường dẫn từ Request
     * @param array<string, string> &$params Mảng tham số trích xuất được
     * @return bool
     */
    public function matches(string $path, array &$params): bool
    {
        $path = '/' . trim($path, '/');
        $pattern = $this->uri;

        // Xử lý các tham số có kèm regex: {id:\d+} hoặc {slug:[a-zA-Z0-9-]+}
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*):([^}]+)\}/', function ($m) {
            $name = $m[1];
            $regex = $m[2];
            return "(?P<{$name}>{$regex})";
        }, $pattern) ?? $pattern;

        // Xử lý các tham số thông thường: {id} hoặc có điều kiện trong $this->whereConditions
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) {
            $name = $m[1];
            $regex = $this->whereConditions[$name] ?? '[^/]+';
            return "(?P<{$name}>{$regex})";
        }, $pattern) ?? $pattern;

        $regexPattern = '#^' . $pattern . '$#u';

        if (preg_match($regexPattern, $path, $matches)) {
            $params = [];
            foreach ($matches as $k => $v) {
                if (is_string($k)) {
                    $params[$k] = $v;
                }
            }
            return true;
        }

        return false;
    }
}

/**
 * Class Router
 * Quản lý danh sách các Route, xử lý Route Group, Method Spoofing,
 * thực thi Middleware Pipeline và gọi Controller / Action.
 */
class Router
{
    /** @var array<int, Route> Danh sách các route đã đăng ký */
    private static array $routes = [];

    /** @var array<string, Route> Danh sách route có tên */
    private static array $namedRoutes = [];

    /** @var array<int, array{prefix: string, middlewares: array<int, string>}> Stack lưu trữ thông tin Route Group */
    private static array $groupStack = [];

    /**
     * Đăng ký một route GET
     */
    public static function get(string $uri, mixed $action): Route
    {
        return self::addRoute('GET', $uri, $action);
    }

    /**
     * Đăng ký một route POST
     */
    public static function post(string $uri, mixed $action): Route
    {
        return self::addRoute('POST', $uri, $action);
    }

    /**
     * Đăng ký một route PUT
     */
    public static function put(string $uri, mixed $action): Route
    {
        return self::addRoute('PUT', $uri, $action);
    }

    /**
     * Đăng ký một route DELETE
     */
    public static function delete(string $uri, mixed $action): Route
    {
        return self::addRoute('DELETE', $uri, $action);
    }

    /**
     * Tạo nhóm Route (hỗ trợ tiền tố URI prefix và middleware)
     *
     * @param array{prefix?: string, middleware?: string|array<int, string>} $attributes
     * @param callable $callback
     */
    public static function group(array $attributes, callable $callback): void
    {
        $prefix = $attributes['prefix'] ?? '';
        $middleware = $attributes['middleware'] ?? [];
        if (is_string($middleware)) {
            $middleware = [$middleware];
        }

        // Lấy prefix cha nếu có
        $currentPrefix = '';
        $currentMiddlewares = [];
        if (!empty(self::$groupStack)) {
            $parent = end(self::$groupStack);
            $currentPrefix = $parent['prefix'];
            $currentMiddlewares = $parent['middlewares'];
        }

        $newPrefix = rtrim($currentPrefix, '/') . '/' . trim($prefix, '/');
        $newMiddlewares = array_merge($currentMiddlewares, $middleware);

        self::$groupStack[] = [
            'prefix' => $newPrefix,
            'middlewares' => $newMiddlewares,
        ];

        $callback();

        array_pop(self::$groupStack);
    }

    /**
     * Đăng ký route nội bộ
     */
    private static function addRoute(string $method, string $uri, mixed $action): Route
    {
        $prefix = '';
        $middlewares = [];

        if (!empty(self::$groupStack)) {
            $current = end(self::$groupStack);
            $prefix = $current['prefix'];
            $middlewares = $current['middlewares'];
        }

        $fullUri = rtrim($prefix, '/') . '/' . ltrim($uri, '/');
        $fullUri = '/' . trim($fullUri, '/');

        $route = new Route($method, $fullUri, $action, $middlewares);
        self::$routes[] = $route;

        return $route;
    }

    /**
     * Đăng ký tên cho Route
     */
    public static function registerNamedRoute(string $name, Route $route): void
    {
        self::$namedRoutes[$name] = $route;
    }

    /**
     * Lấy URL từ tên Route (Reverse Routing)
     *
     * @param string $name Tên route
     * @param array<string, mixed> $params Các tham số truyền vào
     * @return string
     */
    public static function route(string $name, array $params = []): string
    {
        if (!isset(self::$namedRoutes[$name])) {
            throw new InvalidArgumentException("Không tìm thấy route có tên: {$name}");
        }

        $route = self::$namedRoutes[$name];
        $uri = $route->getUri();

        foreach ($params as $k => $v) {
            // Thay thế cả định dạng {param:regex} và {param}
            $uri = preg_replace('/\{' . $k . '(:[^}]+)?\}/', (string)$v, $uri) ?? $uri;
        }

        return url($uri);
    }

    /**
     * Điều hướng và thực thi Request
     *
     * @param Request $request
     * @return Response
     */
    public static function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        $matchedRoute = null;
        $routeParams = [];

        foreach (self::$routes as $route) {
            if ($route->getMethod() === $method && $route->matches($path, $routeParams)) {
                $matchedRoute = $route;
                break;
            }
        }

        if ($matchedRoute === null) {
            // Kiểm tra xem có route nào trùng path nhưng khác method hay không (405 Method Not Allowed)
            $allowedMethods = [];
            foreach (self::$routes as $route) {
                $dummy = [];
                if ($route->matches($path, $dummy)) {
                    $allowedMethods[] = $route->getMethod();
                }
            }

            if (!empty($allowedMethods)) {
                return Response::html(
                    '<h1>405 Method Not Allowed</h1><p>Phương thức không được hỗ trợ.</p>',
                    405
                )->setHeader('Allow', implode(', ', array_unique($allowedMethods)));
            }

            // Trả về 404 Không tìm thấy trang
            return ErrorHandler::renderNotFound($request);
        }

        // Thực thi danh sách Middleware qua Pipeline
        $pipeline = new Pipeline();
        return $pipeline
            ->send($request)
            ->through($matchedRoute->getMiddlewares())
            ->then(function (Request $req) use ($matchedRoute, $routeParams): Response {
                return self::runAction($matchedRoute->getAction(), $req, $routeParams);
            });
    }

    /**
     * Thực thi Action của route (Closure hoặc [Controller::class, 'method'])
     */
    private static function runAction(mixed $action, Request $request, array $params): Response
    {
        if ($action instanceof Closure) {
            $result = $action($request, ...array_values($params));
        } elseif (is_array($action) && count($action) === 2) {
            [$controllerClass, $method] = $action;
            if (!class_exists($controllerClass)) {
                throw new RuntimeException("Controller không tồn tại: {$controllerClass}");
            }

            $controller = new $controllerClass();
            if (!method_exists($controller, $method)) {
                throw new RuntimeException("Action không tồn tại: {$controllerClass}@{$method}");
            }

            $result = $controller->$method($request, ...array_values($params));
        } else {
            throw new InvalidArgumentException("Định dạng Route Action không hợp lệ.");
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return Response::html($result);
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::html((string)$result);
    }

    /**
     * Xóa danh sách route (tiện ích phục vụ Unit Test)
     */
    public static function clear(): void
    {
        self::$routes = [];
        self::$namedRoutes = [];
        self::$groupStack = [];
    }

    /**
     * Lấy danh sách toàn bộ routes
     *
     * @return array<int, Route>
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }
}
