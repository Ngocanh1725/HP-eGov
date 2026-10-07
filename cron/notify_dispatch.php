<?php

declare(strict_types=1);

/**
 * -----------------------------------------------------------------------------
 * HP-eGov: Worker xử lý hàng đợi thông báo ngoại vi (Outbox Pattern)
 * Quét bảng hang_doi_thong_bao và gửi tin qua SMS Brandname / Zalo ZNS / Email
 * Chạy mỗi 1-5 phút bằng Windows Task Scheduler hoặc CLI
 * Lệnh cài đặt schtasks:
 * schtasks /create /tn "HPeGov_NotifyDispatch" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\HP-eGov\cron\notify_dispatch.php" /sc minute /mo 1 /ru "SYSTEM"
 * -----------------------------------------------------------------------------
 */

$baseDir = dirname(__DIR__);
require_once $baseDir . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register($baseDir);
require_once $baseDir . '/app/Helpers/functions.php';

\App\Core\App::setBasePath($baseDir);
\App\Core\App::boot();

use App\Core\Database;
use App\Integrations\Mock\SmsZnsNotificationMock;

echo "======================================================================\n";
echo " [HP-eGov] KHỞI CHẠY XỬ LÝ HÀNG ĐỢI THÔNG BÁO (OUTBOX DISPATCHER)\n";
echo " Thời điểm chạy: " . date('Y-m-d H:i:s') . "\n";
echo "======================================================================\n";

$db = Database::connection();
$startTime = microtime(true);

// 1. Lấy danh sách tin nhắn cần gửi (Chờ gửi hoặc thất bại dưới 3 lần)
$stmt = $db->query("SELECT * FROM hang_doi_thong_bao 
                    WHERE trang_thai = 'CHO_GUI' 
                       OR (trang_thai = 'THAT_BAI' AND so_lan_thu < 3) 
                    ORDER BY id ASC 
                    LIMIT 100");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($messages);
echo "-> Tìm thấy {$total} tin nhắn cần gửi trong hàng đợi.\n\n";

$successCount = 0;
$failCount = 0;

$updateSuccessStmt = $db->prepare("UPDATE hang_doi_thong_bao 
                                   SET trang_thai = 'THANH_CONG', 
                                       so_lan_thu = so_lan_thu + 1, 
                                       phan_hoi_gateway = :res, 
                                       thoi_gian_gui = NOW() 
                                   WHERE id = :id");

$updateFailStmt = $db->prepare("UPDATE hang_doi_thong_bao 
                                SET trang_thai = 'THAT_BAI', 
                                    so_lan_thu = so_lan_thu + 1, 
                                    phan_hoi_gateway = :res 
                                WHERE id = :id");

foreach ($messages as $msg) {
    $msgId = (int)$msg['id'];
    $kenh = (string)$msg['kenh'];
    $nguoiNhan = (string)$msg['nguoi_nhan'];
    $tieuDe = !empty($msg['tieu_de']) ? (string)$msg['tieu_de'] : null;
    $noiDung = (string)$msg['noi_dung'];
    $hoSoId = !empty($msg['ho_so_id']) ? (int)$msg['ho_so_id'] : null;

    try {
        $result = SmsZnsNotificationMock::dispatch(
            kenh: $kenh,
            nguoiNhan: $nguoiNhan,
            tieuDe: $tieuDe,
            noiDung: $noiDung,
            hoSoId: $hoSoId
        );

        if ($result['success']) {
            $updateSuccessStmt->execute([
                ':res' => $result['response'],
                ':id' => $msgId,
            ]);
            $successCount++;
            echo "  ✔ [ID #{$msgId} - {$kenh}] Gửi tới {$nguoiNhan} thành công (Mã: {$result['msg_id']})\n";
        } else {
            $updateFailStmt->execute([
                ':res' => $result['response'],
                ':id' => $msgId,
            ]);
            $failCount++;
            echo "  ✖ [ID #{$msgId} - {$kenh}] Gửi tới {$nguoiNhan} thất bại\n";
        }
    } catch (\Throwable $e) {
        $updateFailStmt->execute([
            ':res' => json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE),
            ':id' => $msgId,
        ]);
        $failCount++;
        echo "  ✖ [ID #{$msgId} - {$kenh}] Lỗi ngoại lệ: {$e->getMessage()}\n";
    }
}

$elapsed = round(microtime(true) - $startTime, 4);

echo "\n----------------------------------------------------------------------\n";
echo " KẾT QUẢ GỬI THÔNG BÁO:\n";
echo " - Tổng số tin xử lý: {$total}\n";
echo " - Gửi thành công: {$successCount}\n";
echo " - Thất bại / Thử lại: {$failCount}\n";
echo " - Thời gian hoàn thành: {$elapsed} giây\n";
echo "======================================================================\n";
