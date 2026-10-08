<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Enums\TrangThaiHoSo;
use PDO;

/**
 * Class CitizenApiController
 * API dành riêng cho ứng dụng công dân (yêu cầu Bearer Token có scope 'cong_dan')
 */
class CitizenApiController extends ApiController
{
    /**
     * Lấy danh sách hồ sơ của công dân hiện tại
     */
    public function danhSachHoSo(Request $request): Response
    {
        $citizenId = $request->getAttribute('api_citizen_id');
        if (!$citizenId) {
            return $this->error('Token không liên kết với thông tin công dân', 403);
        }

        $page = max(1, (int)$request->query('page', 1));
        $limit = min(50, max(1, (int)$request->query('limit', 15)));
        $offset = ($page - 1) * $limit;

        $pdo = Database::pdo();

        // Đếm tổng số
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM ho_so WHERE cong_dan_id = :cid");
        $countStmt->execute(['cid' => $citizenId]);
        $total = (int)$countStmt->fetchColumn();

        // Lấy danh sách
        $stmt = $pdo->prepare("
            SELECT 
                hs.id,
                hs.ma_ho_so,
                tthc.ten_tthc,
                dv.ten_don_vi AS co_quan_tiep_nhan,
                hs.trang_thai,
                hs.sla_mau,
                hs.ngay_nop,
                hs.ngay_tiep_nhan,
                hs.ngay_hen_tra
            FROM ho_so hs
            JOIN thu_tuc_hanh_chinh tthc ON hs.tthc_id = tthc.id
            JOIN don_vi dv ON hs.don_vi_tiep_nhan_id = dv.id
            WHERE hs.cong_dan_id = :cid
            ORDER BY hs.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute(['cid' => $citizenId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $enum = TrangThaiHoSo::tryFrom((string)$item['trang_thai']);
            $item['ten_trang_thai'] = $enum ? $enum->label() : $item['trang_thai'];
        }

        return $this->success([
            'items'      => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total_items'  => $total,
                'total_pages'  => (int)ceil($total / $limit),
            ],
        ], 'Lấy danh sách hồ sơ của công dân thành công');
    }

    /**
     * Lấy chi tiết hồ sơ của công dân (Bảo vệ chống IDOR §3.3)
     */
    public function chiTietHoSo(Request $request, string $maHoSo): Response
    {
        $citizenId = $request->getAttribute('api_citizen_id');
        if (!$citizenId) {
            return $this->error('Token không liên kết với thông tin công dân', 403);
        }

        $maHoSo = trim($maHoSo);
        $pdo = Database::pdo();

        // 1. Tìm hồ sơ
        $stmt = $pdo->prepare("
            SELECT 
                hs.*,
                tthc.ten_tthc,
                tthc.ma_tthc,
                dv.ten_don_vi AS co_quan_tiep_nhan
            FROM ho_so hs
            JOIN thu_tuc_hanh_chinh tthc ON hs.tthc_id = tthc.id
            JOIN don_vi dv ON hs.don_vi_tiep_nhan_id = dv.id
            WHERE hs.ma_ho_so = :ma
            LIMIT 1
        ");
        $stmt->execute(['ma' => $maHoSo]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            return $this->error("Không tìm thấy hồ sơ có mã: '{$maHoSo}'", 404);
        }

        // 2. Chống IDOR: Chỉ công dân sở hữu hồ sơ mới được truy cập chi tiết
        if ((int)$hoSo['cong_dan_id'] !== (int)$citizenId) {
            return $this->error("Bạn không có quyền truy cập hồ sơ của công dân khác (IDOR Protected)", 403);
        }

        // 3. Lấy danh sách tài liệu
        $fileStmt = $pdo->prepare("
            SELECT id, ten_tai_lieu, loai_file, dung_luong_byte, sha256_hash, trang_thai_so_hoa, created_at
            FROM ho_so_tai_lieu
            WHERE ho_so_id = :hs_id
            ORDER BY id ASC
        ");
        $fileStmt->execute(['hs_id' => $hoSo['id']]);
        $files = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Lấy lịch sử luân chuyển
        $lcStmt = $pdo->prepare("
            SELECT lc.id, lc.hanh_dong, lc.y_kien, lc.thoi_gian_chuyen,
                   dv_g.ten_don_vi AS don_vi_gui,
                   dv_n.ten_don_vi AS don_vi_nhan
            FROM ho_so_luan_chuyen lc
            LEFT JOIN don_vi dv_g ON lc.don_vi_gui_id = dv_g.id
            LEFT JOIN don_vi dv_n ON lc.don_vi_nhan_id = dv_n.id
            WHERE lc.ho_so_id = :hs_id
            ORDER BY lc.id ASC
        ");
        $lcStmt->execute(['hs_id' => $hoSo['id']]);
        $luanChuyen = $lcStmt->fetchAll(PDO::FETCH_ASSOC);

        $enum = TrangThaiHoSo::tryFrom((string)$hoSo['trang_thai']);
        $hoSo['ten_trang_thai'] = $enum ? $enum->label() : $hoSo['trang_thai'];
        $hoSo['tai_lieu'] = $files;
        $hoSo['lich_su_luan_chuyen'] = $luanChuyen;

        return $this->success($hoSo, 'Lấy chi tiết hồ sơ thành công');
    }
}
