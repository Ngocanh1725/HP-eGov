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
use App\Services\MdmService;
use PDO;

/**
 * DvhcController – Quản lý Danh mục Đơn vị Hành chính 3 cấp (TP Hải Phòng)
 * Hỗ trợ sáp nhập / chia tách ĐVHC, xuất nhập CSV
 */
class DvhcController extends Controller
{
    /**
     * Danh sách ĐVHC (GET /admin/danh-muc/dvhc)
     */
    public function index(Request $request): Response
    {
        $db = Database::connection();
        $q = trim((string)$request->query('q', ''));
        $cap = $request->query('cap', '');
        $parentId = (int)$request->query('parent_id', 0);

        $sql = "SELECT d.*, p.ten_dvhc AS ten_cha 
                FROM dm_dvhc d
                LEFT JOIN dm_dvhc p ON d.parent_id = p.id
                WHERE 1=1";
        $params = [];

        if (!empty($q)) {
            $sql .= " AND (d.ma_dvhc LIKE :q OR d.ten_dvhc LIKE :q)";
            $params[':q'] = "%{$q}%";
        }
        if (!empty($cap)) {
            $sql .= " AND d.cap_dvhc = :cap";
            $params[':cap'] = $cap;
        }
        if ($parentId > 0) {
            $sql .= " AND d.parent_id = :pid";
            $params[':pid'] = $parentId;
        }

        $sql .= " ORDER BY d.cap_dvhc ASC, d.ma_dvhc ASC LIMIT 200";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $dvhcList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy danh sách quận/huyện để làm bộ lọc
        $districts = $db->query("SELECT id, ten_dvhc FROM dm_dvhc WHERE cap_dvhc = 'HUYEN' ORDER BY ten_dvhc ASC")->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/dvhc/index', [
            'pageTitle' => 'Danh mục Đơn vị Hành chính | HP-eGov',
            'dvhcList' => $dvhcList,
            'districts' => $districts,
            'filters' => ['q' => $q, 'cap' => $cap, 'parent_id' => $parentId],
        ]);
    }

    /**
     * Lưu ĐVHC mới (POST /admin/danh-muc/dvhc)
     */
    public function store(Request $request): Response
    {
        $ma = trim((string)$request->input('ma_dvhc', ''));
        $ten = trim((string)$request->input('ten_dvhc', ''));
        $cap = (string)$request->input('cap_dvhc', 'XA');
        $loai = (string)$request->input('loai_dvhc', 'PHUONG');
        $parentId = (int)$request->input('parent_id', 0) ?: null;
        $hieuLucTu = (string)$request->input('hieu_luc_tu', date('Y-m-d'));

        if (empty($ma) || empty($ten)) {
            Session::flash('error', 'Mã và Tên ĐVHC là bắt buộc.');
            return $this->redirect('/admin/danh-muc/dvhc');
        }

        $db = Database::connection();
        $stmt = $db->prepare("INSERT INTO dm_dvhc (ma_dvhc, ten_dvhc, cap_dvhc, loai_dvhc, parent_id, hieu_luc_tu, trang_thai) 
                              VALUES (:ma, :ten, :cap, :loai, :pid, :hl, 1)");
        $stmt->execute([
            ':ma' => $ma,
            ':ten' => $ten,
            ':cap' => $cap,
            ':loai' => $loai,
            ':pid' => $parentId,
            ':hl' => $hieuLucTu,
        ]);

        $id = (int)$db->lastInsertId();
        AuditService::log(AuthService::id(), 'THEM_DVHC', 'dm_dvhc', $id, null, ['ma' => $ma, 'ten' => $ten]);

        Session::flash('success', "Thêm mới ĐVHC {$ten} ({$ma}) thành công.");
        return $this->redirect('/admin/danh-muc/dvhc');
    }

    /**
     * Cập nhật ĐVHC (POST /admin/danh-muc/dvhc/{id}/cap-nhat)
     */
    public function update(Request $request): Response
    {
        $id = (int)$request->routeParam('id', 0);
        $db = Database::connection();

        $stmt = $db->prepare("SELECT * FROM dm_dvhc WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $old = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$old) {
            Session::flash('error', 'Không tìm thấy ĐVHC.');
            return $this->redirect('/admin/danh-muc/dvhc');
        }

        $ten = trim((string)$request->input('ten_dvhc', $old['ten_dvhc']));
        $loai = (string)$request->input('loai_dvhc', $old['loai_dvhc']);
        $hieuLucDen = $request->input('hieu_luc_den') ?: null;

        $db->prepare("UPDATE dm_dvhc SET ten_dvhc = :ten, loai_dvhc = :loai, hieu_luc_den = :hld WHERE id = :id")->execute([
            ':ten' => $ten,
            ':loai' => $loai,
            ':hld' => $hieuLucDen,
            ':id' => $id,
        ]);

        AuditService::log(AuthService::id(), 'SUA_DVHC', 'dm_dvhc', $id, $old, ['ten' => $ten, 'loai' => $loai, 'hieu_luc_den' => $hieuLucDen]);

        Session::flash('success', "Cập nhật ĐVHC {$ten} thành công.");
        return $this->redirect('/admin/danh-muc/dvhc');
    }

    /**
     * Bật / Tắt hiệu lực ĐVHC (POST /admin/danh-muc/dvhc/{id}/trang-thai)
     */
    public function toggleStatus(Request $request): Response
    {
        $id = (int)$request->routeParam('id', 0);
        $db = Database::connection();

        $stmt = $db->prepare("SELECT id, ten_dvhc, trang_thai FROM dm_dvhc WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            Session::flash('error', 'Không tìm thấy ĐVHC.');
            return $this->redirect('/admin/danh-muc/dvhc');
        }

        $newSt = ((int)$row['trang_thai'] === 1) ? 0 : 1;
        $db->prepare("UPDATE dm_dvhc SET trang_thai = :st WHERE id = :id")->execute([':st' => $newSt, ':id' => $id]);

        AuditService::log(AuthService::id(), 'DOI_TRANG_THAI_DVHC', 'dm_dvhc', $id, ['trang_thai' => $row['trang_thai']], ['trang_thai' => $newSt]);

        $msg = $newSt === 1 ? "Đã kích hoạt lại ĐVHC {$row['ten_dvhc']}." : "Đã ngừng hiệu lực ĐVHC {$row['ten_dvhc']}.";
        Session::flash('success', $msg);
        return $this->redirect('/admin/danh-muc/dvhc');
    }

    /**
     * Xử lý Sáp nhập ĐVHC (POST /admin/danh-muc/dvhc/sap-nhap)
     * Đóng hiệu lực đơn vị cũ và chuyển tiếp sang đơn vị mới
     */
    public function merge(Request $request): Response
    {
        $donViCuId = (int)$request->input('don_vi_cu_id', 0);
        $donViMoiId = (int)$request->input('don_vi_moi_id', 0);
        $ngayHieuLuc = (string)$request->input('ngay_hieu_luc', date('Y-m-d'));
        $canCuPhapLy = (string)$request->input('can_cu_phap_ly', '');

        if ($donViCuId <= 0 || $donViMoiId <= 0 || $donViCuId === $donViMoiId) {
            Session::flash('error', 'Vui lòng chọn đơn vị cũ và đơn vị mới tiếp nhận khác nhau.');
            return $this->redirect('/admin/danh-muc/dvhc');
        }

        $db = Database::connection();
        $dvCu = $db->query("SELECT * FROM dm_dvhc WHERE id = {$donViCuId}")->fetch(PDO::FETCH_ASSOC);
        $dvMoi = $db->query("SELECT * FROM dm_dvhc WHERE id = {$donViMoiId}")->fetch(PDO::FETCH_ASSOC);

        if (!$dvCu || !$dvMoi) {
            Session::flash('error', 'Không tìm thấy thông tin đơn vị sáp nhập.');
            return $this->redirect('/admin/danh-muc/dvhc');
        }

        // 1. Đóng hiệu lực đơn vị cũ
        $db->prepare("UPDATE dm_dvhc SET hieu_luc_den = :hld, trang_thai = 0 WHERE id = :id")->execute([
            ':hld' => $ngayHieuLuc,
            ':id' => $donViCuId,
        ]);

        AuditService::log(
            AuthService::id(),
            'SAP_NHAP_DVHC',
            'dm_dvhc',
            $donViCuId,
            ['id' => $donViCuId, 'ten' => $dvCu['ten_dvhc']],
            [
                'sap_nhap_vao_id' => $donViMoiId,
                'ten_don_vi_moi' => $dvMoi['ten_dvhc'],
                'ngay_hieu_luc' => $ngayHieuLuc,
                'can_cu' => $canCuPhapLy,
            ]
        );

        Session::flash('success', "Đã hoàn thành sáp nhập ĐVHC [{$dvCu['ten_dvhc']}] vào [{$dvMoi['ten_dvhc']}]. Đơn vị cũ đã đóng hiệu lực từ ngày {$ngayHieuLuc}.");
        return $this->redirect('/admin/danh-muc/dvhc');
    }

    /**
     * Xuất danh sách ĐVHC ra file CSV UTF-8 có BOM (GET /admin/danh-muc/dvhc/export)
     */
    public function export(Request $request): Response
    {
        $db = Database::connection();
        $rows = $db->query("SELECT d.ma_dvhc, d.ten_dvhc, d.cap_dvhc, d.loai_dvhc, COALESCE(p.ten_dvhc, 'TP Hải Phòng') as cap_tren, d.hieu_luc_tu, COALESCE(d.hieu_luc_den, '') as hieu_luc_den, CASE WHEN d.trang_thai = 1 THEN 'Hoạt động' ELSE 'Ngừng' END as trang_thai 
                            FROM dm_dvhc d LEFT JOIN dm_dvhc p ON d.parent_id = p.id ORDER BY d.cap_dvhc ASC, d.ma_dvhc ASC")->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Mã ĐVHC', 'Tên Đơn vị', 'Cấp hành chính', 'Loại hình', 'Đơn vị cấp trên', 'Hiệu lực từ', 'Hiệu lực đến', 'Trạng thái'];
        return MdmService::exportCsv('danh_muc_dvhc_haiphong.csv', $headers, $rows);
    }
}
