<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ReportingService;

/**
 * Class ReportingServiceTest – Bộ kiểm thử Đơn vị Phân hệ Báo cáo & Dashboard IOC (PH08)
 */
class ReportingServiceTest
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

    /**
     * Test 1: Thống kê Dashboard Chuyên viên
     */
    public function testStatsChuyenVien(): void
    {
        // Kiểm thử với chuyên viên ID 1 hoặc chuyên viên bất kỳ
        $res = ReportingService::getStatsChuyenVien(1, 10);

        $this->assert(isset($res['cards']), 'Kết quả phải chứa thẻ KPI cards');
        $this->assert(isset($res['cards']['dang_xu_ly']), 'Thẻ KPI phải có dang_xu_ly');
        $this->assert(isset($res['cards']['sap_qua_han']), 'Thẻ KPI phải có sap_qua_han');
        $this->assert(isset($res['cards']['qua_han']), 'Thẻ KPI phải có qua_han');
        $this->assert(isset($res['cards']['da_giai_quyet']), 'Thẻ KPI phải có da_giai_quyet');
        $this->assert(is_array($res['priority_list']), 'priority_list phải là một mảng');
        $this->assert(is_array($res['productivity']), 'productivity phải là một mảng');
    }

    /**
     * Test 2: Thống kê Dashboard Lãnh đạo Phòng
     */
    public function testStatsLdPhong(): void
    {
        $res = ReportingService::getStatsLdPhong(10); // Đơn vị quận Hồng Bàng

        $this->assert(isset($res['cards']), 'Kết quả phải có cards');
        $this->assert(isset($res['cards']['ty_le_dung_han']), 'Phải có tỷ lệ đúng hạn của phòng');
        $this->assert(is_array($res['chuyen_vien_list']), 'chuyen_vien_list phải là mảng');
        $this->assert(is_array($res['alert_list']), 'alert_list phải là mảng');
    }

    /**
     * Test 3: Thống kê Dashboard Lãnh đạo Cơ quan
     */
    public function testStatsLdCoQuan(): void
    {
        $res = ReportingService::getStatsLdCoQuan(10);

        $this->assert(isset($res['kpi']), 'Phải có kpi cơ quan');
        $this->assert(isset($res['kpi']['tong_tiep_nhan']), 'Phải có tổng tiếp nhận');
        $this->assert(isset($res['kpi']['ty_le_truc_tuyen']), 'Phải có tỷ lệ trực tuyến');
        $this->assert(isset($res['kpi']['ty_le_dung_han']), 'Phải có tỷ lệ đúng hạn');
        $this->assert(is_array($res['phong_ban_list']), 'phong_ban_list phải là mảng');
        $this->assert(is_array($res['ranking_list']), 'ranking_list phải là mảng');
        $this->assert(is_array($res['overdue_list']), 'overdue_list phải là mảng');
    }

    /**
     * Test 4: Màn hình IOC và 4 Thẻ Chỉ tiêu Báo cáo Kiến trúc (§2)
     */
    public function testStatsIocAndFourKpi(): void
    {
        $res = ReportingService::getStatsIoc(true); // force refresh

        // Kiểm tra 4 thẻ KPI lớn
        $this->assert(isset($res['kpi_cards']), 'IOC phải có kpi_cards');
        $kpi = $res['kpi_cards'];

        $this->assert(isset($kpi['dung_han']), 'Phải có chỉ tiêu Đúng hạn (≥98%)');
        $this->assert(isset($kpi['toan_trinh']), 'Phải có chỉ tiêu DVC Toàn trình (≥80%)');
        $this->assert(isset($kpi['so_hoa']), 'Phải có chỉ tiêu Số hóa (100%)');
        $this->assert(isset($kpi['hai_long']), 'Phải có chỉ tiêu Hài lòng SIPAS (≥92%)');

        $this->assert(is_bool($kpi['dung_han']['dat']), 'Chỉ tiêu đúng hạn phải có cờ dat boolean');
        $this->assert(is_bool($kpi['toan_trinh']['dat']), 'Chỉ tiêu toàn trình phải có cờ dat boolean');
        $this->assert(is_bool($kpi['so_hoa']['dat']), 'Chỉ tiêu số hóa phải có cờ dat boolean');
        $this->assert(is_bool($kpi['hai_long']['dat']), 'Chỉ tiêu SIPAS phải có cờ dat boolean');

        // Kiểm tra Bản đồ 15 Quận/Huyện Hải Phòng
        $this->assert(isset($res['district_map']), 'IOC phải có district_map');
        $map = $res['district_map'];
        $this->assertEquals(15, count($map), 'Bản đồ Hải Phòng phải có đúng 15 đơn vị cấp huyện/quận');

        // Kiểm tra một số quận huyện tiêu biểu
        $this->assert(isset($map[10]), 'Phải có Quận Hồng Bàng (ID 10)');
        $this->assert(isset($map[13]), 'Phải có TP Thủy Nguyên (ID 13)');
        $this->assert(isset($map[23]), 'Phải có Huyện Cát Hải (ID 23)');
        $this->assert(isset($map[24]), 'Phải có Huyện Bạch Long Vĩ (ID 24)');
        $this->assert(isset($map[10]['color']), 'Mỗi quận/huyện phải có mã màu SVG');

        // Kiểm tra Heatmap ma trận giờ x ngày
        $this->assert(isset($res['heatmap']), 'IOC phải có heatmap');
        $this->assertEquals(7, count($res['heatmap']['days']), 'Heatmap phải có 7 ngày trong tuần');
        $this->assert(count($res['heatmap']['hours']) >= 10, 'Heatmap phải có dải giờ hành chính');

        // Kiểm tra Bảng xếp hạng thi đua
        $this->assert(isset($res['emulation']), 'IOC phải có bảng xếp hạng thi đua emulation');
        $this->assert(isset($res['emulation']['so_nganh']), 'Phải có xếp hạng khối Sở Ngành');
        $this->assert(isset($res['emulation']['quan_huyen']), 'Phải có xếp hạng khối Quận Huyện');

        // Kiểm tra Bottleneck Detector
        $this->assert(isset($res['bottleneck']), 'IOC phải có bottleneck detector');
        $this->assert(is_array($res['bottleneck']['steps']), 'Phải có danh sách bước quy trình');
    }

    /**
     * Test 5: Bộ đệm Cache 5 phút và Thời gian thực thi < 1.0 giây
     */
    public function testIocCacheAndExecutionTimeUnderOneSecond(): void
    {
        // 1. Lần 1: Ép chạy tính toán
        $t1 = ReportingService::getStatsIoc(true);
        $this->assert($t1['thoi_gian_truy_van'] < 1.0, "Thời gian truy vấn trực tiếp phải < 1.0s (Thực tế: {$t1['thoi_gian_truy_van']}s)");

        // 2. Lần 2: Đọc từ cache
        $t2 = ReportingService::getStatsIoc(false);
        $this->assert($t2['tu_cache'] === true, 'Lần gọi thứ 2 phải đọc từ cache storage/cache/ioc_stats.json');
        $this->assert($t2['thoi_gian_truy_van'] < 0.1, "Thời gian đọc từ cache phải siêu tốc < 0.1s (Thực tế: {$t2['thoi_gian_truy_van']}s)");
    }

    /**
     * Test 6: Báo cáo Thông tư 01/2018/TT-VPCP (Biểu mẫu 01)
     */
    public function testReportThongTu01StructureAndCalculation(): void
    {
        $res = ReportingService::getReportThongTu01(['ky' => 'nam_nay']);

        $this->assert(isset($res['date_range']), 'Phải có date_range');
        $this->assert(isset($res['rows']), 'Phải có danh sách rows');
        $this->assert(isset($res['totals']), 'Phải có tổng cộng totals');

        $totals = $res['totals'];
        $this->assert(isset($totals['tong_so_nhan']), 'Phải có tổng tiếp nhận');
        $this->assert(isset($totals['tong_da_giai_quyet']), 'Phải có tổng đã giải quyết');
        $this->assert(isset($totals['ty_le_dung_han']), 'Phải có tỷ lệ giải quyết đúng hạn tổng');

        // Kiểm tra tính nhất quán toán học: Tổng giải quyết = Trước hạn + Đúng hạn + Quá hạn
        $sumDaGq = $totals['giai_quyet_truoc_han'] + $totals['giai_quyet_dung_han'] + $totals['giai_quyet_qua_han'];
        $this->assertEquals($totals['tong_da_giai_quyet'], $sumDaGq, 'Tổng đã giải quyết phải bằng Trước hạn + Đúng hạn + Quá hạn');
    }

    /**
     * Test 7: Các Báo cáo Số hóa (NĐ 107), Quá hạn (Mẫu 04) và Hài lòng (SIPAS)
     */
    public function testOtherReportsModules(): void
    {
        // 1. Báo cáo số hóa
        $soHoa = ReportingService::getReportSoHoa(['ky' => 'nam_nay']);
        $this->assert(isset($soHoa['rows']), 'Báo cáo số hóa phải có rows');

        // 2. Báo cáo quá hạn & Mẫu 04
        $quaHan = ReportingService::getReportQuaHan(['ky' => 'nam_nay']);
        $this->assert(isset($quaHan['tong_qua_han']), 'Báo cáo quá hạn phải có tong_qua_han');
        $this->assert(isset($quaHan['da_co_mau_04']), 'Báo cáo quá hạn phải có da_co_mau_04');
        $this->assert($quaHan['da_co_mau_04'] <= $quaHan['tong_qua_han'], 'Số có Mẫu 04 không thể lớn hơn tổng số quá hạn');

        // 3. Báo cáo hài lòng SIPAS
        $haiLong = ReportingService::getReportHaiLong(['ky' => 'nam_nay']);
        $this->assert(isset($haiLong['summary']['tb_chung']), 'SIPAS phải có điểm trung bình chung');
        $this->assert(isset($haiLong['ty_le_hai_long']), 'SIPAS phải có tỷ lệ hài lòng %');
        $this->assert($haiLong['ty_le_hai_long'] >= 0 && $haiLong['ty_le_hai_long'] <= 100, 'Tỷ lệ SIPAS phải trong khoảng 0-100%');

        // 4. Báo cáo trực tuyến
        $trucTuyen = ReportingService::getReportTrucTuyen(['ky' => 'nam_nay']);
        $this->assert(isset($trucTuyen['rows']), 'Báo cáo trực tuyến phải có rows');

        // 5. Báo cáo liên thông
        $lienThong = ReportingService::getReportLienThong(['ky' => 'nam_nay']);
        $this->assert(isset($lienThong['rows']), 'Báo cáo liên thông phải có rows');
    }
}
