<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum KenhThongBao
 * Kênh gửi thông báo và tin tức đến người dùng và cán bộ
 */
enum KenhThongBao: string
{
    case IN_APP = 'IN_APP';
    case SMS    = 'SMS';
    case ZALO   = 'ZALO';
    case EMAIL  = 'EMAIL';

    /**
     * Nhãn hiển thị tiếng Việt
     */
    public function label(): string
    {
        return match ($this) {
            self::IN_APP => 'Thông báo hệ thống nội bộ',
            self::SMS    => 'Tin nhắn SMS Brandname "UBND TP HP"',
            self::ZALO   => 'Tin nhắn Zalo ZNS Chính quyền điện tử',
            self::EMAIL  => 'Thư điện tử công vụ Hải Phòng',
        };
    }
}
