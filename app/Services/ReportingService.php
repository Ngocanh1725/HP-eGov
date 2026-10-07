<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Class ReportingService
 * Phục vụ phân hệ PH08: Dashboard chỉ đạo điều hành, Trung tâm IOC Hải Phòng và Báo cáo thống kê
 * Tuân thủ Thông tư 01/2018/TT-VPCP, Nghị định 107/2021/NĐ-CP, QĐ 876/QĐ-BNV
 */
class ReportingService
{
    private const CACHE_TTL_SECONDS = 300; // 5 phút

    /**
     * Lấy dữ liệu Dashboard cho Chuyên viên
     *
     * @param int $userId
     * @param int|null $donViId
     * @return array
     */
    public static function getStatsChuyenVien(int $userId, ?int $donViId = null): array
    {
        $db = Database::connection();

        // 1. Số liệu tổng quan 4 thẻ KPI cá nhân
        $sqlCards = "SELECT 
            SUM(CASE WHEN trang_thai = 'DANG_XU_LY' THEN 1 ELSE 0 END) as dang_xu_ly,
            SUM(CASE WHEN trang_thai = 'DANG_XU_LY' AND sla_mau = 'VANG' THEN 1 ELSE 0 END) as sap_qua_han,
            SUM(CASE WHEN trang_thai = 'DANG_XU_LY' AND sla_mau = 'DO' THEN 1 ELSE 0 END) as qua_han,
            SUM(CASE WHEN trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') THEN 1 ELSE 0 END) as da_giai_quyet
        FROM ho_so 
        WHERE is_deleted = 0 AND chuyen_vien_thu_ly_id = :user_id";

        $stmt = $db->prepare($sqlCards);
        $stmt->execute([':user_id' => $userId]);
        $cards = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'dang_xu_ly' => 0,
            'sap_qua_han' => 0,
            'qua_han' => 0,
            'da_giai_quyet' => 0,
        ];

        // 2. Danh sách hồ sơ cần ưu tiên xử lý (Top 10, sắp xếp theo độ khẩn cấp)
        $sqlPriority = "SELECT h.id, h.ma_ho_so, h.nguoi_nop_ho_ten, h.ngay_tiep_nhan, h.ngay_hen_tra,
                               h.sla_mau, h.tong_thoi_han_gio, h.thoi_gian_da_dung_gio,
                               t.ten_tthc, qb.ten_buoc
                        FROM ho_so h
                        JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
                        LEFT JOIN quy_trinh_buoc qb ON h.buoc_hien_tai_id = qb.id
                        WHERE h.is_deleted = 0 
                          AND h.chuyen_vien_thu_ly_id = :user_id
                          AND h.trang_thai = 'DANG_XU_LY'
                        ORDER BY 
                            CASE h.sla_mau 
                                WHEN 'DO' THEN 1 
                                WHEN 'VANG' THEN 2 
                                ELSE 3 
                            END ASC,
                            h.ngay_hen_tra ASC
                        LIMIT 10";
        $stmtPriority = $db->prepare($sqlPriority);
        $stmtPriority->execute([':user_id' => $userId]);
        $priorityList = $stmtPriority->fetchAll(PDO::FETCH_ASSOC);

        // 3. Năng suất xử lý cá nhân trong 7 ngày gần nhất
        $sqlProductivity = "SELECT 
            DATE(ngay_hoan_thanh) as ngay,
            COUNT(*) as so_luong
        FROM ho_so
        WHERE is_deleted = 0 
          AND chuyen_vien_thu_ly_id = :user_id
          AND trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA')
          AND ngay_hoan_thanh >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(ngay_hoan_thanh)
        ORDER BY ngay ASC";
        $stmtProd = $db->prepare($sqlProductivity);
        $stmtProd->execute([':user_id' => $userId]);
        $productivityData = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

        return [
            'cards' => [
                'dang_xu_ly' => (int)($cards['dang_xu_ly'] ?? 0),
                'sap_qua_han' => (int)($cards['sap_qua_han'] ?? 0),
                'qua_han' => (int)($cards['qua_han'] ?? 0),
                'da_giai_quyet' => (int)($cards['da_giai_quyet'] ?? 0),
            ],
            'priority_list' => $priorityList,
            'productivity' => $productivityData,
        ];
    }

    /**
     * Lấy dữ liệu Dashboard cho Lãnh đạo Phòng
     *
     * @param int $donViId
     * @return array
     */
    public static function getStatsLdPhong(int $donViId): array
    {
        $db = Database::connection();

        // 1. Thẻ tổng quan việc của phòng
        $sqlCards = "SELECT 
            COUNT(h.id) as tong_ho_so,
            SUM(CASE WHEN h.trang_thai = 'DANG_XU_LY' THEN 1 ELSE 0 END) as dang_xu_ly,
            SUM(CASE WHEN h.trang_thai = 'DANG_XU_LY' AND h.sla_mau = 'VANG' THEN 1 ELSE 0 END) as sap_qua_han,
            SUM(CASE WHEN h.trang_thai = 'DANG_XU_LY' AND h.sla_mau = 'DO' THEN 1 ELSE 0 END) as qua_han,
            SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') THEN 1 ELSE 0 END) as da_giai_quyet,
            SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.ngay_hoan_thanh <= h.ngay_hen_tra THEN 1 ELSE 0 END) as dung_han
        FROM ho_so h
        WHERE h.is_deleted = 0 
          AND (h.don_vi_hien_tai_id = :don_vi_id OR h.don_vi_tiep_nhan_id = :don_vi_id2)";

        $stmt = $db->prepare($sqlCards);
        $stmt->execute([':don_vi_id' => $donViId, ':don_vi_id2' => $donViId]);
        $cards = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $daGiaiQuyet = (int)($cards['da_giai_quyet'] ?? 0);
        $dungHan = (int)($cards['dung_han'] ?? 0);
        $tyLeDungHan = $daGiaiQuyet > 0 ? round(($dungHan / $daGiaiQuyet) * 100, 1) : 100.0;

        // 2. Phân bổ hồ sơ theo từng chuyên viên trong phòng
        $sqlChuyenVien = "SELECT 
            u.id, u.ho_ten, COALESCE(cd.ten_chuc_danh, 'Chuyên viên') as chuc_danh,
            COUNT(h.id) as tong_nhan,
            SUM(CASE WHEN h.trang_thai = 'DANG_XU_LY' THEN 1 ELSE 0 END) as dang_xu_ly,
            SUM(CASE WHEN h.trang_thai = 'DANG_XU_LY' AND h.sla_mau = 'DO' THEN 1 ELSE 0 END) as qua_han,
            SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') THEN 1 ELSE 0 END) as hoan_thanh
        FROM nguoi_dung u
        LEFT JOIN chuc_danh cd ON u.chuc_danh_id = cd.id
        LEFT JOIN ho_so h ON u.id = h.chuyen_vien_thu_ly_id AND h.is_deleted = 0
        WHERE u.don_vi_chinh_id = :don_vi_id AND u.trang_thai = 1
        GROUP BY u.id, u.ho_ten, cd.ten_chuc_danh
        ORDER BY dang_xu_ly DESC";

