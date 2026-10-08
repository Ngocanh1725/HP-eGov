<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\EtlService;
use PDO;
use Throwable;

/**
 * Class EtlController
 * Phân hệ Chuyển đổi dữ liệu (ETL) 4 bước từ hệ thống Một cửa cũ 5 năm (§1.1, §1.3.A)
 * Vai trò: SYSADMIN
 */
class EtlController extends Controller
{
    private EtlService $etlService;

    public function __construct()
    {
        $this->etlService = new EtlService();
    }

    /**
     * Màn hình chính Quản trị & Điều hành ETL
     */
    public function index(Request $request): Response
    {
        $pdo = Database::pdo();

        // 1. Lấy danh sách các lô đã chạy
        $stmtLo = $pdo->query("SELECT * FROM etl_lo ORDER BY id DESC LIMIT 20");
        $danhSachLo = $stmtLo->fetchAll(PDO::FETCH_ASSOC);

        // 2. Thống kê tổng quan
        $statStmt = $pdo->query("
            SELECT 
                COUNT(*) as tong_so_lo,
                COALESCE(SUM(tong_so_ban_ghi), 0) as tong_ban_ghi,
                COALESCE(SUM(so_thanh_cong), 0) as tong_thanh_cong,
                COALESCE(SUM(so_loi), 0) as tong_loi
            FROM etl_lo
        ");
        $thongKe = $statStmt->fetch(PDO::FETCH_ASSOC);

        // 3. Lấy bảng ánh xạ TTHC
        $stmtMapTthc = $pdo->query("
            SELECT map.*, tthc.ten_tthc
            FROM etl_anh_xa_tthc map
            LEFT JOIN thu_tuc_hanh_chinh tthc ON map.thu_tuc_id = tthc.id
            ORDER BY map.id ASC
        ");
        $anhXaTthc = $stmtMapTthc->fetchAll(PDO::FETCH_ASSOC);

        // 4. Lấy bảng ánh xạ ĐVHC
        $stmtMapDvhc = $pdo->query("
            SELECT map.*, dv.ten_don_vi
            FROM etl_anh_xa_dvhc map
            LEFT JOIN don_vi dv ON map.don_vi_id = dv.id
            ORDER BY map.id ASC
        ");
        $anhXaDvhc = $stmtMapDvhc->fetchAll(PDO::FETCH_ASSOC);

        // 5. Kiểm tra tệp mẫu có sẵn
        $sampleDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'etl_samples';
        $sampleMssqlExists = file_exists($sampleDir . DIRECTORY_SEPARATOR . 'sample_mssql_cu.csv');
        $sampleMysqlExists = file_exists($sampleDir . DIRECTORY_SEPARATOR . 'sample_mysql_cu.csv');

        return $this->view('admin/he_thong/etl', [
            'title'               => 'Chuyển đổi dữ liệu lịch sử ETL 4 bước (§1.3.A)',
            'danhSachLo'          => $danhSachLo,
            'thongKe'             => $thongKe,
            'anhXaTthc'           => $anhXaTthc,
            'anhXaDvhc'           => $anhXaDvhc,
            'sampleMssqlExists'   => $sampleMssqlExists,
            'sampleMysqlExists'   => $sampleMysqlExists,
        ]);
    }

    /**
     * BƯỚC 1: EXTRACT - Đọc file CSV nguồn
     */
    public function extract(Request $request): Response
    {
        $loaiNguon = trim((string)$request->input('loai_nguon', 'SAMPLE_MSSQL'));
        $tenLo = trim((string)$request->input('ten_lo', ''));

        $sampleDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'etl_samples';

        $csvPath = '';
        $heThongNguon = 'MSSQL_CU';

        if ($loaiNguon === 'SAMPLE_MSSQL') {
            $csvPath = $sampleDir . DIRECTORY_SEPARATOR . 'sample_mssql_cu.csv';
            $heThongNguon = 'MSSQL_CU';
            if ($tenLo === '') {
                $tenLo = 'Lô mẫu MS SQL Server cũ (' . date('d/m/Y H:i') . ')';
            }
        } elseif ($loaiNguon === 'SAMPLE_MYSQL') {
            $csvPath = $sampleDir . DIRECTORY_SEPARATOR . 'sample_mysql_cu.csv';
            $heThongNguon = 'MYSQL_CU';
            if ($tenLo === '') {
                $tenLo = 'Lô mẫu MySQL Một cửa cũ (' . date('d/m/Y H:i') . ')';
            }
        } elseif ($loaiNguon === 'UPLOAD') {
            if (!$request->hasFile('file_csv')) {
                return Response::json(['success' => false, 'message' => 'Vui lòng chọn tệp CSV tải lên'], 400);
            }
            $file = $request->file('file_csv');
            $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'etl_uploads';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $csvPath = $uploadDir . DIRECTORY_SEPARATOR . 'etl_' . uniqid() . '.csv';
            move_uploaded_file($file['tmp_name'], $csvPath);
            $heThongNguon = 'CSV_UPLOAD';
            if ($tenLo === '') {
                $tenLo = 'Tải lên: ' . $file['name'] . ' (' . date('d/m/Y H:i') . ')';
            }
        }

        try {
            $result = $this->etlService->extractCsv($csvPath, $tenLo, $heThongNguon);
            return Response::json([
                'success' => true,
                'data'    => $result,
                'message' => "Trích xuất thành công {$result['tong_so']} bản ghi vào Staging (Lô #{$result['lo_id']})",
            ]);
        } catch (Throwable $e) {
            return Response::json([
                'success' => false,
                'message' => 'Lỗi trích xuất CSV: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * BƯỚC 2: STAGING & PROFILING - Đánh giá chất lượng dữ liệu
     */
    public function profile(Request $request): Response
    {
        $loId = (int)$request->input('lo_id', 0);
        if ($loId <= 0) {
            return Response::json(['success' => false, 'message' => 'ID Lô không hợp lệ'], 400);
        }

        try {
            $result = $this->etlService->profileStaging($loId);
            return Response::json([
                'success' => true,
                'data'    => $result,
                'message' => "Đánh giá chất lượng dữ liệu hoàn tất (Hợp lệ: {$result['so_hop_le']}, Lỗi: {$result['so_loi']})",
            ]);
        } catch (Throwable $e) {
            return Response::json([
                'success' => false,
                'message' => 'Lỗi Profiling: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * BƯỚC 3: TRANSFORM - Ánh xạ danh mục và chuẩn hóa dữ liệu
     */
    public function transform(Request $request): Response
    {
        $loId = (int)$request->input('lo_id', 0);
        if ($loId <= 0) {
            return Response::json(['success' => false, 'message' => 'ID Lô không hợp lệ'], 400);
        }

        try {
            $result = $this->etlService->transformData($loId);
            return Response::json([
                'success' => true,
                'data'    => $result,
                'message' => "Chuẩn hóa hoàn tất {$result['so_chuyen_doi']} bản ghi. Gắn nhãn {$result['so_tap_tin_can_pdf_a']} tệp cần chuyển đổi PDF/A-1b.",
            ]);
        } catch (Throwable $e) {
            return Response::json([
                'success' => false,
                'message' => 'Lỗi Transform: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * BƯỚC 4: LOAD & VERIFY - Nạp dữ liệu vào bảng chính & đối soát SHA-256
     */
    public function load(Request $request): Response
    {
        $loId = (int)$request->input('lo_id', 0);
        $chunkSize = max(5, min(100, (int)$request->input('chunk_size', 50)));

        if ($loId <= 0) {
            return Response::json(['success' => false, 'message' => 'ID Lô không hợp lệ'], 400);
        }

        $sampleDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'etl_samples';
        $attachmentsDir = $sampleDir . DIRECTORY_SEPARATOR . 'attachments';

        try {
            $result = $this->etlService->loadAndVerify($loId, $attachmentsDir, $chunkSize);
            return Response::json([
                'success' => true,
                'data'    => $result,
                'message' => "Nạp hoàn tất: {$result['so_thanh_cong']} hồ sơ thành công, {$result['so_that_bai']} hồ sơ lỗi/thất bại.",
            ]);
        } catch (Throwable $e) {
            return Response::json([
                'success' => false,
                'message' => 'Lỗi Load & Verify: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Xuất danh sách lỗi ra file CSV
     */
    public function exportErrors(Request $request, string $loId): Response
    {
        $id = (int)$loId;
        $errors = $this->etlService->getErrorsReport($id);

        $filename = "etl_loi_lo_{$id}_" . date('Ymd_His') . ".csv";

        $output = "\xEF\xBB\xBF"; // UTF-8 BOM
        $output .= "ID_Loi,Ma_Ho_So_Cu,Nguoi_Nop,So_CMND_CCCD,Loai_Loi,Mo_Ta_Loi,Du_Lieu_Loi,Thoi_Gian\n";

        foreach ($errors as $e) {
            $row = [
                $e['id'],
                '"' . str_replace('"', '""', (string)$e['ma_ho_so_cu']) . '"',
                '"' . str_replace('"', '""', (string)$e['nguoi_nop']) . '"',
                '"' . str_replace('"', '""', (string)$e['so_cmnd_cccd']) . '"',
                $e['loai_loi'],
                '"' . str_replace('"', '""', (string)$e['mo_ta_loi']) . '"',
                '"' . str_replace('"', '""', (string)($e['du_lieu_loi'] ?? '')) . '"',
                $e['created_at'],
            ];
            $output .= implode(',', $row) . "\n";
        }

        return Response::downloadContent($output, $filename, 'text/csv; charset=UTF-8');
    }

    /**
     * Cập nhật / Thêm mới ánh xạ TTHC
     */
    public function saveMappingTthc(Request $request): Response
    {
        $maCu = trim((string)$request->input('ma_tthc_cu', ''));
        $tenCu = trim((string)$request->input('ten_tthc_cu', ''));
        $maMoi = trim((string)$request->input('ma_tthc_moi', ''));
        $thuTucId = (int)$request->input('thu_tuc_id', 0) ?: null;

        if ($maCu === '' || $maMoi === '') {
            return Response::json(['success' => false, 'message' => 'Mã TTHC cũ và mới không được để trống'], 400);
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            INSERT INTO etl_anh_xa_tthc (ma_tthc_cu, ten_tthc_cu, ma_tthc_moi, thu_tuc_id, ghi_chu)
            VALUES (:cu, :ten, :moi, :tid, 'Cấu hình quản trị')
            ON DUPLICATE KEY UPDATE
                ten_tthc_cu = VALUES(ten_tthc_cu),
                ma_tthc_moi = VALUES(ma_tthc_moi),
                thu_tuc_id = VALUES(thu_tuc_id)
        ");
        $stmt->execute([
            'cu'  => $maCu,
            'ten' => $tenCu ?: $maCu,
            'moi' => $maMoi,
            'tid' => $thuTucId,
        ]);

        return Response::json(['success' => true, 'message' => 'Lưu ánh xạ TTHC thành công']);
    }

    /**
     * Cập nhật / Thêm mới ánh xạ ĐVHC
     */
    public function saveMappingDvhc(Request $request): Response
    {
        $maCu = trim((string)$request->input('ma_dvhc_cu', ''));
        $tenCu = trim((string)$request->input('ten_dvhc_cu', ''));
        $maTctk = trim((string)$request->input('ma_tctk', ''));
        $donViId = (int)$request->input('don_vi_id', 0) ?: null;

        if ($maCu === '' || $maTctk === '') {
            return Response::json(['success' => false, 'message' => 'Mã ĐVHC cũ và mã TCTK không được để trống'], 400);
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            INSERT INTO etl_anh_xa_dvhc (ma_dvhc_cu, ten_dvhc_cu, ma_tctk, don_vi_id, ghi_chu)
            VALUES (:cu, :ten, :tctk, :dvid, 'Cấu hình quản trị')
            ON DUPLICATE KEY UPDATE
                ten_dvhc_cu = VALUES(ten_dvhc_cu),
                ma_tctk = VALUES(ma_tctk),
                don_vi_id = VALUES(don_vi_id)
        ");
        $stmt->execute([
            'cu'   => $maCu,
            'ten'  => $tenCu ?: $maCu,
            'tctk' => $maTctk,
            'dvid' => $donViId,
        ]);

        return Response::json(['success' => true, 'message' => 'Lưu ánh xạ ĐVHC thành công']);
    }
}
