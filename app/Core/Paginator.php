<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Class Paginator
 * Quản lý dữ liệu phân trang và sinh mã HTML liên kết phân trang
 */
class Paginator
{
    /**
     * @param array<int, mixed> $items Danh sách bản ghi trên trang hiện tại
     * @param int $total Tổng số bản ghi
     * @param int $perPage Số bản ghi mỗi trang
     * @param int $currentPage Trang hiện tại
     * @param string $path URL đường dẫn cơ sở (không kèm query page)
     * @param array<string, mixed> $queryParams Các tham số tìm kiếm/lọc kèm theo
     */
    public function __construct(
        private array $items,
        private int $total,
        private int $perPage,
        private int $currentPage = 1,
        private string $path = '',
        private array $queryParams = []
    ) {
        $this->currentPage = max(1, $this->currentPage);
        $this->perPage = max(1, $this->perPage);
    }

    /**
     * Lấy danh sách bản ghi
     *
     * @return array<int, mixed>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Tổng số bản ghi
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * Số bản ghi mỗi trang
     */
    public function perPage(): int
    {
        return $this->perPage;
    }

    /**
     * Trang hiện tại
     */
    public function currentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Trang cuối cùng
     */
    public function lastPage(): int
    {
        return (int) ceil($this->total / $this->perPage);
    }

    /**
     * Có nhiều hơn 1 trang hay không
     */
    public function hasPages(): bool
    {
        return $this->lastPage() > 1;
    }

    /**
     * Có trang tiếp theo không
     */
    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage();
    }

    /**
     * Tạo URL cho một số trang
     */
    public function url(int $page): string
    {
        $params = $this->queryParams;
        $params['page'] = $page;
        $query = http_build_query($params);
        return $this->path . ($query ? '?' . $query : '');
    }

    /**
     * Sinh HTML các nút phân trang
     */
    public function links(): string
    {
        if (!$this->hasPages()) {
            return '';
        }

        $last = $this->lastPage();
        $curr = $this->currentPage;

        $html = '<nav class="pagination-nav" aria-label="Phân trang"><ul class="pagination">';

        // Nút Trước
        if ($curr > 1) {
            $html .= sprintf('<li class="page-item"><a class="page-link" href="%s">&laquo; Trước</a></li>', htmlspecialchars($this->url($curr - 1)));
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&laquo; Trước</span></li>';
        }

        // Các số trang (hiển thị trang xung quanh)
        $start = max(1, $curr - 2);
        $end = min($last, $curr + 2);

        if ($start > 1) {
            $html .= sprintf('<li class="page-item"><a class="page-link" href="%s">1</a></li>', htmlspecialchars($this->url(1)));
            if ($start > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        for ($p = $start; $p <= $end; $p++) {
            if ($p === $curr) {
                $html .= sprintf('<li class="page-item active" aria-current="page"><span class="page-link">%d</span></li>', $p);
            } else {
                $html .= sprintf('<li class="page-item"><a class="page-link" href="%s">%d</a></li>', htmlspecialchars($this->url($p)), $p);
            }
        }

        if ($end < $last) {
            if ($end < $last - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $html .= sprintf('<li class="page-item"><a class="page-link" href="%s">%d</a></li>', htmlspecialchars($this->url($last)), $last);
        }

        // Nút Tiếp
        if ($this->hasMorePages()) {
            $html .= sprintf('<li class="page-item"><a class="page-link" href="%s">Sau &raquo;</a></li>', htmlspecialchars($this->url($curr + 1)));
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Sau &raquo;</span></li>';
        }

        $html .= '</ul></nav>';
        return $html;
    }
}
