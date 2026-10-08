<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\Request;
use App\Services\AuditService;
use App\Services\ChuKySoService;
use App\Services\ApiTokenService;
use App\Controllers\Api\WebhookController;
use App\Integrations\Mock\VneidGatewayMock;
use App\Integrations\Mock\LgspGatewayMock;
use App\Integrations\Mock\SmsZnsNotificationMock;
use App\Integrations\Mock\BuuChinhGatewayMock;
use DomainException;
use PDO;
use TestRunner;

/**
 * Class Phien4KySoWormApiTest
 * Bộ kiểm thử chuyên biệt Phiên 4: Ký số OpenSSL, WORM Hash Chain, Dịch vụ số & REST API Webhook
 */
class Phien4KySoWormApiTest
{
    /**
     * 1. Test Ký số OpenSSL RSA 2048-bit, đọc lại thành công và phát hiện can thiệp dù chỉ 1 byte
     */
    public function testOpenSslKySoVaPhatHienCanThiep1Byte(): void
    {
        $db = Database::connection();

        // Lấy ID cán bộ Một cửa (motcua.hongbang)
        $stmt = $db->query("SELECT id FROM nguoi_dung WHERE ten_dang_nhap = 'motcua.hongbang' LIMIT 1");
        $userId = (int)$stmt->fetchColumn();
        if ($userId <= 0) {
            $userId = 2; // fallback user một cửa
        }

        // Tạo tệp tin văn bản mẫu trong storage/cache
        $testDir = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        $testFile = $testDir . '/tai_lieu_kiem_thu_ky_so_phien4.pdf';
        $originalContent = "%PDF-1.4\n1 0 obj\n<< /Title (Tai lieu cong vu Hai Phong) >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF";
        file_put_contents($testFile, $originalContent);

        // Ký số tài liệu bằng OpenSSL RSA 2048-bit
        $signResult = ChuKySoService::signDocument($userId, $testFile, 'SO_HOA_MOT_CUA', null, [
            'ghi_chu' => 'Kiểm thử số hóa đầu vào Phiên 4'
        ]);

        TestRunner::assertGreaterThan(0, $signResult['id']);
        TestRunner::assertEquals(64, strlen($signResult['sha256']));
        TestRunner::assertTrue(file_exists($testFile . '.sig'), "Tệp chữ ký .sig phải được tạo song song với tệp gốc.");

        // Xác thực chữ ký khi tệp nguyên vẹn
        $verifyGood = ChuKySoService::verifyDocumentSignature($testFile);
        TestRunner::assertTrue($verifyGood['valid'], "Xác thực chữ ký tài liệu ban đầu phải thành công 100%.");
        TestRunner::assertTrue($verifyGood['file_intact'], "Tính toàn vẹn tệp tin ban đầu phải nguyên vẹn.");
        TestRunner::assertTrue($verifyGood['sig_valid'], "Chữ ký mật mã OpenSSL phải khớp.");
        TestRunner::assertNull($verifyGood['error'], "Không được có thông báo lỗi khi tệp hợp lệ.");

        // SỬA ĐỔI ĐÚNG 1 BYTE TỆP TIN (Thêm 1 byte khoảng trắng ở cuối hoặc đổi 1 ký tự)
        $tamperedContent = $originalContent . " ";
        file_put_contents($testFile, $tamperedContent);

        // Xác thực lại: Hệ thống BẮT BUỘC phát hiện can thiệp và đánh dấu mất tính toàn vẹn
        $verifyTampered = ChuKySoService::verifyDocumentSignature($testFile);
        TestRunner::assertFalse($verifyTampered['valid'], "Tài liệu bị sửa 1 byte phải bị từ chối xác thực ngay lập tức.");
        TestRunner::assertFalse($verifyTampered['file_intact'], "Tính toàn vẹn tệp tin phải bị đánh dấu false.");
        TestRunner::assertFalse(empty($verifyTampered['error']), "Phải có thông báo lỗi cảnh báo tệp bị can thiệp trái phép.");

        // Khôi phục lại nội dung gốc và kiểm tra xác thực thành công trở lại
        file_put_contents($testFile, $originalContent);
        $verifyRestored = ChuKySoService::verifyDocumentSignature($testFile);
        TestRunner::assertTrue($verifyRestored['valid'], "Sau khi khôi phục nội dung gốc, xác thực phải hợp lệ trở lại.");

        // Dọn dẹp tệp thử nghiệm
        @unlink($testFile);
        @unlink($testFile . '.sig');
    }

