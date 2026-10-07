<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;

/**
 * Class TrainingService
 * Quản trị Đào tạo & Chuyển giao công nghệ cho 4 nhóm cán bộ (§1.4)
 */
class TrainingService
{
    /**
     * Khởi tạo 4 khóa đào tạo chuẩn nếu bảng dao_tao_khoa đang trống
     */
    public static function ensureDefaultCourses(): void
    {
        $db = Database::connection();
        $count = (int)$db->query("SELECT COUNT(*) FROM dao_tao_khoa")->fetchColumn();
        if ($count > 0) {
            return;
        }

        $defaultCourses = [
            [
                'nhom_doi_tuong'       => 'MOT_CUA',
                'ten_khoa'             => 'K01-MC: Kỹ năng số hóa & Tiếp nhận hồ sơ Một cửa điện tử liên thông',
                'noi_dung_trong_tam'   => 'Tiếp nhận, kiểm tra thành phần hồ sơ điện tử, số hóa giấy tờ đầu vào (OCR/ký số bản sao điện tử), tra cứu CSDLQG Dân cư qua LGSP, in Giấy tiếp nhận và hẹn trả kết quả (Mẫu 01).',
                'phuong_thuc_danh_gia' => 'Kiểm tra trắc nghiệm lý thuyết nghiệp vụ & thực hành xử lý 5 tình huống tiếp nhận thực tế trên phần mềm.',
                'nguong_dat'           => 'Điểm sát hạch >= 90/100',
                'ngay_bat_dau'         => '2026-10-01',
                'ngay_ket_thuc'        => '2026-10-15',
            ],
            [
                'nhom_doi_tuong'       => 'CHUYEN_VIEN',
                'ten_khoa'             => 'K02-CV: Quy trình nghiệp vụ thụ lý & Liên thông ngang dọc đa cấp',
                'noi_dung_trong_tam'   => 'Thụ lý hồ sơ điện tử, kiểm tra hồ sơ, luân chuyển hồ sơ liên thông (Xã - Quận - Sở), xin ý kiến phối hợp liên ngành, lập tờ trình thẩm định và dự thảo kết quả giải quyết TTHC.',
                'phuong_thuc_danh_gia' => 'Thực hành thao tác quy trình thụ lý trực tiếp trên hệ thống Staging.',
                'nguong_dat'           => 'Hoàn thành 10 hồ sơ mẫu đúng hạn, không lỗi quy trình',
                'ngay_bat_dau'         => '2026-10-01',
                'ngay_ket_thuc'        => '2026-10-20',
            ],
            [
                'nhom_doi_tuong'       => 'LANH_DAO',
                'ten_khoa'             => 'K03-LD: Thẩm tra, Ký số điện tử từ xa & Điều hành qua IOC',
                'noi_dung_trong_tam'   => 'Thẩm tra tờ trình, duyệt hồ sơ điện tử, ký số phê duyệt kết quả bằng SmartCA / USB Token, ký văn bản Mẫu 04 xin lỗi công dân, giám sát chỉ số KPI qua Trung tâm điều hành IOC Hải Phòng.',
                'phuong_thuc_danh_gia' => 'Thực hành ký số điện tử trên hồ sơ thật qua thiết bị di động/máy tính.',
                'nguong_dat'           => 'Ký số thành công 100% hồ sơ mẫu hợp lệ, đúng thẩm quyền',
                'ngay_bat_dau'         => '2026-10-05',
                'ngay_ket_thuc'        => '2026-10-25',
            ],
            [
                'nhom_doi_tuong'       => 'SYSADMIN',
                'ten_khoa'             => 'K04-SA: Quản trị hạ tầng, An toàn thông tin WORM & Phục hồi thảm họa DRP',
                'noi_dung_trong_tam'   => 'Quản lý tài khoản RBAC, cấu hình tham số bảo mật, sao lưu định kỳ (Full, Diff, Binlog), đối soát chuỗi băm WORM Audit Trail, giám sát độ trễ và diễn tập khôi phục CSDL khi gặp sự cố.',
                'phuong_thuc_danh_gia' => 'Diễn tập phục hồi CSDL từ bản sao lưu vào môi trường cô lập hp_egov_restore_test.',
                'nguong_dat'           => 'Phục hồi toàn vẹn CSDL với thời gian RTO < 4 giờ (240 phút)',
                'ngay_bat_dau'         => '2026-10-01',
                'ngay_ket_thuc'        => '2026-10-30',
            ],
        ];

        $stmt = $db->prepare("INSERT INTO dao_tao_khoa (nhom_doi_tuong, ten_khoa, noi_dung_trong_tam, phuong_thuc_danh_gia, nguong_dat, ngay_bat_dau, ngay_ket_thuc) 
                              VALUES (:nhom_doi_tuong, :ten_khoa, :noi_dung_trong_tam, :phuong_thuc_danh_gia, :nguong_dat, :ngay_bat_dau, :ngay_ket_thuc)");

        foreach ($defaultCourses as $c) {
            $stmt->execute($c);
        }
    }

    /**
     * Lấy danh sách toàn bộ các khóa đào tạo kèm thống kê số lượng học viên
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getCoursesWithStats(): array
    {
        self::ensureDefaultCourses();

        $db = Database::connection();
        $sql = "SELECT k.*, 
                       COUNT(h.id) AS tong_hoc_vien,
                       SUM(CASE WHEN h.ket_qua = 'DAT' THEN 1 ELSE 0 END) AS so_hoc_vien_dat,
                       SUM(CASE WHEN h.ket_qua = 'KHONG_DAT' THEN 1 ELSE 0 END) AS so_hoc_vien_khong_dat,
                       SUM(CASE WHEN h.ket_qua = 'CHUA_THI' THEN 1 ELSE 0 END) AS so_chua_thi
                FROM dao_tao_khoa k
                LEFT JOIN dao_tao_hoc_vien h ON k.id = h.khoa_id
                GROUP BY k.id
                ORDER BY k.id ASC";

        $courses = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($courses as &$course) {
            $total = (int)$course['tong_hoc_vien'];
            $passed = (int)$course['so_hoc_vien_dat'];
            $course['ty_le_dat'] = $total > 0 ? round(($passed / $total) * 100, 1) : 0.0;
        }

        return $courses;
    }

    /**
     * Lấy danh sách học viên theo khóa đào tạo
     *
     * @param int $courseId
     * @return array<int, array<string, mixed>>
     */
    public static function getStudentsByCourse(int $courseId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare("SELECT h.*, u.ho_ten, u.email, u.so_dien_thoai, u.ten_dang_nhap,
                                     (SELECT v.ma_vai_tro FROM nguoi_dung_vai_tro ndvt JOIN vai_tro v ON ndvt.vai_tro_id = v.id WHERE ndvt.nguoi_dung_id = u.id LIMIT 1) AS vai_tro,
                                     dv.ten_don_vi
                              FROM dao_tao_hoc_vien h
                              JOIN nguoi_dung u ON h.nguoi_dung_id = u.id
                              LEFT JOIN don_vi dv ON u.don_vi_chinh_id = dv.id
                              WHERE h.khoa_id = :khoa_id
                              ORDER BY h.id ASC");
        $stmt->execute(['khoa_id' => $courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Ghi danh cán bộ vào khóa đào tạo
     */
    public static function enrollStudent(int $courseId, int $userId): bool
    {
        $db = Database::connection();
        try {
            $stmt = $db->prepare("INSERT INTO dao_tao_hoc_vien (khoa_id, nguoi_dung_id, ket_qua) 
                                  VALUES (:khoa_id, :user_id, 'CHUA_THI')
                                  ON DUPLICATE KEY UPDATE id=id");
            return $stmt->execute(['khoa_id' => $courseId, 'user_id' => $userId]);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Chấm điểm và cập nhật kết quả sát hạch theo tiêu chuẩn §1.4
     *
     * @param int $id ID học viên trong bảng dao_tao_hoc_vien
     * @param float|null $diemSo
     * @param int $soHoSoThucHanh
     * @param string $nhanXet
     * @return bool
     */
    public static function gradeStudent(int $id, ?float $diemSo, int $soHoSoThucHanh, string $nhanXet): bool
    {
        $db = Database::connection();
        try {
            // Lấy thông tin nhóm đối tượng của khóa đào tạo
            $stmt = $db->prepare("SELECT k.nhom_doi_tuong 
                                  FROM dao_tao_hoc_vien h 
                                  JOIN dao_tao_khoa k ON h.khoa_id = k.id 
                                  WHERE h.id = :id");
            $stmt->execute(['id' => $id]);
            $nhom = $stmt->fetchColumn();

            if (!$nhom) {
                return false;
            }

            // Tự động xác định kết quả ĐẠT / KHÔNG ĐẠT theo tiêu chí §1.4
            $ketQua = 'KHONG_DAT';
            switch ($nhom) {
                case 'MOT_CUA':
                    // Tiêu chí: Điểm thi >= 90/100
                    if ($diemSo !== null && $diemSo >= 90.0) {
                        $ketQua = 'DAT';
                    }
                    break;

                case 'CHUYEN_VIEN':
                    // Tiêu chí: Hoàn thành 10 bộ hồ sơ mẫu không lỗi
                    if ($soHoSoThucHanh >= 10 && ($diemSo === null || $diemSo >= 80.0)) {
                        $ketQua = 'DAT';
                    }
                    break;

                case 'LANH_DAO':
                    // Tiêu chí: Ký số thành công 100% hồ sơ mẫu (điểm số >= 100 hoặc đánh giá ĐẠT)
                    if ($diemSo !== null && $diemSo >= 100.0) {
                        $ketQua = 'DAT';
                    }
                    break;

                case 'SYSADMIN':
                    // Tiêu chí: Diễn tập khôi phục hệ thống trong RTO < 4h (đạt >= 90 điểm)
                    if ($diemSo !== null && $diemSo >= 90.0) {
                        $ketQua = 'DAT';
                    }
                    break;
            }

            $upStmt = $db->prepare("UPDATE dao_tao_hoc_vien 
                                    SET diem_so = :diem, 
                                        so_ho_so_thuc_hanh = :so_hs, 
                                        ket_qua = :ket_qua, 
                                        ngay_sat_hach = :ngay, 
                                        nhan_xet = :nhan_xet 
                                    WHERE id = :id");
            return $upStmt->execute([
                'diem'     => $diemSo,
                'so_hs'    => $soHoSoThucHanh,
                'ket_qua'  => $ketQua,
                'ngay'     => date('Y-m-d'),
                'nhan_xet' => $nhanXet,
                'id'       => $id,
            ]);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Seed cán bộ mẫu vào 4 khóa đào tạo nếu chưa có học viên
     */
    public static function seedSampleStudents(): void
    {
        self::ensureDefaultCourses();
        $db = Database::connection();
        $count = (int)$db->query("SELECT COUNT(*) FROM dao_tao_hoc_vien")->fetchColumn();
        if ($count > 0) {
            return;
        }

        // Lấy danh sách cán bộ theo vai trò tương ứng
        $users = $db->query("SELECT nd.id, v.ma_vai_tro AS vai_tro 
                             FROM nguoi_dung nd 
                             JOIN nguoi_dung_vai_tro ndvt ON nd.id = ndvt.nguoi_dung_id 
                             JOIN vai_tro v ON ndvt.vai_tro_id = v.id 
                             WHERE nd.is_deleted = 0 LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);

        $courses = $db->query("SELECT id, nhom_doi_tuong FROM dao_tao_khoa")->fetchAll(PDO::FETCH_ASSOC);

        $courseMap = [];
        foreach ($courses as $c) {
            $courseMap[$c['nhom_doi_tuong']] = (int)$c['id'];
        }

        $insertStmt = $db->prepare("INSERT INTO dao_tao_hoc_vien 
            (khoa_id, nguoi_dung_id, so_ho_so_thuc_hanh, diem_so, ket_qua, ngay_sat_hach, nhan_xet) 
            VALUES (:khoa_id, :uid, :so_hs, :diem, :kq, :ngay, :cmt)");

        foreach ($users as $u) {
            $uid = (int)$u['id'];
            $role = (string)$u['vai_tro'];
            $targetCourse = null;
            $soHs = 0;
            $diem = null;
            $kq = 'CHUA_THI';
            $cmt = '';

            if (in_array($role, ['CB_MOT_CUA'], true) && isset($courseMap['MOT_CUA'])) {
                $targetCourse = $courseMap['MOT_CUA'];
                $soHs = 5;
                $diem = 95.0;
                $kq = 'DAT';
                $cmt = 'Thao tác số hóa thành thạo, điểm trắc nghiệm nghiệp vụ đạt 95/100.';
            } elseif (in_array($role, ['CHUYEN_VIEN'], true) && isset($courseMap['CHUYEN_VIEN'])) {
                $targetCourse = $courseMap['CHUYEN_VIEN'];
                $soHs = 10;
                $diem = 90.0;
                $kq = 'DAT';
                $cmt = 'Hoàn thành 10 hồ sơ mẫu liên thông không có lỗi nghiệp vụ.';
            } elseif (in_array($role, ['LD_PHONG', 'LD_CO_QUAN', 'VAN_THU'], true) && isset($courseMap['LANH_DAO'])) {
                $targetCourse = $courseMap['LANH_DAO'];
                $soHs = 8;
                $diem = 100.0;
                $kq = 'DAT';
                $cmt = 'Ký số thành công 100% hồ sơ mẫu bằng SmartCA và USB Token.';
            } elseif (in_array($role, ['SYSADMIN', 'ADMIN_HE_THONG'], true) && isset($courseMap['SYSADMIN'])) {
                $targetCourse = $courseMap['SYSADMIN'];
                $soHs = 12;
                $diem = 98.0;
                $kq = 'DAT';
                $cmt = 'Thực hiện khôi phục CSDL thành công trong RTO 15 phút (< 4h cam kết).';
            }

            if ($targetCourse !== null) {
                $insertStmt->execute([
                    'khoa_id' => $targetCourse,
                    'uid'     => $uid,
                    'so_hs'   => $soHs,
                    'diem'    => $diem,
                    'kq'      => $kq,
                    'ngay'    => '2026-10-06',
                    'cmt'     => $cmt,
                ]);
            }
        }
    }
}
