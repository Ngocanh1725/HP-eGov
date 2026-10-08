<?php

declare(strict_types=1);

use App\Controllers\Api\CitizenApiController;
use App\Controllers\Api\LeaderApiController;
use App\Controllers\Api\PublicApiController;
use App\Controllers\Api\WebhookController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Services\HealthCheckService;

// ============================================================================
// REST API V1 - CỔNG KẾT NỐI MỞ HP-eGov HẢI PHÒNG (§1.1, §1.3, §3.3)
// ============================================================================

Router::group(['prefix' => '/api/v1', 'middleware' => ['security_headers', 'rate_limit']], function () {

    // 1. Giám sát sức khỏe hạ tầng (Prometheus / Zabbix / Uptime Kuma)
    Router::get('/health', function (Request $request): Response {
        $status = HealthCheckService::getHealthStatus();
        $httpCode = ($status['status'] === 'UP') ? 200 : 503;
        return Response::json($status, $httpCode);
    })->name('api.health');

    // 2. Tra cứu công khai tối thiểu (Không lộ thông tin nhạy cảm)
    Router::get('/tra-cuu/{ma_ho_so}', [PublicApiController::class, 'traCuu'])->name('api.public.tra_cuu');

    // 3. Danh mục thủ tục hành chính công khai
    Router::get('/thu-tuc-hanh-chinh', [PublicApiController::class, 'danhMucTthc'])->name('api.public.tthc');

    // 4. API Dành cho Ứng dụng Công dân (Yêu cầu Bearer Token có scope 'cong_dan')
    Router::group(['prefix' => '/cong-dan', 'middleware' => ['api_auth:cong_dan']], function () {
        Router::get('/ho-so', [CitizenApiController::class, 'danhSachHoSo'])->name('api.citizen.ho_so.index');
        Router::get('/ho-so/{ma_ho_so}', [CitizenApiController::class, 'chiTietHoSo'])->name('api.citizen.ho_so.detail');
    });

    // 5. API Dành cho Mobile App Lãnh đạo (Yêu cầu Bearer Token có scope 'lanh_dao')
    Router::group(['prefix' => '/lanh-dao', 'middleware' => ['api_auth:lanh_dao']], function () {
        Router::get('/cho-ky', [LeaderApiController::class, 'danhSachChoKy'])->name('api.leader.cho_ky');
        Router::post('/ky-so', [LeaderApiController::class, 'kySo'])->name('api.leader.ky_so');
    });

    // 6. Webhook tích hợp từ nền tảng LGSP / NDXP Hải Phòng (Xác thực chữ ký HMAC-SHA256)
    Router::post('/webhook/lgsp', [WebhookController::class, 'handleLgsp'])->name('api.webhook.lgsp');
});
