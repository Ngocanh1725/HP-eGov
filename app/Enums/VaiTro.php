<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum VaiTro
 * 7 vai trò cán bộ và người dùng trong hệ thống HP-eGov (§4.1 & Master Rules)
 */
enum VaiTro: string
{
    case CONG_DAN     = 'CONG_DAN';
    case CB_MOT_CUA   = 'CB_MOT_CUA';
    case CHUYEN_VIEN  = 'CHUYEN_VIEN';
    case LD_PHONG     = 'LD_PHONG';
    case LD_CO_QUAN   = 'LD_CO_QUAN';
    case VAN_THU      = 'VAN_THU';
    case SYSADMIN     = 'SYSADMIN';

    /**
     * Tên hiển thị tiếng Việt
     */
    public function label(): string
    {
        return match ($this) {
            self::CONG_DAN     => 'Công dân / Doanh nghiệp',
            self::CB_MOT_CUA   => 'Cán bộ Tiếp nhận Một cửa',
            self::CHUYEN_VIEN  => 'Chuyên viên Thụ lý Thẩm định',
            self::LD_PHONG     => 'Lãnh đạo Phòng chuyên môn',
            self::LD_CO_QUAN   => 'Lãnh đạo Cơ quan / Đơn vị',
            self::VAN_THU      => 'Văn thư số',
            self::SYSADMIN     => 'Quản trị viên Hệ thống',
        };
    }

    /**
     * Mô tả chức năng và phạm vi thẩm quyền
     */
    public function description(): string
    {
        return match ($this) {
            self::CONG_DAN     => 'Nộp hồ sơ trực tuyến, tra cứu tiến độ, đánh giá SIPAS và quản lý kho dữ liệu cá nhân',
            self::CB_MOT_CUA   => 'Tiếp nhận hồ sơ giấy/online, số hóa thành phần hồ sơ theo NĐ 107/2021, in Giấy tiếp nhận hẹn trả kết quả',
            self::CHUYEN_VIEN  => 'Thẩm định hồ sơ, lấy ý kiến phối hợp liên thông ngang/dọc, lập biên bản thẩm định, dự thảo quyết định kết quả',
            self::LD_PHONG     => 'Phân công thụ lý, cho ý kiến chuyên môn, ký số duyệt bước 1 tờ trình/dự thảo',
            self::LD_CO_QUAN   => 'Phê duyệt cuối cùng, ký số phát hành kết quả TTHC, ký văn bản xin lỗi Mẫu 04 khi quá hạn',
            self::VAN_THU      => 'Cấp số văn bản chính thức vào sổ theo dõi, ký số chứng thực cơ quan (đóng dấu số), trả kết quả về Một cửa',
            self::SYSADMIN     => 'Quản trị người dùng, phân quyền RBAC, cấu hình tham số, giám sát an toàn hệ thống (không can thiệp dữ liệu nghiệp vụ)',
        };
    }
}
