<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use Throwable;

/**
 * Class HomeController
 * Xử lý trang chủ Cổng Dịch vụ công Hải Phòng
 */
class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $dbStatus = 'Chưa kiểm tra';
        $dvhcCount = 0;
        $tthcCount = 0;

        try {
            $pdo = Database::getReadPdo();
            $dvhcCount = (int)$pdo->query("SELECT COUNT(*) FROM `dm_dvhc`")->fetchColumn();
            $tthcCount = (int)$pdo->query("SELECT COUNT(*) FROM `thu_tuc_hanh_chinh`")->fetchColumn();
            $dbStatus = 'Kết nối thành công (MariaDB/MySQL InnoDB)';
        } catch (Throwable $e) {
            $dbStatus = 'Lỗi kết nối: ' . $e->getMessage();
        }

        return $this->view('portal/home', [
            'title'     => 'Cổng Dịch vụ công & Một cửa điện tử liên thông TP. Hải Phòng',
            'dbStatus'  => $dbStatus,
            'dvhcCount' => $dvhcCount,
            'tthcCount' => $tthcCount,
        ]);
    }

    public function ping(Request $request): Response
    {
        return $this->json([
            'status'     => 'healthy',
            'app'        => 'HP-eGov',
            'version'    => '1.0.0-dev',
            'timestamp'  => date('Y-m-d H:i:s'),
            'php_version'=> PHP_VERSION,
        ]);
    }
}
