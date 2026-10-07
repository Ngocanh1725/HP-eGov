<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use PDO;
use Throwable;

/**
 * Class SlaEngine – Động cơ tính toán thời hạn giải quyết và giám sát SLA thời gian thực
 * Tuân thủ §2.2 Báo cáo Kiến trúc, Thông tư 01/2018/TT-VPCP và Rule mục 11:
 * - Loại trừ Thứ 7, Chủ Nhật và các ngày nghỉ lễ/nghỉ bù (dm_ngay_nghi)
 * - Loại trừ ngoài giờ hành chính (Sáng 07:30 - 11:30, Chiều 13:30 - 17:30 = 8h làm việc/ngày)
 * - Tiếp nhận sau giờ làm việc (sau 17:30 hoặc ngày nghỉ) tính từ đầu giờ làm việc kế tiếp (07:30)
 * - Khấu trừ thời gian tạm dừng chờ công dân bổ sung hồ sơ
 * - Đánh giá màu SLA: XANH (<= 75% T và > 24h), VÀNG (> 75% T hoặc <= 24h), ĐỎ (> T hoặc trễ hạn)
 */
class SlaEngine
{
    /** @var array<string, bool> Cache danh sách ngày nghỉ lễ theo năm */
    private static array $holidaysCache = [];

    /** Cấu hình giờ làm việc chuẩn (Hành chính Nhà nước) */
    public const SANG_BAT_DAU = '07:30:00';
    public const SANG_KET_THUC = '11:30:00';
    public const CHIEU_BAT_DAU = '13:30:00';
    public const CHIEU_KET_THUC = '17:30:00';
    public const GIO_LAM_VIEC_NGAY = 8.0;

    /**
     * Chuẩn hóa thời điểm bắt đầu tính SLA:
     * - Nếu rơi vào Thứ 7, Chủ Nhật hoặc ngày lễ -> Nhảy sang 07:30 ngày làm việc kế tiếp
     * - Nếu trước 07:30 ngày làm việc -> Tính từ 07:30 cùng ngày
     * - Nếu trong giờ nghỉ trưa (11:30 - 13:30) -> Tính từ 13:30 chiều cùng ngày
     * - Nếu sau 17:30 (ví dụ 17:45) -> Nhảy sang 07:30 ngày làm việc kế tiếp
     *
     * @param DateTimeInterface $time
     * @param array<string, bool>|null $holidays
     * @return DateTime
     */
    public static function normalizeThoiDiemBatDau(DateTimeInterface $time, ?array $holidays = null): DateTime
    {
        $current = $time instanceof DateTime ? clone $time : new DateTime($time->format('Y-m-d H:i:s'));
        $holidays = $holidays ?? self::getHolidays();

        // 1. Nếu ngày hiện tại là ngày nghỉ cuối tuần hoặc nghỉ lễ -> Chuyển sang ngày làm việc kế tiếp lúc 07:30
        while (self::isWeekendOrHoliday($current, $holidays)) {
            $current->modify('+1 day');
            $current->setTime(7, 30, 0);
        }

        $h = (int)$current->format('H');
        $m = (int)$current->format('i');
        $minutes = $h * 60 + $m;

        $sangStart = 7 * 60 + 30;   // 07:30 (450 phút)
        $sangEnd = 11 * 60 + 30;    // 11:30 (690 phút)
        $chieuStart = 13 * 60 + 30; // 13:30 (810 phút)
        $chieuEnd = 17 * 60 + 30;   // 17:30 (1050 phút)

        if ($minutes < $sangStart) {
            // Trước giờ làm việc sáng -> Bắt đầu từ 07:30
            $current->setTime(7, 30, 0);
        } elseif ($minutes > $sangEnd && $minutes < $chieuStart) {
            // Giờ nghỉ trưa -> Bắt đầu từ 13:30
            $current->setTime(13, 30, 0);
        } elseif ($minutes >= $chieuEnd) {
            // Sau giờ làm việc chiều (ví dụ 17:45) -> Chuyển sang 07:30 ngày làm việc kế tiếp
            $current->modify('+1 day');
            $current->setTime(7, 30, 0);
            while (self::isWeekendOrHoliday($current, $holidays)) {
                $current->modify('+1 day');
            }
        }

        return $current;
    }

