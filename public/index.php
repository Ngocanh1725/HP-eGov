<?php

declare(strict_types=1);

/**
 * -----------------------------------------------------------------------------
 * HP-eGov: Hệ thống Một cửa điện tử liên thông TP. Hải Phòng
 * Front Controller duy nhất tiếp nhận toàn bộ HTTP Requests
 * -----------------------------------------------------------------------------
 */

define('HPEGOV_START', microtime(true));

// Nạp bộ khởi động ứng dụng
require_once __DIR__ . '/../app/bootstrap.php';

// Thực thi vòng đời ứng dụng
\App\Core\App::run();