        $stmtCv = $db->prepare($sqlChuyenVien);
        $stmtCv->execute([':don_vi_id' => $donViId]);
        $chuyenVienList = $stmtCv->fetchAll(PDO::FETCH_ASSOC);

        // 3. Danh sách hồ sơ cảnh báo SLA Vàng / Đỏ cần đôn đốc
        $sqlAlerts = "SELECT h.id, h.ma_ho_so, h.nguoi_nop_ho_ten, h.ngay_hen_tra, h.sla_mau,
                             u.ho_ten as ten_chuyen_vien, t.ten_tthc
                      FROM ho_so h
                      JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
                      LEFT JOIN nguoi_dung u ON h.chuyen_vien_thu_ly_id = u.id
                      WHERE h.is_deleted = 0 
                        AND (h.don_vi_hien_tai_id = :don_vi_id OR h.don_vi_tiep_nhan_id = :don_vi_id2)
                        AND h.trang_thai = 'DANG_XU_LY'
                        AND h.sla_mau IN ('VANG', 'DO')
                      ORDER BY h.sla_mau = 'DO' DESC, h.ngay_hen_tra ASC
                      LIMIT 15";
        $stmtAlerts = $db->prepare($sqlAlerts);
        $stmtAlerts->execute([':don_vi_id' => $donViId, ':don_vi_id2' => $donViId]);
        $alertList = $stmtAlerts->fetchAll(PDO::FETCH_ASSOC);

