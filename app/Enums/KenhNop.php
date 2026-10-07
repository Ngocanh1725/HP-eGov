<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum KenhNop
 * Kênh tiếp nhận hồ sơ thủ tục hành chính
 */
enum KenhNop: string
{
    case TRUC_TUYEN         = 'TRUC_TUYEN';
    case MOT_CUA            = 'MOT_CUA';
    case DICH_VU_BUU_CHINH  = 'DICH_VU_BUU_CHINH';

    /**
     * Nhãn hiển thị tiếng Việt
     */
    public function label(): string
    {
        return match ($this) {
            self::TRUC_TUYEN        => 'Cổng Dịch vụ công trực tuyến',
            self::MOT_CUA           => 'Trực tiếp tại Bộ phận Một cửa',
            self::DICH_VU_BUU_CHINH => 'Dịch vụ Bưu chính công ích',
        };
    }
}
