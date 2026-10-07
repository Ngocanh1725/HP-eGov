<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

/**
 * Class UiKitController
 * Hiển thị trang Thư viện giao diện (UI Kit) trình diễn toàn bộ Design System của HP-eGov
 */
class UiKitController extends Controller
{
    public function index(Request $request): Response
    {
        $breadcrumbs = [
            ['title' => 'Quản trị hệ thống', 'url' => '/admin'],
            ['title' => 'Thư viện UI Kit', 'url' => ''],
        ];

        return $this->view('admin/ui_kit', [
            'title'       => 'Thư viện Thành phần Giao diện (UI Kit)',
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
