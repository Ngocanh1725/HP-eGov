<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Response;
use Closure;
use InvalidArgumentException;

/**
 * MdmService – Dịch vụ hỗ trợ Quản trị Danh mục dùng chung (Master Data Management)
 * Xử lý Xuất/Nhập CSV UTF-8 có BOM (tương thích Excel tiếng Việt) và kiểm tra lỗi từng dòng
 */
class MdmService
{
    /**
     * Xuất dữ liệu ra file CSV chuẩn UTF-8 có BOM (Excel mở không bị lỗi font)
     *
     * @param string $filename Tên file khi tải về (Vd: danh_muc_dvhc.csv)
     * @param array<int, string> $headers Tiêu đề các cột
     * @param array<int, array> $rows Dữ liệu các dòng
     * @return Response
     */
    public static function exportCsv(string $filename, array $headers, array $rows): Response
    {
        $handle = fopen('php://memory', 'r+');
        if ($handle === false) {
            throw new InvalidArgumentException("Không thể mở luồng ghi tệp CSV.");
        }

        // 1. Thêm Byte Order Mark (BOM) UTF-8
        fwrite($handle, "\xEF\xBB\xBF");

        // 2. Ghi tiêu đề
        fputcsv($handle, $headers);

        // 3. Ghi từng dòng dữ liệu
        foreach ($rows as $row) {
            fputcsv($handle, array_values($row));
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        $cleanFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

        return Response::html((string)$csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $cleanFilename . '"',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Nhập dữ liệu từ tệp CSV có kiểm tra lỗi từng dòng (Row-by-Row Validator)
     *
     * @param string $filePath Đường dẫn tệp CSV tạm thời
     * @param Closure(array, int): (?string) $rowValidator Hàm kiểm tra dòng, trả về null nếu hợp lệ hoặc thông báo lỗi
     * @param Closure(array, int): void $rowProcessor Hàm xử lý khi dòng hợp lệ
     * @return array{
     *   total_lines: int,
     *   success_count: int,
     *   error_count: int,
     *   errors: array<int, array{line: int, reason: string, data: array}>
     * }
     */
    public static function importCsv(string $filePath, Closure $rowValidator, Closure $rowProcessor): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'total_lines' => 0,
                'success_count' => 0,
                'error_count' => 1,
                'errors' => [['line' => 0, 'reason' => 'Tệp CSV không tồn tại hoặc không thể đọc.', 'data' => []]],
            ];
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [
                'total_lines' => 0,
                'success_count' => 0,
                'error_count' => 1,
                'errors' => [['line' => 0, 'reason' => 'Không thể mở tệp CSV.', 'data' => []]],
            ];
        }

        // Bỏ qua BOM UTF-8 nếu có ở đầu file
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Đọc dòng tiêu đề (Header row)
        $headers = fgetcsv($handle);

        $lineIndex = 1; // Dòng 1 là header
        $successCount = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $lineIndex++;

            // Bỏ qua dòng trống
            if (empty(array_filter($row, fn($val) => trim((string)$val) !== ''))) {
                continue;
            }

            // Kiểm tra tính hợp lệ của dòng
            $errorMsg = $rowValidator($row, $lineIndex);
            if ($errorMsg !== null) {
                $errors[] = [
                    'line' => $lineIndex,
                    'reason' => $errorMsg,
                    'data' => $row,
                ];
                continue;
            }

            // Xử lý nạp dữ liệu
            try {
                $rowProcessor($row, $lineIndex);
                $successCount++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'line' => $lineIndex,
                    'reason' => 'Lỗi lưu CSDL: ' . $e->getMessage(),
                    'data' => $row,
                ];
            }
        }

        fclose($handle);

        return [
            'total_lines' => $lineIndex - 1,
            'success_count' => $successCount,
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }
}
