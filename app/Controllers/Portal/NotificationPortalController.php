<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

/**
 * NotificationPortalController – Trung tâm thông báo của Công dân
 */
class NotificationPortalController extends Controller
{
    /**
     * Danh sách thông báo (GET /cong-dan/thong-bao)
     */
    public function index(Request $request): Response
    {
        $user = auth('cong_dan');
        $db = Database::connection();

        $stmt = $db->prepare("SELECT * FROM thong_bao WHERE cong_dan_id = :cid ORDER BY id DESC LIMIT 50");
        $stmt->execute([':cid' => $user['id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Đánh dấu tất cả là đã đọc
        $db->prepare("UPDATE thong_bao SET da_doc = 1 WHERE cong_dan_id = :cid")->execute([':cid' => $user['id']]);

        return $this->view('portal/thong_bao/index', [
            'pageTitle' => 'Trung tâm Thông báo | Cổng DVC Hải Phòng',
            'notifications' => $notifications,
            'currentUser' => $user,
        ]);
    }
}
