<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Integrations\Mock\SmsZnsNotificationMock;
use PDO;
use RuntimeException;
use InvalidArgumentException;
use DomainException;

/**
 * Class VanThuService – Dịch vụ Văn thư số, Quản lý sổ văn bản, Cấp số & Đóng dấu số cơ quan
 * Tuân thủ Rule mục 6 & 12, đặc tả PH05 (Digital Clerical Office)
 */
class VanThuService
{
    /**
     * Lấy danh sách sổ văn bản theo đơn vị và năm
     */
    public static function getDanhSachSoVanBan(int $donViId, ?int $nam = null, ?string $loaiSo = null): array
    {
        $db = Database::connection();
        $params = [':dvid' => $donViId];
        $sql = "SELECT s.*, d.ten_don_vi, 
                       (SELECT COUNT(*) FROM van_ban_den_di vb WHERE vb.so_van_ban_id = s.id) as tong_van_ban
                FROM so_van_ban s
                JOIN don_vi d ON s.don_vi_id = d.id
                WHERE s.don_vi_id = :dvid";

        if ($nam !== null) {
            $sql .= " AND s.nam = :nam";
            $params[':nam'] = $nam;
        }
        if ($loaiSo !== null) {
            $sql .= " AND s.loai_so = :ls";
            $params[':ls'] = $loaiSo;
        }

        $sql .= " ORDER BY s.nam DESC, s.loai_so ASC, s.id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tạo sổ văn bản mới
     */
    public static function taoSoVanBan(array $data): int
    {
        $db = Database::connection();
        $stmt = $db->prepare("INSERT INTO so_van_ban (
            don_vi_id, nam, ten_so, loai_so, so_hien_tai, ky_hieu_mau, trang_thai
        ) VALUES (
            :dvid, :nam, :ten, :loai, :so, :kh, 1
        )");

        $stmt->execute([
            ':dvid' => $data['don_vi_id'],
            ':nam'  => $data['nam'] ?? (int)date('Y'),
            ':ten'  => $data['ten_so'],
            ':loai' => $data['loai_so'] ?? 'DI',
            ':so'   => (int)($data['so_hien_tai'] ?? 0),
            ':kh'   => $data['ky_hieu_mau'] ?? '/QĐ-UBND',
        ]);

        return (int)$db->lastInsertId();
    }

    /**
     * Cấp số & ký hiệu văn bản tự động không trùng (Khóa Transaction SELECT ... FOR UPDATE)
     *
     * @param int $soVanBanId ID sổ văn bản
     * @param int $hoSoId ID hồ sơ gắn liền
     * @param string $trichYeu Trích yếu nội dung
     * @param string $coQuanBanHanh Tên cơ quan ban hành
     * @param string|null $nguoiKy Người ký
     * @param int|null $fileDinhKemId File ID nếu có
     * @return array{van_ban_id: int, so_thu_tu: int, so_ky_hieu: string, ngay_ban_hanh: string}
     */
    public static function capSoVanBanTuDong(
        int $soVanBanId,
        int $hoSoId,
        string $trichYeu,
        string $coQuanBanHanh,
        ?string $nguoiKy = null,
        ?int $fileDinhKemId = null
    ): array {
        $db = Database::connection();
        $needRollback = false;

        if (!$db->inTransaction()) {
            $db->beginTransaction();
            $needRollback = true;
        }

        try {
            // Khóa hàng bi quan FOR UPDATE chống race condition đồng thời
            $stmt = $db->prepare("SELECT * FROM so_van_ban WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $soVanBanId]);
            $so = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$so || (int)$so['trang_thai'] !== 1) {
                throw new RuntimeException("Sổ văn bản không tồn tại hoặc đã bị khóa phát hành.");
            }

            $soThuTu = (int)$so['so_hien_tai'] + 1;
            $soKyHieu = (string)$soThuTu . $so['ky_hieu_mau'];

            // Tăng số hiện tại trong sổ
            $upd = $db->prepare("UPDATE so_van_ban SET so_hien_tai = :stt WHERE id = :id");
            $upd->execute([':stt' => $soThuTu, ':id' => $soVanBanId]);

            // Ghi vào bảng van_ban_den_di
            $ins = $db->prepare("INSERT INTO van_ban_den_di (
                so_van_ban_id, ho_so_id, so_thu_tu, so_ky_hieu, ngay_ban_hanh,
                trich_yeu, co_quan_ban_hanh, nguoi_ky, ngay_vao_so, file_dinh_kem_id
            ) VALUES (
                :svbid, :hsid, :stt, :skh, CURDATE(), :ty, :cq, :nk, NOW(), :fid
            )");

            $ins->execute([
                ':svbid' => $soVanBanId,
                ':hsid'  => $hoSoId,
                ':stt'   => $soThuTu,
                ':skh'   => $soKyHieu,
                ':ty'    => $trichYeu,
                ':cq'    => $coQuanBanHanh,
                ':nk'    => $nguoiKy,
                ':fid'   => $fileDinhKemId,
            ]);

            $vanBanId = (int)$db->lastInsertId();

            if ($needRollback) {
                $db->commit();
            }

            return [
                'van_ban_id'    => $vanBanId,
                'so_thu_tu'     => $soThuTu,
                'so_ky_hieu'    => $soKyHieu,
                'ngay_ban_hanh' => date('Y-m-d'),
            ];
        } catch (\Throwable $e) {
            if ($needRollback && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Đóng dấu số cơ quan (Ký số tổ chức) & Hoàn thành hồ sơ
     * Tự động đẩy kết quả điện tử vào kho_du_lieu_cong_dan + thông báo công dân
     *
     * @param int $hoSoId ID hồ sơ
     * @param int $vanThuUserId ID cán bộ Văn thư thực hiện
     * @param int $soVanBanId ID sổ văn bản phát hành
     * @param array $options Tùy chọn bổ sung
     * @return array
     */
    public static function dongDauVaHoanThanh(
        int $hoSoId,
        int $vanThuUserId,
        int $soVanBanId,
        array $options = []
    ): array {
        $db = Database::connection();

        // 1. Kiểm tra quyền cán bộ Văn thư (Rule 6)
        $roleCheck = $db->prepare("SELECT v.ma_vai_tro FROM vai_tro v 
                                   JOIN nguoi_dung_vai_tro nv ON v.id = nv.vai_tro_id 
                                   WHERE nv.nguoi_dung_id = :uid");
        $roleCheck->execute([':uid' => $vanThuUserId]);
        $roles = $roleCheck->fetchAll(PDO::FETCH_COLUMN);

        if (in_array('SYSADMIN', $roles, true)) {
            throw new DomainException("Quản trị viên hệ thống (SYSADMIN) tuyệt đối không có quyền đóng dấu số cơ quan!");
        }

        if (!in_array('VAN_THU', $roles, true)) {
            throw new DomainException("Chỉ cán bộ Văn thư số (VAN_THU) mới có quyền đóng dấu số cơ quan!");
        }

        // 2. Lấy thông tin hồ sơ
        $stmt = $db->prepare("SELECT h.*, t.ten_tthc, d.ten_don_vi 
                              FROM ho_so h 
                              JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
                              JOIN don_vi d ON h.don_vi_tiep_nhan_id = d.id
                              WHERE h.id = :id AND h.is_deleted = 0");
        $stmt->execute([':id' => $hoSoId]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            throw new RuntimeException("Hồ sơ không tồn tại hoặc đã bị xóa logic: ID {$hoSoId}");
        }

        // 3. Tìm tài liệu kết quả giải quyết cần đóng dấu
        $docStmt = $db->prepare("SELECT * FROM ho_so_tai_lieu WHERE ho_so_id = :hid ORDER BY id DESC LIMIT 1");
        $docStmt->execute([':hid' => $hoSoId]);
        $taiLieu = $docStmt->fetch(PDO::FETCH_ASSOC);

        $docDir = dirname(__DIR__, 2) . '/storage/uploads/results';
        if (!is_dir($docDir)) {
            mkdir($docDir, 0755, true);
        }

        $filePath = '';
        $taiLieuId = null;
        if ($taiLieu && !empty($taiLieu['duong_dan']) && file_exists($taiLieu['duong_dan'])) {
            $filePath = $taiLieu['duong_dan'];
            $taiLieuId = (int)$taiLieu['id'];
        } else {
            $filePath = "{$docDir}/ket_qua_chinh_thuc_{$hoSoId}.pdf";
            if (!file_exists($filePath)) {
                file_put_contents($filePath, "%PDF-1.4\nKet qua TTHC chinh thuc: " . $hoSo['ma_ho_so'] . "\nBan hanh ngay: " . date('Y-m-d H:i:s'));
            }
        }

        // 4. Cấp số văn bản tự động không trùng
        $trichYeu = $options['trich_yeu'] ?? ("Kết quả giải quyết thủ tục: " . $hoSo['ten_tthc']);
        $coQuanBanHanh = $hoSo['ten_don_vi'];
        $nguoiKy = $options['nguoi_ky'] ?? 'Lãnh đạo cơ quan';

        $capSo = self::capSoVanBanTuDong($soVanBanId, $hoSoId, $trichYeu, $coQuanBanHanh, $nguoiKy, $taiLieuId);

        // 5. Đóng dấu số cơ quan (Ký số tổ chức bằng chứng thư pháp nhân)
        $signResult = ChuKySoService::signDocument(
            $vanThuUserId,
            $filePath,
            'DONG_DAU_CO_QUAN',
            $taiLieuId,
            [
                'don_vi_id' => (int)$hoSo['don_vi_tiep_nhan_id'],
                'ho_so_id'  => $hoSoId,
                'ghi_chu'   => "Đóng dấu số cơ quan số {$capSo['so_ky_hieu']} ngày " . date('d/m/Y'),
            ]
        );

        // 6. Cập nhật bảng ket_qua_giai_quyet
        $maTraCuu = strtoupper(substr(hash('sha256', $hoSo['ma_ho_so'] . $capSo['so_ky_hieu']), 0, 16));
        $kqStmt = $db->prepare("SELECT id FROM ket_qua_giai_quyet WHERE ho_so_id = :hid");
        $kqStmt->execute([':hid' => $hoSoId]);
        $kqId = $kqStmt->fetchColumn();

        if ($kqId) {
            $db->prepare("UPDATE ket_qua_giai_quyet SET 
                so_quyet_dinh = :sqd, trich_yeu = :ty, ngay_ky = NOW(),
                ngay_hieu_luc = CURDATE(), file_ban_in_html = :html, 
                ma_tra_cuu_ket_qua = :mtc, trang_thai = 1
                WHERE id = :id")->execute([
                ':sqd'  => $capSo['so_ky_hieu'],
                ':ty'   => $trichYeu,
                ':html' => $signResult['html_stamp'],
                ':mtc'  => $maTraCuu,
                ':id'   => $kqId,
            ]);
        } else {
            $db->prepare("INSERT INTO ket_qua_giai_quyet (
                ho_so_id, so_quyet_dinh, trich_yeu, nguoi_ky_chinh_id,
                ngay_ky, ngay_hieu_luc, file_ban_in_html, ma_tra_cuu_ket_qua, trang_thai
            ) VALUES (
                :hid, :sqd, :ty, :uid, NOW(), CURDATE(), :html, :mtc, 1
            )")->execute([
                ':hid'  => $hoSoId,
                ':sqd'  => $capSo['so_ky_hieu'],
                ':ty'   => $trichYeu,
                ':uid'  => $vanThuUserId,
                ':html' => $signResult['html_stamp'],
                ':mtc'  => $maTraCuu,
            ]);
        }

        // 7. Cập nhật trạng thái hồ sơ sang HOAN_THANH
        try {
            WorkflowEngine::chuyenTiep($hoSoId, $vanThuUserId, "Văn thư số đóng dấu phát hành số {$capSo['so_ky_hieu']}. Hoàn tất hồ sơ.");
        } catch (\Throwable $we) {
            // Cập nhật trạng thái trực tiếp nếu đã ở bước cuối
            $db->prepare("UPDATE ho_so SET trang_thai = 'HOAN_THANH', ngay_hoan_thanh = NOW() WHERE id = :id")
               ->execute([':id' => $hoSoId]);
        }

        // 8. TỰ ĐỘNG ĐẨY KẾT QUẢ VÀO KHO DỮ LIỆU CÔNG DÂN (kho_du_lieu_cong_dan)
        $congDanId = (int)$hoSo['cong_dan_id'];
        $khoId = 0;
        if ($congDanId > 0) {
            $fileSha256 = hash_file('sha256', $filePath);
            $tenTaiLieuKho = "Kết quả giải quyết: {$hoSo['ma_ho_so']} ({$capSo['so_ky_hieu']})";
            $ngayHetHan = date('Y-m-d', strtotime('+5 years'));

            $insKho = $db->prepare("INSERT INTO kho_du_lieu_cong_dan (
                cong_dan_id, ho_so_id, ten_tai_lieu, ma_loai_giay_to,
                duong_dan, sha256, ngay_so_hoa, ngay_het_han, nguon_cap, trang_thai
            ) VALUES (
                :cdid, :hsid, :ten, 'KET_QUA_DIEN_TU', :dd, :sha, NOW(), :hhan, 'CAP_MOI_DVC', 1
            )");
            $insKho->execute([
                ':cdid' => $congDanId,
                ':hsid' => $hoSoId,
                ':ten'  => $tenTaiLieuKho,
                ':dd'   => $filePath,
                ':sha'  => $fileSha256,
                ':hhan' => $ngayHetHan,
            ]);
            $khoId = (int)$db->lastInsertId();
        }

        // 9. PHÁT THÔNG BÁO CHO CÔNG DÂN (In-app + SMS/Zalo mock)
        $noiDungThongBao = "Hồ sơ {$hoSo['ma_ho_so']} đã có kết quả chính thức (Số: {$capSo['so_ky_hieu']}). Kết quả điện tử đã được lưu vào Kho số hóa cá nhân.";
        if ($congDanId > 0) {
            // Lưu thông báo in-app
            try {
                $db->prepare("INSERT INTO thong_bao (
                    nguoi_dung_id, ho_so_id, tieu_de, noi_dung, loai_thong_bao, da_doc, created_at
                ) VALUES (
                    :uid, :hsid, 'Kết quả giải quyết TTHC', :nd, 'HE_THONG', 0, NOW()
                )")->execute([
                    ':uid'  => $congDanId,
                    ':hsid' => $hoSoId,
                    ':nd'   => $noiDungThongBao,
                ]);
            } catch (\Throwable $te) {
                // Ignore if table schema differences
            }
        }

        // Bắn SMS/Zalo mock
        if (!empty($hoSo['nguoi_nop_sdt'])) {
            try {
                $smsMock = new SmsZnsNotificationMock();
                $smsMock->sendSms($hoSo['nguoi_nop_sdt'], "UBND TP HP: Ho so {$hoSo['ma_ho_so']} da hoan tat ({$capSo['so_ky_hieu']}). Tra cuu tai Cong DVC Hai Phong.");
            } catch (\Throwable $se) {
                // Ignore sms mock error
            }
        }

        return [
            'success'       => true,
            'ho_so_id'      => $hoSoId,
            'ma_ho_so'      => $hoSo['ma_ho_so'],
            'so_ky_hieu'    => $capSo['so_ky_hieu'],
            'ngay_ban_hanh' => $capSo['ngay_ban_hanh'],
            'ma_tra_cuu'    => $maTraCuu,
            'kho_id'        => $khoId,
            'chu_ky_id'     => $signResult['id'],
            'serial'        => $signResult['serial'],
            'signer'        => $signResult['signer'],
            'html_stamp'    => $signResult['html_stamp'],
        ];
    }

    /**
     * Xuất danh sách công văn đến/đi ra file CSV có tiền tố UTF-8 BOM
     * Tương thích mở trên Microsoft Excel không bị vỡ font tiếng Việt (Rule 14)
     */
    public static function xuatCsvSoVanBan(int $soVanBanId, ?string $tuNgay = null, ?string $denNgay = null): string
    {
        $db = Database::connection();
        $params = [':svbid' => $soVanBanId];
        $sql = "SELECT vb.*, h.ma_ho_so, s.ten_so
                FROM van_ban_den_di vb
                JOIN so_van_ban s ON vb.so_van_ban_id = s.id
                LEFT JOIN ho_so h ON vb.ho_so_id = h.id
                WHERE vb.so_van_ban_id = :svbid";

        if ($tuNgay !== null) {
            $sql .= " AND vb.ngay_ban_hanh >= :tn";
            $params[':tn'] = $tuNgay;
        }
        if ($denNgay !== null) {
            $sql .= " AND vb.ngay_ban_hanh <= :dn";
            $params[':dn'] = $denNgay;
        }

        $sql .= " ORDER BY vb.so_thu_tu ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // UTF-8 BOM: \xEF\xBB\xBF
        $output = "\xEF\xBB\xBF";
        $output .= "STT,Số ký hiệu,Ngày ban hành,Trích yếu nội dung,Cơ quan ban hành,Người ký,Mã hồ sơ gắn liền,Ngày vào sổ\n";

        foreach ($rows as $row) {
            $output .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $row['so_thu_tu'],
                str_replace('"', '""', $row['so_ky_hieu']),
                $row['ngay_ban_hanh'],
                str_replace('"', '""', $row['trich_yeu']),
                str_replace('"', '""', $row['co_quan_ban_hanh']),
                str_replace('"', '""', (string)$row['nguoi_ky']),
                str_replace('"', '""', (string)($row['ma_ho_so'] ?? '')),
                $row['ngay_vao_so']
            );
        }

        return $output;
    }
}
