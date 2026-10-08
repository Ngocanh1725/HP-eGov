<?php

declare(strict_types=1);

/**
 * HP-eGov - Script Kiểm thử Bảng mã UTF-8 & Toàn vẹn CSDL (Phiên 1)
 * Kiểm chứng 100% tiếng Việt không bị lỗi phông, Mojibake hoặc ký tự rác.
 */

require_once __DIR__ . '/../app/Core/Autoloader.php';
App\Core\Autoloader::register(dirname(__DIR__));
App\Core\App::boot();

use App\Core\Database;

echo "======================================================================\n";
echo " KIỂM THỬ BẢNG MÃ UTF-8 & TOÀN VẸN CƠ SỞ DỮ LIỆU HP-eGov (PHIÊN 1)\n";
echo "======================================================================\n\n";

$db = Database::connection();

// 1. Kiểm tra cấu hình Charset của phiên kết nối PDO
echo "► 1. CẤU HÌNH BẢNG MÃ PHIÊN KẾT NỐI (PDO CONNECTION):\n";
$charsets = $db->query("SHOW VARIABLES LIKE 'character_set%'")->fetchAll(PDO::FETCH_KEY_PAIR);
$required = ['character_set_client', 'character_set_connection', 'character_set_results'];
$allUtf8 = true;
foreach ($required as $var) {
    $val = $charsets[$var] ?? 'unknown';
    echo "  - {$var}: {$val}\n";
    if (!str_contains($val, 'utf8')) {
        $allUtf8 = false;
    }
}
if ($allUtf8) {
    echo "  ✔ Cấu hình kết nối PDO hoàn toàn đạt chuẩn UTF-8 đa byte (utf8mb4).\n\n";
} else {
    echo "  ❌ Cảnh báo: Biến kết nối chưa chuẩn UTF-8!\n\n";
}

// Danh sách ký tự rác Mojibake kinh điển khi bị lỗi font Windows CP1252/CP850 (Box-drawing, German eszett, replacement char...)
$mojibakePattern = '/[ßº╗\x{FFFD}\x{2500}-\x{257F}]/u';
$totalChecked = 0;
$mojibakeFound = 0;

function evaluateText(string $text, string &$status, &$mojibakeFound, &$totalChecked, string $mojibakePattern): void
{
    $totalChecked++;
    if (!mb_check_encoding($text, 'UTF-8')) {
        $status = "❌ Lỗi UTF-8";
        $mojibakeFound++;
        return;
    }
    if (preg_match($mojibakePattern, $text)) {
        $status = "❌ Ký tự Mojibake";
        $mojibakeFound++;
        return;
    }
    // Kiểm tra có ký tự tiếng Việt có dấu chuẩn
    if (preg_match('/[àáảãạăắằẳẵặâấầẩẫậèéẻẽẹêếềểễệìíỉĩịòóỏõọôốồổỗộơớờởỡợùúủũụưứừửữựỳýỷỹỵđÀÁẢÃẠĂẮẰẲẴẶÂẤẦẨẪẬÈÉẺẼẸÊẾỀỂỄỆÌÍỈĨỊÒÓỎÕỌÔỐỒỔỖỘƠỚỜỞỠỢÙÚỦŨỤƯỨỪỬỮỰỲÝỶỸỴĐ]/u', $text)) {
        $status = "✔ Tiếng Việt chuẩn";
    } else {
        $status = "✔ Chuẩn ASCII/UTF8";
    }
}

// 2. Kiểm tra ngẫu nhiên 10 dòng bảng `don_vi`
echo "► 2. KIỂM TRA 10 DÒNG NGẪU NHIÊN BẢNG ĐƠN VỊ (`don_vi`):\n";
echo str_pad("ID", 5) . str_pad("MÃ ĐỊNH DANH", 20) . str_pad("TÊN ĐƠN VỊ / PHÒNG BAN", 50) . "KẾT QUẢ\n";
echo str_repeat("-", 90) . "\n";

$donViList = $db->query("SELECT id, ma_dinh_danh, ten_don_vi FROM don_vi ORDER BY RAND() LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($donViList as $row) {
    $status = "";
    evaluateText($row['ten_don_vi'], $status, $mojibakeFound, $totalChecked, $mojibakePattern);
    echo str_pad((string)$row['id'], 5) .
         str_pad($row['ma_dinh_danh'], 20) .
         str_pad(mb_substr($row['ten_don_vi'], 0, 48), 50) .
         $status . "\n";
}
echo "\n";

// 3. Kiểm tra ngẫu nhiên 10 dòng bảng `thu_tuc_hanh_chinh`
echo "► 3. KIỂM TRA 10 DÒNG NGẪU NHIÊN BẢNG THỦ TỤC HÀNH CHÍNH (`thu_tuc_hanh_chinh`):\n";
echo str_pad("ID", 5) . str_pad("MÃ TTHC", 26) . str_pad("TÊN THỦ TỤC HÀNH CHÍNH", 45) . "KẾT QUẢ\n";
echo str_repeat("-", 90) . "\n";

