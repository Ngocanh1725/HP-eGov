<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\SlaEngine;
use DateTime;

/**
 * Class SlaEngineTest – Bộ kiểm thử toàn diện Động cơ SLA (§2.2 & Rule mục 11)
 */
class SlaEngineTest
{
    private function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException("FAILED: {$message}");
        }
    }

    private function assertEquals(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException("FAILED: {$message} (Kỳ vọng: " . var_export($expected, true) . ", Thực tế: " . var_export($actual, true) . ")");
        }
    }

    public function setUp(): void
    {
        SlaEngine::clearCache();
    }

    /**
     * Test 1: Tính ngày hẹn trả qua cuối tuần (Thứ 7 & Chủ Nhật)
     * Tiếp nhận: Thứ Sáu 2026-03-06 lúc 09:00:00, thời hạn 3 ngày làm việc
     * Ngày 1: Thứ Sáu 06/03
     * Nghỉ: Thứ Bảy 07/03, Chủ Nhật 08/03
     * Ngày 2: Thứ Hai 09/03
     * Ngày 3: Thứ Ba 10/03 -> Hạn chốt 17:00 Thứ Ba 10/03
     */
    public function testCalculateHenTraQuaCuoiTuan(): void
    {
        $tiepNhan = '2026-03-06 09:00:00'; // Thứ Sáu
        $henTra = SlaEngine::calculateNgayHenTra($tiepNhan, 3, null, []);
        
        $this->assertEquals('2026-03-11 17:00:00', $henTra, 'Hạn trả 3 ngày làm việc kể từ thứ Sáu phải là Thứ Tư tuần sau');
    }


    /**
     * Test 2: Tính ngày hẹn trả qua kỳ nghỉ Tết Nguyên Đán
     * Nghỉ Tết 2026: 16/02 đến 20/02/2026 (29 Tết đến mùng 3 Tết)
     * Kèm cuối tuần: 21/02 (T7) và 22/02 (CN)
     * Tiếp nhận: Thứ Sáu 2026-02-13 10:00:00, hạn 2 ngày làm việc
     * Ngày 1: Thứ Sáu 13/02
     * Nghỉ: 14/02 (T7), 15/02 (CN), 16/02-20/02 (Tết), 21-22/02 (Cuối tuần)
     * Ngày 2: Thứ Hai 23/02/2026 -> Hạn chốt 17:00 Thứ Hai 23/02/2026
     */
    public function testCalculateHenTraQuaKyNghiTet(): void
    {
        $holidays = [
            '2026-02-16' => true,
            '2026-02-17' => true,
            '2026-02-18' => true,
            '2026-02-19' => true,
            '2026-02-20' => true,
        ];

        $tiepNhan = '2026-02-13 10:00:00'; // Thứ Sáu trước Tết
        $henTra = SlaEngine::calculateNgayHenTra($tiepNhan, 2, null, $holidays);

        // Thứ Sáu (13/02) + 2 ngày làm việc:
        // Nghỉ Tết + cuối tuần từ 14/02 đến 22/02
        // Ngày làm việc 1: Thứ Hai 23/02
        // Ngày làm việc 2: Thứ Ba 24/02
        $this->assertEquals('2026-02-24 17:00:00', $henTra, 'Hạn trả 2 ngày làm việc phải tự động nhảy qua 7 ngày nghỉ Tết + cuối tuần đến Thứ Ba');
    }

    /**
     * Test 3: Tiếp nhận sau giờ làm việc (ví dụ 17:45 ngoài giờ)
     * Tiếp nhận: Thứ Hai 2026-03-02 17:45:00
     * Chuẩn hóa: Tính từ Thứ Ba 2026-03-03 07:30:00
     * Hạn 1 ngày làm việc sau giờ làm việc -> Hạn trả là 17:00 Thứ Tư 04/03/2026
     */
    public function testTiepNhanSauGioLamViec1745(): void
    {
        $tiepNhan = '2026-03-02 17:45:00'; // Thứ Hai 17:45
        $start = SlaEngine::normalizeThoiDiemBatDau(new DateTime($tiepNhan), []);
        $this->assertEquals('2026-03-03 07:30:00', $start->format('Y-m-d H:i:s'), 'Tiếp nhận sau 17:30 phải bắt đầu từ 07:30 ngày làm việc kế tiếp');

        $henTra = SlaEngine::calculateNgayHenTra($tiepNhan, 1, null, []);
        $this->assertEquals('2026-03-04 17:00:00', $henTra, 'Hạn trả 1 ngày tiếp nhận lúc 17:45 phải là 17:00 Thứ Tư');
    }


    /**
     * Test 4: Tính giờ làm việc chính xác và Khấu trừ thời gian tạm dừng chờ bổ sung
     * Thứ Hai 2026-03-02 07:30 đến 17:30: tổng cộng 8 giờ làm việc (trừ 2h nghỉ trưa).
     * Nếu có 3 giờ tạm dừng chờ bổ sung -> thời gian thực tế đã dùng còn 5 giờ.
     */
    public function testKhauTruThoiGianTamDungChoBoSung(): void
    {
        $from = '2026-03-02 07:30:00';
        $to = '2026-03-02 17:30:00';
        $workingHours = SlaEngine::calculateWorkingHoursBetween($from, $to, []);
        $this->assertEquals(8.0, $workingHours, 'Một ngày làm việc hành chính phải đủ 8 giờ làm việc');

        // Đánh giá SLA với 3h tạm dừng
        $eval = SlaEngine::evaluateSla(
            ngayHenTra: '2026-03-05 17:00:00',
            ngayTiepNhan: $from,
            ngayHoanThanh: $to,
            tongThoiHanGio: 32.0, // 4 ngày = 32h
            soGioTamDung: 3.0,
            holidays: []
        );

        $this->assertEquals(5.0, $eval['so_gio_da_dung'], 'Thời gian đã dùng phải là 8.0 - 3.0 = 5.0 giờ');
        $this->assertEquals('XANH', $eval['mau'], '5.0h / 32h (15.6%) phải là màu XANH');
    }

    /**
     * Test 5: Ngưỡng biên màu SLA (XANH, VÀNG, ĐỎ, ngưỡng 75%, ngưỡng 24h còn lại)
     */
    public function testNguongBienMauSlaXanhVangDo(): void
    {
        $henTra = '2026-03-10 17:00:00';
        $tiepNhan = '2026-03-02 07:30:00';
        $T = 56.0; // 7 ngày làm việc = 56 giờ

        // Ca 1: Dưới 75% và còn > 24h (Ví dụ đã dùng 20h, còn 36h) -> XANH
        $evalXanh = SlaEngine::evaluateSla(
            ngayHenTra: $henTra,
            ngayTiepNhan: $tiepNhan,
            ngayHoanThanh: '2026-03-04 11:30:00', // Đã dùng 20h
            tongThoiHanGio: $T,
            soGioTamDung: 0.0,
            holidays: []
        );
        $this->assertEquals('XANH', $evalXanh['mau'], 'Dưới 75% và còn > 24h phải có màu XANH');

        // Ca 2: Đã dùng > 75% T (75% của 56 = 42h; đã dùng 45h) -> VÀNG
        $evalVang75 = SlaEngine::evaluateSla(
            ngayHenTra: $henTra,
            ngayTiepNhan: $tiepNhan,
            ngayHoanThanh: '2026-03-09 14:30:00', // Đã dùng ~45h
            tongThoiHanGio: $T,
            soGioTamDung: 0.0,
            holidays: []
        );
        $this->assertEquals('VANG', $evalVang75['mau'], 'Vượt ngưỡng 75% thời hạn phải chuyển cảnh báo VÀNG');

        // Ca 3: Thời gian còn lại <= 24h làm việc (Ví dụ còn 20h dù chưa vượt 75% nếu T dài) -> VÀNG
        $evalVang24h = SlaEngine::evaluateSla(
            ngayHenTra: $henTra,
            ngayTiepNhan: $tiepNhan,
            ngayHoanThanh: '2026-03-06 17:30:00', // Đã dùng 40h / 56h, còn 16h <= 24h
            tongThoiHanGio: $T,
            soGioTamDung: 0.0,
            holidays: []
        );
        $this->assertEquals('VANG', $evalVang24h['mau'], 'Thời gian còn lại <= 24h phải chuyển sang màu VÀNG');

        // Ca 4: Quá hạn T (Ví dụ đã dùng 60h > 56h hoặc vượt mốc ngày hẹn trả) -> ĐỎ
        $evalDo = SlaEngine::evaluateSla(
            ngayHenTra: $henTra,
            ngayTiepNhan: $tiepNhan,
            ngayHoanThanh: '2026-03-11 09:30:00', // Quá ngày hẹn trả
            tongThoiHanGio: $T,
            soGioTamDung: 0.0,
            holidays: []
        );
        $this->assertEquals('DO', $evalDo['mau'], 'Vượt thời hạn phải chuyển màu ĐỎ');
        $this->assert($evalDo['tre_han'] === true, 'Phải đánh dấu trễ hạn là true');
    }

    /**
     * Test 6: Đánh giá SLA từng bước quy trình
     */
    public function testEvaluateStepSla(): void
    {
        // Bước phân công: hạn 8 giờ
        $start = '2026-03-02 07:30:00';
        $end = '2026-03-02 11:30:00'; // Dùng 4 giờ (50%) -> XANH
        $stepXanh = SlaEngine::evaluateStepSla(8, $start, $end, []);
        $this->assertEquals('XANH', $stepXanh['mau'], 'Bước dùng 50% thời hạn phải màu XANH');

        $endLate = '2026-03-03 08:30:00'; // Dùng 9 giờ (> 8h) -> ĐỎ
        $stepDo = SlaEngine::evaluateStepSla(8, $start, $endLate, []);
        $this->assertEquals('DO', $stepDo['mau'], 'Bước dùng 9h/8h phải màu ĐỎ');
    }
}
