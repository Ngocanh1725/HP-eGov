<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Enums\TrangThaiHoSo;
use PDO;

/**
 * Class PublicApiController
 * API công khai không yêu cầu token hoặc chỉ yêu cầu token phạm vi công khai
 */
class PublicApiController extends ApiController
{
    /**
     * Tra cứu tiến độ hồ sơ theo mã (Công khai tối thiểu §1.1)
     *
     * @param Request $request
     * @param string $maHoSo
     * @return Response
     */
    public function traCuu(Request $request, string $maHoSo): Response
    {
        $maHoSo = trim($maHoSo);
        if ($maHoSo === '') {
            return $this->error('Mã hồ sơ không được để trống', 400);
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            SELECT 
                hs.ma_ho_so,
                tthc.ten_tthc,
                tthc.ma_tthc,
                dv.ten_don_vi AS co_quan_tiep_nhan,
                hs.trang_thai,
                hs.sla_mau,
                hs.ngay_tiep_nhan,
                hs.ngay_hen_tra
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

        // Lấy tên trạng thái tiếng Việt thân thiện từ Enum
        $trangThaiEnum = TrangThaiHoSo::tryFrom((string)$hoSo['trang_thai']);
        $tenTrangThai = $trangThaiEnum ? $trangThaiEnum->label() : $hoSo['trang_thai'];

        // Dữ liệu công khai tối thiểu - Tuyệt đối không để lộ CCCD, tên công dân, file cá nhân
        $data = [
            'ma_ho_so'            => $hoSo['ma_ho_so'],
            'ten_thu_tuc'         => $hoSo['ten_tthc'],
            'ma_thu_tuc'          => $hoSo['ma_tthc'],
            'co_quan_tiep_nhan'   => $hoSo['co_quan_tiep_nhan'],
            'trang_thai'          => $hoSo['trang_thai'],
            'ten_trang_thai'      => $tenTrangThai,
            'sla_mau'             => $hoSo['sla_mau'],
            'ngay_tiep_nhan'      => $hoSo['ngay_tiep_nhan'],
            'ngay_hen_tra'        => $hoSo['ngay_hen_tra'],
        ];

        return $this->success($data, 'Tra cứu thông tin hồ sơ thành công');
    }

    /**
     * Danh mục Thủ tục hành chính công khai
     *
     * @param Request $request
     * @return Response
     */
    public function danhMucTthc(Request $request): Response
    {
        $q = trim((string)$request->query('q', ''));
        $mucDo = trim((string)$request->query('muc_do', ''));
        $page = max(1, (int)$request->query('page', 1));
        $limit = min(50, max(1, (int)$request->query('limit', 15)));
        $offset = ($page - 1) * $limit;

        $pdo = Database::pdo();
        $conditions = ["tthc.trang_thai = 1"];
        $params = [];

        if ($q !== '') {
            $conditions[] = "(tthc.ten_tthc LIKE :q OR tthc.ma_tthc LIKE :q)";
            $params['q'] = "%{$q}%";
        }
        if ($mucDo !== '') {
            $conditions[] = "tthc.muc_do_dvc = :muc_do";
            $params['muc_do'] = $mucDo;
        }

        $whereSql = implode(' AND ', $conditions);

        // Đếm tổng số
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM thu_tuc_hanh_chinh tthc WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Lấy danh sách
        $sql = "
            SELECT 
                tthc.id,
                tthc.ma_tthc,
                tthc.ten_tthc,
                tthc.muc_do_dvc AS muc_do,
                tthc.thoi_han_giai_quyet_gio,
                tthc.phi_le_phi,
                tthc.cap_thuc_hien,
                lv.ten_linh_vuc
            FROM thu_tuc_hanh_chinh tthc
            LEFT JOIN dm_linh_vuc lv ON tthc.linh_vuc_id = lv.id
            WHERE {$whereSql}
            ORDER BY tthc.id ASC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = (int)ceil($total / $limit);

        return $this->success([
            'items'       => $items,
            'pagination'  => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total_items'  => $total,
                'total_pages'  => $totalPages,
            ],
        ], 'Lấy danh mục thủ tục hành chính thành công');
    }
}
