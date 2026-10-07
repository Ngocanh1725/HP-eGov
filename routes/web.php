<?php

declare(strict_types=1);

use App\Controllers\Portal\HomeController;
use App\Core\Router;

// Tuyến đường trang chủ và kiểm tra hệ thống
Router::get('/', [HomeController::class, 'index'])->name('home');
Router::get('/ping', [HomeController::class, 'ping'])->name('ping');

// Nhóm Cổng Dịch vụ công cho công dân
Router::group(['prefix' => '/cong-dan', 'middleware' => ['security_headers']], function () {
    Router::get('/', [HomeController::class, 'index'])->name('portal.home');
});

// Nhóm Khu vực Cán bộ / Quản trị Một cửa
Router::group(['prefix' => '/admin', 'middleware' => ['security_headers']], function () {
    Router::get('/', function () {
        return \App\Core\Response::redirect(url('/admin/dang-nhap'));
    });
    
    Router::get('/dang-nhap', function () {
        return \App\Core\Response::html("<h1>Đăng nhập cán bộ HP-eGov</h1><p>Phân hệ xác thực sẽ được triển khai chi tiết tại Phiên 5.</p>");
    })->name('admin.login');
});
