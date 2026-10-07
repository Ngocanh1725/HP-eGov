<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Services\HealthCheckService;

// REST API V1 - Phục vụ Prometheus, Zabbix, Uptime Kuma và các hệ thống giám sát cấp Thành phố
Router::group(['prefix' => '/api/v1', 'middleware' => ['security_headers', 'rate_limit']], function () {
    Router::get('/health', function (Request $request): Response {
        $status = HealthCheckService::getHealthStatus();
        $httpCode = ($status['status'] === 'UP') ? 200 : 503;
        return Response::json($status, $httpCode);
    })->name('api.health');
});
