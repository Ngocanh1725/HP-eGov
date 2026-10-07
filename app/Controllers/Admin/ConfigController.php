<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\AuthService;
use PDO;

/**
 * ConfigController – Quản trị Ngày nghỉ lễ, Tham số hệ thống và Trạng thái chuẩn TT 01/2018
 */
class ConfigController extends Controller
{
    /**
     * Danh sách ngày nghỉ lễ theo năm (GET /admin/danh-muc/ngay-nghi)
     */
    public function holidays(Request $request): Response
    {
        $db = Database::connection();
        $year = (int)$request->query('nam', (int)date('Y'));

        $stmt = $db->prepare("SELECT * FROM dm_ngay_nghi WHERE nam = :y ORDER BY ngay ASC");
        $stmt->execute([':y' => $year]);
        $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy danh sách các năm có dữ liệu
        $years = $db->query("SELECT DISTINCT nam FROM dm_ngay_nghi ORDER BY nam DESC")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array($year, $years, true)) {
            $years[] = $year;
            rsort($years);
        }

        return $this->view('admin/config/holidays', [
            'pageTitle' => 'Danh mục Ngày nghỉ lễ/Tết | HP-eGov',
            'holidays' => $holidays,
            'currentYear' => $year,
            'years' => $years,
        ]);
    }

    /**
     * Thêm ngày nghỉ lễ mới (POST /admin/danh-muc/ngay-nghi)
     */
    public function storeHoliday(Request $request): Response
    {
        $ngay = (string)$request->input('ngay', '');
        $ten = trim((string)$request->input('ten_ngay_nghi', ''));
        $loai = (string)$request->input('loai', 'LE_TET');
        $ghiChu = trim((string)$request->input('ghi_chu', ''));

        if (empty($ngay) || empty($ten)) {
            Session::flash('error', 'Ngày và Tên ngày nghỉ là bắt buộc.');
            return $this->redirect('/admin/danh-muc/ngay-nghi');
        }

        $nam = (int)date('Y', strtotime($ngay));
        $db = Database::connection();

        $stmt = $db->prepare("INSERT INTO dm_ngay_nghi (ngay, nam, ten_ngay_nghi, loai, ghi_chu) 
                              VALUES (:ngay, :nam, :ten, :loai, :gc)");
        $stmt->execute([
            ':ngay' => $ngay,
            ':nam' => $nam,
            ':ten' => $ten,
            ':loai' => $loai,
            ':gc' => $ghiChu,
        ]);

        AuditService::log(AuthService::id(), 'THEM_NGAY_NGHI', 'dm_ngay_nghi', (int)$db->lastInsertId(), null, ['ngay' => $ngay, 'ten' => $ten]);

        Session::flash('success', "Thêm ngày nghỉ {$ten} ({$ngay}) thành công.");
        return $this->redirect("/admin/danh-muc/ngay-nghi?nam={$nam}");
    }

    /**
     * Nhân bản toàn bộ ngày nghỉ lễ sang năm sau (POST /admin/danh-muc/ngay-nghi/nhan-ban)
     */
    public function cloneHolidays(Request $request): Response
    {
        $sourceYear = (int)$request->input('source_year', (int)date('Y'));
        $targetYear = $sourceYear + 1;

        $db = Database::connection();
        $stmt = $db->prepare("SELECT * FROM dm_ngay_nghi WHERE nam = :y");
        $stmt->execute([':y' => $sourceYear]);
        $sourceList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($sourceList)) {
            Session::flash('error', "Năm {$sourceYear} chưa có ngày nghỉ lễ nào để nhân bản.");
            return $this->redirect("/admin/danh-muc/ngay-nghi?nam={$sourceYear}");
        }

        $clonedCount = 0;
        $insertStmt = $db->prepare("INSERT INTO dm_ngay_nghi (ngay, nam, ten_ngay_nghi, loai, ghi_chu) 
                                    VALUES (:ngay, :nam, :ten, :loai, :gc)");

        foreach ($sourceList as $item) {
            // Tính ngày của năm sau: thay năm cũ bằng năm mới
            $targetDate = date("{$targetYear}-m-d", strtotime($item['ngay']));

            // Kiểm tra xem đã tồn tại chưa
            $exists = $db->query("SELECT id FROM dm_ngay_nghi WHERE ngay = '{$targetDate}'")->fetchColumn();
            if (!$exists) {
                $insertStmt->execute([
                    ':ngay' => $targetDate,
                    ':nam' => $targetYear,
                    ':ten' => $item['ten_ngay_nghi'],
                    ':loai' => $item['loai'],
                    ':gc' => "Nhân bản từ năm {$sourceYear}",
                ]);
                $clonedCount++;
            }
        }

        AuditService::log(
            AuthService::id(),
            'NHAN_BAN_LICH_NGHI_LE',
            'dm_ngay_nghi',
            null,
            ['source_year' => $sourceYear],
            ['target_year' => $targetYear, 'cloned_count' => $clonedCount]
        );

        Session::flash('success', "Đã nhân bản thành công {$clonedCount} ngày nghỉ lễ từ năm {$sourceYear} sang năm {$targetYear}.");
        return $this->redirect("/admin/danh-muc/ngay-nghi?nam={$targetYear}");
    }

    /**
     * Danh sách tham số cấu hình hệ thống (GET /admin/danh-muc/cau-hinh)
     */
    public function parameters(Request $request): Response
    {
        $db = Database::connection();
        $configs = $db->query("SELECT * FROM cau_hinh_he_thong ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/config/parameters', [
            'pageTitle' => 'Tham số Hệ thống & Lịch làm việc | HP-eGov',
            'configs' => $configs,
        ]);
    }

    /**
     * Cập nhật tham số cấu hình và lưu lịch sử phiên bản (POST /admin/danh-muc/cau-hinh/{id})
     */
    public function updateParameter(Request $request): Response
    {
        $id = (int)$request->routeParam('id', 0);
        $value = trim((string)$request->input('gia_tri', ''));

        $db = Database::connection();
        $stmt = $db->prepare("SELECT * FROM cau_hinh_he_thong WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $cfg = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cfg) {
            Session::flash('error', 'Không tìm thấy tham số cấu hình.');
            return $this->redirect('/admin/danh-muc/cau-hinh');
        }

        // Kiểm tra hợp lệ kiểu dữ liệu
        $dataType = $cfg['kieu_du_lieu'] ?? 'string';
        if ($dataType === 'integer' && !is_numeric($value)) {
            Session::flash('error', 'Giá trị tham số phải là số nguyên.');
            return $this->redirect('/admin/danh-muc/cau-hinh');
        }
        if ($dataType === 'json' && json_decode($value) === null) {
            Session::flash('error', 'Giá trị tham số phải là định dạng JSON hợp lệ.');
            return $this->redirect('/admin/danh-muc/cau-hinh');
        }

        // Cập nhật CSDL
        $db->prepare("UPDATE cau_hinh_he_thong SET gia_tri = :val WHERE id = :id")->execute([
            ':val' => $value,
            ':id' => $id,
        ]);

        // Lưu lịch sử phiên bản vào bảng phien_ban_cau_hinh nếu có
        try {
            $db->prepare("INSERT INTO phien_ban_cau_hinh (cau_hinh_id, gia_tri_cu, gia_tri_moi, nguoi_thay_doi_id, thoi_gian) 
                          VALUES (:cid, :old, :new, :uid, NOW())")->execute([
                ':cid' => $id,
                ':old' => $cfg['gia_tri'],
                ':new' => $value,
                ':uid' => AuthService::id(),
            ]);
        } catch (\Throwable) {
            // Không ngắt nếu bảng chưa tạo
        }

        AuditService::log(
            AuthService::id(),
            'CAP_NHAT_THAM_SO_HE_THONG',
            'cau_hinh_he_thong',
            $id,
            ['gia_tri' => $cfg['gia_tri']],
            ['gia_tri' => $value]
        );

        Session::flash('success', "Cập nhật tham số [{$cfg['ma_cau_hinh']}] thành công.");
        return $this->redirect('/admin/danh-muc/cau-hinh');
    }

    /**
     * Danh mục Trạng thái xử lý hồ sơ (Chỉ xem theo TT 01/2018) (GET /admin/danh-muc/trang-thai)
     */
    public function statuses(Request $request): Response
    {
        $db = Database::connection();
        $statuses = $db->query("SELECT * FROM dm_trang_thai ORDER BY thu_tu ASC")->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/config/statuses', [
            'pageTitle' => 'Danh mục Trạng thái Hồ sơ (TT 01/2018/TT-VPCP) | HP-eGov',
            'statuses' => $statuses,
        ]);
    }
}
