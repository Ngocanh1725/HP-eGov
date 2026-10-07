<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Integrations\Mock\SmsZnsNotificationMock;
use PDO;
use Throwable;

/**
 * Class ThongBaoService – Dịch vụ phát thông báo đa kênh trong hệ thống HP-eGov
 * Hỗ trợ: Thông báo in-app cho cán bộ/công dân, SMS Brandname & Zalo ZNS
 */
class ThongBaoService
{
    /**
     * Gửi thông báo in-app và đẩy SMS/Zalo mock
     *
     * @param int|null $nguoiNhanId ID cán bộ nhận (nếu có)
     * @param int|null $congDanId ID công dân nhận (nếu có)
     * @param string $tieuDe Tiêu đề thông báo
     * @param string $noiDung Nội dung chi tiết
     * @param string|null $lienKet Liên kết chuyển tiếp
     * @param string $loai Loại thông báo (HE_THONG, HO_SO, LIEN_THONG, SLA)
     * @param array<string, mixed> $extraData Dữ liệu kèm theo để gửi SMS/Zalo
     * @return int ID thông báo vừa tạo
     */
    public static function gui(
        ?int $nguoiNhanId,
        ?int $congDanId,
        string $tieuDe,
        string $noiDung,
        ?string $lienKet = null,
        string $loai = 'HE_THONG',
        array $extraData = []
    ): int {
        $db = Database::connection();

        $stmt = $db->prepare("INSERT INTO thong_bao (
            nguoi_nhan_id, cong_dan_id, tieu_de, noi_dung, lien_ket, loai, da_doc, created_at
        ) VALUES (
            :uid, :cid, :title, :content, :link, :loai, 0, NOW()
        )");

        $stmt->execute([
            ':uid' => $nguoiNhanId,
            ':cid' => $congDanId,
            ':title' => $tieuDe,
            ':content' => $noiDung,
            ':link' => $lienKet,
            ':loai' => $loai,
        ]);

        $thongBaoId = (int)$db->lastInsertId();

        // Nếu có số điện thoại công dân hoặc cán bộ, đưa vào hàng đợi SMS/Zalo mock
        if (!empty($extraData['so_dien_thoai']) && !empty($extraData['ma_ho_so'])) {
            try {
                SmsZnsNotificationMock::guiThongBaoHoSo(
                    (string)$extraData['ma_ho_so'],
                    (string)$extraData['so_dien_thoai'],
                    (string)($extraData['ho_ten'] ?? 'Công dân'),
                    (string)($extraData['trang_thai'] ?? $loai),
                    $noiDung
                );
            } catch (Throwable $e) {
                // Log lỗi thông báo nền, không làm gián đoạn transaction chính
                error_log("Lỗi gửi thông báo SMS/Zalo: " . $e->getMessage());
            }
        }

        return $thongBaoId;
    }
}
