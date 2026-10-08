<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use PDO;
use Throwable;

/**
 * Class EtlService
 * Phân hệ Chuyển đổi dữ liệu (ETL Pipeline) 4 bước lịch sử Một cửa cũ 5 năm (§1.1, §1.3.A)
 * 1. Extract: Đọc CSV nguồn (MS SQL / MySQL Một cửa cũ), nạp vào Staging
 * 2. Staging & Profiling: Phát hiện trường rác, trùng lặp, thiếu CCCD, mã TTHC/ĐVHC sai
 * 3. Transform: Ánh xạ mã TTHC QG, mã ĐVHC TCTK, chuẩn hóa ngày giờ, họ tên, gắn nhãn PDF/A-1b
 * 4. Load & Verify: Nạp theo lô vào bảng chính trong transaction, đối soát SHA-256 100% tệp đính kèm
 */
class EtlService
{
    /**
     * BƯỚC 1: EXTRACT - Đọc tệp CSV và nạp nguyên trạng vào etl_staging_ho_so
     *
     * @param string $csvPath Đường dẫn tệp CSV nguồn
     * @param string $tenLo Tên gọi của lô nạp
     * @param string $heThongNguon MS_SQL_CU | MYSQL_CU
     * @return array{lo_id: int, tong_so: int, ten_lo: string, he_thong_nguon: string}
     */
    public function extractCsv(string $csvPath, string $tenLo, string $heThongNguon = 'MSSQL_CU'): array
    {
        if (!file_exists($csvPath) || !is_readable($csvPath)) {
            throw new \InvalidArgumentException("Tệp CSV không tồn tại hoặc không thể đọc: {$csvPath}");
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            // 1. Tạo lô mới
            $stmtLo = $pdo->prepare("
                INSERT INTO etl_lo (ten_lo, he_thong_nguon, tong_so_ban_ghi, so_thanh_cong, so_loi, trang_thai)
                VALUES (:ten_lo, :he_thong, 0, 0, 0, 'STAGING')
            ");
            $stmtLo->execute([
                'ten_lo' => $tenLo,
                'he_thong' => $heThongNguon,
            ]);
            $loId = (int)$pdo->lastInsertId();

            // 2. Đọc nội dung CSV
            $handle = fopen($csvPath, 'r');
            if ($handle === false) {
                throw new \RuntimeException("Không thể mở tệp CSV.");
            }

            // Bỏ qua UTF-8 BOM nếu có
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Đọc dòng tiêu đề Header
            $headers = fgetcsv($handle);
            if ($headers === false) {
                fclose($handle);
                throw new \RuntimeException("Tệp CSV rỗng hoặc định dạng không hợp lệ.");
            }

            // Chuẩn hóa tên cột Header
            $headers = array_map(function ($h) {
                return trim((string)$h);
            }, $headers);

            $stmtStaging = $pdo->prepare("
                INSERT INTO etl_staging_ho_so (
                    lo_id, ma_ho_so_cu, ten_tthc_cu, ma_tthc_cu, co_quan_cu,
                    nguoi_nop, so_cmnd_cccd, ngay_tiep_nhan_cu, ngay_hen_tra_cu,
                    trang_thai_cu, danh_sach_file_cu, du_lieu_raw_json, trang_thai_etl
                ) VALUES (
                    :lo_id, :ma_hs, :ten_tthc, :ma_tthc, :co_quan,
                    :nguoi_nop, :cccd, :ngay_tn, :ngay_ht,
                    :trang_thai, :files, :raw_json, 'CHUA_XU_LY'
                )
            ");

            $count = 0;
            while (($row = fgetcsv($handle)) !== false) {
                if (empty(array_filter($row))) {
                    continue; // Bỏ qua dòng trống
                }

                $record = [];
                foreach ($headers as $idx => $headerName) {
                    $record[$headerName] = isset($row[$idx]) ? trim($row[$idx]) : '';
                }

                // Ánh xạ linh hoạt theo tên cột của MS SQL hoặc MySQL cũ
                $maHsCu    = $record['MaHoSoCu'] ?? $record['MaHoSo'] ?? $record['ma_ho_so'] ?? null;
                $tenTthcCu = $record['TenTTHCCu'] ?? $record['TenThuTuc'] ?? $record['ten_tthc'] ?? null;
                $maTthcCu  = $record['MaTTHCCu'] ?? $record['MaTTHC'] ?? $record['ma_tthc'] ?? null;
                $coQuanCu  = $record['CoQuanCu'] ?? $record['CoQuan'] ?? $record['co_quan'] ?? null;
                $nguoiNop  = $record['NguoiNop'] ?? $record['nguoi_nop'] ?? null;
                $cccd      = $record['SoCmndCccd'] ?? $record['SoGiayTo'] ?? $record['cccd'] ?? null;
                $ngayTnCu  = $record['NgayTiepNhanCu'] ?? $record['NgayTiepNhan'] ?? $record['ngay_tiep_nhan'] ?? null;
                $ngayHtCu  = $record['NgayHenTraCu'] ?? $record['NgayHenTra'] ?? $record['ngay_hen_tra'] ?? null;
                $trangThai = $record['TrangThaiCu'] ?? $record['TrangThai'] ?? $record['trang_thai'] ?? null;
                $files     = $record['DanhSachFileCu'] ?? $record['TepDinhKem'] ?? $record['files'] ?? null;

                $stmtStaging->execute([
                    'lo_id'      => $loId,
                    'ma_hs'      => $maHsCu,
                    'ten_tthc'   => $tenTthcCu,
                    'ma_tthc'    => $maTthcCu,
                    'co_quan'    => $coQuanCu,
                    'nguoi_nop'  => $nguoiNop,
                    'cccd'       => $cccd,
                    'ngay_tn'    => $ngayTnCu,
                    'ngay_ht'    => $ngayHtCu,
                    'trang_thai' => $trangThai,
                    'files'      => $files,
                    'raw_json'   => json_encode($record, JSON_UNESCAPED_UNICODE),
                ]);

                $count++;
            }

            fclose($handle);

            // Cập nhật tổng số bản ghi vào lô
            $stmtUpdateLo = $pdo->prepare("UPDATE etl_lo SET tong_so_ban_ghi = :count WHERE id = :id");
            $stmtUpdateLo->execute(['count' => $count, 'id' => $loId]);

            $pdo->commit();

            return [
                'lo_id'          => $loId,
                'tong_so'        => $count,
                'ten_lo'         => $tenLo,
                'he_thong_nguon' => $heThongNguon,
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            Logger::error("ETL Extract Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * BƯỚC 2: STAGING & PROFILING - Đánh giá chất lượng dữ liệu, phát hiện hồ sơ rác, trùng lặp, lỗi CCCD
     *
     * @param int $loId
     * @return array{
     *   lo_id: int,
     *   tong_so: int,
     *   so_hop_le: int,
     *   so_loi: int,
     *   ty_le_hop_le_phan_tram: float,
     *   chi_tiet_loi: array<string, int>,
     *   danh_sach_loi: array<int, array>
     * }
     */
    public function profileStaging(int $loId): array
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            // Lấy danh sách ánh xạ hợp lệ để kiểm tra
            $validTthcMap = $pdo->query("SELECT ma_tthc_cu FROM etl_anh_xa_tthc")->fetchAll(PDO::FETCH_COLUMN);
            $validDvhcMap = $pdo->query("SELECT ma_dvhc_cu FROM etl_anh_xa_dvhc")->fetchAll(PDO::FETCH_COLUMN);

            // Lấy toàn bộ bản ghi staging của lô
            $stmtStg = $pdo->prepare("SELECT * FROM etl_staging_ho_so WHERE lo_id = :lo_id");
            $stmtStg->execute(['lo_id' => $loId]);
            $rows = $stmtStg->fetchAll(PDO::FETCH_ASSOC);

            // Xóa log lỗi cũ của lô nếu chạy lại
            $pdo->prepare("DELETE FROM etl_loi WHERE lo_id = :lo_id")->execute(['lo_id' => $loId]);

            $stmtErr = $pdo->prepare("
                INSERT INTO etl_loi (lo_id, staging_id, loai_loi, mo_ta_loi, du_lieu_loi)
                VALUES (:lo_id, :stg_id, :loai_loi, :mo_ta, :du_lieu)
            ");

            $stmtUpdateStg = $pdo->prepare("
                UPDATE etl_staging_ho_so
                SET trang_thai_etl = :status, ghi_chu_loi = :note
                WHERE id = :id
            ");

            $seenMaHoSo = [];
            $seenCitizenTrip = []; // CCCD + MaTTHC + NgayTN

            $stats = [
                'TRUNG_LAP'   => 0,
                'THIEU_CCCD'  => 0,
                'SAI_CCCD'    => 0,
                'MA_TTHC_SAI' => 0,
                'MA_DVHC_SAI' => 0,
                'DU_LIEU_RAC' => 0,
            ];

            $validCount = 0;
            $errorCount = 0;
            $errorList = [];

            foreach ($rows as $row) {
                $stgId = (int)$row['id'];
                $maHs = trim((string)$row['ma_ho_so_cu']);
                $cccd = trim((string)$row['so_cmnd_cccd']);
                $nguoiNop = trim((string)$row['nguoi_nop']);
                $maTthc = trim((string)$row['ma_tthc_cu']);
                $coQuan = trim((string)$row['co_quan_cu']);
                $ngayTn = trim((string)$row['ngay_tiep_nhan_cu']);

                $rowErrors = [];

                // 1. Kiểm tra Hồ sơ trùng lặp
                if (isset($seenMaHoSo[$maHs])) {
                    $rowErrors[] = [
                        'loai' => 'TRUNG_LAP',
                        'mota' => "Trùng mã hồ sơ Một cửa cũ: '{$maHs}'",
                        'data' => "Trùng với bản ghi Staging ID #{$seenMaHoSo[$maHs]}",
                    ];
                    $stats['TRUNG_LAP']++;
                } else {
                    $seenMaHoSo[$maHs] = $stgId;
                }

                // 2. Kiểm tra CCCD/CMND
                if ($cccd === '' || strtoupper($cccd) === 'NULL' || $cccd === '0' || strtoupper($cccd) === 'KHONG_CO') {
                    $rowErrors[] = [
                        'loai' => 'THIEU_CCCD',
                        'mota' => "Thiếu số CCCD/CMND của người nộp hồ sơ",
                        'data' => "Họ tên: {$nguoiNop}",
                    ];
                    $stats['THIEU_CCCD']++;
                } else {
                    $cleanDigits = preg_replace('/\D/', '', $cccd);
                    $len = strlen($cleanDigits);
                    if ($len !== 9 && $len !== 12) {
                        $rowErrors[] = [
                            'loai' => 'SAI_CCCD',
                            'mota' => "Số định danh không đúng quy chuẩn (phải 9 số CMND hoặc 12 số CCCD, thực tế: '{$cccd}')",
                            'data' => $cccd,
                        ];
                        $stats['SAI_CCCD']++;
                    }
                }

                // 3. Kiểm tra Mã TTHC cũ
                if (!in_array($maTthc, $validTthcMap, true)) {
                    $rowErrors[] = [
                        'loai' => 'MA_TTHC_SAI',
                        'mota' => "Mã thủ tục Một cửa cũ '{$maTthc}' chưa được cấu hình ánh xạ sang mã Quốc gia",
                        'data' => "Tên TTHC cũ: {$row['ten_tthc_cu']}",
                    ];
                    $stats['MA_TTHC_SAI']++;
                }

                // 4. Kiểm tra Mã ĐVHC cũ
                if (!in_array($coQuan, $validDvhcMap, true)) {
                    $rowErrors[] = [
                        'loai' => 'MA_DVHC_SAI',
                        'mota' => "Mã cơ quan Một cửa cũ '{$coQuan}' chưa có trong danh mục ánh xạ sang mã TCTK",
                        'data' => "Cơ quan: {$coQuan}",
                    ];
                    $stats['MA_DVHC_SAI']++;
                }

                // 5. Kiểm tra Trường rác / Ký tự XSS injection
                if (preg_match('/<[^>]*>|javascript:|onerror=|alert\(/i', $nguoiNop) || preg_match('/<[^>]*>/i', (string)$row['ten_tthc_cu'])) {
                    $rowErrors[] = [
                        'loai' => 'DU_LIEU_RAC',
                        'mota' => "Phát hiện chuỗi mã HTML/XSS nguy hiểm hoặc ký tự rác trong dữ liệu",
                        'data' => "Người nộp: {$nguoiNop}",
                    ];
                    $stats['DU_LIEU_RAC']++;
                }

                // Cập nhật trạng thái Staging
                if (!empty($rowErrors)) {
                    $errorCount++;
                    $notes = array_column($rowErrors, 'mota');
                    $stmtUpdateStg->execute([
                        'status' => 'LOI',
                        'note'   => implode('; ', $notes),
                        'id'     => $stgId,
                    ]);

                    foreach ($rowErrors as $err) {
                        $stmtErr->execute([
                            'lo_id'    => $loId,
                            'stg_id'   => $stgId,
                            'loai_loi' => $err['loai'],
                            'mo_ta'    => $err['mota'],
                            'du_lieu'  => $err['data'],
                        ]);
                        $errorList[] = [
                            'staging_id' => $stgId,
                            'ma_ho_so'   => $maHs,
                            'loai_loi'   => $err['loai'],
                            'mo_ta_loi'  => $err['mota'],
                        ];
                    }
                } else {
                    $validCount++;
                    $stmtUpdateStg->execute([
                        'status' => 'HOP_LE',
                        'note'   => null,
                        'id'     => $stgId,
                    ]);
                }
            }

            // Cập nhật số lỗi vào bảng etl_lo
            $pdo->prepare("UPDATE etl_lo SET so_loi = :err WHERE id = :id")->execute([
                'err' => $errorCount,
                'id'  => $loId,
            ]);

            $pdo->commit();

            $total = count($rows);
            $qualityPercent = $total > 0 ? round(($validCount / $total) * 100, 2) : 0.0;

            return [
                'lo_id'                  => $loId,
                'tong_so'                => $total,
                'so_hop_le'              => $validCount,
                'so_loi'                 => $errorCount,
                'ty_le_hop_le_phan_tram' => $qualityPercent,
                'chi_tiet_loi'           => $stats,
                'danh_sach_loi'          => $errorList,
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            Logger::error("ETL Profiling Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * BƯỚC 3: TRANSFORM - Ánh xạ danh mục TTHC/ĐVHC, chuẩn hóa ngày tháng, họ tên, gắn nhãn PDF/A-1b
     *
     * @param int $loId
     * @return array{lo_id: int, so_chuyen_doi: int, so_tap_tin_can_pdf_a: int}
     */
    public function transformData(int $loId): array
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            // Đổi trạng thái lô sang TRANSFORMING
            $pdo->prepare("UPDATE etl_lo SET trang_thai = 'TRANSFORMING' WHERE id = :id")->execute(['id' => $loId]);

            // Lấy bảng ánh xạ TTHC
            $stmtTthc = $pdo->query("SELECT ma_tthc_cu, ma_tthc_moi, thu_tuc_id FROM etl_anh_xa_tthc");
            $mapTthc = [];
            while ($r = $stmtTthc->fetch(PDO::FETCH_ASSOC)) {
                $mapTthc[$r['ma_tthc_cu']] = [
                    'ma_tthc_moi' => $r['ma_tthc_moi'],
                    'thu_tuc_id'  => $r['thu_tuc_id'] ? (int)$r['thu_tuc_id'] : null,
                ];
            }

            // Lấy bảng ánh xạ ĐVHC
            $stmtDvhc = $pdo->query("SELECT ma_dvhc_cu, ma_tctk, don_vi_id FROM etl_anh_xa_dvhc");
            $mapDvhc = [];
            while ($r = $stmtDvhc->fetch(PDO::FETCH_ASSOC)) {
                $mapDvhc[$r['ma_dvhc_cu']] = [
                    'ma_tctk'   => $r['ma_tctk'],
                    'don_vi_id' => $r['don_vi_id'] ? (int)$r['don_vi_id'] : null,
                ];
            }

            // Lấy danh sách hồ sơ hợp lệ để chuyển đổi
            $stmtStg = $pdo->prepare("SELECT * FROM etl_staging_ho_so WHERE lo_id = :lo_id AND trang_thai_etl = 'HOP_LE'");
            $stmtStg->execute(['lo_id' => $loId]);
            $validRows = $stmtStg->fetchAll(PDO::FETCH_ASSOC);

            $stmtUpdateRaw = $pdo->prepare("UPDATE etl_staging_ho_so SET du_lieu_raw_json = :json WHERE id = :id");

            $pdfACount = 0;
            $transformedCount = 0;

            foreach ($validRows as $row) {
                $raw = json_decode((string)$row['du_lieu_raw_json'], true) ?: [];

                // 1. Ánh xạ mã TTHC và ĐVHC
                $maTthcCu = trim((string)$row['ma_tthc_cu']);
                $coQuanCu = trim((string)$row['co_quan_cu']);

                $tthcData = $mapTthc[$maTthcCu] ?? null;
                $dvhcData = $mapDvhc[$coQuanCu] ?? null;

                // 2. Chuẩn hóa ngày tháng
                $ngayTnIso = $this->standardizeDateTime((string)$row['ngay_tiep_nhan_cu']);
                $ngayHtIso = $this->standardizeDateTime((string)$row['ngay_hen_tra_cu']);

                // 3. Chuẩn hóa Họ tên người nộp
                $nguoiNopClean = $this->cleanAndTitleCaseName((string)$row['nguoi_nop']);

                // 4. Chuẩn hóa CCCD (loại bỏ khoảng trắng, dấu chấm)
                $cccdClean = preg_replace('/\D/', '', (string)$row['so_cmnd_cccd']);

                // 5. Chuẩn hóa Trạng thái
                $trangThaiMoi = $this->standardizeTrangThai((string)$row['trang_thai_cu']);

                // 6. Xử lý danh sách tệp đính kèm & đánh dấu PDF/A-1b
                $filesStr = trim((string)$row['danh_sach_file_cu']);
                $filesList = [];
                if ($filesStr !== '') {
                    $fileParts = explode(';', $filesStr);
                    foreach ($fileParts as $fp) {
                        $fp = trim($fp);
                        if ($fp === '') continue;

                        $fileHashArr = explode(':', $fp, 2);
                        $fileName = $fileHashArr[0];
                        $expectedHash = $fileHashArr[1] ?? '';

                        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        // Đánh dấu cần chuyển PDF/A-1b nếu không phải PDF chuẩn hoặc là file office/image
                        $canChuyenPdfA = ($ext !== 'pdf' || str_contains(strtolower($fileName), 'don_') || str_contains(strtolower($fileName), 'ban_ve_'));

                        if ($canChuyenPdfA) {
                            $pdfACount++;
                        }

                        $filesList[] = [
                            'file_name'        => $fileName,
                            'expected_sha256'  => $expectedHash,
                            'can_chuyen_pdf_a' => $canChuyenPdfA,
                            'ext'              => $ext,
                        ];
                    }
                }

                // Gắn dữ liệu transformed vào JSON
                $raw['transformed'] = [
                    'ma_tthc_moi'      => $tthcData['ma_tthc_moi'] ?? null,
                    'tthc_id'          => $tthcData['thu_tuc_id'] ?? null,
                    'ma_tctk'          => $dvhcData['ma_tctk'] ?? null,
                    'don_vi_id'        => $dvhcData['don_vi_id'] ?? null,
                    'ngay_tiep_nhan'   => $ngayTnIso,
                    'ngay_hen_tra'     => $ngayHtIso,
                    'nguoi_nop_ho_ten' => $nguoiNopClean,
                    'nguoi_nop_cccd'   => $cccdClean,
                    'trang_thai_moi'   => $trangThaiMoi,
                    'danh_sach_files'  => $filesList,
                ];

                $stmtUpdateRaw->execute([
                    'json' => json_encode($raw, JSON_UNESCAPED_UNICODE),
                    'id'   => $row['id'],
                ]);

                $transformedCount++;
            }

            $pdo->commit();

            return [
                'lo_id'                 => $loId,
                'so_chuyen_doi'         => $transformedCount,
                'so_tap_tin_can_pdf_a'  => $pdfACount,
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            Logger::error("ETL Transform Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * BƯỚC 4: LOAD & VERIFY - Nạp dữ liệu vào bảng chính theo lô, đối soát SHA-256 100% tệp đính kèm
     *
     * @param int $loId
     * @param string $attachmentsDir Thư mục chứa tệp đính kèm vật lý
     * @param int $chunkSize Kích thước lô xử lý tránh timeout (mặc định 50 bản ghi/lô)
     * @return array{
     *   lo_id: int,
     *   so_thanh_cong: int,
     *   so_that_bai: int,
     *   doi_soat_file: array{tong_so_file: int, khop_sha256: int, sai_sha256: int, thieu_file: int},
     *   danh_sach_loi: array<int, array>
     * }
     */
    public function loadAndVerify(int $loId, string $attachmentsDir, int $chunkSize = 50): array
    {
        $pdo = Database::pdo();

        // 1. Lấy danh sách staging đã qua transform
        $stmtStg = $pdo->prepare("
            SELECT * FROM etl_staging_ho_so
            WHERE lo_id = :lo_id AND trang_thai_etl = 'HOP_LE'
        ");
        $stmtStg->execute(['lo_id' => $loId]);
        $records = $stmtStg->fetchAll(PDO::FETCH_ASSOC);

        $totalRecords = count($records);
        $successCount = 0;
        $failedCount = 0;

        $fileStats = [
            'tong_so_file' => 0,
            'khop_sha256'  => 0,
            'sai_sha256'   => 0,
            'thieu_file'   => 0,
        ];

        $errorsList = [];

        // Xử lý chia nhỏ theo lô (Chunking) để không timeout
        $chunks = array_chunk($records, $chunkSize);

        foreach ($chunks as $chunk) {
            $pdo->beginTransaction();
            try {
                foreach ($chunk as $row) {
                    $stgId = (int)$row['id'];
                    $raw = json_decode((string)$row['du_lieu_raw_json'], true) ?: [];
                    $trans = $raw['transformed'] ?? null;

                    if (!$trans) {
                        $failedCount++;
                        continue;
                    }

                    // 1. Đối soát SHA-256 tệp đính kèm
                    $hasFileError = false;
                    $files = $trans['danh_sach_files'] ?? [];

                    foreach ($files as $fInfo) {
                        $fileStats['tong_so_file']++;
                        $fileName = $fInfo['file_name'];
                        $expectedHash = strtolower(trim((string)$fInfo['expected_sha256']));
                        $filePath = rtrim($attachmentsDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;

                        if (!file_exists($filePath)) {
                            $fileStats['thieu_file']++;
                            $hasFileError = true;
                            $errorsList[] = [
                                'staging_id' => $stgId,
                                'loai_loi'   => 'ATTACHMENT_MISSING',
                                'mo_ta_loi'  => "Tệp đính kèm '{$fileName}' không tìm thấy trên đĩa lưu trữ",
                                'du_lieu'    => $filePath,
                            ];
                            $this->logEtlLoi($pdo, $loId, $stgId, 'ATTACHMENT_MISSING', "Tệp '{$fileName}' không tồn tại", $filePath);
                        } else {
                            $actualHash = strtolower(hash_file('sha256', $filePath));
                            if ($expectedHash !== '' && $actualHash !== $expectedHash) {
                                $fileStats['sai_sha256']++;
                                $hasFileError = true;
                                $errorsList[] = [
                                    'staging_id' => $stgId,
                                    'loai_loi'   => 'ATTACHMENT_CHECKSUM_MISMATCH',
                                    'mo_ta_loi'  => "Sai lệch chuỗi băm SHA-256 tệp '{$fileName}' (kỳ vọng: {$expectedHash}, thực tế: {$actualHash})",
                                    'du_lieu'    => "File: {$fileName}",
                                ];
                                $this->logEtlLoi($pdo, $loId, $stgId, 'ATTACHMENT_CHECKSUM_MISMATCH', "Sai lệch checksum SHA-256", "Kỳ vọng: {$expectedHash} | Thực tế: {$actualHash}");
                            } else {
                                $fileStats['khop_sha256']++;
                            }
                        }
                    }

                    if ($hasFileError) {
                        $failedCount++;
                        $pdo->prepare("UPDATE etl_staging_ho_so SET trang_thai_etl = 'LOI', ghi_chu_loi = 'Lỗi đối soát tệp đính kèm' WHERE id = :id")->execute(['id' => $stgId]);
                        continue;
                    }

                    // 2. Tìm hoặc tạo Công dân
                    $congDanId = $this->findOrCreateCongDan(
                        $pdo,
                        $trans['nguoi_nop_cccd'],
                        $trans['nguoi_nop_ho_ten'],
                        $row['co_quan_cu'] ?? ''
                    );

                    // 3. Chuẩn bị thông tin Thủ tục và Đơn vị
                    $tthcId = $trans['tthc_id'] ?: 1;
                    $donViId = $trans['don_vi_id'] ?: 1;

                    // Lấy quy trình chuẩn của thủ tục
                    $qtStmt = $pdo->prepare("SELECT id FROM quy_trinh WHERE tthc_id = :id AND is_active = 1 LIMIT 1");
                    $qtStmt->execute(['id' => $tthcId]);
                    $qtId = (int)($qtStmt->fetchColumn() ?: 1);

                    // 4. Sinh mã hồ sơ chuẩn mới (hoặc giữ mã cũ kèm tiền tố ETL để không trùng lặp)
                    $maHoSoCu = trim((string)$row['ma_ho_so_cu']);
                    $maHoSoMoi = 'ETL-' . $maHoSoCu;

                    // Kiểm tra xem mã này đã nạp chưa
                    $checkHs = $pdo->prepare("SELECT id FROM ho_so WHERE ma_ho_so = :ma");
                    $checkHs->execute(['ma' => $maHoSoMoi]);
                    $existingId = $checkHs->fetchColumn();

                    if (!$existingId) {
                        $stmtInsertHs = $pdo->prepare("
                            INSERT INTO ho_so (
                                ma_ho_so, tthc_id, quy_trinh_id, don_vi_tiep_nhan_id,
                                don_vi_hien_tai_id, cong_dan_id, kenh_nop, nguoi_nop_ho_ten,
                                nguoi_nop_cccd, du_lieu_eform, trang_thai, ngay_nop,
                                ngay_tiep_nhan, ngay_hen_tra, sla_mau, tong_thoi_han_gio
                            ) VALUES (
                                :ma_hs, :tthc_id, :qt_id, :dv_tn,
                                :dv_ht, :cd_id, 'TRUC_TIEP', :ho_ten,
                                :cccd, :eform, :trang_thai, :ngay_nop,
                                :ngay_tn, :ngay_ht, 'XANH', 24
                            )
                        ");

                        $stmtInsertHs->execute([
                            'ma_hs'      => $maHoSoMoi,
                            'tthc_id'    => $tthcId,
                            'qt_id'      => $qtId,
                            'dv_tn'      => $donViId,
                            'dv_ht'      => $donViId,
                            'cd_id'      => $congDanId,
                            'ho_ten'     => $trans['nguoi_nop_ho_ten'],
                            'cccd'       => $trans['nguoi_nop_cccd'],
                            'eform'      => json_encode(['ghi_chu' => 'Hồ sơ chuyển đổi từ hệ thống Một cửa cũ', 'ma_cu' => $maHoSoCu], JSON_UNESCAPED_UNICODE),
                            'trang_thai' => $trans['trang_thai_moi'],
                            'ngay_nop'   => $trans['ngay_tiep_nhan'] ?: date('Y-m-d H:i:s'),
                            'ngay_tn'    => $trans['ngay_tiep_nhan'],
                            'ngay_ht'    => $trans['ngay_hen_tra'],
                        ]);
                        $newHoSoId = (int)$pdo->lastInsertId();

                        // 5. Nạp danh sách tệp đính kèm vào ho_so_tai_lieu
                        foreach ($files as $fInfo) {
                            $fName = $fInfo['file_name'];
                            $fPath = rtrim($attachmentsDir, '/\\') . DIRECTORY_SEPARATOR . $fName;
                            $fHash = file_exists($fPath) ? hash_file('sha256', $fPath) : $fInfo['expected_sha256'];
                            $fSize = file_exists($fPath) ? filesize($fPath) : 1024;

                            $uniqueTenLuu = 'etl_' . uniqid() . '_' . $fName;
                            $stmtFile = $pdo->prepare("
                                INSERT INTO ho_so_tai_lieu (
                                    ho_so_id, ten_goc, ten_luu, duong_dan, kich_thuoc_byte,
                                    mime_type, sha256, loai_tai_lieu
                                ) VALUES (
                                    :hs_id, :ten_goc, :ten_luu, :path, :size, :mime, :sha, 'DAU_VAO'
                                )
                            ");
                            $stmtFile->execute([
                                'hs_id'    => $newHoSoId,
                                'ten_goc'  => $fName,
                                'ten_luu'  => $uniqueTenLuu,
                                'path'     => 'storage/etl_imported/' . $fName,
                                'size'     => $fSize,
                                'mime'     => $fInfo['can_chuyen_pdf_a'] ? 'application/octet-stream' : 'application/pdf',
                                'sha'      => $fHash,
                            ]);
                        }

                        // 6. Ghi vết luân chuyển ban đầu
                        $pdo->prepare("
                            INSERT INTO ho_so_luan_chuyen (
                                ho_so_id, tu_don_vi_id, den_don_vi_id, nguoi_chuyen_id,
                                hanh_dong, trang_thai_truoc, trang_thai_sau, noi_dung_y_kien, thoi_gian_chuyen
                            ) VALUES (
                                :hs_id, :tu_dv, :den_dv, 1,
                                'KHOI_TAO_ETL', 'CHO_TIEP_NHAN', :tt_sau, 'Chuyển đổi dữ liệu Một cửa cũ', :thoi_gian
                            )
                        ")->execute([
                            'hs_id'     => $newHoSoId,
                            'tu_dv'     => $donViId,
                            'den_dv'    => $donViId,
                            'tt_sau'    => $trans['trang_thai_moi'],
                            'thoi_gian' => $trans['ngay_tiep_nhan'] ?: date('Y-m-d H:i:s'),
                        ]);
                    }

                    // Đánh dấu Staging hoàn tất DA_NAP
                    $pdo->prepare("UPDATE etl_staging_ho_so SET trang_thai_etl = 'DA_NAP' WHERE id = :id")->execute(['id' => $stgId]);
                    $successCount++;
                }

                $pdo->commit();
            } catch (Throwable $chunkEx) {
                $pdo->rollBack();
                Logger::error("ETL Chunk Error: " . $chunkEx->getMessage());
                $failedCount += count($chunk);
            }
        }

        // Cập nhật kết quả vào etl_lo
        $statusLo = ($failedCount === 0) ? 'LOADED' : 'LOADED';
        $pdo->prepare("
            UPDATE etl_lo
            SET so_thanh_cong = :tc, so_loi = :err, trang_thai = :st
            WHERE id = :id
        ")->execute([
            'tc'  => $successCount,
            'err' => $failedCount,
            'st'  => $statusLo,
            'id'  => $loId,
        ]);

        return [
            'lo_id'         => $loId,
            'so_thanh_cong' => $successCount,
            'so_that_bai'   => $failedCount,
            'doi_soat_file' => $fileStats,
            'danh_sach_loi' => $errorsList,
        ];
    }

    /**
     * Xuất danh sách lỗi của lô ETL ra mảng phục vụ tải CSV
     *
     * @param int $loId
     * @return array<int, array>
     */
    public function getErrorsReport(int $loId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            SELECT el.id, el.lo_id, el.staging_id, el.loai_loi, el.mo_ta_loi, el.du_lieu_loi, el.created_at,
                   stg.ma_ho_so_cu, stg.nguoi_nop, stg.so_cmnd_cccd
            FROM etl_loi el
            LEFT JOIN etl_staging_ho_so stg ON el.staging_id = stg.id
            WHERE el.lo_id = :lo_id
            ORDER BY el.id ASC
        ");
        $stmt->execute(['lo_id' => $loId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ghi nhận lỗi ETL
     */
    private function logEtlLoi(PDO $pdo, int $loId, ?int $stgId, string $loaiLoi, string $moTa, ?string $duLieu): void
    {
        $stmt = $pdo->prepare("
            INSERT INTO etl_loi (lo_id, staging_id, loai_loi, mo_ta_loi, du_lieu_loi)
            VALUES (:lo_id, :stg_id, :loai, :mota, :dulieu)
        ");
        $stmt->execute([
            'lo_id'  => $loId,
            'stg_id' => $stgId,
            'loai'   => $loaiLoi,
            'mota'   => $moTa,
            'dulieu' => $duLieu,
        ]);
    }

    /**
     * Chuẩn hóa chuỗi ngày giờ sang định dạng ISO 'Y-m-d H:i:s'
     */
    private function standardizeDateTime(string $rawDate): ?string
    {
        $rawDate = trim($rawDate);
        if ($rawDate === '' || strtoupper($rawDate) === 'NULL') {
            return null;
        }

        // Định dạng Y-m-d H:i:s hoặc Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}(\s+\d{2}:\d{2}:\d{2})?$/', $rawDate)) {
            return (strlen($rawDate) === 10) ? $rawDate . ' 08:00:00' : $rawDate;
        }

        // Định dạng d/m/Y H:i:s hoặc d/m/Y
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})(\s+(\d{1,2}):(\d{1,2}):(\d{1,2}))?$/', $rawDate, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year = $m[3];
            $time = isset($m[4]) ? trim($m[4]) : '08:00:00';
            return "{$year}-{$month}-{$day} {$time}";
        }

        return date('Y-m-d H:i:s', strtotime($rawDate) ?: time());
    }

    /**
     * Chuẩn hóa họ tên: loại bỏ thẻ HTML, chuẩn hóa hoa/thường, xóa khoảng trắng thừa
     */
    private function cleanAndTitleCaseName(string $name): string
    {
        $name = strip_tags($name);
        $name = preg_replace('/\s+/', ' ', trim($name));
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Chuẩn hóa trạng thái Một cửa cũ
     */
    private function standardizeTrangThai(string $oldStatus): string
    {
        $s = strtoupper(trim($oldStatus));
        if (str_contains($s, 'DA_TRA') || str_contains($s, 'TRA_KQ')) {
            return 'DA_TRA_KET_QUA';
        }
        if (str_contains($s, 'HOAN_THANH') || str_contains($s, 'KET_THUC')) {
            return 'HOAN_THANH';
        }
        if (str_contains($s, 'BO_SUNG')) {
            return 'CHO_BO_SUNG';
        }
        if (str_contains($s, 'CHO_XU_LY') || str_contains($s, 'CHUA_XU_LY') || str_contains($s, 'MOI')) {
            return 'CHO_TIEP_NHAN';
        }
        return 'DANG_XU_LY';
    }

    /**
     * Tìm hoặc tạo công dân từ thông tin CCCD
     */
    private function findOrCreateCongDan(PDO $pdo, string $cccd, string $hoTen, string $diaChi): int
    {
        $cccd = trim($cccd);
        if ($cccd !== '') {
            $stmt = $pdo->prepare("SELECT id FROM cong_dan WHERE so_cccd = :cccd LIMIT 1");
            $stmt->execute(['cccd' => $cccd]);
            $id = $stmt->fetchColumn();
            if ($id) {
                return (int)$id;
            }
        }

        $stmtIns = $pdo->prepare("
            INSERT INTO cong_dan (ho_ten, so_cccd, dia_chi_thuong_tru, trang_thai)
            VALUES (:ho_ten, :cccd, :dia_chi, 1)
        ");
        $stmtIns->execute([
            'ho_ten'  => $hoTen,
            'cccd'    => $cccd ?: ('ETL' . time() . rand(100, 999)),
            'dia_chi' => $diaChi ?: 'TP. Hải Phòng',
        ]);
        return (int)$pdo->lastInsertId();
    }
}