    /**
     * Tính toán ngày hẹn trả kết quả dựa trên ngày tiếp nhận và số ngày giải quyết (hoặc số giờ)
     *
     * @param string|DateTimeInterface $ngayTiepNhan Thời điểm tiếp nhận
     * @param int $thoiHanNgay Số ngày làm việc giải quyết TTHC
     * @param int|null $thoiHanGio Số giờ làm việc nếu quy định chi tiết (null sẽ lấy thoiHanNgay * 8)
     * @param array<string, bool>|null $holidays Danh sách ngày lễ (cho phép truyền mock khi test)
     * @return string Định dạng 'Y-m-d H:i:s'
     */
    public static function calculateNgayHenTra(
        string|DateTimeInterface $ngayTiepNhan,
        int $thoiHanNgay,
        ?int $thoiHanGio = null,
        ?array $holidays = null
    ): string {
        $holidays = $holidays ?? self::getHolidays();
        $dt = $ngayTiepNhan instanceof DateTimeInterface
            ? new DateTime($ngayTiepNhan->format('Y-m-d H:i:s'))
            : new DateTime($ngayTiepNhan);

        // Nếu có thời hạn tính theo giờ làm việc cụ thể
        if ($thoiHanGio !== null && $thoiHanGio > 0) {
            $end = self::addWorkingHours($dt, (float)$thoiHanGio, $holidays);
            return $end->format('Y-m-d H:i:s');
        }

        if ($thoiHanNgay <= 0) {
            $thoiHanNgay = 1;
        }

        // Trường hợp tính theo số ngày làm việc:
        $current = clone $dt;

        // Nếu tiếp nhận sau giờ làm việc (>= 17:00 hoặc rơi vào ngày nghỉ), chuyển sang đầu ngày làm việc tiếp theo
        if ((int)$current->format('H') >= 17 || self::isWeekendOrHoliday($current, $holidays)) {
            $current->modify('+1 day');
            while (self::isWeekendOrHoliday($current, $holidays)) {
                $current->modify('+1 day');
            }
        }

        // Tịnh tiến từng ngày làm việc
        $daysAdded = 0;
        while ($daysAdded < $thoiHanNgay) {
            $current->modify('+1 day');
            if (!self::isWeekendOrHoliday($current, $holidays)) {
                $daysAdded++;
            }
        }

        // Hạn trả kết quả theo quy chuẩn hành chính chốt vào 17:00 của ngày kết thúc
        $current->setTime(17, 0, 0);
        return $current->format('Y-m-d H:i:s');

    }

    /**
     * Cộng thêm số giờ làm việc thực tế vào một mốc thời gian (loại trừ ngoài giờ, trưa, cuối tuần, lễ)
     *
     * @param DateTimeInterface $start
     * @param float $hours
     * @param array<string, bool>|null $holidays
     * @return DateTime
     */
    public static function addWorkingHours(DateTimeInterface $start, float $hours, ?array $holidays = null): DateTime
    {
        $holidays = $holidays ?? self::getHolidays();
        $current = self::normalizeThoiDiemBatDau($start, $holidays);
        $remainingHours = max(0.0, $hours);

        while ($remainingHours > 0.0001) {
            $h = (int)$current->format('H');
            $m = (int)$current->format('i');
            $s = (int)$current->format('s');
            $minutes = $h * 60 + $m + ($s / 60);

            $sangEnd = 11 * 60 + 30; // 11:30
            $chieuEnd = 17 * 60 + 30; // 17:30

            if ($minutes < $sangEnd) {
                // Đang trong ca sáng
                $availableMinutes = $sangEnd - $minutes;
                $availableHours = $availableMinutes / 60;

                if ($remainingHours <= $availableHours) {
                    $addSeconds = (int)round($remainingHours * 3600);
                    $current->modify("+{$addSeconds} seconds");
                    $remainingHours = 0.0;
                } else {
                    $remainingHours -= $availableHours;
                    // Nhảy sang đầu ca chiều 13:30
                    $current->setTime(13, 30, 0);
                }
            } else {
                // Đang trong ca chiều
                $availableMinutes = $chieuEnd - $minutes;
                $availableHours = $availableMinutes / 60;

                if ($remainingHours <= $availableHours) {
                    $addSeconds = (int)round($remainingHours * 3600);
                    $current->modify("+{$addSeconds} seconds");
                    $remainingHours = 0.0;
                } else {
                    $remainingHours -= $availableHours;
                    // Chuyển sang 07:30 ngày làm việc tiếp theo
                    $current->modify('+1 day');
                    $current->setTime(7, 30, 0);
                    while (self::isWeekendOrHoliday($current, $holidays)) {
                        $current->modify('+1 day');
                    }
                }
            }
        }

        return $current;
    }