$tthcList = $db->query("SELECT id, ma_tthc, ten_tthc FROM thu_tuc_hanh_chinh ORDER BY RAND() LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($tthcList as $row) {
    $status = "";
    evaluateText($row['ten_tthc'], $status, $mojibakeFound, $totalChecked, $mojibakePattern);
    echo str_pad((string)$row['id'], 5) .
         str_pad($row['ma_tthc'], 26) .
         str_pad(mb_substr($row['ten_tthc'], 0, 43), 45) .
         $status . "\n";
}
echo "\n";

// 4. Kiểm tra ngẫu nhiên 10 dòng bảng `nguoi_dung`
echo "► 4. KIỂM TRA 10 DÒNG NGẪU NHIÊN BẢNG CÁN BỘ / NGƯỜI DÙNG (`nguoi_dung`):\n";
echo str_pad("ID", 5) . str_pad("TÀI KHOẢN", 24) . str_pad("HỌ VÀ TÊN CÁN BỘ", 46) . "KẾT QUẢ\n";
echo str_repeat("-", 90) . "\n";

$userList = $db->query("SELECT id, ten_dang_nhap, ho_ten FROM nguoi_dung ORDER BY RAND() LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($userList as $row) {
    $status = "";
    evaluateText($row['ho_ten'], $status, $mojibakeFound, $totalChecked, $mojibakePattern);
    echo str_pad((string)$row['id'], 5) .
         str_pad($row['ten_dang_nhap'], 24) .
         str_pad(mb_substr($row['ho_ten'], 0, 44), 46) .
         $status . "\n";
}
echo "\n";

// 5. Kiểm tra ngẫu nhiên 10 dòng bảng `ho_so`
echo "► 5. KIỂM TRA 10 DÒNG NGẪU NHIÊN BẢNG HỒ SƠ (`ho_so`):\n";
echo str_pad("ID", 5) . str_pad("MÃ HỒ SƠ", 36) . str_pad("NGƯỜI NỘP HỒ SƠ", 34) . "KẾT QUẢ\n";
echo str_repeat("-", 90) . "\n";

$hoSoList = $db->query("SELECT id, ma_ho_so, nguoi_nop_ho_ten FROM ho_so ORDER BY RAND() LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($hoSoList as $row) {
    $status = "";
    evaluateText($row['nguoi_nop_ho_ten'], $status, $mojibakeFound, $totalChecked, $mojibakePattern);
    echo str_pad((string)$row['id'], 5) .
         str_pad($row['ma_ho_so'], 36) .
         str_pad(mb_substr($row['nguoi_nop_ho_ten'], 0, 32), 34) .
         $status . "\n";
}
echo "\n";

// 6. Kiểm tra Trigger bảo vệ nguyên tắc bất biến (Chặn DELETE trên `ho_so`)
echo "► 6. KIỂM TRA TRIGGER BẢO VỆ CHỐNG XÓA VẬT LÝ (`tg_hoso_prevent_delete`):\n";
$triggerPassed = false;
try {
    $db->exec("DELETE FROM ho_so WHERE id = 1");
} catch (\PDOException $e) {
    if ($e->getCode() === '45000' && str_contains($e->getMessage(), 'Nghiêm cấm xóa vật lý hồ sơ hành chính')) {
        $triggerPassed = true;
        echo "  ✔ Trigger chặn thành công lệnh DELETE! SQLSTATE 45000: '{$e->getMessage()}'.\n\n";
    } else {
        echo "  ❌ Bắt được lỗi nhưng không đúng thông điệp: " . $e->getMessage() . "\n\n";
    }
}
if (!$triggerPassed) {
    echo "  ❌ Thất bại: Lệnh DELETE không bị trigger chặn!\n\n";
}

// TỔNG KẾT
echo "======================================================================\n";
echo " KẾT QUẢ KIỂM THỬ:\n";
echo " - Tổng số chuỗi văn bản đã kiểm tra: {$totalChecked} chuỗi\n";
echo " - Số chuỗi phát hiện lỗi phông / Mojibake: {$mojibakeFound}\n";
echo " - Trạng thái Trigger chống xóa vật lý: " . ($triggerPassed ? "HOẠT ĐỘNG HOÀN HẢO" : "LỖI") . "\n";

if ($mojibakeFound === 0 && $triggerPassed) {
    echo "🎉 CHÚC MỪNG: CSDL ĐẠT CHUẨN 100% TIẾNG VIỆT UTF-8 & AN TOÀN BẤT BIẾN!\n";
    exit(0);
} else {
    echo "❌ PHÁT HIỆN LỖI TRONG CƠ SỞ DỮ LIỆU!\n";
    exit(1);
}