    /**
     * 2. Test 4 loại chữ ký công vụ và chặn đứng SYSADMIN can thiệp ký số (Nguyên tắc PoLP)
     */
    public function testBonLoaiChuKySoVaChanSysadmin(): void
    {
        $db = Database::connection();
        $testDir = dirname(__DIR__, 2) . '/storage/cache';
        $testFile = $testDir . '/test_4_signatures.txt';
        file_put_contents($testFile, "Van ban kiem thu 4 loai chu ky so HP-eGov " . time());

        // Lấy cán bộ từng vai trò
        $cbMotCua = (int)$db->query("SELECT u.id FROM nguoi_dung u JOIN nguoi_dung_vai_tro nv ON u.id = nv.nguoi_dung_id JOIN vai_tro v ON nv.vai_tro_id = v.id WHERE v.ma_vai_tro = 'CB_MOT_CUA' LIMIT 1")->fetchColumn();
        $ldPhong = (int)$db->query("SELECT u.id FROM nguoi_dung u JOIN nguoi_dung_vai_tro nv ON u.id = nv.nguoi_dung_id JOIN vai_tro v ON nv.vai_tro_id = v.id WHERE v.ma_vai_tro = 'LD_PHONG' LIMIT 1")->fetchColumn();
        $ldCoQuan = (int)$db->query("SELECT u.id FROM nguoi_dung u JOIN nguoi_dung_vai_tro nv ON u.id = nv.nguoi_dung_id JOIN vai_tro v ON nv.vai_tro_id = v.id WHERE v.ma_vai_tro = 'LD_CO_QUAN' LIMIT 1")->fetchColumn();
        $vanThu = (int)$db->query("SELECT u.id FROM nguoi_dung u JOIN nguoi_dung_vai_tro nv ON u.id = nv.nguoi_dung_id JOIN vai_tro v ON nv.vai_tro_id = v.id WHERE v.ma_vai_tro = 'VAN_THU' LIMIT 1")->fetchColumn();
        $sysAdmin = (int)$db->query("SELECT u.id FROM nguoi_dung u JOIN nguoi_dung_vai_tro nv ON u.id = nv.nguoi_dung_id JOIN vai_tro v ON nv.vai_tro_id = v.id WHERE v.ma_vai_tro = 'SYSADMIN' LIMIT 1")->fetchColumn();

        // 1. Số hóa Một cửa: SO_HOA_MOT_CUA
        $res1 = ChuKySoService::signDocument($cbMotCua, $testFile, 'SO_HOA_MOT_CUA');
        TestRunner::assertGreaterThan(0, $res1['id']);

        // 2. Ký duyệt bước 1 của Lãnh đạo Phòng: KY_DUYET_LD_PHONG
        $res2 = ChuKySoService::signDocument($ldPhong, $testFile, 'KY_DUYET_LD_PHONG');
        TestRunner::assertGreaterThan(0, $res2['id']);

        // 3. Ký phát hành SmartCA của Lãnh đạo Cơ quan: KY_PHAT_HANH_LD_CO_QUAN
        $res3 = ChuKySoService::signDocument($ldCoQuan, $testFile, 'KY_PHAT_HANH_LD_CO_QUAN', null, ['pin' => '123456']);
        TestRunner::assertGreaterThan(0, $res3['id']);

        // 4. Đóng dấu số cơ quan của Văn thư: DONG_DAU_SO_CO_QUAN
        $res4 = ChuKySoService::signDocument($vanThu, $testFile, 'DONG_DAU_SO_CO_QUAN');
        TestRunner::assertGreaterThan(0, $res4['id']);

        // 5. Chặn đứng SYSADMIN: Quản trị viên hệ thống tuyệt đối không có quyền ký duyệt nghiệp vụ
        $sysadminBlocked = false;
        try {
            ChuKySoService::signDocument($sysAdmin, $testFile, 'KY_PHAT_HANH_LD_CO_QUAN');
        } catch (DomainException $e) {
            $sysadminBlocked = true;
            TestRunner::assertTrue(str_contains($e->getMessage(), 'SYSADMIN'), "Thông báo chặn phải nêu rõ quyền hạn SYSADMIN.");
        }
        TestRunner::assertTrue($sysadminBlocked, "Quản trị viên SYSADMIN phải bị chặn ký số 100%.");

        @unlink($testFile);
        @unlink($testFile . '.sig');
    }

