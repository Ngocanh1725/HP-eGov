<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Admin\BaoCaoController;
use App\Controllers\Admin\DashboardController;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * Class DashboardBaoCaoTest – Kiểm thử Tích hợp Dashboard theo vai trò, IOC Center & Báo cáo Thông tư 01
 * Căn cứ: §2, §2.2, §4.3 Báo cáo Kiến trúc & Phân hệ PH08
 */
class DashboardBaoCaoTest
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

    /**
     * Test 1: Dashboard tự động phân luồng theo vai trò người dùng
     */
    public function testDashboardControllerRoleRouting(): void
    {
        $controller = new DashboardController();

        // 1. Giả lập đăng nhập Chuyên viên
        AuthService::attemptCanBo('chuyenvien.hongbang', 'Hpegov@2026');
        $req = new Request();
        $res = $controller->index($req);
        $this->assertEquals(200, $res->getStatusCode(), 'Dashboard Chuyên viên phải trả về HTTP 200');

        // 2. Giả lập đăng nhập Lãnh đạo Phòng
        AuthService::attemptCanBo('ldphong.hongbang', 'Hpegov@2026');
        $res = $controller->index($req);
        $this->assertEquals(200, $res->getStatusCode(), 'Dashboard Lãnh đạo Phòng phải trả về HTTP 200');

        // 3. Giả lập đăng nhập Lãnh đạo Cơ quan
        AuthService::attemptCanBo('ld.hongbang', 'Hpegov@2026');
        $res = $controller->index($req);
        $this->assertEquals(200, $res->getStatusCode(), 'Dashboard Lãnh đạo Cơ quan phải trả về HTTP 200');
    }

    /**
     * Test 2: Màn hình IOC và API JSON tự động làm mới
     */
    public function testIocScreenAndApiStats(): void
    {
        $controller = new DashboardController();
        AuthService::attemptCanBo('ld.hongbang', 'Hpegov@2026');

        // 1. Màn hình IOC giao diện
        $req = new Request(['refresh' => '1']);
        $resIoc = $controller->ioc($req);
        $this->assertEquals(200, $resIoc->getStatusCode(), 'Màn hình IOC phải trả về HTTP 200');

        // 2. API JSON số liệu IOC (cho TV Wall và auto-refresh 60s)
        $resApi = $controller->apiIocStats($req);
        $this->assertEquals(200, $resApi->getStatusCode(), 'API IOC stats phải trả về HTTP 200');

        $body = $resApi->getContent();
        $json = json_decode($body, true);
        $this->assert(is_array($json), 'Nội dung API IOC phải là JSON hợp lệ');
        $this->assert(isset($json['kpi_cards']), 'JSON IOC phải có kpi_cards');
        $this->assert(isset($json['district_map']), 'JSON IOC phải có district_map');
        $this->assert(isset($json['heatmap']), 'JSON IOC phải có heatmap');
        $this->assert(isset($json['emulation']), 'JSON IOC phải có emulation ranking');
        $this->assert(isset($json['bottleneck']), 'JSON IOC phải có bottleneck');
    }

    /**
     * Test 3: API chi tiết Quận/Huyện khi click trên Bản đồ SVG
     */
    public function testDistrictDetailApi(): void
    {
        $controller = new DashboardController();
        AuthService::attemptCanBo('ld.hongbang', 'Hpegov@2026');

        $req = new Request();
        $res = $controller->apiDistrictDetail($req, 10); // Quận Hồng Bàng ID 10
        $this->assertEquals(200, $res->getStatusCode(), 'API District detail phải trả về HTTP 200');

        $json = json_decode($res->getContent(), true);
        $this->assert(isset($json['unit']), 'Kết quả phải có unit');
        $this->assertEquals('000.04.15.H21', $json['unit']['ma_dinh_danh'], 'Mã định danh quận Hồng Bàng phải đúng');
    }

    /**
     * Test 4: Phân hệ Báo cáo (Danh mục, Thông tư 01, In A4)
     */
    public function testBaoCaoControllerViewsAndActions(): void
    {
        $controller = new BaoCaoController();
        AuthService::attemptCanBo('ld.hongbang', 'Hpegov@2026');

        // 1. Danh mục báo cáo
        $req = new Request();
        $resIndex = $controller->index($req);
        $this->assertEquals(200, $resIndex->getStatusCode(), 'Trang danh mục báo cáo phải trả về HTTP 200');

        // 2. Màn hình Báo cáo Thông tư 01/2018
        $resTt01 = $controller->thongTu01($req);
        $this->assertEquals(200, $resTt01->getStatusCode(), 'Báo cáo TT 01 phải trả về HTTP 200');

        // 3. Bản in A4 ngang Thông tư 01
        $reqPrint = new Request(['print' => '1', 'ky' => 'nam_nay']);
        $resPrint = $controller->thongTu01($reqPrint);
        $this->assertEquals(200, $resPrint->getStatusCode(), 'Bản in A4 Thông tư 01 phải trả về HTTP 200');
        $this->assert(str_contains($resPrint->getContent(), 'A4 landscape'), 'Bản in phải chứa định dạng A4 landscape');

        // 4. Các báo cáo chuyên đề
        $resSoHoa = $controller->soHoa($req);
        $this->assertEquals(200, $resSoHoa->getStatusCode(), 'Báo cáo số hóa phải trả về HTTP 200');

        $resQuaHan = $controller->quaHan($req);
        $this->assertEquals(200, $resQuaHan->getStatusCode(), 'Báo cáo quá hạn phải trả về HTTP 200');

        $resHaiLong = $controller->haiLong($req);
        $this->assertEquals(200, $resHaiLong->getStatusCode(), 'Báo cáo hài lòng phải trả về HTTP 200');
    }
}
