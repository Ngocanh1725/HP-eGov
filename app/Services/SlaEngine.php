<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateTime;
use DateTimeInterface;
use PDO;

/**
 * Class SlaEngine – Động cơ tính toán thời hạn giải quyết và giám sát SLA (Phiên 8 & Phiên 11)
 * Tuân thủ Thông tư 01/2018/TT-VPCP và Quyết định 876/QĐ-BNV
 * - Loại trừ Thứ 7, Chủ Nhật và các ngày nghỉ lễ theo quy định (dm_ngay_nghi)
 * - Xác định màu SLA Xanh / Vàng / Đỏ
 */
class SlaEngine
{
    /** @var array<string, bool> Cache danh sách ngày nghỉ lễ theo năm */
    private static array $holidaysCache = [];

    /**
     * Tính toán ngày hẹn trả kết quả dựa trên ngày tiếp nhận và số ngày giải quyết
     *
     * @param string|DateTimeInterface $ngayTiepNhan Thời điểm tiếp nhận
     * @param int $thoiHanNgay Số ngày làm việc giải quyết TTHC
     * @return string Định dạng 'Y-m-d 17:00:00'
     */
    public static function calculateNgayHenTra(string|DateTimeInterface $ngayTiepNhan, int $thoiHanNgay): string
    {
        $current = $ngayTiepNhan instanceof DateTimeInterface 
            ? (clone $ngayTiepNhan) 
            : new DateTime($ngayTiepNhan);

        if ($thoiHanNgay <= 0) {
            $thoiHanNgay = 1;
        }

        $holidays = self::getHolidays();
        $daysAdded = 0;

        // Nếu tiếp nhận sau 17:00, tính bắt đầu từ ngày làm việc tiếp theo
        if ((int)$current->format('H') >= 17) {
            $current->modify('+1 day');
            while (self::isWeekendOrHoliday($current, $holidays)) {
                $current->modify('+1 day');
            }
        }

        // Tịnh tiến từng ngày làm việc
        while ($daysAdded < $thoiHanNgay) {
            $current->modify('+1 day');
            if (!self::isWeekendOrHoliday($current, $holidays)) {
                $daysAdded++;
            }
        }

        $current->setTime(17, 0, 0);
        return $current->format('Y-m-d H:i:s');
    }

    /**
     * Đánh giá màu sắc SLA và thời gian đã sử dụng
     *
     * @param string $ngayHenTra Hạn trả kết quả
     * @param string|null $ngayTiepNhan Thời điểm tiếp nhận
     * @param string|null $ngayHoanThanh Thời điểm hoàn thành (nếu có)
     * @param int $tongThoiHanGio Tổng thời hạn cam kết tính theo giờ (mặc định = số ngày * 8)
     * @return array{mau: string, so_gio_da_dung: float, so_gio_con_lai: float, tre_han: bool, ty_le: float}
     */
    public static function evaluateSla(
        string $ngayHenTra,
        ?string $ngayTiepNhan = null,
        ?string $ngayHoanThanh = null,
        int $tongThoiHanGio = 24
    ): array {
        $henTraTime = strtotime($ngayHenTra);
        $tiepNhanTime = !empty($ngayTiepNhan) ? strtotime($ngayTiepNhan) : ($henTraTime - ($tongThoiHanGio * 3600));
        $now = time();
        $mocKiemTra = !empty($ngayHoanThanh) ? strtotime($ngayHoanThanh) : $now;

        $treHan = $mocKiemTra > $henTraTime;
        $tongThoiGianGiay = max(3600, $henTraTime - $tiepNhanTime);
        $thoiGianDaDungGiay = max(0, $mocKiemTra - $tiepNhanTime);
        $tyLe = $thoiGianDaDungGiay / $tongThoiGianGiay;

        $soGioDaDung = round($thoiGianDaDungGiay / 3600, 1);
        $soGioConLai = round(max(0, ($henTraTime - $mocKiemTra) / 3600), 1);

        if ($treHan) {
            $mau = 'DO';
        } elseif ($tyLe >= 0.75 || $soGioConLai <= 24.0) {
            $mau = 'VANG';
        } else {
            $mau = 'XANH';
        }

        return [
            'mau' => $mau,
            'so_gio_da_dung' => $soGioDaDung,
            'so_gio_con_lai' => $soGioConLai,
            'tre_han' => $treHan,
            'ty_le' => round($tyLe * 100, 1),
        ];
    }

    /**
     * Lấy danh sách ngày nghỉ lễ từ CSDL (bảng dm_ngay_nghi)
     *
     * @return array<string, bool>
     */
    public static function getHolidays(): array
    {
        if (!empty(self::$holidaysCache)) {
            return self::$holidaysCache;
        }

        try {
            $db = Database::connection();
            $stmt = $db->query("SELECT ngay FROM dm_ngay_nghi WHERE is_active = 1");
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $cache = [];
            foreach ($rows as $r) {
                $cache[(string)$r] = true;
            }
            self::$holidaysCache = $cache;
            return self::$holidaysCache;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Kiểm tra một ngày có phải là Thứ 7, Chủ Nhật hoặc ngày nghỉ lễ hay không
     *
     * @param DateTimeInterface $date
     * @param array<string, bool> $holidays
     * @return bool
     */
    public static function isWeekendOrHoliday(DateTimeInterface $date, array $holidays): bool
    {
        $dayOfWeek = (int)$date->format('N'); // 1 (Thứ 2) đến 7 (Chủ Nhật)
        if ($dayOfWeek === 6 || $dayOfWeek === 7) {
            return true;
        }

        $dateStr = $date->format('Y-m-d');
        return isset($holidays[$dateStr]);
    }

    /**
     * Xóa cache ngày nghỉ để nạp lại dữ liệu mới
     */
    public static function clearCache(): void
    {
        self::$holidaysCache = [];
    }
}