    /**
     * 3. Test Kiểm tra toàn vẹn chuỗi băm WORM Hash Chain với 50 bản ghi mẫu và phát hiện chính xác bản ghi bị can thiệp
     */
    public function testWormHashChainToanVen50BanGhiVaPhatHienCanThiep(): void
    {
        $db = Database::connection();

        // 1. Kiểm tra chuỗi băm hiện tại trong CSDL
        $initialVerify = AuditService::verifyChain(1000);
        TestRunner::assertTrue($initialVerify['valid'], "Chuỗi băm ban đầu trong CSDL phải toàn vẹn 100%.");

        // 2. Tạo 10 bản ghi kiểm toán mẫu nối tiếp để kiểm tra tính toàn vẹn chuỗi mở rộng
        $createdIds = [];
        for ($i = 1; $i <= 10; $i++) {
            $id = AuditService::log(
                2,
                "TEST_ACTION_{$i}",
                'ho_so',
                1000 + $i,
                ['buoc' => $i],
                ['buoc' => $i + 1, 'ghi_chu' => "Nội dung kiểm toán mắt xích số {$i}"]
            );
            TestRunner::assertGreaterThan(0, $id);
            $createdIds[] = $id;
        }

        // Kiểm tra sau khi chèn 10 bản ghi, chuỗi băm vẫn toàn vẹn
        $verifyAfterInsert = AuditService::verifyChain(1000);
        TestRunner::assertTrue($verifyAfterInsert['valid'], "Sau khi chèn bản ghi mới, chuỗi băm WORM phải toàn vẹn.");

        // 3. Giả lập kẻ xấu can thiệp trực tiếp vào CSDL để sửa dữ liệu quá khứ của 1 bản ghi
        $targetId = $createdIds[4]; // Bản ghi thứ 5 trong loạt tạo mới

        // Lưu lại dữ liệu cũ để sau đó khôi phục
        $stmtTarget = $db->prepare("SELECT * FROM nhat_ky_kiem_toan WHERE id = :id");
        $stmtTarget->execute([':id' => $targetId]);
        $targetRow = $stmtTarget->fetch(PDO::FETCH_ASSOC);

        try {
            // Tạm thời drop trigger để giả lập hacker có quyền can thiệp CSDL
            $db->exec("DROP TRIGGER IF EXISTS `tg_audit_prevent_update`");

            // Sửa nội dung bản ghi mục tiêu (can thiệp dữ liệu nhưng không thể sửa được hash liên hoàn phía sau)
            $tamperedData = 'DỮ LIỆU ĐÃ BỊ HACKER CAN THIỆP SỬA TRỰC TIẾP TRONG DATABASE!';
            $db->prepare("UPDATE nhat_ky_kiem_toan SET du_lieu_moi = :tampered WHERE id = :id")
               ->execute([':tampered' => $tamperedData, ':id' => $targetId]);

            // Chạy hàm verifyChain: BẮT BUỘC phát hiện can thiệp và chỉ rõ chính xác ID bị đứt gãy
            $verifyTampered = AuditService::verifyChain(1000);
            TestRunner::assertFalse($verifyTampered['valid'], "Hệ thống phải lập tức phát hiện chuỗi băm bị phá vỡ.");
            TestRunner::assertEquals($targetId, $verifyTampered['broken_at_id'], "Phải chỉ rõ chính xác bản ghi ID {$targetId} bị can thiệp!");
            TestRunner::assertTrue(!empty($verifyTampered['reason']), "Phải nêu rõ lý do can thiệp dữ liệu.");
        } finally {
            // 4. Luôn luôn khôi phục lại bản ghi về nguyên trạng và tạo lại trigger trong finally block
            $db->exec("DROP TRIGGER IF EXISTS `tg_audit_prevent_update`");
            $db->prepare("UPDATE nhat_ky_kiem_toan SET du_lieu_moi = :orig WHERE id = :id")
               ->execute([':orig' => $targetRow['du_lieu_moi'], ':id' => $targetId]);
            $db->exec("
                CREATE TRIGGER `tg_audit_prevent_update`
                BEFORE UPDATE ON `nhat_ky_kiem_toan`
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'LỖI BẢO MẬT: Bảng nhat_ky_kiem_toan là kho WORM bất biến, nghiêm cấm chỉnh sửa dữ liệu!';
                END
            ");
        }

        // Xác thực lại sau khi khôi phục: Chuỗi băm phải hợp lệ 100%
        $verifyClean = AuditService::verifyChain(1000);
        TestRunner::assertTrue($verifyClean['valid'], "Sau khi khôi phục dữ liệu gốc, chuỗi băm phải toàn vẹn trở lại.");
    }

    /**
     * 4. Test REST API /api/v1 với Bearer Token (token hợp lệ, sai hash, hết hạn, phân tách scopes)
     */
    public function testRestApiBearerTokenValidation(): void
    {
        $tokenService = new ApiTokenService();

        // 1. Tạo Token với scope 'cong_dan'
        $token1 = $tokenService->createToken(null, 1, 'Token Test Cong Dan', 'cong_dan', 30);
        TestRunner::assertGreaterThan(0, $token1['token_id']);
        TestRunner::assertEquals(64, strlen($token1['raw_token']));

        // Xác thực Token 1 thành công
        $verified1 = $tokenService->verifyToken($token1['raw_token']);
        TestRunner::assertTrue($verified1 !== null, "Token hợp lệ phải được verify thành công.");
        TestRunner::assertEquals($token1['token_id'], (int)$verified1['id']);
        TestRunner::assertTrue($tokenService->hasScope($verified1, 'cong_dan'), "Token 1 phải có scope 'cong_dan'.");
        TestRunner::assertFalse($tokenService->hasScope($verified1, 'lanh_dao'), "Token 1 không được có scope 'lanh_dao'.");

        // 2. Tạo Token với scope 'lanh_dao'
        $token2 = $tokenService->createToken(2, null, 'Token Test Lanh Dao', 'lanh_dao', 15);
        $verified2 = $tokenService->verifyToken($token2['raw_token']);
        TestRunner::assertTrue($verified2 !== null);
        TestRunner::assertTrue($tokenService->hasScope($verified2, 'lanh_dao'));

        // 3. Test với Token giả mạo / sai lệch hash
        $fakeToken = bin2hex(random_bytes(32));
        $verifiedFake = $tokenService->verifyToken($fakeToken);
        TestRunner::assertNull($verifiedFake, "Token ngẫu nhiên không tồn tại trong CSDL phải trả về null.");

        // 4. Test Token rỗng
        $verifiedEmpty = $tokenService->verifyToken('');
        TestRunner::assertNull($verifiedEmpty);

        // 5. Test Token đã hết hạn
        $db = Database::connection();
        $expiredToken = bin2hex(random_bytes(32));
        $expiredHash = hash('sha256', $expiredToken);
        $db->prepare("INSERT INTO api_tokens (token_hash, ten_token, scopes, expires_at, trang_thai, created_at)
                      VALUES (:h, 'Expired Token', '*', DATE_SUB(NOW(), INTERVAL 1 DAY), 1, NOW())")
           ->execute([':h' => $expiredHash]);

        $verifiedExpired = $tokenService->verifyToken($expiredToken);
        TestRunner::assertNull($verifiedExpired, "Token đã hết hạn không được phép xác thực thành công.");
    }

    /**
     * 5. Test Webhook tiếp nhận kết quả thanh toán trực tuyến & sinh biên lai điện tử
     */
    public function testWebhookThanhToanCapNhatBienLaiDienTu(): void
    {
        $db = Database::connection();

        // Lấy một hồ sơ thực tế trong CSDL
        $stmt = $db->query("SELECT * FROM ho_so WHERE is_deleted = 0 LIMIT 1");
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertTrue(!empty($hoSo), "Cần có ít nhất 1 hồ sơ mẫu để kiểm thử thanh toán.");

        $maHoSo = $hoSo['ma_ho_so'];
        $maGiaoDich = 'VNPAY-' . date('ymd') . '-' . bin2hex(random_bytes(3));
        $soTien = 150000.0;

        $webhookController = new WebhookController();

        // Chuẩn bị payload thanh toán
        $payload = [
            'ma_ho_so'             => $maHoSo,
            'ma_giao_dich'         => $maGiaoDich,
            'so_tien'              => $soTien,
            'cong_thanh_toan'      => 'VNPAY',
            'trang_thai'           => 'SUCCESS',
            'thoi_gian_thanh_toan' => date('Y-m-d H:i:s'),
        ];
        $rawJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // 1. Test chữ ký HMAC hợp lệ
        $secretKey = 'payment_hmac_secret_sha256_hp_egov';
        $validSignature = hash_hmac('sha256', (string)$rawJson, $secretKey);

        $requestValid = new Request([], $payload, [
            'HTTP_X_PAYMENT_SIGNATURE' => $validSignature,
            'CONTENT_TYPE'             => 'application/json',
        ]);

        $response = $webhookController->handlePayment($requestValid);
        $resData = json_decode($response->getContent(), true);

        TestRunner::assertEquals(200, $response->getStatusCode(), "Webhook với chữ ký hợp lệ phải trả về HTTP 200.");
        TestRunner::assertEquals('success', $resData['status'] ?? '');
        TestRunner::assertEquals('DA_THANH_TOAN', $resData['data']['trang_thai_thanh_toan'] ?? '');

        // Kiểm tra CSDL: eform của hồ sơ đã cập nhật thông tin biên lai
        $checkHs = $db->prepare("SELECT du_lieu_eform FROM ho_so WHERE id = :id");
        $checkHs->execute([':id' => $hoSo['id']]);
        $updatedEform = json_decode((string)$checkHs->fetchColumn(), true);

        TestRunner::assertTrue(isset($updatedEform['thong_tin_thanh_toan']));
        TestRunner::assertEquals($maGiaoDich, $updatedEform['thong_tin_thanh_toan']['ma_giao_dich']);

        // Kiểm tra tài liệu biên lai điện tử được chèn vào ho_so_tai_lieu
        $checkTl = $db->prepare("SELECT * FROM ho_so_tai_lieu WHERE ho_so_id = :hs_id AND ten_luu LIKE :prefix");
        $checkTl->execute([':hs_id' => $hoSo['id'], ':prefix' => "bien_lai_{$maGiaoDich}%"]);
        $receiptDoc = $checkTl->fetch(PDO::FETCH_ASSOC);

        TestRunner::assertTrue(!empty($receiptDoc), "Phải có bản ghi biên lai điện tử trong ho_so_tai_lieu.");
        TestRunner::assertEquals(64, strlen($receiptDoc['sha256']), "Mã băm biên lai phải là SHA-256.");

        // 2. Test chữ ký HMAC sai lệch -> Bị từ chối HTTP 403
        $requestInvalid = new Request([], $payload, [
            'HTTP_X_PAYMENT_SIGNATURE' => 'invalid_signature_hmac_fake',
            'CONTENT_TYPE'             => 'application/json',
        ]);
        $responseInvalid = $webhookController->handlePayment($requestInvalid);
        TestRunner::assertEquals(403, $responseInvalid->getStatusCode(), "Webhook chữ ký giả mạo phải bị trả về HTTP 403.");

        // 3. Test thiếu chữ ký -> Bị từ chối HTTP 401
        $requestMissing = new Request([], $payload, []);
        $responseMissing = $webhookController->handlePayment($requestMissing);
        TestRunner::assertEquals(401, $responseMissing->getStatusCode(), "Webhook thiếu chữ ký phải bị trả về HTTP 401.");
    }

    /**
     * 6. Test các Adapter mô phỏng dịch vụ số: VNeID Mức 2, C06, SMS Brandname / ZNS, VNPost
     */
    public function testAdaptersDichVuSo(): void
    {
        // 1. VNeID Mức 2 Adapter
        $vneidResult = VneidGatewayMock::authenticateLevel2('031088001234');
        TestRunner::assertTrue($vneidResult['success'], "Xác thực VNeID Mức 2 phải thành công.");
        TestRunner::assertEquals(2, $vneidResult['muc_do_dinh_danh']);
        TestRunner::assertTrue(!empty($vneidResult['data']['vneid_sub']));
        TestRunner::assertTrue($vneidResult['data']['xac_thuc_c06']);

        // Test CCCD không hợp lệ
        $vneidFail = VneidGatewayMock::authenticateLevel2('1234');
        TestRunner::assertFalse($vneidFail['success'], "Số CCCD sai định dạng phải thất bại.");

        // 2. C06 LGSP Gateway Adapter
        $c06Result = LgspGatewayMock::verifyCitizen('031088001234', 'NGUYỄN VĂN HẢI');
        TestRunner::assertTrue($c06Result['success'], "Đối soát C06 qua LGSP phải thành công.");
        TestRunner::assertEquals('C06_MATCH_SUCCESS', $c06Result['code']);

        // 3. SMS Brandname / Zalo ZNS Adapter
        $notifyResult = SmsZnsNotificationMock::notify('0912345678', '000.04.15.H21-TEST', 'Thông báo tiếp nhận', 'Hồ sơ đã được tiếp nhận.');
        TestRunner::assertEquals('DELIVERED', $notifyResult['status']);
        TestRunner::assertTrue(str_starts_with($notifyResult['sms_id'], 'SMS_'));
        TestRunner::assertTrue(str_starts_with($notifyResult['zns_msg_id'], 'ZNS_'));

        // 4. Bưu chính công ích VNPost Adapter
        $shipmentResult = BuuChinhGatewayMock::createShipment('000.04.15.H21-TEST', 'Nguyễn Văn Hải', '15 Lê Đại Hành, Hồng Bàng, Hải Phòng', '0912345678');
        TestRunner::assertTrue($shipmentResult['success'], "Tạo đơn chuyển phát bưu chính công ích phải thành công.");
        TestRunner::assertTrue(str_starts_with($shipmentResult['ma_van_don'], 'HP'));
        TestRunner::assertTrue(str_ends_with($shipmentResult['ma_van_don'], 'VN'));
        TestRunner::assertEquals('DA_TIEP_NHAN_BUU_CUC', $shipmentResult['trang_thai_van_don']);
    }
}
