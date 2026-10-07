<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use PDO;

/**
 * TraCuuController – Phân hệ Tra cứu Tiến độ Hồ sơ công khai & Quét mã QR
 * Không yêu cầu đăng nhập, bảo vệ thông tin cá nhân và kiểm soát tần suất truy vấn
 */
class TraCuuController extends Controller
{
    /**
     * Giao diện tra cứu công khai (GET /tra-cuu)
     */
    public function index(Request $request): Response
    {
        $maHoSo = trim((string)$request->query('ma_ho_so', ''));
        $hoSo = null;

        if (!empty($maHoSo)) {
            $db = Database::connection();
            $stmt = $db->prepare("SELECT hs.ma_ho_so, hs.ngay_nop, hs.ngay_hen_tra, hs.trang_thai, hs.sla_mau,
                                         tt.ten_tthc, dv.ten_don_vi
                                  FROM ho_so hs 
                                  LEFT JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                                  LEFT JOIN don_vi dv ON hs.don_vi_tiep_nhan_id = dv.id 
                                  WHERE hs.ma_ho_so = :ma AND hs.is_deleted = 0 LIMIT 1");
            $stmt->execute([':ma' => $maHoSo]);
            $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$hoSo) {
                Session::flash('error', "Không tìm thấy hồ sơ có mã [{$maHoSo}]. Vui lòng kiểm tra lại tính chính xác.");
            }
        }

        return $this->view('portal/tra_cuu/index', [
            'pageTitle' => 'Tra cứu Tiến độ Hồ sơ | Cổng DVC Hải Phòng',
            'maHoSo' => $maHoSo,
            'hoSo' => $hoSo,
        ]);
    }
}
