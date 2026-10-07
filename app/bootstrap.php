<?php

declare(strict_types=1);

// Xác định thư mục gốc của dự án
$baseDir = dirname(__DIR__);

// 1. Nạp Autoloader PSR-4 thuần
require_once __DIR__ . '/Core/Autoloader.php';
\App\Core\Autoloader::register($baseDir);

// 2. Nạp các hàm tiện ích toàn cục
require_once __DIR__ . '/Helpers/functions.php';

// 3. Thiết lập đường dẫn gốc cho ứng dụng
\App\Core\App::setBasePath($baseDir);

// Khởi động các dịch vụ lõi
\App\Core\App::boot();
