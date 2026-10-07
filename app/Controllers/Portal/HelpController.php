<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

/**
 * HelpController – Trang Trợ giúp, Hướng dẫn & Hỏi đáp thường gặp (FAQ)
 */
class HelpController extends Controller
{
    /**
     * Màn hình hỗ trợ & FAQ (GET /tro-giup)
     */
    public function index(Request $request): Response
    {
        return $this->view('portal/tro_giup/index', [
            'pageTitle' => 'Hướng dẫn & Hỏi đáp Dịch vụ công | HP-eGov Hải Phòng',
        ]);
    }
}
