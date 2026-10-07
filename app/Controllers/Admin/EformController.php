<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\AuthService;
use PDO;

/**
 * EformController – Phân hệ PH09 Trình thiết kế Biểu mẫu điện tử E-Form tương tác
 * Hỗ trợ các trường dữ liệu hành chính chuẩn hóa & Auto-fill tự động từ VNeID (C06)
 */
class EformController extends Controller
{
    /**
     * Màn hình danh sách và thiết kế E-Form (GET /admin/danh-muc/eform)
     */
    public function index(Request $request): Response
    {
        $db = Database::connection();
        $tthcId = (int)$request->query('tthc_id', 1);

        // Lấy danh sách TTHC để chọn thiết kế
        $tthcList = $db->query("SELECT id, ma_rut_gon, ten_tthc FROM thu_tuc_hanh_chinh ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Lấy biểu mẫu hiện tại của TTHC được chọn
        $stmt = $db->prepare("SELECT * FROM bieu_mau_eform WHERE tthc_id = :id ORDER BY phien_ban DESC LIMIT 1");
        $stmt->execute([':id' => $tthcId]);
        $eform = $stmt->fetch(PDO::FETCH_ASSOC);

        // Nếu chưa có, tạo cấu trúc mẫu
        $defaultSchema = [
            'title' => 'Tờ khai điện tử',
            'fields' => [
                [
                    'id' => 'ho_ten',
                    'type' => 'text',
                    'label' => 'Họ và tên người nộp',
                    'required' => true,
                    'autofill_vneid' => 'ho_ten',
                    'placeholder' => 'Họ và tên theo CCCD',
                ],
                [
                    'id' => 'so_cccd',
                    'type' => 'cccd',
                    'label' => 'Số định danh cá nhân / CCCD',
                    'required' => true,
                    'autofill_vneid' => 'so_cccd',
                    'placeholder' => '12 chữ số',
                ],
                [
                    'id' => 'ngay_sinh',
                    'type' => 'date',
                    'label' => 'Ngày tháng năm sinh',
                    'required' => true,
                    'autofill_vneid' => 'ngay_sinh',
                ],
                [
                    'id' => 'gioi_tinh',
                    'type' => 'select',
                    'label' => 'Giới tính',
                    'required' => true,
                    'autofill_vneid' => 'gioi_tinh',
                    'options' => ['NAM' => 'Nam', 'NU' => 'Nữ', 'KHAC' => 'Khác'],
                ],
                [
                    'id' => 'dia_chi_thuong_tru',
                    'type' => 'dvhc_address',
                    'label' => 'Nơi đăng ký thường trú',
                    'required' => true,
                    'autofill_vneid' => 'dia_chi_thuong_tru',
                    'placeholder' => 'Xã/Phường, Quận/Huyện, Tỉnh/TP',
                ],
                [
                    'id' => 'noi_dung_yeu_cau',
                    'type' => 'textarea',
                    'label' => 'Nội dung đề nghị giải quyết',
                    'required' => true,
                    'placeholder' => 'Trình bày chi tiết lý do và yêu cầu...',
                ],
            ],
        ];

        $schema = !empty($eform['cau_truc_json']) ? json_decode($eform['cau_truc_json'], true) : $defaultSchema;

        return $this->view('admin/eform/designer', [
            'pageTitle' => 'Trình thiết kế Biểu mẫu E-Form | HP-eGov',
            'tthcList' => $tthcList,
            'currentTthcId' => $tthcId,
            'eform' => $eform,
            'schema' => $schema,
        ]);
    }

    /**
     * Lưu cấu trúc Schema JSON biểu mẫu (POST /admin/danh-muc/eform/luu)
     */
    public function save(Request $request): Response
    {
        $tthcId = (int)$request->input('tthc_id', 1);
        $tenBieuMau = trim((string)$request->input('ten_bieu_mau', 'Tờ khai điện tử E-Form'));
        $jsonStr = (string)$request->input('schema_json', '');

        // Kiểm tra tính hợp lệ của JSON
        $decoded = json_decode($jsonStr, true);
        if ($decoded === null) {
            Session::flash('error', 'Cấu trúc biểu mẫu JSON không hợp lệ.');
            return $this->redirect("/admin/danh-muc/eform?tthc_id={$tthcId}");
        }

        $db = Database::connection();

        // Lấy phiên bản cao nhất hiện có
        $verStmt = $db->prepare("SELECT MAX(phien_ban) FROM bieu_mau_eform WHERE tthc_id = :id");
        $verStmt->execute([':id' => $tthcId]);
        $currentMaxVer = (int)$verStmt->fetchColumn();
        $nextVer = $currentMaxVer + 1;

        $stmt = $db->prepare("INSERT INTO bieu_mau_eform (tthc_id, ten_bieu_mau, phien_ban, cau_truc_json, trang_thai) 
                              VALUES (:tid, :ten, :ver, :json, 1)");
        $stmt->execute([
            ':tid' => $tthcId,
            ':ten' => $tenBieuMau,
            ':ver' => $nextVer,
            ':json' => $jsonStr,
        ]);

        AuditService::log(
            AuthService::id(),
            'LUU_EFORM_SCHEMA',
            'bieu_mau_eform',
            (int)$db->lastInsertId(),
            null,
            "Lưu biểu mẫu E-Form phiên bản v{$nextVer} cho TTHC ID {$tthcId}"
        );

        Session::flash('success', "Đã lưu thành công biểu mẫu E-Form phiên bản v{$nextVer}!");
        return $this->redirect("/admin/danh-muc/eform?tthc_id={$tthcId}");
    }
}
