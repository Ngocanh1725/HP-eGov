<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum LoaiTaiLieu
 * Phân loại tài liệu điện tử đính kèm hồ sơ
 */
enum LoaiTaiLieu: string
{
    case DAU_VAO = 'DAU_VAO';
    case SO_HOA  = 'SO_HOA';
    case DU_THAO = 'DU_THAO';
    case KET_QUA = 'KET_QUA';

    /**
     * Nhãn hiển thị tiếng Việt
     */
    public function label(): string
    {
        return match ($this) {
            self::DAU_VAO => 'Tài liệu đầu vào (Công dân nộp)',
            self::SO_HOA  => 'Hồ sơ số hóa từ bản giấy (NĐ 107/2021)',
            self::DU_THAO => 'Dự thảo kết quả giải quyết',
            self::KET_QUA => 'Kết quả giải quyết chính thức (Đã ký số)',
        };
    }
}
