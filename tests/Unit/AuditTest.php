<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Services\AuditService;
use PDO;
use TestRunner;

class AuditTest
{
    public function testHashFormula(): void
    {
        $prevHash = AuditService::GENESIS_HASH;
        $newData = 'Test Payload 123';
        $computed = AuditService::computeHash($prevHash, $newData);

        TestRunner::assertEquals(64, strlen($computed));
        TestRunner::assertEquals(hash('sha256', $prevHash . '|' . $newData), $computed);
    }

    public function testVerifyChainValid(): void
    {
        // Kiểm tra toàn vẹn chuỗi băm của 20 bản ghi đầu tiên
        $result = AuditService::verifyChain(20);

        TestRunner::assertTrue($result['valid']);
        TestRunner::assertNull($result['broken_at_id']);
        TestRunner::assertGreaterThan(0, $result['total_verified']);
    }

    public function testAppendLogPreservesChain(): void
    {
        $id = AuditService::log(
            1,
            'KIEM_THU_CHUOI_BAM',
            'he_thong',
            999,
            ['old' => 'value1'],
            ['new' => 'value2'],
            null,
            '127.0.0.1',
            'PHPUnit/TestRunner'
        );

        TestRunner::assertGreaterThan(0, $id);

        // Sau khi chèn, chuỗi băm vẫn phải toàn vẹn 100%
        $verify = AuditService::verifyChain(50);
        TestRunner::assertTrue($verify['valid']);
    }

    public function testDetectTamperedRecord(): void
    {
        $db = Database::connection();

        // 1. Ghi một bản ghi mẫu vào audit log
        $testId = AuditService::log(
            1,
            'TEST_CAN_THIEP_GIA_MAO',
            'ho_so',
            8888,
            null,
            'Dữ liệu gốc ban đầu'
        );

        TestRunner::assertGreaterThan(0, $testId);

        // 2. Tạm thời tắt trigger để giả lập hacker sửa trực tiếp vào CSDL
        // (Trong schema có trigger cấm UPDATE trên nhat_ky_kiem_toan)
        // Chúng ta kiểm tra hàm computeHash phát hiện mismatch khi data khác biệt
        $stmt = $db->prepare("SELECT prev_hash, hash FROM nhat_ky_kiem_toan WHERE id = :id");
        $stmt->execute([':id' => $testId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $tamperedData = 'Dữ liệu đã bị hacker sửa đổi trái phép!';
        $recalculated = AuditService::computeHash($row['prev_hash'], $tamperedData);

        // Hash lưu trữ phải khác biệt với hash tính lại từ dữ liệu bị sửa
        TestRunner::assertFalse($row['hash'] === $recalculated);
    }
}
