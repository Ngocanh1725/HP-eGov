<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum LoaiBuoc
 * Loại bước nghiệp vụ trong quy trình xử lý hồ sơ hành chính
 */
enum LoaiBuoc: string
{
    case TIEP_NHAN   = 'TIEP_NHAN';
    case PHAN_CONG   = 'PHAN_CONG';
    case THU_LY      = 'THU_LY';
    case LAY_Y_KIEN  = 'LAY_Y_KIEN';
    case DU_THAO     = 'DU_THAO';
    case PHE_DUYET   = 'PHE_DUYET';
    case KY_SO       = 'KY_SO';
    case DONG_DAU    = 'DONG_DAU';
    case TRA_KET_QUA = 'TRA_KET_QUA';

    /**
     * Nhãn hiển thị tiếng Việt
     */
    public function label(): string
    {
        return match ($this) {
            self::TIEP_NHAN   => 'Tiếp nhận hồ sơ',
            self::PHAN_CONG   => 'Phân công chuyên viên',
            self::THU_LY      => 'Thụ lý, thẩm định',
            self::LAY_Y_KIEN  => 'Lấy ý kiến liên thông',
            self::DU_THAO     => 'Dự thảo kết quả',
            self::PHE_DUYET   => 'Phê duyệt kết quả',
            self::KY_SO       => 'Ký số văn bản',
            self::DONG_DAU    => 'Cấp số & Đóng dấu số',
            self::TRA_KET_QUA => 'Trả kết quả giải quyết',
        };
    }
}
