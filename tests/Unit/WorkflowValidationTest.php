<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Integrations\Mock\CsdlTthcQuocGiaMock;
use App\Services\MdmService;
use App\Services\WorkflowEngine;
use PDO;
use TestRunner;

/**
 * WorkflowValidationTest – Kiểm thử đơn vị Động cơ Quy trình (BPMN-lite) & Danh mục dùng chung
 */
class WorkflowValidationTest
{
    private PDO $db;

    public function setUp(): void
    {
        $this->db = Database::connection();
    }

    /**
     * Kiểm tra thẩm định quy trình chuẩn trong CSDL (có điểm đầu/cuối và không có bước mồ côi)
     */
    public function testValidateStandardWorkflow(): void
    {
        // Lấy quy trình đầu tiên có trong CSDL (được seed từ Phiên 2)
        $quyTrinhId = (int)$this->db->query("SELECT id FROM quy_trinh ORDER BY id ASC LIMIT 1")->fetchColumn();
        TestRunner::assertGreaterThan(0, $quyTrinhId, "Phải có ít nhất 1 quy trình được khởi tạo trong CSDL.");

        $result = WorkflowEngine::validateWorkflow($quyTrinhId);

        TestRunner::assertTrue(is_array($result), "Kết quả thẩm định quy trình phải là mảng.");
        TestRunner::assertTrue($result['has_start'], "Quy trình phải có bước bắt đầu TIEP_NHAN.");
        TestRunner::assertTrue($result['has_end'], "Quy trình phải có bước kết thúc TRA_KET_QUA.");
        TestRunner::assertGreaterThan(0, $result['step_count'], "Quy trình phải có nhiều hơn 0 bước.");
        TestRunner::assertGreaterThan(0, $result['total_hours'], "Tổng thời hạn các bước phải lớn hơn 0 giờ.");
    }

    /**
     * Kiểm tra tính tổng thời hạn SLA của các bước trong quy trình
     */
    public function testCalculateTotalHours(): void
    {
        $quyTrinhId = (int)$this->db->query("SELECT id FROM quy_trinh ORDER BY id ASC LIMIT 1")->fetchColumn();
        
        $calculatedHours = WorkflowEngine::calculateTotalHours($quyTrinhId);

        // Đối chiếu với tổng trong bảng quy_trinh_buoc
        $sumFromDb = (int)$this->db->query("SELECT SUM(thoi_han_gio) FROM quy_trinh_buoc WHERE quy_trinh_id = {$quyTrinhId}")->fetchColumn();

        TestRunner::assertEquals($sumFromDb, $calculatedHours, "Tổng số giờ từ WorkflowEngine phải khớp với SUM trong CSDL.");
    }

    /**
     * Kiểm tra phát hiện quy trình không hợp lệ khi thiếu bước bắt đầu hoặc kết thúc
     */
    public function testValidationDetectsMissingStartOrEnd(): void
    {
        $this->db->beginTransaction();
        try {
            // Tạo quy trình giả lập chỉ có 1 bước THU_LY (không có TIEP_NHAN, không có TRA_KET_QUA)
            $this->db->exec("INSERT INTO quy_trinh (tthc_id, ten_quy_trinh, phien_ban, tong_thoi_han_gio, is_active, created_at) 
                             VALUES (1, 'Quy trình thử nghiệm thiếu đầu cuối', 99, 8, 1, NOW())");
            $mockQtId = (int)$this->db->lastInsertId();

            $this->db->exec("INSERT INTO quy_trinh_buoc (quy_trinh_id, stt_buoc, ten_buoc, loai_buoc, vai_tro_xu_ly, don_vi_xu_ly_id, thoi_han_gio, cho_phep_tra_lai) 
                             VALUES ({$mockQtId}, 1, 'Thụ lý chuyên môn độc lập', 'THU_LY', 'CHUYEN_VIEN', 1, 8, 0)");

            $validation = WorkflowEngine::validateWorkflow($mockQtId);

            TestRunner::assertFalse($validation['valid'], "Quy trình thiếu đầu cuối phải không hợp lệ (valid = false).");
            TestRunner::assertFalse($validation['has_start'], "has_start phải là false.");
            TestRunner::assertFalse($validation['has_end'], "has_end phải là false.");

            // Kiểm tra thông báo lỗi
            $errText = implode(' ', $validation['errors']);
            TestRunner::assertContains('TIEP_NHAN', $errText, "Lỗi phải đề cập đến thiếu TIEP_NHAN.");
            TestRunner::assertContains('TRA_KET_QUA', $errText, "Lỗi phải đề cập đến thiếu TRA_KET_QUA.");
        } finally {
            $this->db->rollBack();
        }
    }

    /**
     * Kiểm tra sinh sơ đồ Mermaid trực quan
     */
    public function testGenerateMermaidDiagram(): void
    {
        $quyTrinhId = (int)$this->db->query("SELECT id FROM quy_trinh ORDER BY id ASC LIMIT 1")->fetchColumn();
        $mermaid = WorkflowEngine::generateMermaidDiagram($quyTrinhId);

        TestRunner::assertTrue(is_string($mermaid), "Mã Mermaid phải là chuỗi.");
        TestRunner::assertContains('graph TD', $mermaid, "Mã Mermaid phải bắt đầu bằng cú pháp graph TD.");
        TestRunner::assertContains('-->', $mermaid, "Mã Mermaid phải chứa mũi tên luồng liên kết.");
    }

    /**
     * Kiểm tra Adapter CSDL TTHC Quốc gia trả về đầy đủ các danh mục đồng bộ
     */
    public function testCsdlTthcQuocGiaMockSync(): void
    {
        $adapter = new CsdlTthcQuocGiaMock();
        $data = $adapter->sync();

        TestRunner::assertTrue(isset($data['added']), "Kết quả đồng bộ phải có mảng added.");
        TestRunner::assertTrue(isset($data['updated']), "Kết quả đồng bộ phải có mảng updated.");
        TestRunner::assertTrue(isset($data['deprecated']), "Kết quả đồng bộ phải có mảng deprecated.");

        TestRunner::assertGreaterThan(0, count($data['added']), "Phải có ít nhất 1 TTHC mới được trả về.");
        TestRunner::assertGreaterThan(0, count($data['updated']), "Phải có ít nhất 1 TTHC cập nhật.");
        TestRunner::assertGreaterThan(0, count($data['deprecated']), "Phải có ít nhất 1 TTHC bãi bỏ.");
    }

    /**
     * Kiểm tra xuất file CSV UTF-8 có tiền tố BOM (Byte Order Mark) chuẩn cho Excel tiếng Việt
     */
    public function testMdmCsvExportBomUtf8(): void
    {
        $headers = ['Mã ĐVHC', 'Tên ĐVHC', 'Cấp'];
        $rows = [
            ['T031', 'Thành phố Hải Phòng', 'TINH'],
            ['H303', 'Quận Hồng Bàng', 'HUYEN'],
        ];

        $response = MdmService::exportCsv('dvhc_test.csv', $headers, $rows);
        $csv = $response->getContent();

        // Kiểm tra tiền tố BOM: \xEF\xBB\xBF
        $bom = "\xEF\xBB\xBF";
        TestRunner::assertTrue(str_starts_with($csv, $bom), "File CSV phải bắt đầu bằng UTF-8 BOM byte sequence.");
        TestRunner::assertContains('Thành phố Hải Phòng', $csv, "File CSV phải chứa nội dung tiếng Việt có dấu chuẩn xác.");
    }
}
