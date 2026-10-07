<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * WorkflowEngine – Động cơ Quản lý Quy trình luân chuyển hành chính (BPMN-lite)
 * Kiểm tra tính hợp lệ quy trình, tính tổng thời hạn SLA và sinh sơ đồ Mermaid trực quan
 */
class WorkflowEngine
{
    /**
     * Thẩm định tính toàn vẹn và hợp lệ của một quy trình BPMN-lite
     *
     * @param int $quyTrinhId ID quy trình
     * @return array{
     *   valid: bool,
     *   errors: array<int, string>,
     *   warnings: array<int, string>,
     *   total_hours: int,
     *   tthc_hours: int,
     *   step_count: int,
     *   has_start: bool,
     *   has_end: bool
     * }
     */
    public static function validateWorkflow(int $quyTrinhId): array
    {
        $db = Database::connection();

        // 1. Lấy thông tin quy trình và TTHC
        $qtStmt = $db->prepare("SELECT qt.*, tt.thoi_han_giai_quyet_ngay, tt.thoi_han_giai_quyet_gio, tt.ten_tthc 
                                FROM quy_trinh qt 
                                LEFT JOIN thu_tuc_hanh_chinh tt ON qt.tthc_id = tt.id 
                                WHERE qt.id = :id");
        $qtStmt->execute([':id' => $quyTrinhId]);
        $quyTrinh = $qtStmt->fetch(PDO::FETCH_ASSOC);

        if (!$quyTrinh) {
            return [
                'valid' => false,
                'errors' => ['Không tìm thấy quy trình trong hệ thống.'],
                'warnings' => [],
                'total_hours' => 0,
                'tthc_hours' => 0,
                'step_count' => 0,
                'has_start' => false,
                'has_end' => false,
            ];
        }

        // 2. Lấy danh sách các bước
        $bStmt = $db->prepare("SELECT * FROM quy_trinh_buoc WHERE quy_trinh_id = :id ORDER BY stt_buoc ASC");
        $bStmt->execute([':id' => $quyTrinhId]);
        $steps = $bStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Lấy danh sách các luồng chuyển tiếp
        $ctStmt = $db->prepare("SELECT * FROM quy_trinh_chuyen_tiep WHERE quy_trinh_id = :id");
        $ctStmt->execute([':id' => $quyTrinhId]);
        $transitions = $ctStmt->fetchAll(PDO::FETCH_ASSOC);

        $errors = [];
        $warnings = [];

        if (empty($steps)) {
            $errors[] = 'Quy trình chưa được định nghĩa bất kỳ bước thực hiện nào.';
            return [
                'valid' => false,
                'errors' => $errors,
                'warnings' => [],
                'total_hours' => 0,
                'tthc_hours' => 0,
                'step_count' => 0,
                'has_start' => false,
                'has_end' => false,
            ];
        }

        // 4. Kiểm tra bước bắt đầu (TIEP_NHAN) và bước kết thúc (TRA_KET_QUA)
        $hasStart = false;
        $hasEnd = false;
        $totalHours = 0;
        $stepIds = [];

        foreach ($steps as $s) {
            $stepId = (int)$s['id'];
            $stepIds[$stepId] = $s;
            $totalHours += (int)$s['thoi_han_gio'];

            if ($s['loai_buoc'] === 'TIEP_NHAN') {
                $hasStart = true;
            }
            if ($s['loai_buoc'] === 'TRA_KET_QUA') {
                $hasEnd = true;
            }
        }

        if (!$hasStart) {
            $errors[] = 'Quy trình thiếu bước bắt đầu loại [TIEP_NHAN].';
        }
        if (!$hasEnd) {
            $errors[] = 'Quy trình thiếu bước kết thúc loại [TRA_KET_QUA].';
        }

        // 5. Kiểm tra bước mồ côi (Unreachable hoặc Dead-end)
        $incomingTransitions = [];
        $outgoingTransitions = [];

        foreach ($transitions as $tr) {
            $from = (int)$tr['tu_buoc_id'];
            $to = (int)$tr['den_buoc_id'];
            $outgoingTransitions[$from][] = $to;
            $incomingTransitions[$to][] = $from;
        }

        foreach ($steps as $s) {
            $stepId = (int)$s['id'];
            // Bước không phải TIEP_NHAN mà không có luồng đến
            if ($s['loai_buoc'] !== 'TIEP_NHAN' && empty($incomingTransitions[$stepId])) {
                $errors[] = "Bước #{$s['stt_buoc']} [{$s['ten_buoc']}] bị mồ côi: Không có luồng chuyển tiếp nào dẫn đến bước này.";
            }
            // Bước không phải TRA_KET_QUA mà không có luồng đi
            if ($s['loai_buoc'] !== 'TRA_KET_QUA' && empty($outgoingTransitions[$stepId])) {
                $errors[] = "Bước #{$s['stt_buoc']} [{$s['ten_buoc']}] bị tắc nghẽn (Dead-end): Không có luồng chuyển tiếp nào đi tiếp từ bước này.";
            }
        }

        // 6. Kiểm tra tổng thời hạn của các bước so với thời hạn của TTHC
        $tthcDays = (int)($quyTrinh['thoi_han_giai_quyet_ngay'] ?? 0);
        $tthcHours = (int)($quyTrinh['thoi_han_giai_quyet_gio'] ?? ($tthcDays * 8)); // 1 ngày = 8 giờ làm việc

        if ($tthcHours > 0) {
            if ($totalHours > $tthcHours) {
                $warnings[] = "Cảnh báo lệch SLA: Tổng thời hạn các bước ({$totalHours} giờ) vượt quá thời hạn cam kết của TTHC ({$tthcHours} giờ). Có nguy cơ gây chậm trễ hồ sơ!";
            } elseif ($totalHours < $tthcHours) {
                $warnings[] = "Lưu ý tối ưu: Tổng thời hạn các bước ({$totalHours} giờ) ngắn hơn thời hạn tối đa của TTHC ({$tthcHours} giờ).";
            }
        }

        return [
            'valid' => empty($errors),
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'total_hours' => $totalHours,
            'total_step_hours' => $totalHours,
            'tthc_hours' => $tthcHours,
            'expected_tthc_hours' => $tthcHours,
            'step_count' => count($steps),
            'has_start' => $hasStart,
            'has_end' => $hasEnd,
        ];
    }

    /**
     * Sinh mã sơ đồ Mermaid trực quan từ cấu trúc các bước và chuyển tiếp
     */
    public static function generateMermaidDiagram(int $quyTrinhId): string
    {
        $db = Database::connection();

        $steps = $db->query("SELECT s.*, dv.ten_viet_tat as ten_don_vi_ngan 
                             FROM quy_trinh_buoc s 
                             LEFT JOIN don_vi dv ON s.don_vi_xu_ly_id = dv.id 
                             WHERE s.quy_trinh_id = {$quyTrinhId} 
                             ORDER BY s.stt_buoc ASC")->fetchAll(PDO::FETCH_ASSOC);

        $transitions = $db->query("SELECT * FROM quy_trinh_chuyen_tiep WHERE quy_trinh_id = {$quyTrinhId}")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($steps)) {
            return "graph TD\n    Empty[Chưa có bước quy trình]";
        }

        $lines = ["graph TD"];

        // Định nghĩa các node
        foreach ($steps as $s) {
            $id = "S" . $s['id'];
            $stt = $s['stt_buoc'];
            $name = addslashes($s['ten_buoc']);
            $role = $s['vai_tro_xu_ly'];
            $hours = $s['thoi_han_gio'] . "h";
            $unit = $s['ten_don_vi_ngan'] ?: 'Nội bộ';

            if ($s['loai_buoc'] === 'TIEP_NHAN') {
                $lines[] = "    {$id}([B{$stt}: {$name}<br/><b>{$unit}</b> - {$role} ({$hours})])";
            } elseif ($s['loai_buoc'] === 'TRA_KET_QUA') {
                $lines[] = "    {$id}([B{$stt}: {$name}<br/><b>{$unit}</b> - {$role} ({$hours})])";
            } elseif ($s['loai_buoc'] === 'LAY_Y_KIEN' || $s['loai_buoc'] === 'THAM_TRA') {
                $lines[] = "    {$id}{{B{$stt}: {$name}<br/><b>{$unit}</b> - {$role} ({$hours})}}";
            } else {
                $lines[] = "    {$id}[B{$stt}: {$name}<br/><b>{$unit}</b> - {$role} ({$hours})]";
            }
        }

        // Định nghĩa các edge luân chuyển
        foreach ($transitions as $tr) {
            $from = "S" . $tr['tu_buoc_id'];
            $to = "S" . $tr['den_buoc_id'];
            $action = addslashes($tr['hanh_dong'] ?? 'Chuyển tiếp');

            if ($action === 'Trả lại') {
                $lines[] = "    {$from} -.->|{$action}| {$to}";
            } else {
                $lines[] = "    {$from} -->|{$action}| {$to}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Tính tổng thời hạn (giờ) của tất cả các bước trong một quy trình
     */
    public static function calculateTotalHours(int $quyTrinhId): int
    {
        $db = Database::connection();
        $stmt = $db->prepare("SELECT SUM(thoi_han_gio) FROM quy_trinh_buoc WHERE quy_trinh_id = :id");
        $stmt->execute([':id' => $quyTrinhId]);
        return (int)$stmt->fetchColumn();
    }
}
