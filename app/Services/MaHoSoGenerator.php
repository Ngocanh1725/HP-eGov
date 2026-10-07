<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * MaHoSoGenerator – Trình sinh Mã định danh Hồ sơ TTHC chuẩn Quốc gia
 * Tuân thủ QCVN 102:2016/BTTTT & Nghị định 61/2018/NĐ-CP:
 * Cấu trúc: {MaDinhDanhDonVi}-{yyMMdd}-{MaTTHC4}-{STT6}
 * Ví dụ: 000.04.15.H21-261007-4123-000001
 * 
 * Đảm bảo CHỐNG TRÙNG LẶP ĐỒNG THỜI tuyệt đối bằng cơ chế Transaction + Row-level lock (FOR UPDATE).
 */
class MaHoSoGenerator
{
    /**
     * Sinh mã hồ sơ duy nhất toàn hệ thống
     *
     * @param int $donViId ID đơn vị tiếp nhận hồ sơ
     * @param string $maRutGonTthc Mã rút gọn TTHC 4 chữ số (VD: '4123', '2888')
     * @param string|null $maDinhDanhDonVi Mã định danh QCVN 102 của đơn vị (nếu có, không thì lấy từ DB)
     * @return string Mã hồ sơ chuẩn QCVN 102
     */
    public static function generate(int $donViId, string $maRutGonTthc, ?string $maDinhDanhDonVi = null): string
    {
        $db = Database::connection();
        $nam = (int)date('Y');
        $datePart = date('ymd'); // 2 chữ số năm + 2 chữ số tháng + 2 chữ số ngày (VD: 261007)

        // Chuẩn hóa mã 4 số TTHC
        $cleanMaTthc = preg_replace('/\D/', '', $maRutGonTthc);
        $cleanMaTthc = str_pad(substr($cleanMaTthc, 0, 4), 4, '0', STR_PAD_LEFT);

        // Lấy mã định danh cơ quan QCVN 102 nếu chưa truyền
        if (empty($maDinhDanhDonVi)) {
            $stmtDv = $db->prepare("SELECT ma_dinh_danh FROM don_vi WHERE id = :id");
            $stmtDv->execute([':id' => $donViId]);
            $maDinhDanhDonVi = (string)$stmtDv->fetchColumn();

            if (empty($maDinhDanhDonVi)) {
                $maDinhDanhDonVi = '000.00.00.H21';
            }
        }

        // Bắt đầu khóa bản ghi bộ đếm (Transaction & SELECT ... FOR UPDATE)
        $needCommit = false;
        if (!$db->inTransaction()) {
            $db->beginTransaction();
            $needCommit = true;
        }

        try {
            // 1. Kiểm tra và khóa dòng đếm
            $stmtLock = $db->prepare("SELECT so_hien_tai FROM bo_dem_ma_ho_so 
                                      WHERE don_vi_id = :dvid AND nam = :nam 
                                      FOR UPDATE");
            $stmtLock->execute([':dvid' => $donViId, ':nam' => $nam]);
            $currentVal = $stmtLock->fetchColumn();

            if ($currentVal === false) {
                // Chưa có bộ đếm cho đơn vị trong năm này -> Khởi tạo với số 1
                $soTiepTheo = 1;
                $stmtIns = $db->prepare("INSERT INTO bo_dem_ma_ho_so (don_vi_id, nam, so_hien_tai) 
                                         VALUES (:dvid, :nam, :val)");
                $stmtIns->execute([':dvid' => $donViId, ':nam' => $nam, ':val' => $soTiepTheo]);
            } else {
                // Đã có -> Tăng lên 1
                $soTiepTheo = ((int)$currentVal) + 1;
                $stmtUpd = $db->prepare("UPDATE bo_dem_ma_ho_so 
                                         SET so_hien_tai = :val 
                                         WHERE don_vi_id = :dvid AND nam = :nam");
                $stmtUpd->execute([':val' => $soTiepTheo, ':dvid' => $donViId, ':nam' => $nam]);
            }

            if ($needCommit) {
                $db->commit();
            }

            // Định dạng STT 6 chữ số: 000001 -> 999999
            $sttStr = str_pad((string)$soTiepTheo, 6, '0', STR_PAD_LEFT);

            // Ghép thành mã hoàn chỉnh: {MaDinhDanhDonVi}-{yyMMdd}-{MaTTHC4}-{STT6}
            return "{$maDinhDanhDonVi}-{$datePart}-{$cleanMaTthc}-{$sttStr}";

        } catch (\Throwable $e) {
            if ($needCommit && $db->inTransaction()) {
                $db->rollBack();
            }
            throw new RuntimeException("Lỗi cấp phát mã hồ sơ chuẩn QCVN 102: " . $e->getMessage(), 0, $e);
        }
    }
}
