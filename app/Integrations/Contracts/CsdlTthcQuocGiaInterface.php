<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

/**
 * Interface CsdlTthcQuocGiaInterface
 * Hợp đồng tích hợp Trục Cơ sở dữ liệu Quốc gia về Thủ tục hành chính (VPCP / Cổng DVC Quốc gia)
 */
interface CsdlTthcQuocGiaInterface
{
    /**
     * Đồng bộ danh sách TTHC mới nhất từ CSDL Quốc gia
     *
     * @return array{
     *   added: array<int, array<string, mixed>>,
     *   updated: array<int, array<string, mixed>>,
     *   deprecated: array<int, array<string, mixed>>,
     *   sync_time: string,
     *   total_changes: int
     * }
     */
    public function sync(): array;

    /**
     * Lấy chi tiết thủ tục hành chính từ CSDL Quốc gia
     *
     * @param string $maTthc Mã TTHC quốc gia
     * @return array<string, mixed>|null
     */
    public function getDetail(string $maTthc): ?array;
}
