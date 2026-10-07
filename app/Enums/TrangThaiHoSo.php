<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum TrangThaiHoSo
 * Trạng thái vòng đời hồ sơ hành chính theo Thông tư 01/2018/TT-VPCP và chuẩn quốc gia
 */
enum TrangThaiHoSo: string
{
    case CHO_TIEP_NHAN = 'CHO_TIEP_NHAN';
    case MOI_TIEP_NHAN = 'MOI_TIEP_NHAN';
    case DANG_XU_LY    = 'DANG_XU_LY';
    case CHO_BO_SUNG   = 'CHO_BO_SUNG';
    case CHO_PHE_DUYET = 'CHO_PHE_DUYET';
    case HOAN_THANH    = 'HOAN_THANH';
    case DA_TRA_KET_QUA = 'DA_TRA_KET_QUA';
    case TU_CHOI       = 'TU_CHOI';
    case DA_HUY        = 'DA_HUY';

    /**
     * Nhãn tiếng Việt
     */
    public function label(): string
    {
        return match ($this) {
            self::CHO_TIEP_NHAN => 'Chờ tiếp nhận trực tuyến',
            self::MOI_TIEP_NHAN => 'Mới tiếp nhận',
            self::DANG_XU_LY    => 'Đang xử lý',
            self::CHO_BO_SUNG   => 'Chờ bổ sung',
            self::CHO_PHE_DUYET => 'Chờ phê duyệt',
            self::HOAN_THANH    => 'Đã giải quyết (Chờ trả KQ)',
            self::DA_TRA_KET_QUA => 'Đã trả kết quả',
            self::TU_CHOI       => 'Từ chối giải quyết',
            self::DA_HUY        => 'Đã hủy hồ sơ',
        };
    }

    /**
     * Mã màu hiển thị giao diện
     */
    public function color(): string
    {
        return match ($this) {
            self::CHO_TIEP_NHAN => '#0284c7', // Sky Blue
            self::MOI_TIEP_NHAN => '#2563eb', // Royal Blue
            self::DANG_XU_LY    => '#d97706', // Amber
            self::CHO_BO_SUNG   => '#ea580c', // Orange
            self::CHO_PHE_DUYET => '#7c3aed', // Purple
            self::HOAN_THANH    => '#16a34a', // Emerald Green
            self::DA_TRA_KET_QUA => '#059669', // Dark Green
            self::TU_CHOI       => '#dc2626', // Red
            self::DA_HUY        => '#6b7280', // Gray
        };
    }

    /**
     * Nhóm chuẩn hóa thống kê theo Thông tư 01/2018/TT-VPCP (6 nhóm báo cáo)
     */
    public function nhomChuan(): string
    {
        return match ($this) {
            self::CHO_TIEP_NHAN, self::MOI_TIEP_NHAN => 'TIEP_NHAN',
            self::DANG_XU_LY, self::CHO_PHE_DUYET   => 'DANG_XU_LY',
            self::CHO_BO_SUNG                       => 'BO_SUNG',
            self::HOAN_THANH, self::DA_TRA_KET_QUA   => 'DA_GIAI_QUYET',
            self::TU_CHOI                           => 'TRA_LAI',
            self::DA_HUY                            => 'RUT_HO_SO',
        };
    }

    /**
     * Sinh HTML Badge hiển thị trên bảng
     */
    public function badge(): string
    {
        return sprintf(
            '<span class="badge" style="background-color: %s; color: #ffffff; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">%s</span>',
            $this->color(),
            htmlspecialchars($this->label())
        );
    }
}
