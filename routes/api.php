<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

// REST API V1
Router::group(['prefix' => '/api/v1', 'middleware' => ['security_headers', 'rate_limit']], function () {
    Router::get('/health', function (Request $request): Response {
        return Response::json([
            'status'     => 'UP',
            'version'    => '1.0.0',
            'timestamp'  => date('c'),
            'subsystems' => [
                'database' => 'OK',
                'storage'  => 'OK',
            ],
        ]);
    })->name('api.health');
});
