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

    /**
     * Tìm bước tiếp theo của quy trình dựa vào bước hiện tại và hành động
     *
     * @param int $quyTrinhId
     * @param int|null $buocHienTaiId
     * @param string $hanhDong Mặc định 'CHUYEN'
     * @return array<string, mixed>|null
     */
    public static function getNextStep(int $quyTrinhId, ?int $buocHienTaiId, string $hanhDong = 'CHUYEN'): ?array
    {
        $db = Database::connection();

        if (empty($buocHienTaiId)) {
            // Lấy bước đầu tiên (loại bước TIEP_NHAN hoặc STT nhỏ nhất)
            $stmt = $db->prepare("SELECT * FROM quy_trinh_buoc WHERE quy_trinh_id = :qid ORDER BY stt_buoc ASC LIMIT 1");
            $stmt->execute([':qid' => $quyTrinhId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        // Tìm trong bảng quy_trinh_chuyen_tiep
        $stmt = $db->prepare("SELECT qb.* 
                              FROM quy_trinh_chuyen_tiep ct
                              JOIN quy_trinh_buoc qb ON ct.den_buoc_id = qb.id
                              WHERE ct.quy_trinh_id = :qid AND ct.tu_buoc_id = :bid
                              LIMIT 1");
        $stmt->execute([':qid' => $quyTrinhId, ':bid' => $buocHienTaiId]);
        $nextStep = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$nextStep) {
            // Fallback: Tìm bước có stt_buoc lớn hơn kế tiếp
            $currStmt = $db->prepare("SELECT stt_buoc FROM quy_trinh_buoc WHERE id = :bid");
            $currStmt->execute([':bid' => $buocHienTaiId]);
            $currStt = (int)$currStmt->fetchColumn();

            $nextStmt = $db->prepare("SELECT * FROM quy_trinh_buoc WHERE quy_trinh_id = :qid AND stt_buoc > :stt ORDER BY stt_buoc ASC LIMIT 1");
            $nextStmt->execute([':qid' => $quyTrinhId, ':stt' => $currStt]);
            $nextStep = $nextStmt->fetch(PDO::FETCH_ASSOC);
        }

        return $nextStep ?: null;
    }

    /**
    /**
     * Chuyển tiếp hồ sơ sang bước kế tiếp trong quy trình (State Machine transition)
     * Có kiểm tra: Vai trò, SLA ĐỎ (chặn chuyển bước), Tính toàn vẹn SHA-256 tệp tin, Khóa đồng thời
     *
     * @param int $hoSoId ID hồ sơ
     * @param int $nguoiThucHienId ID cán bộ thực hiện
     * @param int|null $denBuocId ID bước chỉ định (nếu có rẽ nhánh), nếu null thì tự tìm bước kế
     * @param string|null $yKien Ý kiến xử lý
     * @return array{success: bool, message: string, code?: string, trang_thai_moi?: string, buoc_moi_id?: ?int}
     */
    public static function chuyenTiep(
        int $hoSoId,
        int $nguoiThucHienId,
        ?int $denBuocId = null,
        ?string $yKien = null
    ): array {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            // Khóa bi quan bản ghi hồ sơ (Pessimistic Row Lock)
            $stmtHs = $db->prepare("SELECT hs.*, tt.thoi_han_giai_quyet_ngay, tt.ten_tthc 
                                    FROM ho_so hs 
                                    LEFT JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                                    WHERE hs.id = :id AND hs.is_deleted = 0 
                                    FOR UPDATE");
            $stmtHs->execute([':id' => $hoSoId]);
            $hoSo = $stmtHs->fetch(PDO::FETCH_ASSOC);

            if (!$hoSo) {
                $db->rollBack();
                return ['success' => false, 'code' => 'NOT_FOUND', 'message' => 'Không tìm thấy hồ sơ hoặc hồ sơ đã bị hủy.'];
            }

            // 1. Kiểm tra SLA ĐỎ: Chặn chuyển bước thông thường nếu quá hạn mà chưa có văn bản xin lỗi Mẫu 04
            if (($hoSo['sla_mau'] ?? '') === 'DO') {
                $checkVbxl = $db->query("SELECT id FROM van_ban_xin_loi WHERE ho_so_id = {$hoSoId} AND trang_thai = 'DA_KY'")->fetch();
                if (!$checkVbxl) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'code' => 'SLA_BREACH_LOCKED',
                        'message' => 'Hồ sơ đã quá hạn giải quyết (SLA ĐỎ), bị khóa chuyển bước thông thường theo quy định. Bắt buộc Lãnh đạo cơ quan ban hành Giấy xin lỗi Mẫu số 04 trước khi hoàn tất.',
                    ];
                }
            }

            // 2. Kiểm tra tính toàn vẹn của tệp đính kèm (Checksum SHA-256)
            $integrity = self::verifyFileIntegrity($hoSoId);
            if (!$integrity['valid']) {
                $db->rollBack();
                return [
                    'success' => false,
                    'code' => 'FILE_INTEGRITY_FAIL',
                    'message' => 'Phát hiện tệp đính kèm không toàn vẹn hoặc đã bị sửa đổi trái phép (sai khớp mã băm SHA-256).',
                ];
            }

            $quyTrinhId = (int)($hoSo['quy_trinh_id'] ?? 0);
            $buocHienTaiId = !empty($hoSo['buoc_hien_tai_id']) ? (int)$hoSo['buoc_hien_tai_id'] : null;
            $trangThaiTruoc = (string)$hoSo['trang_thai'];

            // 3. Xác định bước tiếp theo
            if ($denBuocId) {
                $nextStmt = $db->prepare("SELECT * FROM quy_trinh_buoc WHERE id = :bid AND quy_trinh_id = :qid");
                $nextStmt->execute([':bid' => $denBuocId, ':qid' => $quyTrinhId]);
                $nextStep = $nextStmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $nextStep = self::getNextStep($quyTrinhId, $buocHienTaiId, 'CHUYEN_TIEP');
            }

            if (!$nextStep) {
                $db->rollBack();
                return ['success' => false, 'code' => 'DEAD_END', 'message' => 'Không tìm thấy bước kế tiếp trong quy trình.'];
            }

            // Quy đổi loại bước sang trạng thái TT 01/2018
            $trangThaiMoi = match ($nextStep['loai_buoc']) {
                'TIEP_NHAN' => 'MOI_TIEP_NHAN',
                'PHAN_CONG', 'THU_LY', 'LAY_Y_KIEN', 'THAM_TRA' => 'DANG_XU_LY',
                'PHE_DUYET', 'DONG_DAU' => 'CHO_PHE_DUYET',
                'TRA_KET_QUA' => 'HOAN_THANH',
                default => 'DANG_XU_LY'
            };

            $donViMoiId = !empty($nextStep['don_vi_xu_ly_id']) ? (int)$nextStep['don_vi_xu_ly_id'] : (int)$hoSo['don_vi_hien_tai_id'];
            $nextStepId = (int)$nextStep['id'];

            // Nếu chuyển liên thông sang cơ quan khác -> reset chuyên viên để lãnh đạo đơn vị mới phân công
            $chuyenVienMoiId = ($donViMoiId !== (int)$hoSo['don_vi_hien_tai_id']) ? null : $hoSo['chuyen_vien_thu_ly_id'];

            // Cập nhật hồ sơ
            $upd = $db->prepare("UPDATE ho_so SET 
                buoc_hien_tai_id = :bid,
                don_vi_hien_tai_id = :dvid,
                chuyen_vien_thu_ly_id = :cvid,
                trang_thai = :st,
                updated_at = NOW()
                WHERE id = :hid");
            $upd->execute([
                ':bid' => $nextStepId,
                ':dvid' => $donViMoiId,
                ':cvid' => $chuyenVienMoiId,
                ':st' => $trangThaiMoi,
                ':hid' => $hoSoId,
            ]);

            // Ghi nhật ký luân chuyển
            $lc = $db->prepare("INSERT INTO ho_so_luan_chuyen (
                ho_so_id, tu_buoc_id, den_buoc_id, tu_don_vi_id, den_don_vi_id, 
                nguoi_chuyen_id, nguoi_nhan_id, hanh_dong, trang_thai_truoc, trang_thai_sau, 
                noi_dung_y_kien, thoi_gian_chuyen
            ) VALUES (
                :hid, :tubid, :denbid, :tudvid, :dendvid,
                :ncid, :nnid, 'CHUYEN_TIEP', :ttt, :tts,
                :yk, NOW()
            )");
            $lc->execute([
                ':hid' => $hoSoId,
                ':tubid' => $buocHienTaiId,
                ':denbid' => $nextStepId,
                ':tudvid' => (int)$hoSo['don_vi_hien_tai_id'],
                ':dendvid' => $donViMoiId,
                ':ncid' => $nguoiThucHienId,
                ':nnid' => $chuyenVienMoiId,
                ':ttt' => $trangThaiTruoc,
                ':tts' => $trangThaiMoi,
                ':yk' => $yKien ?? "Chuyển tiếp xử lý sang bước {$nextStep['ten_buoc']}.",
            ]);

            // Ghi Audit log WORM
            AuditService::log(
                $nguoiThucHienId,
                'WORKFLOW_CHUYEN_TIEP',
                'ho_so',
                $hoSoId,
                ['trang_thai' => $trangThaiTruoc, 'buoc_id' => $buocHienTaiId, 'don_vi_id' => $hoSo['don_vi_hien_tai_id']],
                ['trang_thai' => $trangThaiMoi, 'buoc_id' => $nextStepId, 'don_vi_id' => $donViMoiId]
            );

            // Bắn thông báo in-app cho đơn vị tiếp nhận
            ThongBaoService::gui(
                $chuyenVienMoiId,
                null,
                "Hồ sơ luân chuyển: {$hoSo['ma_ho_so']}",
                "Hồ sơ {$hoSo['ma_ho_so']} đã được chuyển đến bước '{$nextStep['ten_buoc']}'.",
                "/admin/thu-ly/{$hoSoId}",
                'LIEN_THONG'
            );

            $db->commit();

            return [
                'success' => true,
                'message' => "Chuyển bước thành công sang [{$nextStep['ten_buoc']}].",
                'trang_thai_moi' => $trangThaiMoi,
                'buoc_moi_id' => $nextStepId,
                'don_vi_moi_id' => $donViMoiId,
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['success' => false, 'code' => 'ERROR', 'message' => 'Lỗi hệ thống khi chuyển bước: ' . $e->getMessage()];
        }
    }

    /**
     * Trả lại hồ sơ cho bước trước đó để thẩm định lại hoặc bổ sung
     */
    public static function traLai(int $hoSoId, int $nguoiThucHienId, string $lyDo, ?int $veBuocId = null): array
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmtHs = $db->prepare("SELECT * FROM ho_so WHERE id = :id AND is_deleted = 0 FOR UPDATE");
            $stmtHs->execute([':id' => $hoSoId]);
            $hoSo = $stmtHs->fetch(PDO::FETCH_ASSOC);

            if (!$hoSo) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Không tìm thấy hồ sơ.'];
            }

            $buocHienTaiId = !empty($hoSo['buoc_hien_tai_id']) ? (int)$hoSo['buoc_hien_tai_id'] : null;

            // Xác định bước trả về: Lấy bước có STT nhỏ hơn gần nhất hoặc chỉ định
            if (!$veBuocId && $buocHienTaiId) {
                $currStmt = $db->prepare("SELECT stt_buoc FROM quy_trinh_buoc WHERE id = :bid");
                $currStmt->execute([':bid' => $buocHienTaiId]);
                $currStt = (int)$currStmt->fetchColumn();

                $prevStmt = $db->prepare("SELECT * FROM quy_trinh_buoc WHERE quy_trinh_id = :qid AND stt_buoc < :stt ORDER BY stt_buoc DESC LIMIT 1");
                $prevStmt->execute([':qid' => $hoSo['quy_trinh_id'], ':stt' => $currStt]);
                $prevStep = $prevStmt->fetch(PDO::FETCH_ASSOC);
                $veBuocId = $prevStep ? (int)$prevStep['id'] : null;
            }

            $donViVeId = (int)$hoSo['don_vi_hien_tai_id'];
            if ($veBuocId) {
                $bInfo = $db->query("SELECT don_vi_xu_ly_id FROM quy_trinh_buoc WHERE id = {$veBuocId}")->fetch(PDO::FETCH_ASSOC);
                if (!empty($bInfo['don_vi_xu_ly_id'])) {
                    $donViVeId = (int)$bInfo['don_vi_xu_ly_id'];
                }
            }

            $trangThaiTruoc = (string)$hoSo['trang_thai'];
            $trangThaiMoi = 'DANG_XU_LY';

            $db->prepare("UPDATE ho_so SET 
                buoc_hien_tai_id = :bid, 
                don_vi_hien_tai_id = :dvid, 
                trang_thai = :st, 
                updated_at = NOW() 
                WHERE id = :hid")
               ->execute([
                   ':bid' => $veBuocId,
                   ':dvid' => $donViVeId,
                   ':st' => $trangThaiMoi,
                   ':hid' => $hoSoId,
               ]);

            $db->prepare("INSERT INTO ho_so_luan_chuyen (
                ho_so_id, tu_buoc_id, den_buoc_id, tu_don_vi_id, den_don_vi_id, 
                nguoi_chuyen_id, hanh_dong, trang_thai_truoc, trang_thai_sau, 
                noi_dung_y_kien, thoi_gian_chuyen
            ) VALUES (
                :hid, :tubid, :denbid, :tudvid, :dendvid,
                :ncid, 'TRA_LAI', :ttt, :tts, :yk, NOW()
            )")->execute([
                ':hid' => $hoSoId,
                ':tubid' => $buocHienTaiId,
                ':denbid' => $veBuocId,
                ':tudvid' => (int)$hoSo['don_vi_hien_tai_id'],
                ':dendvid' => $donViVeId,
                ':ncid' => $nguoiThucHienId,
                ':ttt' => $trangThaiTruoc,
                ':tts' => $trangThaiMoi,
                ':yk' => "Trả lại hồ sơ: {$lyDo}",
            ]);

            AuditService::log($nguoiThucHienId, 'WORKFLOW_TRA_LAI', 'ho_so', $hoSoId, ['buoc_id' => $buocHienTaiId], ['buoc_id' => $veBuocId, 'ly_do' => $lyDo]);

            $db->commit();
            return ['success' => true, 'message' => 'Đã trả lại hồ sơ thành công.'];
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Lỗi khi trả lại hồ sơ: ' . $e->getMessage()];
        }
    }

    /**
     * Lãnh đạo phòng phân công / chuyển phân công chuyên viên thụ lý
     */
    public static function phanCong(int $hoSoId, int $ldPhongId, int $chuyenVienId, ?string $yKien = null): array
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmtHs = $db->prepare("SELECT * FROM ho_so WHERE id = :id AND is_deleted = 0 FOR UPDATE");
            $stmtHs->execute([':id' => $hoSoId]);
            $hoSo = $stmtHs->fetch(PDO::FETCH_ASSOC);

            if (!$hoSo) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Không tìm thấy hồ sơ.'];
            }

            // Lấy tên chuyên viên
            $cvStmt = $db->prepare("SELECT ho_ten FROM nguoi_dung WHERE id = :id");
            $cvStmt->execute([':id' => $chuyenVienId]);
            $tenChuyenVien = (string)$cvStmt->fetchColumn();

            $db->prepare("UPDATE ho_so SET 
                chuyen_vien_thu_ly_id = :cvid, 
                trang_thai = 'DANG_XU_LY', 
                updated_at = NOW() 
                WHERE id = :hid")
               ->execute([':cvid' => $chuyenVienId, ':hid' => $hoSoId]);

            $db->prepare("INSERT INTO ho_so_luan_chuyen (
                ho_so_id, tu_buoc_id, den_buoc_id, tu_don_vi_id, den_don_vi_id, 
                nguoi_chuyen_id, nguoi_nhan_id, hanh_dong, trang_thai_truoc, trang_thai_sau, 
                noi_dung_y_kien, thoi_gian_chuyen
            ) VALUES (
                :hid, :tubid, :denbid, :tudvid, :dendvid,
                :ncid, :nnid, 'PHAN_CONG', :st, 'DANG_XU_LY', :yk, NOW()
            )")->execute([
                ':hid' => $hoSoId,
                ':tubid' => $hoSo['buoc_hien_tai_id'],
                ':denbid' => $hoSo['buoc_hien_tai_id'],
                ':tudvid' => (int)$hoSo['don_vi_hien_tai_id'],
                ':dendvid' => (int)$hoSo['don_vi_hien_tai_id'],
                ':ncid' => $ldPhongId,
                ':nnid' => $chuyenVienId,
                ':st' => (string)$hoSo['trang_thai'],
                ':yk' => $yKien ?? "Phân công chuyên viên [{$tenChuyenVien}] thụ lý giải quyết.",
            ]);

            AuditService::log($ldPhongId, 'WORKFLOW_PHAN_CONG', 'ho_so', $hoSoId, ['chuyen_vien_cu' => $hoSo['chuyen_vien_thu_ly_id']], ['chuyen_vien_moi' => $chuyenVienId]);

            ThongBaoService::gui(
                $chuyenVienId,
                null,
                "Phân công thụ lý: {$hoSo['ma_ho_so']}",
                "Bạn được phân công thụ lý hồ sơ {$hoSo['ma_ho_so']}. Vui lòng tiếp nhận và xử lý.",
                "/admin/thu-ly/{$hoSoId}",
                'HO_SO'
            );

            $db->commit();
            return ['success' => true, 'message' => "Đã phân công chuyên viên {$tenChuyenVien} thụ lý thành công."];
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Lỗi phân công: ' . $e->getMessage()];
        }
    }

    /**
     * Yêu cầu công dân bổ sung hồ sơ (Tạm dừng tính SLA)
     */
    public static function yeuCauBoSung(int $hoSoId, int $nguoiYeuCauId, string $lyDo, string $danhSachGiayTo, int $hanNgay = 30): array
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmtHs = $db->prepare("SELECT * FROM ho_so WHERE id = :id AND is_deleted = 0 FOR UPDATE");
            $stmtHs->execute([':id' => $hoSoId]);
            $hoSo = $stmtHs->fetch(PDO::FETCH_ASSOC);

            if (!$hoSo) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Không tìm thấy hồ sơ.'];
            }

            $hanBoSung = date('Y-m-d 17:00:00', strtotime("+{$hanNgay} days"));

            // 1. Lưu vào bảng ho_so_bo_sung
            $db->prepare("INSERT INTO ho_so_bo_sung (
                ho_so_id, nguoi_yeu_cau_id, ly_do_bo_sung, danh_sach_giay_to_yeu_cau, han_bo_sung, ngay_yeu_cau, trang_thai
            ) VALUES (
                :hid, :uid, :lydo, :ds, :han, NOW(), 'CHO_BO_SUNG'
            )")->execute([
                ':hid' => $hoSoId,
                ':uid' => $nguoiYeuCauId,
                ':lydo' => $lyDo,
                ':ds' => $danhSachGiayTo,
                ':han' => $hanBoSung,
            ]);

            // 2. Chuyển trạng thái hồ sơ sang CHO_BO_SUNG
            $trangThaiTruoc = (string)$hoSo['trang_thai'];
            $db->prepare("UPDATE ho_so SET trang_thai = 'CHO_BO_SUNG', updated_at = NOW() WHERE id = :id")
               ->execute([':id' => $hoSoId]);

            // 3. Ghi nhật ký luân chuyển
            $db->prepare("INSERT INTO ho_so_luan_chuyen (
                ho_so_id, tu_buoc_id, den_buoc_id, tu_don_vi_id, den_don_vi_id, 
                nguoi_chuyen_id, hanh_dong, trang_thai_truoc, trang_thai_sau, 
                noi_dung_y_kien, thoi_gian_chuyen
            ) VALUES (
                :hid, :tubid, :denbid, :tudvid, :dendvid,
                :ncid, 'YEU_CAU_BO_SUNG', :ttt, 'CHO_BO_SUNG', :yk, NOW()
            )")->execute([
                ':hid' => $hoSoId,
                ':tubid' => $hoSo['buoc_hien_tai_id'],
                ':denbid' => $hoSo['buoc_hien_tai_id'],
                ':tudvid' => (int)$hoSo['don_vi_hien_tai_id'],
                ':dendvid' => (int)$hoSo['don_vi_hien_tai_id'],
                ':ncid' => $nguoiYeuCauId,
                ':ttt' => $trangThaiTruoc,
                ':yk' => "Yêu cầu bổ sung hồ sơ (tạm dừng SLA): {$lyDo}",
            ]);

            AuditService::log($nguoiYeuCauId, 'WORKFLOW_BO_SUNG', 'ho_so', $hoSoId, ['trang_thai' => $trangThaiTruoc], ['trang_thai' => 'CHO_BO_SUNG', 'ly_do' => $lyDo]);

            // Thông báo công dân
            ThongBaoService::gui(
                null,
                (int)$hoSo['cong_dan_id'],
                "Yêu cầu bổ sung hồ sơ: {$hoSo['ma_ho_so']}",
                "Hồ sơ {$hoSo['ma_ho_so']} cần bổ sung giấy tờ: {$lyDo}. Thời hạn bổ sung trước ngày " . date('d/m/Y', strtotime($hanBoSung)),
                "/cong-dan/ho-so/{$hoSo['ma_ho_so']}/bo-sung",
                'HO_SO',
                [
                    'so_dien_thoai' => $hoSo['nguoi_nop_sdt'],
                    'ma_ho_so' => $hoSo['ma_ho_so'],
                    'ho_ten' => $hoSo['nguoi_nop_ho_ten'],
                    'trang_thai' => 'CHO_BO_SUNG',
                ]
            );

            $db->commit();
            return ['success' => true, 'message' => 'Đã gửi yêu cầu bổ sung hồ sơ và tạm dừng tính SLA.'];
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Lỗi yêu cầu bổ sung: ' . $e->getMessage()];
        }
    }

    /**
     * Khôi phục tiếp tục thụ lý sau khi công dân nộp bổ sung
     */
    public static function tiepTucSauBoSung(int $hoSoId, int $nguoiThucHienId, ?string $ghiChu = null): array
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmtHs = $db->prepare("SELECT * FROM ho_so WHERE id = :id AND is_deleted = 0 FOR UPDATE");
            $stmtHs->execute([':id' => $hoSoId]);
            $hoSo = $stmtHs->fetch(PDO::FETCH_ASSOC);

            if (!$hoSo || $hoSo['trang_thai'] !== 'CHO_BO_SUNG') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Hồ sơ không ở trạng thái chờ bổ sung.'];
            }

            // Tính thời gian tạm dừng
            $bsStmt = $db->prepare("SELECT * FROM ho_so_bo_sung WHERE ho_so_id = :hid AND trang_thai = 'CHO_BO_SUNG' ORDER BY id DESC LIMIT 1");
            $bsStmt->execute([':hid' => $hoSoId]);
            $bs = $bsStmt->fetch(PDO::FETCH_ASSOC);

            $gioTamDungThem = 0.0;
            if ($bs && !empty($bs['ngay_yeu_cau'])) {
                $sec = time() - strtotime($bs['ngay_yeu_cau']);
                $gioTamDungThem = round($sec / 3600, 2);
                $db->prepare("UPDATE ho_so_bo_sung SET trang_thai = 'DA_BO_SUNG', ngay_nop_bo_sung = NOW() WHERE id = :id")
                   ->execute([':id' => $bs['id']]);
            }

            // Cập nhật lại trạng thái DANG_XU_LY và cộng dồn số giờ tạm dừng
            $tongGioTamDung = (float)($hoSo['so_gio_tam_dung'] ?? 0) + $gioTamDungThem;
            $db->prepare("UPDATE ho_so SET 
                trang_thai = 'DANG_XU_LY', 
                so_gio_tam_dung = :gtd, 
                updated_at = NOW() 
                WHERE id = :hid")
               ->execute([':gtd' => $tongGioTamDung, ':hid' => $hoSoId]);

            $db->prepare("INSERT INTO ho_so_luan_chuyen (
                ho_so_id, tu_buoc_id, den_buoc_id, tu_don_vi_id, den_don_vi_id, 
                nguoi_chuyen_id, hanh_dong, trang_thai_truoc, trang_thai_sau, 
                noi_dung_y_kien, thoi_gian_chuyen
            ) VALUES (
                :hid, :tubid, :denbid, :tudvid, :dendvid,
                :ncid, 'TIEP_TUC_XU_LY', 'CHO_BO_SUNG', 'DANG_XU_LY', :yk, NOW()
            )")->execute([
                ':hid' => $hoSoId,
                ':tubid' => $hoSo['buoc_hien_tai_id'],
                ':denbid' => $hoSo['buoc_hien_tai_id'],
                ':tudvid' => (int)$hoSo['don_vi_hien_tai_id'],
                ':dendvid' => (int)$hoSo['don_vi_hien_tai_id'],
                ':ncid' => $nguoiThucHienId,
                ':yk' => $ghiChu ?? "Tiếp tục giải quyết hồ sơ sau khi công dân bổ sung (Đã tạm dừng: {$gioTamDungThem} giờ).",
            ]);

            $db->commit();
            return ['success' => true, 'message' => 'Hồ sơ đã được khôi phục trạng thái Đang xử lý.'];
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Lỗi khôi phục hồ sơ: ' . $e->getMessage()];
        }
    }

    /**
     * Gửi yêu cầu lấy ý kiến phối hợp liên phòng / liên sở (Liên thông ngang)
     */
    public static function layYKien(int $hoSoId, int $nguoiYeuCauId, int $donViPhoiHopId, string $noiDung, string $hanTraLoi): array
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmtHs = $db->prepare("SELECT * FROM ho_so WHERE id = :id AND is_deleted = 0");
            $stmtHs->execute([':id' => $hoSoId]);
            $hoSo = $stmtHs->fetch(PDO::FETCH_ASSOC);

            if (!$hoSo) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Không tìm thấy hồ sơ.'];
            }

            // Thêm bản ghi vào ho_so_y_kien_phoi_hop
            $db->prepare("INSERT INTO ho_so_y_kien_phoi_hop (
                ho_so_id, don_vi_yeu_cau_id, nguoi_yeu_cau_id, don_vi_phoi_hop_id, noi_dung_yeu_cau, han_tra_loi, trang_thai
            ) VALUES (
                :hid, :dvyc, :uid, :dvph, :nd, :han, 'CHO_Y_KIEN'
            )")->execute([
                ':hid' => $hoSoId,
                ':dvyc' => (int)$hoSo['don_vi_hien_tai_id'],
                ':uid' => $nguoiYeuCauId,
                ':dvph' => $donViPhoiHopId,
                ':nd' => $noiDung,
                ':han' => $hanTraLoi,
            ]);

            $yKienId = (int)$db->lastInsertId();

            // Lấy tên đơn vị phối hợp
            $tenDv = (string)$db->query("SELECT ten_don_vi FROM don_vi WHERE id = {$donViPhoiHopId}")->fetchColumn();

            // Ghi nhật ký luân chuyển
            $db->prepare("INSERT INTO ho_so_luan_chuyen (
                ho_so_id, tu_buoc_id, den_buoc_id, tu_don_vi_id, den_don_vi_id, 
                nguoi_chuyen_id, hanh_dong, trang_thai_truoc, trang_thai_sau, 
                noi_dung_y_kien, thoi_gian_chuyen
            ) VALUES (
                :hid, :tubid, :denbid, :tudv, :dendv,
                :ncid, 'LAY_Y_KIEN', :ttt, :tts, :yk, NOW()
            )")->execute([
                ':hid' => $hoSoId,
                ':tubid' => $hoSo['buoc_hien_tai_id'],
                ':denbid' => $hoSo['buoc_hien_tai_id'],
                ':tudv' => (int)$hoSo['don_vi_hien_tai_id'],
                ':dendv' => $donViPhoiHopId,
                ':ncid' => $nguoiYeuCauId,
                ':ttt' => (string)$hoSo['trang_thai'],
                ':tts' => (string)$hoSo['trang_thai'],
                ':yk' => "Gửi yêu cầu lấy ý kiến phối hợp tới [{$tenDv}]: {$noiDung}",
            ]);

            // Bắn thông báo cho đơn vị phối hợp
            ThongBaoService::gui(
                null,
                null,
                "Yêu cầu phối hợp ý kiến: {$hoSo['ma_ho_so']}",
                "Đơn vị bạn nhận được yêu cầu phối hợp thẩm định hồ sơ {$hoSo['ma_ho_so']}. Hạn trả lời: " . date('d/m/Y', strtotime($hanTraLoi)),
                "/admin/thu-ly/{$hoSoId}",
                'LIEN_THONG'
            );

            $db->commit();
            return ['success' => true, 'y_kien_id' => $yKienId, 'message' => "Đã gửi yêu cầu lấy ý kiến phối hợp tới {$tenDv}."];
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Lỗi gửi yêu cầu ý kiến: ' . $e->getMessage()];
        }
    }

    /**
     * Đơn vị phối hợp trả lời ý kiến
     */
    public static function traLoiYKien(int $yKienId, int $nguoiTraLoiId, string $noiDungTraLoi, ?int $fileDinhKemId = null): array
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmtYk = $db->prepare("SELECT * FROM ho_so_y_kien_phoi_hop WHERE id = :id");
            $stmtYk->execute([':id' => $yKienId]);
            $yk = $stmtYk->fetch(PDO::FETCH_ASSOC);

            if (!$yk) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Không tìm thấy yêu cầu ý kiến phối hợp.'];
            }

            $db->prepare("UPDATE ho_so_y_kien_phoi_hop SET 
                nguoi_tra_loi_id = :uid, 
                noi_dung_tra_loi = :nd, 
                ngay_tra_loi = NOW(), 
                trang_thai = 'DA_TRA_LOI' 
                WHERE id = :id")
               ->execute([
                   ':uid' => $nguoiTraLoiId,
                   ':nd' => $noiDungTraLoi,
                   ':id' => $yKienId,
               ]);

            // Ghi nhật ký luân chuyển
            $db->prepare("INSERT INTO ho_so_luan_chuyen (
                ho_so_id, tu_don_vi_id, den_don_vi_id, nguoi_chuyen_id, nguoi_nhan_id, 
                hanh_dong, trang_thai_truoc, trang_thai_sau, noi_dung_y_kien, thoi_gian_chuyen
            ) VALUES (
                :hid, :tudv, :dendv, :ncid, :nnid, 
                'PHAN_HOI_Y_KIEN', 'DANG_XU_LY', 'DANG_XU_LY', :yk, NOW()
            )")->execute([
                ':hid' => $yk['ho_so_id'],
                ':tudv' => $yk['don_vi_phoi_hop_id'],
                ':dendv' => $yk['don_vi_yeu_cau_id'],
                ':ncid' => $nguoiTraLoiId,
                ':nnid' => $yk['nguoi_yeu_cau_id'],
                ':yk' => "Phản hồi ý kiến phối hợp: {$noiDungTraLoi}",
            ]);

            // Bắn thông báo cho người yêu cầu ý kiến
            ThongBaoService::gui(
                (int)$yk['nguoi_yeu_cau_id'],
                null,
                "Có phản hồi ý kiến phối hợp",
                "Đơn vị phối hợp đã gửi văn bản trả lời ý kiến cho hồ sơ.",
                "/admin/thu-ly/{$yk['ho_so_id']}",
                'LIEN_THONG'
            );

            $db->commit();
            return ['success' => true, 'message' => 'Đã gửi phản hồi ý kiến phối hợp thành công.'];
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Lỗi gửi phản hồi: ' . $e->getMessage()];
        }
    }

    /**
     * Thu hồi hồ sơ đã chuyển (trước khi người nhận ở bước tiếp theo mở xử lý)
     */
    public static function thuHoi(int $hoSoId, int $nguoiThuHoiId, string $lyDo): array
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmtHs = $db->prepare("SELECT * FROM ho_so WHERE id = :id AND is_deleted = 0 FOR UPDATE");
            $stmtHs->execute([':id' => $hoSoId]);
            $hoSo = $stmtHs->fetch(PDO::FETCH_ASSOC);

            if (!$hoSo) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Không tìm thấy hồ sơ.'];
            }

            // Lấy lượt luân chuyển gần nhất
            $lcStmt = $db->prepare("SELECT * FROM ho_so_luan_chuyen WHERE ho_so_id = :hid ORDER BY id DESC LIMIT 1");
            $lcStmt->execute([':hid' => $hoSoId]);
            $lastLc = $lcStmt->fetch(PDO::FETCH_ASSOC);

            if (!$lastLc || (int)$lastLc['nguoi_chuyen_id'] !== $nguoiThuHoiId) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Bạn không phải là người thực hiện chuyển bước gần nhất để có quyền thu hồi.'];
            }

            // Thu hồi: hoàn trả về bước trước đó
            $buocThuHoiVeId = $lastLc['tu_buoc_id'];
            $donViThuHoiVeId = $lastLc['tu_don_vi_id'];

            $db->prepare("UPDATE ho_so SET 
                buoc_hien_tai_id = :bid, 
                don_vi_hien_tai_id = :dvid, 
                chuyen_vien_thu_ly_id = :uid,
                updated_at = NOW() 
                WHERE id = :hid")
               ->execute([
                   ':bid' => $buocThuHoiVeId,
                   ':dvid' => $donViThuHoiVeId,
                   ':uid' => $nguoiThuHoiId,
                   ':hid' => $hoSoId,
               ]);

            $db->prepare("INSERT INTO ho_so_luan_chuyen (
                ho_so_id, tu_buoc_id, den_buoc_id, tu_don_vi_id, den_don_vi_id, 
                nguoi_chuyen_id, hanh_dong, trang_thai_truoc, trang_thai_sau, 
                noi_dung_y_kien, thoi_gian_chuyen
            ) VALUES (
                :hid, :tubid, :denbid, :tudvid, :dendvid,
                :ncid, 'THU_HOI', :ttt, :tts, :yk, NOW()
            )")->execute([
                ':hid' => $hoSoId,
                ':tubid' => $hoSo['buoc_hien_tai_id'],
                ':denbid' => $buocThuHoiVeId,
                ':tudvid' => (int)$hoSo['don_vi_hien_tai_id'],
                ':dendvid' => $donViThuHoiVeId,
                ':ncid' => $nguoiThuHoiId,
                ':ttt' => (string)$hoSo['trang_thai'],
                ':tts' => (string)$hoSo['trang_thai'],
                ':yk' => "Thu hồi hồ sơ đã chuyển: {$lyDo}",
            ]);

            $db->commit();
            return ['success' => true, 'message' => 'Đã thu hồi hồ sơ thành công.'];
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Lỗi thu hồi hồ sơ: ' . $e->getMessage()];
        }
    }

    /**
     * Kiểm tra tính toàn vẹn của toàn bộ tệp tin đính kèm trong hồ sơ (Đối soát SHA-256)
     *
     * @param int $hoSoId ID hồ sơ
     * @return array{valid: bool, errors: array<string>}
     */
    public static function verifyFileIntegrity(int $hoSoId): array
    {
        $db = Database::connection();
        $files = $db->query("SELECT id, ten_goc, duong_dan, sha256 FROM ho_so_tai_lieu WHERE ho_so_id = {$hoSoId}")->fetchAll(PDO::FETCH_ASSOC);

        $errors = [];
        foreach ($files as $file) {
            $filePath = (string)$file['duong_dan'];
            if (file_exists($filePath)) {
                $currentSha = hash_file('sha256', $filePath);
                if ($currentSha !== $file['sha256']) {
                    $errors[] = "Tệp [{$file['ten_goc']}] bị sai lệch mã băm SHA-256 (Gốc: {$file['sha256']}, Hiện tại: {$currentSha}).";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Alias giữ tương thích cho các module gọi transition() cũ
     */
    public static function transition(
        int $hoSoId,
        int $nguoiThucHienId,
        string $hanhDong = 'CHUYEN',
        ?string $yKien = null
    ): array {
        return self::chuyenTiep($hoSoId, $nguoiThucHienId, null, $yKien);
    }
}