        return [
            'cards' => [
                'tong_ho_so' => (int)($cards['tong_ho_so'] ?? 0),
                'dang_xu_ly' => (int)($cards['dang_xu_ly'] ?? 0),
                'sap_qua_han' => (int)($cards['sap_qua_han'] ?? 0),
                'qua_han' => (int)($cards['qua_han'] ?? 0),
                'da_giai_quyet' => $daGiaiQuyet,
                'ty_le_dung_han' => $tyLeDungHan,
            ],
            'chuyen_vien_list' => $chuyenVienList,
            'alert_list' => $alertList,
        ];
    }

    /**
     * Lấy dữ liệu Dashboard cho Lãnh đạo Cơ quan (Giám đốc Sở / Chủ tịch UBND Quận)
     *
     * @param int $donViId
     * @return array
     */
    public static function getStatsLdCoQuan(int $donViId): array
    {
        $db = Database::connection();

        // 1. KPI tổng thể cơ quan
        $sqlKpi = "SELECT 
            COUNT(h.id) as tong_tiep_nhan,
            SUM(CASE WHEN h.kenh_nop = 'TRUC_TUYEN' THEN 1 ELSE 0 END) as truc_tuyen,
            SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') THEN 1 ELSE 0 END) as da_giai_quyet,
            SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.ngay_hoan_thanh <= h.ngay_hen_tra THEN 1 ELSE 0 END) as giai_quyet_dung_han,
            SUM(CASE WHEN h.trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.sla_mau = 'DO' THEN 1 ELSE 0 END) as dang_qua_han,
            SUM(CASE WHEN h.trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.sla_mau = 'VANG' THEN 1 ELSE 0 END) as sap_qua_han
        FROM ho_so h
        WHERE h.is_deleted = 0 
          AND (h.don_vi_tiep_nhan_id = :don_vi_id OR h.don_vi_hien_tai_id = :don_vi_id2)";

        $stmtKpi = $db->prepare($sqlKpi);
        $stmtKpi->execute([':don_vi_id' => $donViId, ':don_vi_id2' => $donViId]);
        $kpi = $stmtKpi->fetch(PDO::FETCH_ASSOC) ?: [];

        $tongTiepNhan = (int)($kpi['tong_tiep_nhan'] ?? 0);
        $trucTuyen = (int)($kpi['truc_tuyen'] ?? 0);
        $daGiaiQuyet = (int)($kpi['da_giai_quyet'] ?? 0);
        $dungHan = (int)($kpi['giai_quyet_dung_han'] ?? 0);

        $tyLeTrucTuyen = $tongTiepNhan > 0 ? round(($trucTuyen / $tongTiepNhan) * 100, 1) : 0.0;
        $tyLeDungHan = $daGiaiQuyet > 0 ? round(($dungHan / $daGiaiQuyet) * 100, 1) : 100.0;

        // 2. Thống kê theo các phòng ban / đơn vị con trực thuộc
        $sqlPhongBan = "SELECT 
            dv.id, dv.ten_don_vi, dv.ma_dinh_danh,
            COUNT(h.id) as tong_ho_so,
            SUM(CASE WHEN h.trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.sla_mau = 'DO' THEN 1 ELSE 0 END) as so_qua_han,
            SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') THEN 1 ELSE 0 END) as da_xong,
            SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.ngay_hoan_thanh <= h.ngay_hen_tra THEN 1 ELSE 0 END) as dung_han
        FROM don_vi dv
        LEFT JOIN ho_so h ON dv.id = h.don_vi_hien_tai_id AND h.is_deleted = 0
        WHERE dv.parent_id = :don_vi_id OR dv.id = :don_vi_id2
        GROUP BY dv.id, dv.ten_don_vi, dv.ma_dinh_danh
        ORDER BY so_qua_han DESC, tong_ho_so DESC";

        $stmtPb = $db->prepare($sqlPhongBan);
        $stmtPb->execute([':don_vi_id' => $donViId, ':don_vi_id2' => $donViId]);
        $phongBanList = $stmtPb->fetchAll(PDO::FETCH_ASSOC);

        // 3. Xếp hạng chuyên viên trong cơ quan
        $sqlRanking = "SELECT 
            u.id, u.ho_ten, dv.ten_don_vi as phong_ban,
            COUNT(h.id) as tong_thu_ly,
            SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') THEN 1 ELSE 0 END) as da_hoan_thanh,
            SUM(CASE WHEN h.trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.sla_mau = 'DO' THEN 1 ELSE 0 END) as qua_han,
            ROUND(SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.ngay_hoan_thanh <= h.ngay_hen_tra THEN 1 ELSE 0 END) * 100.0 / NULLIF(SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') THEN 1 ELSE 0 END), 0), 1) as ty_le_dung_han
        FROM nguoi_dung u
        JOIN don_vi dv ON u.don_vi_chinh_id = dv.id
        LEFT JOIN ho_so h ON u.id = h.chuyen_vien_thu_ly_id AND h.is_deleted = 0
        WHERE (dv.parent_id = :don_vi_id OR dv.id = :don_vi_id2) AND u.trang_thai = 1
        GROUP BY u.id, u.ho_ten, dv.ten_don_vi
        ORDER BY da_hoan_thanh DESC, ty_le_dung_han DESC
        LIMIT 10";

        $stmtRk = $db->prepare($sqlRanking);
        $stmtRk->execute([':don_vi_id' => $donViId, ':don_vi_id2' => $donViId]);
        $rankingList = $stmtRk->fetchAll(PDO::FETCH_ASSOC);

        // 4. Danh sách hồ sơ quá hạn cần đính kèm Mẫu 04
        $sqlOverdue = "SELECT * FROM v_ho_so_qua_han LIMIT 10";
        $overdueList = $db->query($sqlOverdue)->fetchAll(PDO::FETCH_ASSOC);

        return [
            'kpi' => [
                'tong_tiep_nhan' => $tongTiepNhan,
                'truc_tuyen' => $trucTuyen,
                'ty_le_truc_tuyen' => $tyLeTrucTuyen,
                'da_giai_quyet' => $daGiaiQuyet,
                'dung_han' => $dungHan,
                'ty_le_dung_han' => $tyLeDungHan,
                'dang_qua_han' => (int)($kpi['dang_qua_han'] ?? 0),
                'sap_qua_han' => (int)($kpi['sap_qua_han'] ?? 0),
            ],
            'phong_ban_list' => $phongBanList,
            'ranking_list' => $rankingList,
            'overdue_list' => $overdueList,
        ];
    }

    /**
     * Lấy dữ liệu Trung tâm Điều hành Thông minh IOC Hải Phòng
     * Tích hợp 4 Thẻ KPI, Heatmap, Bản đồ số SVG 15 quận/huyện, Bảng xếp hạng thi đua, Điểm thắt cổ chai
     * Tối ưu truy vấn & Cache 5 phút tại storage/cache/ioc_stats.json
     *
     * @param bool $forceRefresh
     * @return array
     */
    public static function getStatsIoc(bool $forceRefresh = false): array
    {
        $startTime = microtime(true);
        $cacheFile = dirname(__DIR__, 2) . '/storage/cache/ioc_stats.json';

        // 1. Kiểm tra cache nếu không ép refresh
        if (!$forceRefresh && file_exists($cacheFile)) {
            $mtime = filemtime($cacheFile);
            if ($mtime && (time() - $mtime < self::CACHE_TTL_SECONDS)) {
                $cachedJson = file_get_contents($cacheFile);
                if ($cachedJson !== false) {
                    $cachedData = json_decode($cachedJson, true);
                    if (is_array($cachedData)) {
                        $cachedData['tu_cache'] = true;
                        $cachedData['thoi_gian_truy_van'] = round(microtime(true) - $startTime, 4);
                        return $cachedData;
                    }
                }
            }
        }

        $db = Database::connection();

        // --------------------------------------------------------------------
        // A. 4 THẺ CHỈ TIÊU KPI LỚN (§2 Báo cáo Kiến trúc)
        // --------------------------------------------------------------------
        // 1. Tỷ lệ Đúng/trước hạn >= 98%
        // 2. DVC Toàn trình >= 80%
        // 3. Số hóa 100% (theo NĐ 107/2021)
        // 4. Hài lòng SIPAS >= 92% (theo QĐ 876/QĐ-BNV)
        $sqlKpiCity = "SELECT 
            COUNT(h.id) as tong_ho_so,
            SUM(CASE WHEN h.kenh_nop = 'TRUC_TUYEN' THEN 1 ELSE 0 END) as so_truc_tuyen,
            SUM(CASE WHEN t.muc_do_dvc = 'TOAN_TRINH' AND h.kenh_nop = 'TRUC_TUYEN' THEN 1 ELSE 0 END) as so_toan_trinh,
            SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') THEN 1 ELSE 0 END) as da_giai_quyet,
            SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.ngay_hoan_thanh <= h.ngay_hen_tra THEN 1 ELSE 0 END) as dung_han,
            SUM(CASE WHEN h.trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.sla_mau = 'DO' THEN 1 ELSE 0 END) as dang_qua_han,
            SUM(CASE WHEN EXISTS (SELECT 1 FROM ho_so_tai_lieu tl WHERE tl.ho_so_id = h.id AND tl.loai_tai_lieu = 'SO_HOA') 
                     OR EXISTS (SELECT 1 FROM ket_qua_giai_quyet kq WHERE kq.ho_so_id = h.id) THEN 1 ELSE 0 END) as so_ho_so_so_hoa
        FROM ho_so h
        JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
        WHERE h.is_deleted = 0";

        $stmtKpi = $db->query($sqlKpiCity);
        $kpiCity = $stmtKpi->fetch(PDO::FETCH_ASSOC) ?: [];

        $tongHoSo = (int)($kpiCity['tong_ho_so'] ?? 0);
        $daGiaiQuyet = (int)($kpiCity['da_giai_quyet'] ?? 0);
        $dungHan = (int)($kpiCity['dung_han'] ?? 0);
        $soToanTrinh = (int)($kpiCity['so_toan_trinh'] ?? 0);
        $soSoHoa = (int)($kpiCity['so_ho_so_so_hoa'] ?? 0);

        // Tính 4 chỉ số
        $tyLeDungHan = $daGiaiQuyet > 0 ? round(($dungHan / $daGiaiQuyet) * 100, 1) : 100.0;
        $tyLeToanTrinh = $tongHoSo > 0 ? round(($soToanTrinh / $tongHoSo) * 100, 1) : 0.0;
        $tyLeSoHoa = $tongHoSo > 0 ? round(($soSoHoa / $tongHoSo) * 100, 1) : 0.0;

        // Điểm hài lòng SIPAS (trung bình từ đánh giá)
        $sqlSipas = "SELECT 
            COUNT(*) as so_luot,
            AVG(diem_trung_binh) as diem_tb
        FROM danh_gia_hai_long";
        $stmtSipas = $db->query($sqlSipas);
        $sipasRow = $stmtSipas->fetch(PDO::FETCH_ASSOC) ?: [];
        $diemTbSipas = (float)($sipasRow['diem_tb'] ?? 4.8);
        $tyLeHaiLong = round(($diemTbSipas / 5.0) * 100, 1);

        $kpiCards = [
            'dung_han' => [
                'tieu_de' => 'Tỷ lệ Đúng & Trước hạn',
                'gia_tri' => $tyLeDungHan,
                'don_vi' => '%',
                'chi_tieu' => '≥ 98.0%',
                'dat' => ($tyLeDungHan >= 98.0),
                'so_lieu_phu' => "{$dungHan}/{$daGiaiQuyet} hồ sơ hoàn thành",
            ],
            'toan_trinh' => [
                'tieu_de' => 'Dịch vụ công Toàn trình',
                'gia_tri' => $tyLeToanTrinh,
                'don_vi' => '%',
                'chi_tieu' => '≥ 80.0%',
                'dat' => ($tyLeToanTrinh >= 80.0),
                'so_lieu_phu' => "{$soToanTrinh}/{$tongHoSo} hồ sơ tiếp nhận",
            ],
            'so_hoa' => [
                'tieu_de' => 'Số hóa Hồ sơ & Kết quả',
                'gia_tri' => $tyLeSoHoa,
                'don_vi' => '%',
                'chi_tieu' => '100%',
                'dat' => ($tyLeSoHoa >= 98.0),
                'so_lieu_phu' => "{$soSoHoa}/{$tongHoSo} hồ sơ đã số hóa",
            ],
            'hai_long' => [
                'tieu_de' => 'Mức độ Hài lòng SIPAS',
                'gia_tri' => $tyLeHaiLong,
                'don_vi' => '%',
                'chi_tieu' => '≥ 92.0%',
                'dat' => ($tyLeHaiLong >= 92.0),
                'so_lieu_phu' => "Điểm TB: {$diemTbSipas}/5.0 (" . (int)($sipasRow['so_luot'] ?? 0) . " lượt đánh giá)",
            ],
        ];

        // --------------------------------------------------------------------
        // B. HEATMAP TIẾP NHẬN THEO GIỜ × NGÀY (Matrix 24 Giờ × 7 Ngày)
        // --------------------------------------------------------------------
        $heatmapMatrix = self::buildHeatmapData($db);

        // --------------------------------------------------------------------
        // C. BẢN ĐỒ SỐ 15 QUẬN/HUYỆN HẢI PHÒNG
        // --------------------------------------------------------------------
        $districtMap = self::buildDistrictMapData($db);

        // --------------------------------------------------------------------
        // D. BẢNG XẾP HẠNG THI ĐUA SỞ / QUẬN
        // --------------------------------------------------------------------
        $emulationRanking = self::buildEmulationRanking($db);

        // --------------------------------------------------------------------
        // E. PHÁT HIỆN ĐIỂM THẮT CỔ CHAI (BOTTLENECK DETECTOR)
        // --------------------------------------------------------------------
        $bottleneck = self::detectBottleneck($db);

        // Tổng hợp kết quả
        $result = [
            'kpi_cards' => $kpiCards,
            'summary' => [
                'tong_tiep_nhan' => $tongHoSo,
                'da_giai_quyet' => $daGiaiQuyet,
                'dang_xu_ly' => $tongHoSo - $daGiaiQuyet,
                'dang_qua_han' => (int)($kpiCity['dang_qua_han'] ?? 0),
            ],
            'heatmap' => $heatmapMatrix,
            'district_map' => $districtMap,
            'emulation' => $emulationRanking,
            'bottleneck' => $bottleneck,
            'cap_nhat_luc' => date('d/m/Y H:i:s'),
            'tu_cache' => false,
            'thoi_gian_truy_van' => round(microtime(true) - $startTime, 4),
        ];

        // Lưu cache JSON
        @file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $result;
    }

    /**
     * Dựng dữ liệu Heatmap tiếp nhận Giờ x Ngày trong tuần
     */
    private static function buildHeatmapData(PDO $db): array
    {
        // Nhóm theo DAYOFWEEK (1=CN, 2=T2 ... 7=T7) và HOUR(ngay_tiep_nhan)
        $sql = "SELECT 
            DAYOFWEEK(ngay_tiep_nhan) as thu_num,
            HOUR(ngay_tiep_nhan) as gio,
            COUNT(*) as so_luong
        FROM ho_so
        WHERE is_deleted = 0 AND ngay_tiep_nhan IS NOT NULL
        GROUP BY DAYOFWEEK(ngay_tiep_nhan), HOUR(ngay_tiep_nhan)";

        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $days = [
            2 => 'Thứ 2',
            3 => 'Thứ 3',
            4 => 'Thứ 4',
            5 => 'Thứ 5',
            6 => 'Thứ 6',
            7 => 'Thứ 7',
            1 => 'Chủ Nhật'
        ];

        // Khung giờ hành chính chính: 7h đến 18h
        $hours = range(7, 18);

        // Khởi tạo ma trận rỗng
        $matrix = [];
        $maxVal = 1;
        foreach ($days as $dayKey => $dayName) {
            $matrix[$dayKey] = [
                'ten_ngay' => $dayName,
                'gio' => array_fill_keys($hours, 0),
            ];
        }

        foreach ($rows as $r) {
            $d = (int)$r['thu_num'];
            $h = (int)$r['gio'];
            $cnt = (int)$r['so_luong'];
            if (isset($matrix[$d]['gio'][$h])) {
                $matrix[$d]['gio'][$h] = $cnt;
                if ($cnt > $maxVal) {
                    $maxVal = $cnt;
                }
            }
        }

        return [
            'days' => $days,
            'hours' => $hours,
            'matrix' => $matrix,
            'max_val' => $maxVal,
        ];
    }

    /**
     * Dựng dữ liệu Bản đồ số 15 quận/huyện Hải Phòng
     */
    private static function buildDistrictMapData(PDO $db): array
    {
        // 15 quận/huyện Hải Phòng với mã chuẩn và tên gọi
        $districtsDef = [
            10 => ['code' => 'HONG_BANG', 'name' => 'Hồng Bàng', 'short' => 'HB', 'type' => 'QUAN'],
            11 => ['code' => 'NGO_QUYEN', 'name' => 'Ngô Quyền', 'short' => 'NQ', 'type' => 'QUAN'],
            12 => ['code' => 'LE_CHAN', 'name' => 'Lê Chân', 'short' => 'LC', 'type' => 'QUAN'],
            13 => ['code' => 'THUY_NGUYEN', 'name' => 'Thủy Nguyên', 'short' => 'TN', 'type' => 'TP_THUOC_TINH'],
            14 => ['code' => 'AN_DUONG', 'name' => 'An Dương', 'short' => 'AD', 'type' => 'QUAN'],
            15 => ['code' => 'HAI_AN', 'name' => 'Hải An', 'short' => 'HA', 'type' => 'QUAN'],
            16 => ['code' => 'KIEN_AN', 'name' => 'Kiến An', 'short' => 'KA', 'type' => 'QUAN'],
            17 => ['code' => 'DO_SON', 'name' => 'Đồ Sơn', 'short' => 'DS', 'type' => 'QUAN'],
            18 => ['code' => 'DUONG_KINH', 'name' => 'Dương Kinh', 'short' => 'DK', 'type' => 'QUAN'],
            19 => ['code' => 'AN_LAO', 'name' => 'An Lão', 'short' => 'AL', 'type' => 'HUYEN'],
            20 => ['code' => 'KIEN_THUY', 'name' => 'Kiến Thụy', 'short' => 'KT', 'type' => 'HUYEN'],
            21 => ['code' => 'TIEN_LANG', 'name' => 'Tiên Lãng', 'short' => 'TL', 'type' => 'HUYEN'],
            22 => ['code' => 'VINH_BAO', 'name' => 'Vĩnh Bảo', 'short' => 'VB', 'type' => 'HUYEN'],
            23 => ['code' => 'CAT_HAI', 'name' => 'Cát Hải', 'short' => 'CH', 'type' => 'HUYEN_DAO'],
            24 => ['code' => 'BACH_LONG_VI', 'name' => 'Bạch Long Vĩ', 'short' => 'BLV', 'type' => 'HUYEN_DAO'],
        ];

        // Lấy dữ liệu từ view v_ty_le_dung_han_don_vi cho cấp huyện
        $sql = "SELECT * FROM v_ty_le_dung_han_don_vi WHERE loai_don_vi = 'UBND_HUYEN'";
        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $statsByDvId = [];
        foreach ($rows as $r) {
            $statsByDvId[(int)$r['don_vi_id']] = $r;
        }

        // Lấy điểm phạt thi đua
        $sqlDiem = "SELECT don_vi_id, SUM(diem_thay_doi) as tong_phat 
                    FROM diem_thi_dua_don_vi 
                    GROUP BY don_vi_id";
        $phatRows = $db->query($sqlDiem)->fetchAll(PDO::FETCH_KEY_PAIR);

        $result = [];
        foreach ($districtsDef as $id => $def) {
            $data = $statsByDvId[$id] ?? null;
            $tongTiepNhan = $data ? (int)$data['tong_ho_so_tiep_nhan'] : 0;
            $daGiaiQuyet = $data ? (int)$data['so_ho_so_da_giai_quyet'] : 0;
            $dungHan = $data ? (int)$data['so_giai_quyet_dung_han'] : 0;
            $soQuaHan = $data ? (int)$data['so_dang_qua_han'] : 0;
            $tyLeDungHan = $data && $data['ty_le_dung_han_pt'] !== null ? (float)$data['ty_le_dung_han_pt'] : 100.0;
            $trucTuyen = $data ? (int)$data['so_ho_so_truc_tuyen'] : 0;

            // Xác định màu trên bản đồ SVG
            if ($tongTiepNhan === 0) {
                $color = '#94a3b8'; // Xám: Chưa có dữ liệu
                $statusLevel = 'NONE';
            } elseif ($tyLeDungHan >= 98.0) {
                $color = '#10b981'; // Xanh ngọc: Đạt chuẩn vàng
                $statusLevel = 'XANH';
            } elseif ($tyLeDungHan >= 95.0) {
                $color = '#f59e0b'; // Vàng cam: Cảnh báo
                $statusLevel = 'VANG';
            } else {
                $color = '#ef4444'; // Đỏ: Kém
                $statusLevel = 'DO';
            }

            $diemPhat = (float)($phatRows[$id] ?? 0.0);
            $diemThiDua = max(0.0, round(100.0 + $diemPhat, 1));

            $result[$id] = [
                'id' => $id,
                'code' => $def['code'],
                'ten' => $def['name'],
                'short' => $def['short'],
                'type' => $def['type'],
                'tong_tiep_nhan' => $tongTiepNhan,
                'da_giai_quyet' => $daGiaiQuyet,
                'dang_xu_ly' => $tongTiepNhan - $daGiaiQuyet,
                'dung_han' => $dungHan,
                'so_qua_han' => $soQuaHan,
                'ty_le_dung_han' => $tyLeDungHan,
                'truc_tuyen' => $trucTuyen,
                'diem_thi_dua' => $diemThiDua,
                'color' => $color,
                'status_level' => $statusLevel,
            ];
        }

        return $result;
    }

    /**
     * Dựng Bảng xếp hạng thi đua 15 Sở, Ban, Ngành và 15 Quận/Huyện/TP
     */
    private static function buildEmulationRanking(PDO $db): array
    {
        $sql = "SELECT 
            dv.id, dv.ten_don_vi, dv.ma_dinh_danh, dv.loai_don_vi,
            v.tong_ho_so_tiep_nhan,
            v.so_ho_so_da_giai_quyet,
            v.so_giai_quyet_dung_han,
            v.ty_le_dung_han_pt,
            v.so_dang_qua_han,
            COALESCE(SUM(d.diem_thay_doi), 0) as tong_phat
        FROM don_vi dv
        LEFT JOIN v_ty_le_dung_han_don_vi v ON dv.id = v.don_vi_id
        LEFT JOIN diem_thi_dua_don_vi d ON dv.id = d.don_vi_id
        WHERE dv.loai_don_vi IN ('SO_NGANH', 'UBND_HUYEN')
        GROUP BY dv.id, dv.ten_don_vi, dv.ma_dinh_danh, dv.loai_don_vi, 
                 v.tong_ho_so_tiep_nhan, v.so_ho_so_da_giai_quyet, v.so_giai_quyet_dung_han, 
                 v.ty_le_dung_han_pt, v.so_dang_qua_han";

        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $soNganh = [];
        $quanHuyen = [];

        foreach ($rows as $r) {
            $tongTiepNhan = (int)($r['tong_ho_so_tiep_nhan'] ?? 0);
            $tyLeDungHan = $r['ty_le_dung_han_pt'] !== null ? (float)$r['ty_le_dung_han_pt'] : 100.0;
            $diemPhat = (float)($r['tong_phat'] ?? 0.0);
            $diemThiDua = max(0.0, round(100.0 + $diemPhat, 1));

            $item = [
                'id' => (int)$r['id'],
                'ten_don_vi' => $r['ten_don_vi'],
                'ma_dinh_danh' => $r['ma_dinh_danh'],
                'tong_tiep_nhan' => $tongTiepNhan,
                'ty_le_dung_han' => $tyLeDungHan,
                'so_qua_han' => (int)($r['so_dang_qua_han'] ?? 0),
                'diem_thi_dua' => $diemThiDua,
            ];

            if ($r['loai_don_vi'] === 'SO_NGANH') {
                $soNganh[] = $item;
            } else {
                $quanHuyen[] = $item;
            }
        }

        // Sắp xếp giảm dần theo điểm thi đua, sau đó theo tỷ lệ đúng hạn
        $sortFn = function ($a, $b) {
            if ($a['diem_thi_dua'] !== $b['diem_thi_dua']) {
                return $b['diem_thi_dua'] <=> $a['diem_thi_dua'];
            }
            return $b['ty_le_dung_han'] <=> $a['ty_le_dung_han'];
        };

        usort($soNganh, $sortFn);
        usort($quanHuyen, $sortFn);

        return [
            'so_nganh' => $soNganh,
            'quan_huyen' => $quanHuyen,
        ];
    }

    /**
     * Phát hiện điểm thắt cổ chai (Bottleneck Detector)
     * Xác định bước quy trình có thời gian tồn đọng trung bình cao nhất
     */
    private static function detectBottleneck(PDO $db): array
    {
        $sql = "SELECT 
            qb.id as buoc_id,
            qb.ten_buoc,
            qb.loai_buoc,
            qb.vai_tro_xu_ly,
            qb.thoi_han_gio as thoi_han_quy_dinh,
            COUNT(h.id) as so_ho_so_dang_ton,
            AVG(h.thoi_gian_da_dung_gio) as thoi_gian_da_dung_tb,
            SUM(CASE WHEN h.sla_mau = 'DO' THEN 1 ELSE 0 END) as so_ho_so_qua_han
        FROM quy_trinh_buoc qb
        LEFT JOIN ho_so h ON qb.id = h.buoc_hien_tai_id AND h.is_deleted = 0 AND h.trang_thai = 'DANG_XU_LY'
        GROUP BY qb.id, qb.ten_buoc, qb.loai_buoc, qb.vai_tro_xu_ly, qb.thoi_han_gio
        ORDER BY so_ho_so_dang_ton DESC, so_ho_so_qua_han DESC";

        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $bottleneckStep = null;
        $maxOverdue = -1;

        $stepList = [];
        foreach ($rows as $r) {
            $ton = (int)$r['so_ho_so_dang_ton'];
            $quaHan = (int)$r['so_ho_so_qua_han'];
            $usedHours = round((float)($r['thoi_gian_da_dung_tb'] ?? 0), 1);
            $limitHours = (int)$r['thoi_han_quy_dinh'];

            $isBottleneck = false;
            if ($quaHan > $maxOverdue && $ton > 0) {
                $maxOverdue = $quaHan;
                $bottleneckStep = $r;
            }

            $stepList[] = [
                'buoc_id' => (int)$r['buoc_id'],
                'ten_buoc' => $r['ten_buoc'],
                'loai_buoc' => $r['loai_buoc'],
                'vai_tro' => $r['vai_tro_xu_ly'],
                'thoi_han_gio' => $limitHours,
                'so_luong_ton' => $ton,
                'so_qua_han' => $quaHan,
                'thoi_gian_tb' => $usedHours,
            ];
        }

        // Đề xuất cảnh báo nếu có bước nghẽn
        $warning = null;
        if ($bottleneckStep && ((int)$bottleneckStep['so_ho_so_qua_han'] > 0 || (int)$bottleneckStep['so_ho_so_dang_ton'] > 5)) {
            $warning = [
                'buoc' => $bottleneckStep['ten_buoc'],
                'vai_tro' => $bottleneckStep['vai_tro_xu_ly'],
                'so_qua_han' => (int)$bottleneckStep['so_ho_so_qua_han'],
                'so_ton' => (int)$bottleneckStep['so_ho_so_dang_ton'],
                'khuyen_nghi' => "Phát hiện tồn đọng lớn tại bước [{$bottleneckStep['ten_buoc']}]. Khuyến nghị Lãnh đạo chỉ đạo tăng cường cán bộ thụ lý hoặc rà soát khâu thẩm tra chuyên môn.",
            ];
        }

        return [
            'steps' => $stepList,
            'alert' => $warning,
        ];
    }

    /**
     * Báo cáo Tình hình Tiếp nhận và Giải quyết TTHC (Biểu mẫu 01 Thông tư 01/2018/TT-VPCP)
     *
     * @param array $filters (ky, tu_ngay, den_ngay, don_vi_id, linh_vuc_id)
     * @return array
     */
    public static function getReportThongTu01(array $filters): array
    {
        $db = Database::connection();
        $dateRange = self::parseDateRange($filters);

        $params = [];
        $whereSql = "WHERE h.is_deleted = 0";

        if ($dateRange['tu_ngay']) {
            $whereSql .= " AND h.ngay_tiep_nhan >= :tu_ngay";
            $params[':tu_ngay'] = $dateRange['tu_ngay'] . ' 00:00:00';
        }
        if ($dateRange['den_ngay']) {
            $whereSql .= " AND h.ngay_tiep_nhan <= :den_ngay";
            $params[':den_ngay'] = $dateRange['den_ngay'] . ' 23:59:59';
        }
        if (!empty($filters['don_vi_id'])) {
            $whereSql .= " AND h.don_vi_tiep_nhan_id = :don_vi_id";
            $params[':don_vi_id'] = (int)$filters['don_vi_id'];
        }
        if (!empty($filters['linh_vuc_id'])) {
            $whereSql .= " AND t.linh_vuc_id = :linh_vuc_id";
            $params[':linh_vuc_id'] = (int)$filters['linh_vuc_id'];
        }

        $sql = "SELECT 
            dv.id as don_vi_id,
            dv.ten_don_vi,
            dv.ma_dinh_danh,
            0 as ton_ky_truoc,
            SUM(CASE WHEN h.kenh_nop = 'TRUC_TIEP' THEN 1 ELSE 0 END) as tiep_nhan_truc_tiep,
            SUM(CASE WHEN h.kenh_nop = 'TRUC_TUYEN' THEN 1 ELSE 0 END) as tiep_nhan_truc_tuyen,
            COUNT(h.id) as tong_so_nhan,
            SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.ngay_hoan_thanh < h.ngay_hen_tra THEN 1 ELSE 0 END) as giai_quyet_truoc_han,
            SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.ngay_hoan_thanh = h.ngay_hen_tra THEN 1 ELSE 0 END) as giai_quyet_dung_han,
            SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.ngay_hoan_thanh > h.ngay_hen_tra THEN 1 ELSE 0 END) as giai_quyet_qua_han,
            SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') THEN 1 ELSE 0 END) as tong_da_giai_quyet,
            SUM(CASE WHEN h.trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.sla_mau != 'DO' THEN 1 ELSE 0 END) as dang_xu_ly_trong_han,
            SUM(CASE WHEN h.trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.sla_mau = 'DO' THEN 1 ELSE 0 END) as dang_xu_ly_qua_han,
            SUM(CASE WHEN h.trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA') THEN 1 ELSE 0 END) as tong_dang_giai_quyet
        FROM ho_so h
        JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
        JOIN don_vi dv ON h.don_vi_tiep_nhan_id = dv.id
        {$whereSql}
        GROUP BY dv.id, dv.ten_don_vi, dv.ma_dinh_danh
        ORDER BY tong_so_nhan DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Tính tổng và tỷ lệ
        $totals = [
            'ton_ky_truoc' => 0,
            'tiep_nhan_truc_tiep' => 0,
            'tiep_nhan_truc_tuyen' => 0,
            'tong_so_nhan' => 0,
            'giai_quyet_truoc_han' => 0,
            'giai_quyet_dung_han' => 0,
            'giai_quyet_qua_han' => 0,
            'tong_da_giai_quyet' => 0,
            'dang_xu_ly_trong_han' => 0,
            'dang_xu_ly_qua_han' => 0,
            'tong_dang_giai_quyet' => 0,
        ];

        foreach ($rows as &$r) {
            $daGq = (int)$r['tong_da_giai_quyet'];
            $dungVaTruoc = (int)$r['giai_quyet_truoc_han'] + (int)$r['giai_quyet_dung_han'];
            $r['ty_le_dung_han'] = $daGq > 0 ? round(($dungVaTruoc / $daGq) * 100, 1) : 100.0;

            foreach ($totals as $k => $val) {
                if (isset($r[$k])) {
                    $totals[$k] += (int)$r[$k];
                }
            }
        }
        unset($r);

        $tongDaGq = $totals['tong_da_giai_quyet'];
        $tongDungVaTruoc = $totals['giai_quyet_truoc_han'] + $totals['giai_quyet_dung_han'];
        $totals['ty_le_dung_han'] = $tongDaGq > 0 ? round(($tongDungVaTruoc / $tongDaGq) * 100, 1) : 100.0;

        return [
            'date_range' => $dateRange,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * Báo cáo Số hóa Hồ sơ & Kết quả (Nghị định 107/2021/NĐ-CP)
     *
     * @param array $filters
     * @return array
     */
    public static function getReportSoHoa(array $filters): array
    {
        $db = Database::connection();
        $dateRange = self::parseDateRange($filters);

        $params = [];
        $whereSql = "WHERE h.is_deleted = 0";

        if ($dateRange['tu_ngay']) {
            $whereSql .= " AND h.ngay_tiep_nhan >= :tu_ngay";
            $params[':tu_ngay'] = $dateRange['tu_ngay'] . ' 00:00:00';
        }
        if ($dateRange['den_ngay']) {
            $whereSql .= " AND h.ngay_tiep_nhan <= :den_ngay";
            $params[':den_ngay'] = $dateRange['den_ngay'] . ' 23:59:59';
        }
        if (!empty($filters['don_vi_id'])) {
            $whereSql .= " AND h.don_vi_tiep_nhan_id = :don_vi_id";
            $params[':don_vi_id'] = (int)$filters['don_vi_id'];
        }

        $sql = "SELECT 
            dv.id as don_vi_id,
            dv.ten_don_vi,
            COUNT(h.id) as tong_tiep_nhan,
            SUM(CASE WHEN EXISTS (SELECT 1 FROM ho_so_tai_lieu tl WHERE tl.ho_so_id = h.id AND tl.loai_tai_lieu = 'SO_HOA') THEN 1 ELSE 0 END) as so_hoa_thanh_phan,
            SUM(CASE WHEN EXISTS (SELECT 1 FROM ket_qua_giai_quyet kq WHERE kq.ho_so_id = h.id) THEN 1 ELSE 0 END) as so_hoa_ket_qua,
            SUM(CASE WHEN EXISTS (SELECT 1 FROM kho_du_lieu_cong_dan kd WHERE kd.ho_so_id = h.id) THEN 1 ELSE 0 END) as dong_bo_kho_du_lieu
        FROM ho_so h
        JOIN don_vi dv ON h.don_vi_tiep_nhan_id = dv.id
        {$whereSql}
        GROUP BY dv.id, dv.ten_don_vi
        ORDER BY tong_tiep_nhan DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $tong = (int)$r['tong_tiep_nhan'];
            $shTp = (int)$r['so_hoa_thanh_phan'];
            $shKq = (int)$r['so_hoa_ket_qua'];
            $r['ty_le_thanh_phan'] = $tong > 0 ? round(($shTp / $tong) * 100, 1) : 0.0;
            $r['ty_le_ket_qua'] = $tong > 0 ? round(($shKq / $tong) * 100, 1) : 0.0;
        }
        unset($r);

        return [
            'date_range' => $dateRange,
            'rows' => $rows,
        ];
    }

    /**
     * Báo cáo Hồ sơ Quá hạn kèm Mẫu số 04 TT 01/2018/TT-VPCP
     *
     * @param array $filters
     * @return array
     */
    public static function getReportQuaHan(array $filters): array
    {
        $db = Database::connection();
        $dateRange = self::parseDateRange($filters);

        $params = [];
        $whereSql = "WHERE h.is_deleted = 0 AND (h.sla_mau = 'DO' OR (h.ngay_hoan_thanh IS NULL AND NOW() > h.ngay_hen_tra))";

        if (!empty($filters['don_vi_id'])) {
            $whereSql .= " AND h.don_vi_hien_tai_id = :don_vi_id";
            $params[':don_vi_id'] = (int)$filters['don_vi_id'];
        }

        $sql = "SELECT 
            h.id, h.ma_ho_so, t.ten_tthc, dv.ten_don_vi,
            u.ho_ten as ten_chuyen_vien,
            h.nguoi_nop_ho_ten, h.ngay_tiep_nhan, h.ngay_hen_tra,
            TIMESTAMPDIFF(HOUR, h.ngay_hen_tra, NOW()) as so_gio_tre_han,
            CASE WHEN xl.id IS NOT NULL THEN 1 ELSE 0 END as da_co_mau_04,
            xl.so_van_ban, xl.ngay_ky, xl.ngay_hen_moi, xl.ly_do_qua_han
        FROM ho_so h
        JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
        JOIN don_vi dv ON h.don_vi_hien_tai_id = dv.id
        LEFT JOIN nguoi_dung u ON h.chuyen_vien_thu_ly_id = u.id
        LEFT JOIN van_ban_xin_loi xl ON h.id = xl.ho_so_id
        {$whereSql}
        ORDER BY so_gio_tre_han DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'date_range' => $dateRange,
            'rows' => $rows,
            'tong_qua_han' => count($rows),
            'da_co_mau_04' => count(array_filter($rows, fn($r) => (int)$r['da_co_mau_04'] === 1)),
        ];
    }

    /**
     * Báo cáo Mức độ Hài lòng SIPAS (theo Quyết định 876/QĐ-BNV)
     *
     * @param array $filters
     * @return array
     */
    public static function getReportHaiLong(array $filters): array
    {
        $db = Database::connection();
        $dateRange = self::parseDateRange($filters);

        $sql = "SELECT 
            COUNT(*) as tong_luot,
            ROUND(AVG(diem_tiep_can), 2) as tb_tiep_can,
            ROUND(AVG(diem_thu_tuc), 2) as tb_thu_tuc,
            ROUND(AVG(diem_cong_chuc), 2) as tb_cong_chuc,
            ROUND(AVG(diem_ket_qua), 2) as tb_ket_qua,
            ROUND(AVG(diem_phan_anh), 2) as tb_phan_anh,
            ROUND(AVG(diem_trung_binh), 2) as tb_chung
        FROM danh_gia_hai_long";

        $summary = $db->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [
            'tong_luot' => 0,
            'tb_tiep_can' => 5.0,
            'tb_thu_tuc' => 5.0,
            'tb_cong_chuc' => 5.0,
            'tb_ket_qua' => 5.0,
            'tb_phan_anh' => 5.0,
            'tb_chung' => 5.0,
        ];

        // Lấy danh sách đánh giá gần nhất
        $sqlList = "SELECT dg.*, h.ma_ho_so, t.ten_tthc, dv.ten_don_vi
                    FROM danh_gia_hai_long dg
                    JOIN ho_so h ON dg.ho_so_id = h.id
                    JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
                    JOIN don_vi dv ON h.don_vi_tiep_nhan_id = dv.id
                    ORDER BY dg.ngay_danh_gia DESC
                    LIMIT 20";
        $list = $db->query($sqlList)->fetchAll(PDO::FETCH_ASSOC);

        $tbChung = (float)($summary['tb_chung'] ?? 4.8);
        $tyLeHaiLong = round(($tbChung / 5.0) * 100, 1);

        return [
            'date_range' => $dateRange,
            'summary' => $summary,
            'ty_le_hai_long' => $tyLeHaiLong,
            'dat_chi_tieu' => ($tyLeHaiLong >= 92.0),
            'list' => $list,
        ];
    }

    /**
     * Báo cáo Hồ sơ Trực tuyến Toàn trình và Một phần
     */
    public static function getReportTrucTuyen(array $filters): array
    {
        $db = Database::connection();
        $dateRange = self::parseDateRange($filters);

        $sql = "SELECT 
            dv.id as don_vi_id,
            dv.ten_don_vi,
            COUNT(h.id) as tong_so,
            SUM(CASE WHEN h.kenh_nop = 'TRUC_TUYEN' THEN 1 ELSE 0 END) as tong_truc_tuyen,
            SUM(CASE WHEN t.muc_do_dvc = 'TOAN_TRINH' AND h.kenh_nop = 'TRUC_TUYEN' THEN 1 ELSE 0 END) as toan_trinh,
            SUM(CASE WHEN t.muc_do_dvc = 'MOT_PHAN' AND h.kenh_nop = 'TRUC_TUYEN' THEN 1 ELSE 0 END) as mot_phan,
            SUM(CASE WHEN h.kenh_nop = 'TRUC_TIEP' THEN 1 ELSE 0 END) as truc_tiep
        FROM ho_so h
        JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
        JOIN don_vi dv ON h.don_vi_tiep_nhan_id = dv.id
        WHERE h.is_deleted = 0
        GROUP BY dv.id, dv.ten_don_vi
        ORDER BY tong_so DESC";

        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $tong = (int)$r['tong_so'];
            $toanTrinh = (int)$r['toan_trinh'];
            $tt = (int)$r['tong_truc_tuyen'];
            $r['ty_le_toan_trinh'] = $tong > 0 ? round(($toanTrinh / $tong) * 100, 1) : 0.0;
            $r['ty_le_truc_tuyen'] = $tong > 0 ? round(($tt / $tong) * 100, 1) : 0.0;
        }
        unset($r);

        return [
            'date_range' => $dateRange,
            'rows' => $rows,
        ];
    }

    /**
     * Báo cáo Phối hợp Liên thông giữa các đơn vị
     */
    public static function getReportLienThong(array $filters): array
    {
        $db = Database::connection();
        $dateRange = self::parseDateRange($filters);

        $sql = "SELECT 
            dv_gui.ten_don_vi as don_vi_gui,
            dv_nhan.ten_don_vi as don_vi_nhan,
            COUNT(lc.id) as so_luot_chuyen,
            AVG(TIMESTAMPDIFF(HOUR, lc.thoi_gian_chuyen, NOW())) as thoi_gian_trung_binh_gio
        FROM ho_so_luan_chuyen lc
        JOIN don_vi dv_gui ON lc.tu_don_vi_id = dv_gui.id
        JOIN don_vi dv_nhan ON lc.den_don_vi_id = dv_nhan.id
        WHERE lc.tu_don_vi_id != lc.den_don_vi_id
        GROUP BY dv_gui.ten_don_vi, dv_nhan.ten_don_vi
        ORDER BY so_luot_chuyen DESC
        LIMIT 20";

        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return [
            'date_range' => $dateRange,
            'rows' => $rows,
        ];
    }

    /**
     * Phân tích khoảng ngày từ bộ lọc kỳ (Tháng này, Quý này, Năm nay, Tùy chọn)
     */
    public static function parseDateRange(array $filters): array
    {
        $ky = $filters['ky'] ?? 'thang_nay';
        $tuNgay = $filters['tu_ngay'] ?? null;
        $denNgay = $filters['den_ngay'] ?? null;

        if ($ky === 'tuy_chon' && $tuNgay && $denNgay) {
            return [
                'ky' => 'tuy_chon',
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay,
                'label' => "Từ {$tuNgay} đến {$denNgay}",
            ];
        }

        switch ($ky) {
            case 'thang_truoc':
                $start = date('Y-m-01', strtotime('first day of last month'));
                $end = date('Y-m-t', strtotime('last day of last month'));
                $label = 'Tháng trước (' . date('m/Y', strtotime('last month')) . ')';
                break;
            case 'quy_nay':
                $curMonth = (int)date('m');
                $curQuarter = ceil($curMonth / 3);
                $startMonth = sprintf('%02d', ($curQuarter - 1) * 3 + 1);
                $endMonth = sprintf('%02d', $curQuarter * 3);
                $start = date('Y-') . $startMonth . '-01';
                $end = date('Y-') . $endMonth . '-' . cal_days_in_month(CAL_GREGORIAN, (int)$endMonth, (int)date('Y'));
                $label = "Quý {$curQuarter}/" . date('Y');
                break;
            case 'nam_nay':
                $start = date('Y-01-01');
                $end = date('Y-12-31');
                $label = 'Năm ' . date('Y');
                break;
            case 'thang_nay':
            default:
                $start = date('Y-m-01');
                $end = date('Y-m-t');
                $label = 'Tháng ' . date('m/Y');
                break;
        }

        return [
            'ky' => $ky,
            'tu_ngay' => $start,
            'den_ngay' => $end,
            'label' => $label,
        ];
    }

    /**
     * Xuất tệp CSV UTF-8 có BOM (mở trực tiếp bằng Excel không lỗi phông tiếng Việt)
     *
     * @param array $headers
     * @param array $rows
     * @param string $filename
     * @return void
     */
    public static function exportCsv(array $headers, array $rows, string $filename = 'bao_cao.csv'): void
    {
        if (php_sapi_name() !== 'cli') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        $output = fopen('php://output', 'w');
        // Ghi BOM UTF-8 (\xEF\xBB\xBF) để Excel nhận diện bảng mã tiếng Việt
        fwrite($output, "\xEF\xBB\xBF");

        // Ghi dòng tiêu đề
        fputcsv($output, $headers);

        // Ghi dữ liệu
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
