<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Class View
 * Công cụ hiển thị giao diện thuần PHP, hỗ trợ Layouts kế thừa, Section bắt khối và Partial tái sử dụng.
 */
class View
{
    private static string $viewsPath = '';
    private ?string $layout = null;
    private array $layoutData = [];
    private array $sections = [];
    private ?string $currentSection = null;

    /**
     * Thiết lập đường dẫn thư mục Views
     */
    public static function setViewsPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/\\');
    }

    /**
     * Render một view file
     *
     * @param string $view Tên view (ví dụ: 'portal/home' hoặc 'admin/mot_cua/index')
     * @param array<string, mixed> $data Dữ liệu truyền vào view
     * @return string HTML kết quả
     */
    public static function render(string $view, array $data = []): string
    {
        $instance = new self();
        return $instance->renderView($view, $data);
    }

    /**
     * Thực hiện render view nội bộ
     */
    public function renderView(string $view, array $data = []): string
    {
        $filePath = $this->resolvePath($view);
        if (!file_exists($filePath)) {
            throw new RuntimeException("Không tìm thấy file View: {$filePath}");
        }

        // Trích xuất biến vào phạm vi cục bộ
        extract($data, EXTR_SKIP);

        ob_start();
        include $filePath;
        $content = ob_get_clean();

        // Nếu view khai báo sử dụng layout
        if ($this->layout !== null) {
            $layoutPath = $this->resolvePath($this->layout);
            if (!file_exists($layoutPath)) {
                throw new RuntimeException("Không tìm thấy Layout: {$layoutPath}");
            }

            // Gộp dữ liệu layout
            $layoutVars = array_merge($data, $this->layoutData);
            // Mặc định nội dung chính được gán vào section 'content' nếu chưa có section
            if (!isset($this->sections['content'])) {
                $this->sections['content'] = $content;
            }

            extract($layoutVars, EXTR_SKIP);

            ob_start();
            include $layoutPath;
            return ob_get_clean() ?: '';
        }

        return $content ?: '';
    }

    /**
     * Khai báo Layout mà view này kế thừa
     *
     * @param string $layout Tên layout (ví dụ: 'layouts/admin')
     * @param array<string, mixed> $data Dữ liệu bổ sung cho layout
     */
    public function layout(string $layout, array $data = []): void
    {
        $this->layout = $layout;
        $this->layoutData = $data;
    }

    /**
     * Bắt đầu một khối Section (alias của startSection)
     */
    public function section(string $name): void
    {
        $this->startSection($name);
    }

    /**
     * Bắt đầu một khối Section
     */
    public function startSection(string $name): void
    {
        if ($this->currentSection !== null) {
            throw new RuntimeException("Không thể lồng section bên trong section khác ({$this->currentSection}).");
        }
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * Kết thúc khối Section
     */
    public function endSection(): void
    {
        if ($this->currentSection === null) {
            throw new RuntimeException("Gọi endSection() mà không có startSection().");
        }
        $content = ob_get_clean();
        $this->sections[$this->currentSection] = $content ?: '';
        $this->currentSection = null;
    }

    /**
     * Chèn nội dung của một Section vào Layout
     *
     * @param string $name Tên section
     * @param string $default Giá trị mặc định nếu section không tồn tại
     * @return string
     */
    public function yieldSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Chèn một Partial (thành phần giao diện nhỏ)
     *
     * @param string $partial Tên partial (ví dụ: 'partials/header')
     * @param array<string, mixed> $data Dữ liệu truyền vào partial
     * @return void
     */
    public function partial(string $partial, array $data = []): void
    {
        $filePath = $this->resolvePath($partial);
        if (!file_exists($filePath)) {
            throw new RuntimeException("Không tìm thấy Partial: {$filePath}");
        }

        extract($data, EXTR_SKIP);
        include $filePath;
    }

    /**
     * Chuyển đổi tên view thành đường dẫn file .php
     */
    private function resolvePath(string $view): string
    {
        $normalized = str_replace('.', DIRECTORY_SEPARATOR, $view);
        $normalized = str_replace('/', DIRECTORY_SEPARATOR, $normalized);

        return self::$viewsPath . DIRECTORY_SEPARATOR . $normalized . '.php';
    }
}
