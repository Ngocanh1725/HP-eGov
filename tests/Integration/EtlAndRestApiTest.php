<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Services\ApiTokenService;
use App\Services\EtlService;
use App\Services\IntegrationService;
use PDO;

/**
 * Class EtlAndRestApiTest
 * Kiểm thử Tích hợp Phân hệ Chuyển đổi dữ liệu ETL, Quản trị Tích hợp & REST API /api/v1 (Phiên 14/16)
 * (§1.1, §1.3.A, §2.3, §3.3)
 */
class EtlAndRestApiTest
{
    private function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException("FAILED: {$message}");
        }
    }

    private function assertEquals(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException("FAILED: {$message} (Kỳ vọng: " . var_export($expected, true) . ", Thực tế: " . var_export($actual, true) . ")");
        }
    }

    public function setUp(): void
    {
        if (empty(Router::getRoutes())) {
            Router::clear();
            require dirname(__DIR__, 2) . '/routes/web.php';
            require dirname(__DIR__, 2) . '/routes/api.php';
        }
    }

    /**
     * Test 1: Luồng ETL 4 bước với dữ liệu bẩn mẫu MS SQL Server cũ
     */
    public function testEtlPipelineWithDirtyData(): void
    {
        $etl = new EtlService();
        $sampleDir = dirname(__DIR__, 2) . '/database/etl_samples';
        $csvPath = $sampleDir . '/sample_mssql_cu.csv';
        $attachmentsDir = $sampleDir . '/attachments';

        $this->assert(file_exists($csvPath), 'Tệp CSV mẫu phải tồn tại');

        // BƯỚC 1: EXTRACT
        $extractRes = $etl->extractCsv($csvPath, 'Test Lô MS SQL Cũ ' . time(), 'MSSQL_CU');
        $this->assert($extractRes['lo_id'] > 0, 'Lo ID phải được tạo');
        $this->assertEquals(15, $extractRes['tong_so'], 'Tổng số bản ghi trích xuất phải là 15');
        $loId = $extractRes['lo_id'];

        // BƯỚC 2: PROFILING
        $profileRes = $etl->profileStaging($loId);
        $this->assertEquals(15, $profileRes['tong_so'], 'Tổng bản ghi staging');
        $this->assert($profileRes['so_loi'] > 0, 'Phải phát hiện các bản ghi có dữ liệu bẩn');
        $this->assert($profileRes['chi_tiet_loi']['TRUNG_LAP'] >= 1, 'Phải phát hiện hồ sơ trùng mã');
        $this->assert($profileRes['chi_tiet_loi']['THIEU_CCCD'] >= 1, 'Phải phát hiện hồ sơ thiếu CCCD');
        $this->assert($profileRes['chi_tiet_loi']['MA_TTHC_SAI'] >= 1, 'Phải phát hiện mã TTHC chưa ánh xạ');
        $this->assert($profileRes['chi_tiet_loi']['MA_DVHC_SAI'] >= 1, 'Phải phát hiện mã ĐVHC chưa ánh xạ');
        $this->assert($profileRes['chi_tiet_loi']['DU_LIEU_RAC'] >= 1, 'Phải phát hiện chuỗi XSS injection');

        // BƯỚC 3: TRANSFORM
        $transformRes = $etl->transformData($loId);
        $this->assert($transformRes['so_chuyen_doi'] > 0, 'Số bản ghi hợp lệ được chuẩn hóa phải lớn hơn 0');
        $this->assert($transformRes['so_tap_tin_can_pdf_a'] > 0, 'Phải đánh dấu các tệp cần chuyển đổi PDF/A-1b');

        // BƯỚC 4: LOAD & VERIFY
        $loadRes = $etl->loadAndVerify($loId, $attachmentsDir, 50);
        $this->assert($loadRes['so_thanh_cong'] > 0, 'Số bản ghi nạp thành công vào CSDL lõi phải lớn hơn 0');
        $this->assert($loadRes['doi_soat_file']['khop_sha256'] > 0, 'Phải có tệp khớp 100% SHA-256');
        $this->assert($loadRes['doi_soat_file']['sai_sha256'] >= 1, 'Phải phát hiện tệp có checksum SHA-256 giả mạo');
        $this->assert($loadRes['doi_soat_file']['thieu_file'] >= 1, 'Phải phát hiện tệp đính kèm bị thất lạc');

        // BÁO CÁO LỖI
        $errors = $etl->getErrorsReport($loId);
        $this->assert(!empty($errors), 'Báo cáo danh sách lỗi không được rỗng');
    }

    /**
     * Test 2: Các API công khai (Public)
     */
    public function testRestApiPublicEndpoints(): void
    {
        // 1. Health check
        $reqHealth = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/health', 'HTTP_ACCEPT' => 'application/json']);
        $resHealth = Router::dispatch($reqHealth);
        $this->assertEquals(200, $resHealth->getStatusCode(), 'Health check phải trả về 200');
        $dataHealth = json_decode($resHealth->getContent(), true);
        $this->assertEquals('UP', $dataHealth['status'] ?? '', 'Trạng thái hệ thống phải là UP');

        // 2. Danh mục TTHC
        $reqTthc = new Request(['limit' => 5], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/thu-tuc-hanh-chinh?limit=5', 'HTTP_ACCEPT' => 'application/json']);
        $resTthc = Router::dispatch($reqTthc);
        $this->assertEquals(200, $resTthc->getStatusCode(), 'Danh mục TTHC phải trả về 200');
        $dataTthc = json_decode($resTthc->getContent(), true);
        $this->assertEquals('success', $dataTthc['status'] ?? '', 'Status phải là success');
        $this->assert(isset($dataTthc['data']['items']), 'Phải có danh sách items');

        // 3. Tra cứu công khai tối thiểu
        $db = Database::connection();
        $hs = $db->query("SELECT ma_ho_so FROM ho_so LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $maHs = $hs['ma_ho_so'] ?? 'HP-303-202610-0001';

        $reqTraCuu = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/api/v1/tra-cuu/{$maHs}", 'HTTP_ACCEPT' => 'application/json']);
        $resTraCuu = Router::dispatch($reqTraCuu);
        $this->assertEquals(200, $resTraCuu->getStatusCode(), 'Tra cứu công khai phải trả về 200');
        $dataTraCuu = json_decode($resTraCuu->getContent(), true);
        $this->assert(isset($dataTraCuu['data']['ma_ho_so']), 'Dữ liệu phải có mã hồ sơ');
        $this->assert(!isset($dataTraCuu['data']['so_cccd']), 'Dữ liệu công khai tuyệt đối không được lộ số CCCD');
    }

    /**
     * Test 3: Xác thực Bearer Token thiếu hoặc sai trả 401
     */
    public function testRestApiAuthInvalidOrMissingToken(): void
    {
        // 1. Không gửi header Authorization -> 401
        $reqNoToken = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/cong-dan/ho-so', 'HTTP_ACCEPT' => 'application/json']);
        $resNoToken = Router::dispatch($reqNoToken);
        $this->assertEquals(401, $resNoToken->getStatusCode(), 'Thiếu token phải trả về 401');

        // 2. Token giả mạo / không tồn tại -> 401
        $reqBadToken = new Request([], [], [
            'REQUEST_METHOD'     => 'GET',
            'REQUEST_URI'        => '/api/v1/cong-dan/ho-so',
            'HTTP_ACCEPT'         => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer token_gia_mao_khong_ton_tai_123456',
        ]);
        $resBadToken = Router::dispatch($reqBadToken);
        $this->assertEquals(401, $resBadToken->getStatusCode(), 'Token sai phải trả về 401');
    }

    /**
     * Test 4: Token đúng nhưng sai quyền phạm vi (Scope) trả 403
     */
    public function testRestApiScopeEnforcement(): void
    {
        // Token công dân mẫu: 'hpegov_citizen_token_secret_sample_2026' có scope 'cong_dan'
        // Gọi API lãnh đạo yêu cầu scope 'lanh_dao' -> Phải bị từ chối 403
        $reqWrongScope = new Request([], [], [
            'REQUEST_METHOD'     => 'GET',
            'REQUEST_URI'        => '/api/v1/lanh-dao/cho-ky',
            'HTTP_ACCEPT'         => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer hpegov_citizen_token_secret_sample_2026',
        ]);
        $resWrongScope = Router::dispatch($reqWrongScope);
        $this->assertEquals(403, $resWrongScope->getStatusCode(), 'Token sai scope phải trả về 403 Forbidden');
    }

    /**
     * Test 5: API Công dân lấy danh sách hồ sơ và chi tiết (Kèm kiểm tra chống IDOR)
     */
    public function testRestApiCitizenEndpointsAndIdor(): void
    {
        // 1. Danh sách hồ sơ công dân hợp lệ
        $reqList = new Request([], [], [
            'REQUEST_METHOD'     => 'GET',
            'REQUEST_URI'        => '/api/v1/cong-dan/ho-so',
            'HTTP_ACCEPT'         => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer hpegov_citizen_token_secret_sample_2026',
        ]);
        $resList = Router::dispatch($reqList);
        $this->assertEquals(200, $resList->getStatusCode(), 'Lấy danh sách hồ sơ công dân phải trả về 200');

        // 2. Chống IDOR: Thử xem chi tiết hồ sơ của người khác
        $db = Database::connection();
        $tokenService = new ApiTokenService();
        $tokenInfo = $tokenService->verifyToken('hpegov_citizen_token_secret_sample_2026');
        $myCitizenId = $tokenInfo ? (int)$tokenInfo['cong_dan_id'] : 1;

        // Tìm 1 hồ sơ của người khác
        $otherHs = $db->query("SELECT ma_ho_so FROM ho_so WHERE cong_dan_id != {$myCitizenId} LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        if ($otherHs) {
            $otherMa = $otherHs['ma_ho_so'];
            $reqIdor = new Request([], [], [
                'REQUEST_METHOD'     => 'GET',
                'REQUEST_URI'        => "/api/v1/cong-dan/ho-so/{$otherMa}",
                'HTTP_ACCEPT'         => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer hpegov_citizen_token_secret_sample_2026',
            ]);
            $resIdor = Router::dispatch($reqIdor);
            $this->assertEquals(403, $resIdor->getStatusCode(), 'Truy cập hồ sơ người khác phải bị chặn 403 IDOR');
        }
    }

    /**
     * Test 6: API Lãnh đạo xem danh sách chờ ký & Ký số qua Mobile App
     */
    public function testRestApiLeaderEndpoints(): void
    {
        // 1. Danh sách chờ ký
        $reqLeader = new Request([], [], [
            'REQUEST_METHOD'     => 'GET',
            'REQUEST_URI'        => '/api/v1/lanh-dao/cho-ky',
            'HTTP_ACCEPT'         => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer hpegov_leader_token_secret_sample_2026',
        ]);
        $resLeader = Router::dispatch($reqLeader);
        $this->assertEquals(200, $resLeader->getStatusCode(), 'Mobile App Lãnh đạo phải trả về 200');
        $dataLeader = json_decode($resLeader->getContent(), true);
        $this->assertEquals('success', $dataLeader['status'] ?? '', 'Status phải là success');

        // 2. Ký số qua Mobile App
        $db = Database::connection();
        $hs = $db->query("SELECT id FROM ho_so LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($hs) {
            $hsId = (int)$hs['id'];
            $reqSign = new Request(
                [],
                ['ho_so_id' => $hsId, 'ma_pin_smartca' => '123456', 'y_kien' => 'Duyệt ký số từ test'],
                [
                    'REQUEST_METHOD'     => 'POST',
                    'REQUEST_URI'        => '/api/v1/lanh-dao/ky-so',
                    'HTTP_ACCEPT'         => 'application/json',
                    'HTTP_AUTHORIZATION' => 'Bearer hpegov_leader_token_secret_sample_2026',
                ]
            );
            $resSign = Router::dispatch($reqSign);
            $this->assertEquals(200, $resSign->getStatusCode(), 'Ký số qua Mobile App phải thành công');
        }
    }

    /**
     * Test 7: Webhook LGSP xác thực chữ ký HMAC-SHA256
     */
    public function testWebhookLgspHmacValidation(): void
    {
        $payloadArr = [
            'event'     => 'DONG_BO_KET_QUA_GIAI_QUYET',
            'ma_ho_so'  => 'HP-TEST-LGSP-001',
            'trang_thai'=> 'DA_TRA_KET_QUA',
        ];
        $payloadStr = json_encode($payloadArr, JSON_UNESCAPED_UNICODE);

        $secretKey = 'lgsp_hmac_secret_sha256_hp_connect_webhook';
        $validHmac = hash_hmac('sha256', $payloadStr, $secretKey);
        $invalidHmac = 'sai_chu_ky_hmac_khong_hop_le_1234567890';

        // 1. Thiếu chữ ký HMAC -> 401
        $reqNoSig = new Request([], $payloadArr, [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/api/v1/webhook/lgsp',
            'HTTP_ACCEPT'    => 'application/json',
        ]);
        $resNoSig = Router::dispatch($reqNoSig);
        $this->assertEquals(401, $resNoSig->getStatusCode(), 'Thiếu HMAC phải trả về 401');

        // 2. Chữ ký HMAC sai -> 403
        $reqBadSig = new Request([], $payloadArr, [
            'REQUEST_METHOD'        => 'POST',
            'REQUEST_URI'           => '/api/v1/webhook/lgsp',
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X_LGSP_SIGNATURE' => $invalidHmac,
        ]);
        $resBadSig = Router::dispatch($reqBadSig);
        $this->assertEquals(403, $resBadSig->getStatusCode(), 'Chữ ký HMAC sai phải trả về 403');

        // 3. Chữ ký HMAC đúng -> 200 OK
        $reqGoodSig = new Request([], $payloadArr, [
            'REQUEST_METHOD'        => 'POST',
            'REQUEST_URI'           => '/api/v1/webhook/lgsp',
            'HTTP_ACCEPT'           => 'application/json',
            'HTTP_X_LGSP_SIGNATURE' => $validHmac,
        ]);
        $resGoodSig = Router::dispatch($reqGoodSig);
        $this->assertEquals(200, $resGoodSig->getStatusCode(), 'Chữ ký HMAC đúng phải trả về 200 OK');
    }

    /**
     * Test 8: Quản trị Tích hợp - Kiểm tra kết nối dịch vụ (Ping test)
     */
    public function testIntegrationGatewayPing(): void
    {
        $intService = new IntegrationService();
        $pingResult = $intService->pingService('VNEID');

        $this->assert($pingResult['success'], 'Ping VNeID mock phải thành công');
        $this->assertEquals(200, $pingResult['status_code'], 'Mã phản hồi phải là 200');
        $this->assert($pingResult['latency_ms'] > 0, 'Độ trễ phải lớn hơn 0ms');

        // Kiểm tra nhật ký được ghi vào nhat_ky_tich_hop
        $db = Database::connection();
        $stmt = $db->query("SELECT * FROM nhat_ky_tich_hop WHERE dich_vu = 'VNEID' ORDER BY id DESC LIMIT 1");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assert(!empty($log), 'Nhật ký tích hợp phải ghi nhận giao dịch ping');
    }
}
