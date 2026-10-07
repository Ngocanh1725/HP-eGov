<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\ChuKySoService;
use PDO;

/**
 * Class KiemTraChuKyController – Cổng dịch vụ công: Kiểm tra chữ ký số & Chứng thư số công khai
 * Phục vụ người dân, doanh nghiệp và tổ chức đối soát tính toàn vẹn của kết quả điện tử
 */
class KiemTraChuKyController extends Controller
{
    /**
     * Màn hình tra cứu và kiểm tra chữ ký số
     */
    public function index(Request $request): Response
    {
        $maTraCuu = trim((string)$request->get('ma_tra_cuu', ''));
        $result = null;

        if (!empty($maTraCuu)) {
            $db = Database::connection();
            // Tìm theo mã tra cứu kết quả hoặc mã hồ sơ hoặc số quyết định
            $stmt = $db->prepare("SELECT kq.*, h.ma_ho_so, h.nguoi_nop_ho_ten as ho_ten_nguoi_nop, t.ten_tthc, d.ten_don_vi
                                  FROM ket_qua_giai_quyet kq
                                  JOIN ho_so h ON kq.ho_so_id = h.id
                                  JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id
                                  JOIN don_vi d ON h.don_vi_tiep_nhan_id = d.id
                                  WHERE kq.ma_tra_cuu_ket_qua = :mtc 
                                     OR h.ma_ho_so = :mtc 
                                     OR kq.so_quyet_dinh = :mtc
                                  LIMIT 1");
            $stmt->execute([':mtc' => $maTraCuu]);
            $ketQua = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($ketQua) {
                // Lấy các chữ ký số gắn liền
                $sigStmt = $db->prepare("SELECT c.*, cts.ten_chu_the, cts.so_serial, cts.nha_cung_cap,
                                                cts.ngay_hieu_luc, cts.ngay_het_han, cts.loai_chu_the
                                         FROM chu_ky_so c
                                         JOIN chung_thu_so cts ON c.chung_thu_so_id = cts.id
                                         WHERE c.ho_so_tai_lieu_id IN (SELECT id FROM ho_so_tai_lieu WHERE ho_so_id = :hid)
                                            OR c.ghi_chu LIKE :like
                                         ORDER BY c.id ASC");
                $sigStmt->execute([':hid' => $ketQua['ho_so_id'], ':like' => "%{$ketQua['ho_so_id']}%"]);
                $chuKyList = $sigStmt->fetchAll(PDO::FETCH_ASSOC);

                $result = [
                    'tim_thay'   => true,
                    'ket_qua'    => $ketQua,
                    'chu_ky'     => $chuKyList,
                    'is_valid'   => !empty($chuKyList),
                ];
            } else {
                $result = [
                    'tim_thay' => false,
                    'message'  => "Không tìm thấy hồ sơ hoặc kết quả điện tử tương ứng với mã: {$maTraCuu}",
                ];
            }
        }

        return $this->view('portal/kiem_tra_chu_ky', [
            'maTraCuu' => $maTraCuu,
            'result'   => $result,
            'title'    => 'Kiểm tra Chữ ký số & Tính toàn vẹn kết quả TTHC điện tử',
        ]);
    }

    /**
     * Xử lý tải tệp tin lên để kiểm tra chữ ký số trực tiếp
     */
    public function verifyFile(Request $request): Response
    {
        if (!isset($_FILES['file_tai_lieu']) || $_FILES['file_tai_lieu']['error'] !== UPLOAD_ERR_OK) {
            return $this->view('portal/kiem_tra_chu_ky', [
                'maTraCuu' => '',
                'result'   => [
                    'tim_thay' => false,
                    'message'  => 'Vui lòng chọn tệp tin văn bản cần kiểm tra chữ ký số.',
                ],
                'title'    => 'Kiểm tra Chữ ký số văn bản điện tử',
            ]);
        }

        $tmpPath = $_FILES['file_tai_lieu']['tmp_name'];
        $fileName = $_FILES['file_tai_lieu']['name'];
        $sigData = null;

        // Nếu người dùng tải kèm file chữ ký .sig
        if (isset($_FILES['file_chu_ky']) && $_FILES['file_chu_ky']['error'] === UPLOAD_ERR_OK) {
            $sigData = file_get_contents($_FILES['file_chu_ky']['tmp_name']);
        }

        $checkResult = ChuKySoService::verifyDocumentSignature($tmpPath, $sigData);

        return $this->view('portal/kiem_tra_chu_ky', [
            'maTraCuu'    => '',
            'result'      => [
                'tim_thay'     => true,
                'file_check'   => true,
                'file_name'    => $fileName,
                'check_result' => $checkResult,
            ],
            'title'       => 'Kết quả kiểm tra chữ ký số văn bản điện tử',
        ]);
    }
}
