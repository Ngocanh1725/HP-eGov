<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\CaptchaService;

/**
 * CaptchaController – Xuất ảnh captcha bảo vệ form đăng nhập
 */
class CaptchaController extends Controller
{
    public function render(Request $request): Response
    {
        CaptchaService::renderImage();
        return new Response('');
    }
}