    /**
     * Tính toán chính xác số giờ làm việc giữa hai mốc thời gian
     * (Loại trừ ngoài giờ hành chính, nghỉ trưa 11:30-13:30, Thứ 7, Chủ Nhật, Ngày lễ)
     *
     * @param string|DateTimeInterface $from
     * @param string|DateTimeInterface $to
     * @param array<string, bool>|null $holidays
     * @return float Số giờ làm việc thực tế
     */
    public static function calculateWorkingHoursBetween(
        string|DateTimeInterface $from,
        string|DateTimeInterface $to,
        ?array $holidays = null
    ): float {
        $holidays = $holidays ?? self::getHolidays();
        $start = $from instanceof DateTimeInterface ? clone $from : new DateTime($from);
        $end = $to instanceof DateTimeInterface ? clone $to : new DateTime($to);

        if ($start >= $end) {
            return 0.0;
        }

        $totalSeconds = 0;
        $curDay = new DateTime($start->format('Y-m-d 00:00:00'));
        $endDay = new DateTime($end->format('Y-m-d 00:00:00'));

        while ($curDay <= $endDay) {
            if (!self::isWeekendOrHoliday($curDay, $holidays)) {
                $dayStr = $curDay->format('Y-m-d');

                // Ca sáng: 07:30 - 11:30
                $mStart = new DateTime("{$dayStr} " . self::SANG_BAT_DAU);
                $mEnd = new DateTime("{$dayStr} " . self::SANG_KET_THUC);
                $effStart = max($start->getTimestamp(), $mStart->getTimestamp());
                $effEnd = min($end->getTimestamp(), $mEnd->getTimestamp());
                if ($effStart < $effEnd) {
                    $totalSeconds += ($effEnd - $effStart);
                }

                // Ca chiều: 13:30 - 17:30
                $aStart = new DateTime("{$dayStr} " . self::CHIEU_BAT_DAU);
                $aEnd = new DateTime("{$dayStr} " . self::CHIEU_KET_THUC);
                $effStart = max($start->getTimestamp(), $aStart->getTimestamp());
                $effEnd = min($end->getTimestamp(), $aEnd->getTimestamp());
                if ($effStart < $effEnd) {
                    $totalSeconds += ($effEnd - $effStart);
                }
            }

            $curDay->modify('+1 day');
        }

        return round($totalSeconds / 3600, 2);
    }

