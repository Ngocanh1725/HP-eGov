<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use RuntimeException;

/**
 * Class Response
 * Quản lý và phát tín hiệu phản hồi HTTP (HTML, JSON, Redirect, Tải file an toàn).
 */
class Response
{
    /** @var string Nội dung phản hồi */
    private string $content = '';

    /** @var int HTTP Status code */
    private int $statusCode = 200;

    /** @var array<string, string> Các header phản hồi */
    private array $headers = [];

    /** @var string|null Đường dẫn file nếu là phản hồi dạng download */
    private ?string $downloadFilePath = null;

    /** @var string|null Tên file hiển thị khi tải xuống */
    private ?string $downloadFileName = null;

    /**
     * Khởi tạo Response
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Thiết lập HTTP Status Code
     */
    public function setStatusCode(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Lấy HTTP Status Code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Thiết lập nội dung
     */
    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Lấy nội dung
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Thêm header
     */
    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Phản hồi dạng JSON
     *
     * @param mixed $data Dữ liệu cần serialize
     * @param int $status Mã trạng thái HTTP
     * @return static
     */
    public static function json(mixed $data, int $status = 200): static
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '{"error":"Không thể mã hóa dữ liệu JSON"}';
            $status = 500;
        }

        $res = new static($json, $status);
        $res->setHeader('Content-Type', 'application/json; charset=utf-8');
        return $res;
    }

    /**
     * Phản hồi dạng HTML
     *
     * @param string $html
     * @param int $status
     * @return static
     */
    public static function html(string $html, int $status = 200): static
    {
        $res = new static($html, $status);
        $res->setHeader('Content-Type', 'text/html; charset=utf-8');
        return $res;
    }

    /**
     * Phản hồi chuyển hướng (Redirect)
     *
     * @param string $url URL đích
     * @param int $status Mã chuyển hướng (301, 302, 303)
     * @return static
     */
    public static function redirect(string $url, int $status = 302): static
    {
        $res = new static('', $status);
        $res->setHeader('Location', $url);
        return $res;
    }

    /**
     * Tải tệp xuống an toàn (chống tấn công Path Traversal / Directory Traversal)
     *
     * @param string $filePath Đường dẫn file trên ổ đĩa
     * @param string|null $downloadName Tên gợi ý khi tải về (tùy chọn)
     * @return static
     */
    public static function download(string $filePath, ?string $downloadName = null): static
    {
        $realPath = realpath($filePath);
        if ($realPath === false || !is_file($realPath)) {
            throw new RuntimeException("Tệp tin yêu cầu không tồn tại: {$filePath}");
        }

        // Kiểm tra chống Path Traversal: đường dẫn file phải nằm trong project
        $projectRoot = realpath(dirname(__DIR__, 2));
        if ($projectRoot !== false && !str_starts_with($realPath, $projectRoot)) {
            throw new InvalidArgumentException("Từ chối truy cập tệp tin ngoài phạm vi hệ thống.");
        }

        $fileName = $downloadName ?? basename($realPath);
        // Làm sạch tên file chống tấn công Header Injection
        $fileName = preg_replace('/[^\w\.\-\s_]/u', '_', $fileName);

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $realPath) : 'application/octet-stream';
        if ($finfo) {
            finfo_close($finfo);
        }

        $res = new static('', 200);
        $res->downloadFilePath = $realPath;
        $res->downloadFileName = $fileName;

        $res->setHeader('Content-Type', $mime ?: 'application/octet-stream');
        $res->setHeader('Content-Disposition', 'attachment; filename="' . rawurlencode($fileName) . '"');
        $res->setHeader('Content-Length', (string)filesize($realPath));
        $res->setHeader('Pragma', 'no-cache');
        $res->setHeader('Expires', '0');

        return $res;
    }

    /**
     * Gửi toàn bộ headers và output về Client
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        if ($this->downloadFilePath !== null) {
            readfile($this->downloadFilePath);
            exit;
        }

        echo $this->content;
    }
}
