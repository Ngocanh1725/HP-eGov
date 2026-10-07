<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;

/**
 * Class IncidentService
 * Quản lý Sự cố An toàn thông tin, Kế hoạch duy trì hoạt động BCP/DRP và Diễn tập định kỳ (§4.3, §4.4)
 */
class IncidentService
{
    /**
     * Lấy danh sách sự cố
     *
     * @param string|null $status Bộ lọc trạng thái (MOI, DANG_XU_LY, DA_KHAC_PHUC)
     * @return array<int, array<string, mixed>>
     */
    public static function getIncidents(?string $status = null): array
    {
        self::ensureSampleIncidents();

        $db = Database::connection();
        $sql = "SELECT s.*, u.ho_ten AS nguoi_xu_ly_ten, u.email AS nguoi_xu_ly_email
                FROM su_co s
                LEFT JOIN nguoi_dung u ON s.nguoi_xu_ly_id = u.id";
        $params = [];

        if ($status !== null && $status !== '') {
            $sql .= " WHERE s.trang_thai = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY s.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tạo mới một sự cố
     */
    public static function createIncident(array $data, int $userId): bool
    {
        $db = Database::connection();
        try {
            $stmt = $db->prepare("INSERT INTO su_co 
                (loai_su_co, muc_do, tieu_de, mo_ta, thoi_gian_phat_hien, raci_trach_nhiem, nguoi_xu_ly_id, bien_phap_khac_phuc, trang_thai)
                VALUES (:loai, :muc_do, :tieu_de, :mo_ta, :tg_phat_hien, :raci, :nguoi_xl, :bien_phap, :trang_thai)");

            return $stmt->execute([
                'loai'         => $data['loai_su_co'] ?? 'SU_CO_KHAC',
                'muc_do'       => $data['muc_do'] ?? 'TRUNG_BINH',
                'tieu_de'      => $data['tieu_de'] ?? '',
                'mo_ta'        => $data['mo_ta'] ?? '',
                'tg_phat_hien' => $data['thoi_gian_phat_hien'] ?? date('Y-m-d H:i:s'),
                'raci'         => $data['raci_trach_nhiem'] ?? 'Sở TT&TT (A/R) - Đơn vị nghiệp vụ (C)',
                'nguoi_xl'     => !empty($data['nguoi_xu_ly_id']) ? (int)$data['nguoi_xu_ly_id'] : $userId,
                'bien_phap'    => $data['bien_phap_khac_phuc'] ?? '',
                'trang_thai'   => $data['trang_thai'] ?? 'MOI',
            ]);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Cập nhật tiến độ khắc phục sự cố
     */
    public static function updateIncident(int $id, array $data): bool
    {
        $db = Database::connection();
        try {
            $stmt = $db->prepare("UPDATE su_co 
                SET muc_do = :muc_do,
                    tieu_de = :tieu_de,
                    mo_ta = :mo_ta,
                    raci_trach_nhiem = :raci,
                    bien_phap_khac_phuc = :bien_phap,
                    trang_thai = :trang_thai,
                    thoi_gian_khac_phuc = :tg_khac_phuc,
                    nguoi_xu_ly_id = :nguoi_xl
                WHERE id = :id");

            $tgKhacPhuc = null;
            if (($data['trang_thai'] ?? '') === 'DA_KHAC_PHUC') {
                $tgKhacPhuc = !empty($data['thoi_gian_khac_phuc']) ? $data['thoi_gian_khac_phuc'] : date('Y-m-d H:i:s');
            }

            return $stmt->execute([
                'muc_do'       => $data['muc_do'],
                'tieu_de'      => $data['tieu_de'],
                'mo_ta'        => $data['mo_ta'],
                'raci'         => $data['raci_trach_nhiem'],
                'bien_phap'    => $data['bien_phap_khac_phuc'],
                'trang_thai'   => $data['trang_thai'],
                'tg_khac_phuc' => $tgKhacPhuc,
                'nguoi_xl'     => !empty($data['nguoi_xu_ly_id']) ? (int)$data['nguoi_xu_ly_id'] : null,
                'id'           => $id,
            ]);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Lấy danh sách biên bản diễn tập
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getDrills(): array
    {
        self::ensureSampleDrills();

        $db = Database::connection();
        $stmt = $db->query("SELECT d.*, u.ho_ten AS nguoi_chu_tri_ten
                            FROM dien_tap d
                            JOIN nguoi_dung u ON d.nguoi_chu_tri_id = u.id
                            ORDER BY d.thoi_gian_dien_tap DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ghi nhận biên bản diễn tập mới
     */
    public static function recordDrill(array $data, int $userId): bool
    {
        $db = Database::connection();
        try {
            $stmt = $db->prepare("INSERT INTO dien_tap 
                (loai_dien_tap, tieu_de, kich_ban, thoi_gian_dien_tap, rto_thuc_te_phut, ket_qua, bien_ban_danh_gia, nguoi_chu_tri_id)
                VALUES (:loai, :tieu_de, :kich_ban, :tg, :rto, :kq, :bien_ban, :chu_tri)");

            return $stmt->execute([
                'loai'      => $data['loai_dien_tap'],
                'tieu_de'   => $data['tieu_de'],
                'kich_ban'  => $data['kich_ban'],
                'tg'        => $data['thoi_gian_dien_tap'] ?? date('Y-m-d H:i:s'),
                'rto'       => !empty($data['rto_thuc_te_phut']) ? (int)$data['rto_thuc_te_phut'] : null,
                'kq'        => $data['ket_qua'] ?? 'DAT',
                'bien_ban'  => $data['bien_ban_danh_gia'] ?? '',
                'chu_tri'   => $userId,
            ]);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Kiểm tra lịch diễn tập định kỳ và đưa ra cảnh báo nhắc nhở
     * - An toàn thông tin: 6 tháng/lần
     * - Chuyển đổi thảm họa DRP: 12 tháng/lần
     *
     * @return array<string, mixed>
     */
    public static function checkDrillSchedule(): array
    {
        $db = Database::connection();
        $sql = "SELECT loai_dien_tap, MAX(thoi_gian_dien_tap) as lan_cuoi FROM dien_tap GROUP BY loai_dien_tap";
        $drills = $db->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);

        $now = time();

        // 1. Kiểm tra ATTT (chu kỳ 6 tháng = ~180 ngày)
        $atttLast = isset($drills['AN_TOAN_THONG_TIN']) ? strtotime($drills['AN_TOAN_THONG_TIN']) : null;
        $atttDays = $atttLast ? (int)floor(($now - $atttLast) / 86400) : 999;
        $atttDue = $atttDays > 180;
        $atttWarning = $atttDays > 150 && !$atttDue;

        // 2. Kiểm tra DRP (chu kỳ 12 tháng = ~365 ngày)
        $drpLast = isset($drills['CHUYEN_DOI_THAM_HOA_DRP']) ? strtotime($drills['CHUYEN_DOI_THAM_HOA_DRP']) : null;
        $drpDays = $drpLast ? (int)floor(($now - $drpLast) / 86400) : 999;
        $drpDue = $drpDays > 365;
        $drpWarning = $drpDays > 330 && !$drpDue;

        return [
            'attt' => [
                'lan_cuoi'     => $drills['AN_TOAN_THONG_TIN'] ?? 'Chưa diễn tập',
                'so_ngay_qua'  => $atttDays,
                'den_han'      => $atttDue,
                'canh_bao'     => $atttWarning,
                'chu_ky_thang' => 6,
            ],
            'drp'  => [
                'lan_cuoi'     => $drills['CHUYEN_DOI_THAM_HOA_DRP'] ?? 'Chưa diễn tập',
                'so_ngay_qua'  => $drpDays,
                'den_han'      => $drpDue,
                'canh_bao'     => $drpWarning,
                'chu_ky_thang' => 12,
            ],
        ];
    }

    /**
     * Khởi tạo các sự cố mẫu chuẩn báo cáo §4.3, §4.4 nếu bảng su_co đang trống
     */
    public static function ensureSampleIncidents(): void
    {
        $db = Database::connection();
        $count = (int)$db->query("SELECT COUNT(*) FROM su_co")->fetchColumn();
        if ($count > 0) {
            return;
        }

        // Lấy admin user id
        $adminId = (int)$db->query("SELECT nd.id FROM nguoi_dung nd 
                                    JOIN nguoi_dung_vai_tro ndvt ON nd.id = ndvt.nguoi_dung_id 
                                    JOIN vai_tro v ON ndvt.vai_tro_id = v.id 
                                    WHERE v.ma_vai_tro = 'SYSADMIN' LIMIT 1")->fetchColumn();
        if (!$adminId) {
            $adminId = 1;
        }


        $samples = [
            [
                'loai_su_co'          => 'DUT_TSLCD',
                'muc_do'              => 'CAO',
                'tieu_de'             => 'Đứt tuyến cáp truyền số liệu chuyên dùng kết nối UBND Huyện Cát Hải',
                'mo_ta'               => 'Sự cố sạt lở đê biển gây đứt tuyến cáp quang TSLCD chính tuyến Hải Phòng - Cát Hải. Cán bộ Một cửa huyện Cát Hải tạm thời không kết nối được hệ thống chính.',
                'thoi_gian_phat_hien' => '2026-10-02 08:30:00',
                'thoi_gian_khac_phuc' => '2026-10-02 11:15:00',
                'nguoi_xu_ly_id'      => $adminId,
                'raci_trach_nhiem'    => 'Sở TT&TT (A/R) - VNPT Hải Phòng (R) - UBND Huyện Cát Hải (I)',
                'bien_phap_khac_phuc' => 'Kích hoạt ngay đường truyền vô tuyến dự phòng (4G/5G APN dùng riêng cơ quan nhà nước); VNPT hàn nối cáp quang ngầm hoàn thành trong 2 giờ 45 phút.',
                'trang_thai'          => 'DA_KHAC_PHUC',
            ],
            [
                'loai_su_co'          => 'DDOS',
                'muc_do'              => 'NGHIEM_TRONG',
                'tieu_de'             => 'Tấn công từ chối dịch vụ phân tán (DDoS UDP Flood) vào Cổng DVC thành phố',
                'mo_ta'               => 'Lượng truy cập tăng đột biến 120,000 req/s từ botnet quốc tế nhắm vào IP Gateway Cổng Dịch vụ công, gây nghẽn băng thông cổng biên.',
                'thoi_gian_phat_hien' => '2026-10-04 14:10:00',
                'thoi_gian_khac_phuc' => '2026-10-04 14:45:00',
                'nguoi_xu_ly_id'      => $adminId,
                'raci_trach_nhiem'    => 'Sở TT&TT (A/R) - VNCERT/Cục ATTT (C) - VNPT ISP (R)',
                'bien_phap_khac_phuc' => 'Kích hoạt định tuyến qua trạm lọc rửa lưu lượng (Anti-DDoS Scrubbing Center) của VNPT; bật chế độ Rate Limit Layer 7 và Geo-IP blocking.',
                'trang_thai'          => 'DA_KHAC_PHUC',
            ],
            [
                'loai_su_co'          => 'RANSOMWARE',
                'muc_do'              => 'CAO',
                'tieu_de'             => 'Phát hiện mã độc tống tiền Ransomware trên 01 máy trạm tại Sở Tài nguyên Môi trường',
                'mo_ta'               => 'Hệ thống EDR giám sát an toàn thông tin phát hiện hành vi mã hóa file bất thường xuất phát từ máy trạm cán bộ nhận tệp đính kèm qua email giả mạo.',
                'thoi_gian_phat_hien' => '2026-10-05 09:15:00',
                'thoi_gian_khac_phuc' => '2026-10-05 10:30:00',
                'nguoi_xu_ly_id'      => $adminId,
                'raci_trach_nhiem'    => 'Sở TT&TT (A/R) - Đội ứng cứu sự cố ATTT Hải Phòng (R) - Sở TN&MT (I)',
                'bien_phap_khac_phuc' => 'Cô lập máy trạm khỏi mạng LAN nội bộ; quét và bóc tách mã độc; khôi phục máy trạm từ image chuẩn; dữ liệu hệ thống Một cửa trên máy chủ an toàn tuyệt đối nhờ cơ chế WORM.',
                'trang_thai'          => 'DA_KHAC_PHUC',
            ],
            [
                'loai_su_co'          => 'DB_FAILOVER',
                'muc_do'              => 'TRUNG_BINH',
                'tieu_de'             => 'Treo lock bảng nhat_ky_kiem_toan giờ cao điểm tiếp nhận hồ sơ đầu tuần',
                'mo_ta'               => 'Giao dịch kiểm toán ghi nhật ký đồng thời nhiều phiên làm việc gây hiện tượng khóa bảng (table lock) tạm thời trong 3 phút.',
                'thoi_gian_phat_hien' => '2026-10-06 09:00:00',
                'thoi_gian_khac_phuc' => '2026-10-06 09:12:00',
                'nguoi_xu_ly_id'      => $adminId,
                'raci_trach_nhiem'    => 'Sở TT&TT / SysAdmin (A/R) - Trung tâm CNTT&TT (R)',
                'bien_phap_khac_phuc' => 'Tối ưu hóa chỉ mục index, phân tách kết nối Read/Write Replica qua Database master-slave, giải phóng lock thành công.',
                'trang_thai'          => 'DA_KHAC_PHUC',
            ],
        ];

        $stmt = $db->prepare("INSERT INTO su_co 
            (loai_su_co, muc_do, tieu_de, mo_ta, thoi_gian_phat_hien, thoi_gian_khac_phuc, nguoi_xu_ly_id, raci_trach_nhiem, bien_phap_khac_phuc, trang_thai)
            VALUES (:loai_su_co, :muc_do, :tieu_de, :mo_ta, :thoi_gian_phat_hien, :thoi_gian_khac_phuc, :nguoi_xu_ly_id, :raci_trach_nhiem, :bien_phap_khac_phuc, :trang_thai)");

        foreach ($samples as $s) {
            $stmt->execute($s);
        }
    }

    /**
     * Khởi tạo các biên bản diễn tập mẫu nếu bảng dien_tap đang trống
     */
    public static function ensureSampleDrills(): void
    {
        $db = Database::connection();
        $count = (int)$db->query("SELECT COUNT(*) FROM dien_tap")->fetchColumn();
        if ($count > 0) {
            return;
        }

        $adminId = (int)$db->query("SELECT nd.id FROM nguoi_dung nd 
                                    JOIN nguoi_dung_vai_tro ndvt ON nd.id = ndvt.nguoi_dung_id 
                                    JOIN vai_tro v ON ndvt.vai_tro_id = v.id 
                                    WHERE v.ma_vai_tro = 'SYSADMIN' LIMIT 1")->fetchColumn();
        if (!$adminId) {
            $adminId = 1;
        }


        $samples = [
            [
                'loai_dien_tap'      => 'AN_TOAN_THONG_TIN',
                'tieu_de'            => 'Diễn tập ATTT định kỳ Kỳ 2/2026: Ứng cứu sự cố khai thác lỗ hổng Web API & rò rỉ dữ liệu',
                'kich_ban'           => 'Giả lập tình huống tin tặc khai thác lỗ hổng IDOR trên API tra cứu hồ sơ để đánh cắp thông tin CCCD công dân. Đội ứng cứu kích hoạt quy trình điều tra vết WORM Audit Trail, vá lỗ hổng và thông báo cơ quan chức năng.',
                'thoi_gian_dien_tap' => '2026-08-15 09:00:00',
                'rto_thuc_te_phut'   => 45,
                'ket_qua'            => 'DAT',
                'bien_ban_danh_gia'  => 'Đội ngũ kỹ thuật phát hiện và cô lập nguy cơ trong 15 phút. Chuỗi băm WORM không bị xâm nhập. Đạt yêu cầu theo Thông tư 12/2022/TT-BTTTT.',
                'nguoi_chu_tri_id'   => $adminId,
            ],
            [
                'loai_dien_tap'      => 'CHUYEN_DOI_THAM_HOA_DRP',
                'tieu_de'            => 'Diễn tập Phục hồi thảm họa DRP định kỳ năm 2026: Chuyển đổi toàn diện sang Trung tâm DR Site',
                'kich_ban'           => 'Mô phỏng sự cố cháy nổ gây mất điện toàn phần tại Trung tâm Dữ liệu chính (DC Hải Phòng). Kích hoạt kịch bản DRP: khôi phục CSDL từ bản Full + Differential + Binlog tại Trung tâm dự phòng (DR Site). Đo thời gian RTO thực tế so với cam kết <= 4 giờ.',
                'thoi_gian_dien_tap' => '2026-04-20 01:00:00',
                'rto_thuc_te_phut'   => 115,
                'ket_qua'            => 'DAT',
                'bien_ban_danh_gia'  => 'Toàn bộ dữ liệu 100% nguyên vẹn (đối soát khớp chuỗi SHA-256). Thời gian khôi phục hoàn chỉnh dịch vụ là 115 phút, đạt cam kết RTO <= 4 giờ (240 phút) và RPO <= 2 giờ.',
                'nguoi_chu_tri_id'   => $adminId,
            ],
        ];

        $stmt = $db->prepare("INSERT INTO dien_tap 
            (loai_dien_tap, tieu_de, kich_ban, thoi_gian_dien_tap, rto_thuc_te_phut, ket_qua, bien_ban_danh_gia, nguoi_chu_tri_id)
            VALUES (:loai_dien_tap, :tieu_de, :kich_ban, :thoi_gian_dien_tap, :rto_thuc_te_phut, :ket_qua, :bien_ban_danh_gia, :nguoi_chu_tri_id)");

        foreach ($samples as $s) {
            $stmt->execute($s);
        }
    }
}
