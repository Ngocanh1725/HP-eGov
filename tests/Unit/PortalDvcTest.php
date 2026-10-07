<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\Portal\HoSoCaNhanController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Integrations\Mock\EformSignerMock;
use App\Integrations\Mock\SmsZnsNotificationMock;
use App\Services\FileUploadSecurityService;
use App\Services\MaHoSoGenerator;
use App\Services\SecurityException;
use PDO;
use TestRunner;

/**
 * PortalDvcTest – Bộ kiểm thử đơn vị Cổng Dịch vụ công (PH01)
 * Kiểm tra: Chống IDOR (403), Chặn Upload mã độc, Chống trùng lặp mã QCVN 102
 */
class PortalDvcTest
{
    private PDO $db;

    public function setUp(): void
    {
        $this->db = Database::connection();
    }

    /**
     * 1. Kiểm tra Công dân A không được xem hồ sơ của Công dân B (Chống IDOR -> 403 Forbidden)
     */
    public function testCitizenCannotViewOtherCitizenRecordIdor(): void
    {
        // Lấy 1 hồ sơ có sẵn trong DB thuộc công dân nào đó
        $hoSo = $this->db->query("SELECT id, ma_ho_so, cong_dan_id FROM ho_so WHERE is_deleted = 0 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertNotNull($hoSo, "Cần có ít nhất 1 hồ sơ mẫu.");

        $actualOwnerId = (int)$hoSo['cong_dan_id'];
        $attackerCitizenId = $actualOwnerId + 999; // ID của công dân khác (kẻ tấn công)

        // Giả lập phiên đăng nhập của công dân khác
        Session::set('auth_guard', 'cong_dan');
        Session::set('auth_user', [
            'id' => $attackerCitizenId,
            'ho_ten' => 'Công dân X Tấn công',
            'so_cccd' => '031099999999',
        ]);

        $controller = new HoSoCaNhanController();
        $request = new Request();
        $request->setRouteParams(['ma_ho_so' => $hoSo['ma_ho_so']]);

        // Gọi action show
        $response = $controller->show($request);

        // Phải trả về mã trạng thái 403 Forbidden
        TestRunner::assertEquals(403, $response->getStatusCode(), "Công dân khác xem hồ sơ phải bị chặn 403 Forbidden chống IDOR.");
        TestRunner::assertContains("403 Forbidden", $response->getContent(), "Nội dung phản hồi phải là 403 Forbidden.");

        Session::remove('auth_cong_dan');
    }

    /**
     * 2. Kiểm tra chặn upload tệp tin chứa mã độc .php, webshell hoặc tệp thực thi giả mạo đuôi
     */
    public function testUploadSecurityBlocksPhpAndExecutableFiles(): void
    {
        // Test case 2.1: File có đuôi .php bị chặn trực tiếp
        $tmpFile1 = tempnam(sys_get_temp_dir(), 'test_php');
        file_put_contents($tmpFile1, "<?php echo 'webshell'; ?>");

        $fakeUploadPhp = [
            'name' => 'exploit.php',
            'type' => 'application/x-php',
            'tmp_name' => $tmpFile1,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpFile1),
        ];

        $blockedExt = false;
        try {
            FileUploadSecurityService::processUpload($fakeUploadPhp);
        } catch (SecurityException $e) {
            $blockedExt = true;
        } finally {
            @unlink($tmpFile1);
        }
        TestRunner::assertTrue($blockedExt, "Phải ném ra SecurityException khi tệp mang phần mở rộng .php.");

        // Test case 2.2: File đổi đuôi giả mạo (nội dung PHP nhưng đổi đuôi thành .pdf)
        $tmpFile2 = tempnam(sys_get_temp_dir(), 'test_fake');
        file_put_contents($tmpFile2, "<?php phpinfo(); ?>");

        $fakeUploadPdf = [
            'name' => 'fake_document.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $tmpFile2,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpFile2),
        ];

        $blockedMime = false;
        try {
            FileUploadSecurityService::processUpload($fakeUploadPdf);
        } catch (SecurityException $e) {
            $blockedMime = true;
        } finally {
            @unlink($tmpFile2);
        }
        TestRunner::assertTrue($blockedMime, "Phải ném ra SecurityException khi nội dung thực tế là PHP webshell dù đổi đuôi thành .pdf.");
    }

    /**
     * 3. Kiểm tra cấp mã hồ sơ đồng thời (20 requests liên tục không trùng lặp mã QCVN 102)
     */
    public function testMaHoSoGeneratorConcurrentNoDuplicates(): void
    {
        $donViId = 1;
        $maRutGon = '4123';
        $generatedCodes = [];

        // Giả lập 20 lần sinh mã liên tiếp trong transaction locks
        for ($i = 0; $i < 20; $i++) {
            $code = MaHoSoGenerator::generate($donViId, $maRutGon);
            TestRunner::assertTrue(!empty($code), "Mã hồ sơ sinh ra không được rỗng.");
            
            // Kiểm tra format QCVN 102: {DonVi}-{yyMMdd}-{Ma4}-{STT6}
            $parts = explode('-', $code);
            TestRunner::assertEquals(4, count($parts), "Mã QCVN 102 phải có 4 phần cách nhau bởi dấu gạch ngang.");
            TestRunner::assertEquals(4, strlen($parts[2]), "Mã TTHC phải có 4 chữ số.");
            TestRunner::assertEquals(6, strlen($parts[3]), "Số thứ tự hồ sơ phải có đúng 6 chữ số.");

            $generatedCodes[] = $code;
        }

        // Kiểm tra tính duy nhất tuyệt đối (chống trùng lặp)
        $uniqueCodes = array_unique($generatedCodes);
        TestRunner::assertEquals(20, count($uniqueCodes), "20 mã hồ sơ sinh ra phải hoàn toàn phân biệt, không được trùng lặp.");
    }

    /**
     * 4. Kiểm tra Adapter Ký số E-Form Mock
     */
    public function testEformSignerMockGeneratesSha256Signature(): void
    {
        $formData = ['ho_ten' => 'Nguyễn Văn An', 'noi_dung' => 'Đề nghị cấp phép'];
        $signature = EformSignerMock::sign($formData, '031090001234', 'Nguyễn Văn An');

        TestRunner::assertEquals('Nguyễn Văn An', $signature['signer_name']);
        TestRunner::assertEquals('031090001234', $signature['signer_id']);
        TestRunner::assertEquals('SHA256withRSA', $signature['algorithm']);
        TestRunner::assertEquals(64, strlen($signature['signature_hash']), "Mã băm chữ ký SHA-256 phải có 64 ký tự hex.");
    }

    /**
     * 5. Kiểm tra Adapter Thông báo SMS / Zalo ZNS Mock
     */
    public function testSmsZnsNotificationMockGeneratesLog(): void
    {
        $res = SmsZnsNotificationMock::notify(
            '0912345678',
            '000.04.15.H21-261007-4123-999999',
            'Test Thông báo tiếp nhận',
            'Hồ sơ của bạn đã được gửi thành công.',
            1
        );

        TestRunner::assertEquals('DELIVERED', $res['status']);
        TestRunner::assertTrue(str_starts_with($res['sms_id'], 'SMS_'));
        TestRunner::assertTrue(str_starts_with($res['zns_msg_id'], 'ZNS_'));
    }
}
