<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Class Request
 * Đóng gói và xử lý HTTP Request, lọc dữ liệu đầu vào, hỗ trợ Method Spoofing,
 * đọc JSON payload, thông tin file và Client IP an toàn.
 */
class Request
{
    /** @var array<string, mixed> Mảng tham số GET */
    private array $query;

    /** @var array<string, mixed> Mảng tham số POST */
    private array $post;

    /** @var array<string, mixed> Mảng Server headers và biến môi trường */
    private array $server;

    /** @var array<string, mixed> Mảng Cookies */
    private array $cookies;

    /** @var array<string, mixed> Mảng Files upload */
    private array $files;

    /** @var array<string, mixed> Mảng tham số định tuyến từ URL */
    private array $routeParams = [];

    /** @var array<string, mixed> Dữ liệu JSON body sau khi parse */
    private ?array $jsonBody = null;

    /** @var array<string, mixed> Thuộc tính tùy biến truyền qua middleware */
    private array $attributes = [];

    /**
     * Gán thuộc tính tùy biến cho request
     */
    public function setAttribute(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Lấy thuộc tính tùy biến từ request
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Khởi tạo Request từ các biến siêu toàn cục của PHP
     */
    public function __construct(
        array $query = [],
        array $post = [],
        array $server = [],
        array $cookies = [],
        array $files = []
    ) {
        $this->query = $query;
        $this->post = $post;
        $this->server = $server;
        $this->cookies = $cookies;
        $this->files = $files;
    }

    /**
     * Gán mảng tham số route từ Router
     */
    public function setRouteParams(array $params): static
    {
        $this->routeParams = $params;
        return $this;
    }

    /**
     * Lấy toàn bộ mảng tham số route
     */
    public function routeParams(): array
    {
        return $this->routeParams;
    }

    /**
     * Lấy một tham số route theo key
     */
    public function routeParam(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * Tạo đối tượng Request từ trạng thái toàn cục hiện tại
     *
     * @return static
     */
    public static function capture(): static
    {
        return new static($_GET, $_POST, $_SERVER, $_COOKIE, $_FILES);
    }

    /**
     * Lấy phương thức HTTP thực tế hoặc phương thức được giả lập (Method Spoofing)
     * Hỗ trợ trường `_method` trong form POST hoặc header `X-HTTP-Method-Override`.
     *
     * @return string (GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD)
     */
    public function method(): string
    {
        $realMethod = $this->realMethod();

        if ($realMethod === 'POST') {
            // Kiểm tra trường ẩn trong form
            if (isset($this->post['_method'])) {
                $override = strtoupper(trim((string)$this->post['_method']));
                if (in_array($override, ['PUT', 'DELETE', 'PATCH'], true)) {
                    return $override;
                }
            }

            // Kiểm tra header
            $headerOverride = $this->header('X-HTTP-Method-Override');
            if ($headerOverride !== null) {
                $override = strtoupper(trim($headerOverride));
                if (in_array($override, ['PUT', 'DELETE', 'PATCH'], true)) {
                    return $override;
                }
            }
        }

        return $realMethod;
    }

    /**
     * Lấy phương thức HTTP gốc từ Web Server
     */
    public function realMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Kiểm tra phương thức HTTP
     */
    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    /**
     * Lấy đường dẫn URI tương đối đã chuẩn hóa (bỏ query string và thư mục gốc nếu có)
     * Ví dụ: /HP-eGov/admin/mot-cua/tiep-nhan?tab=1 -> /admin/mot-cua/tiep-nhan
     *
     * @return string
     */
    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Lấy base path từ SCRIPT_NAME (ví dụ: /HP-eGov/public/index.php -> /HP-eGov/public hoặc /HP-eGov)
        $scriptName = $this->server['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName); // e.g. /HP-eGov/public hoặc \HP-eGov\public
        $scriptDir = str_replace('\\', '/', $scriptDir);

        if ($scriptDir !== '/' && $scriptDir !== '.' && !empty($scriptDir)) {
            if (str_starts_with($path, $scriptDir)) {
                $path = substr($path, strlen($scriptDir));
            } else {
                // Kiểm tra trường hợp rewrite từ thư mục cha (ví dụ: /HP-eGov)
                $parentDir = dirname($scriptDir);
                $parentDir = str_replace('\\', '/', $parentDir);
                if ($parentDir !== '/' && $parentDir !== '.' && !empty($parentDir)) {
                    if (str_starts_with($path, $parentDir)) {
                        $path = substr($path, strlen($parentDir));
                    }
                }
            }
        }

        // Bỏ tiền tố /public nếu còn sót lại
        if (str_starts_with($path, '/public')) {
            $path = substr($path, 7);
        }

        $path = '/' . trim($path, '/');
        return $path === '' ? '/' : $path;
    }

    /**
     * Lấy toàn bộ dữ liệu đầu vào đã gộp (JSON + POST + GET)
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->json());
    }

    /**
     * Lấy một giá trị đầu vào theo key
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        $all = $this->all();
        if ($key === null) {
            return $all;
        }

        return $all[$key] ?? $default;
    }

    /**
     * Alias thuận tiện cho input()
     */
    public function get(?string $key = null, mixed $default = null): mixed
    {
        return $this->input($key, $default);
    }

    /**
     * Lấy giá trị từ query string (GET)
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    /**
     * Lấy giá trị từ form body (POST)
     */
    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    /**
     * Phân tích và lấy dữ liệu JSON từ request body
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($this->jsonBody === null) {
            $raw = file_get_contents('php://input');
            if (!empty($raw) && $this->isJsonContentType()) {
                $decoded = json_decode($raw, true);
                $this->jsonBody = is_array($decoded) ? $decoded : [];
            } else {
                $this->jsonBody = [];
            }
        }

        if ($key === null) {
            return $this->jsonBody;
        }

        return $this->jsonBody[$key] ?? $default;
    }

    /**
     * Kiểm tra xem Content-Type có phải là JSON
     */
    private function isJsonContentType(): bool
    {
        $contentType = $this->header('Content-Type') ?? '';
        return str_contains(strtolower($contentType), 'application/json');
    }

    /**
     * Lấy thông tin tệp tải lên
     *
     * @param string $key
     * @return array<string, mixed>|null
     */
    /**
     * Lấy danh sách toàn bộ các file được gửi lên
     *
     * @return array<string, mixed>
     */
    public function files(): array
    {
        return $this->files;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Kiểm tra có file tải lên hợp lệ không
     */
    public function hasFile(string $key): bool
    {
        $file = $this->file($key);
        return $file !== null &&
            isset($file['error']) &&
            $file['error'] === UPLOAD_ERR_OK &&
            !empty($file['tmp_name']) &&
            is_uploaded_file($file['tmp_name']);
    }

    /**
     * Lấy giá trị một HTTP Header
     */
    public function header(string $name, ?string $default = null): ?string
    {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($this->server[$normalized])) {
            return (string)$this->server[$normalized];
        }

        // Trường hợp đặc biệt không có tiền tố HTTP_
        $direct = strtoupper(str_replace('-', '_', $name));
        if (isset($this->server[$direct])) {
            return (string)$this->server[$direct];
        }

        return $default;
    }

    /**
     * Lấy địa chỉ IP của Client một cách an toàn
     */
    public function ip(): string
    {
        // Trên môi trường WAF/Reverse Proxy đã được chứng thực, có thể đọc X-Forwarded-For
        // Mặc định lấy REMOTE_ADDR để tránh IP Spoofing
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Kiểm tra xem có phải AJAX request không
     */
    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    /**
     * Kiểm tra client có yêu cầu trả về định dạng JSON hay không
     */
    public function wantsJson(): bool
    {
        $accept = $this->header('Accept') ?? '';
        return str_contains(strtolower($accept), 'application/json') || $this->isAjax();
    }
}