    /**
     * Đánh giá màu sắc SLA và thời gian đã sử dụng
     * Tuân thủ công thức Rule mục 11:
     * - XANH: t <= 75% * T VÀ thời gian còn lại > 24 giờ
     * - VÀNG: 75% * T < t <= T HOẶC thời gian còn lại <= 24 giờ (khi t <= T)
     * - ĐỎ: t > T (quá hạn)
     *
     * @param string $ngayHenTra Hạn trả kết quả
     * @param string|null $ngayTiepNhan Thời điểm tiếp nhận
     * @param string|null $ngayHoanThanh Thời điểm hoàn thành (nếu có)
     * @param int|float $tongThoiHanGio Tổng thời hạn cam kết tính theo giờ làm việc (mặc định 24h)
     * @param float $soGioTamDung Số giờ tạm dừng chờ bổ sung (được trừ khỏi thời gian đã dùng)
     * @param array<string, bool>|null $holidays Danh sách ngày lễ mock khi test
     * @return array{mau: string, so_gio_da_dung: float, so_gio_con_lai: float, tre_han: bool, ty_le: float, tong_gio: float}
     */
    public static function evaluateSla(
        string $ngayHenTra,
        ?string $ngayTiepNhan = null,
        ?string $ngayHoanThanh = null,
        int|float $tongThoiHanGio = 24.0,
        float $soGioTamDung = 0.0,
        ?array $holidays = null
    ): array {
        $holidays = $holidays ?? self::getHolidays();
        $henTraDt = new DateTime($ngayHenTra);
        $tiepNhanDt = !empty($ngayTiepNhan) ? new DateTime($ngayTiepNhan) : null;
        $mocKiemTraDt = !empty($ngayHoanThanh) ? new DateTime($ngayHoanThanh) : new DateTime();

        // 1. Tính tổng thời hạn T (giờ làm việc)
        $T = (float)$tongThoiHanGio;
        if ($T <= 0.0 && $tiepNhanDt !== null) {
            $T = self::calculateWorkingHoursBetween($tiepNhanDt, $henTraDt, $holidays);
        }
        $T = max(1.0, $T);

        // 2. Tính thời gian đã sử dụng t (giờ làm việc), trừ thời gian tạm dừng
        if ($tiepNhanDt !== null) {
            $rawUsed = self::calculateWorkingHoursBetween($tiepNhanDt, $mocKiemTraDt, $holidays);
            $t = max(0.0, $rawUsed - $soGioTamDung);
        } else {
            // Dự phòng nếu không có ngày tiếp nhận
            $henTraTs = $henTraDt->getTimestamp();
            $mocTs = $mocKiemTraDt->getTimestamp();
            $diffHours = ($mocTs - ($henTraTs - ($T * 3600))) / 3600;
            $t = max(0.0, $diffHours - $soGioTamDung);
        }

        // 3. Đánh giá trạng thái quá hạn
        $treHan = ($mocKiemTraDt > $henTraDt) || ($t > $T);
        $soGioConLai = max(0.0, round($T - $t, 1));
        $tyLe = round(($t / $T) * 100, 1);

        // 4. Xác định màu sắc theo Rule mục 11:
        if ($treHan) {
            $mau = 'DO';
        } elseif ($t > (0.75 * $T) || ($T > 24.0 && $soGioConLai <= 24.0)) {
            $mau = 'VANG';
        } else {
            $mau = 'XANH';
        }

        return [
            'mau' => $mau,
            'so_gio_da_dung' => round($t, 1),
            'so_gio_con_lai' => $soGioConLai,
            'tre_han' => $treHan,
            'ty_le' => $tyLe,
            'tong_gio' => round($T, 1),
        ];
    }

    /**
     * Đánh giá SLA cho một bước quy trình cụ thể
     *
     * @param int|float $buocThoiHanGio Hạn giờ của bước
     * @param string $thoiDiemBatDau Thời điểm bắt đầu bước
     * @param string|null $thoiDiemKetThuc Thời điểm kết thúc (nếu đã xong)
     * @param array<string, bool>|null $holidays
     * @return array{mau: string, so_gio_da_dung: float, so_gio_con_lai: float, tre_han: bool}
     */
    public static function evaluateStepSla(
        int|float $buocThoiHanGio,
        string $thoiDiemBatDau,
        ?string $thoiDiemKetThuc = null,
        ?array $holidays = null
    ): array {
        $holidays = $holidays ?? self::getHolidays();
        $start = new DateTime($thoiDiemBatDau);
        $end = !empty($thoiDiemKetThuc) ? new DateTime($thoiDiemKetThuc) : new DateTime();

        $t = self::calculateWorkingHoursBetween($start, $end, $holidays);
        $T = max(1.0, (float)$buocThoiHanGio);
        $treHan = $t > $T;
        $conLai = max(0.0, round($T - $t, 1));

        if ($treHan) {
            $mau = 'DO';
        } elseif ($t > (0.75 * $T)) {
            $mau = 'VANG';
        } else {
            $mau = 'XANH';
        }

        return [
            'mau' => $mau,
            'so_gio_da_dung' => round($t, 1),
            'so_gio_con_lai' => $conLai,
            'tre_han' => $treHan,
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
            // Lấy toàn bộ ngày trong dm_ngay_nghi
            $stmt = $db->query("SELECT ngay FROM dm_ngay_nghi");
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $cache = [];
            foreach ($rows as $r) {
                $cache[(string)$r] = true;
            }
            self::$holidaysCache = $cache;
            return self::$holidaysCache;
        } catch (Throwable) {
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
     * Nạp thủ công danh sách ngày lễ (phục vụ unit test)
     *
     * @param array<string, bool> $holidays
     */
    public static function setHolidaysCache(array $holidays): void
    {
        self::$holidaysCache = $holidays;
    }

    /**
     * Xóa cache ngày nghỉ để nạp lại dữ liệu mới
     */
    public static function clearCache(): void
    {
        self::$holidaysCache = [];
    }
}
