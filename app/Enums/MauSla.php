<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum MauSla
 * 3 màu kiểm soát tiến độ SLA theo §2.2 Kiến trúc HP-eGov
 */
enum MauSla: string
{
    case XANH = 'XANH';
    case VANG = 'VANG';
    case DO   = 'DO';

    /**
     * Nhãn hiển thị tiếng Việt
     */
    public function label(): string
    {
        return match ($this) {
            self::XANH => 'Đúng hạn',
            self::VANG => 'Sắp đến hạn',
            self::DO   => 'Quá hạn',
        };
    }

    /**
     * Mã màu theo Design System
     */
    public function color(): string
    {
        return match ($this) {
            self::XANH => '#16A34A', // Xanh lá cây
            self::VANG => '#F59E0B', // Vàng cam cảnh báo
            self::DO   => '#DC2626', // Đỏ cảnh báo khẩn cấp
        };
    }

    /**
     * Sinh HTML Badge
     */
    public function badge(): string
    {
        return sprintf(
            '<span class="sla-badge" style="background-color: %s; color: #ffffff; padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;"><span style="width:6px;height:6px;border-radius:50%%;background:#fff;"></span>%s</span>',
            $this->color(),
            htmlspecialchars($this->label())
        );
    }
}
