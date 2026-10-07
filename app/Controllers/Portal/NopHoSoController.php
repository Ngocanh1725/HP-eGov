<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Integrations\Mock\EformSignerMock;
use App\Integrations\Mock\SmsZnsNotificationMock;
use App\Services\AuditService;
use App\Services\FileUploadSecurityService;
use App\Services\MaHoSoGenerator;
use PDO;

/**
 * NopHoSoController – Trình xử lý Wizard 4 bước Nộp hồ sơ Dịch vụ công trực tuyến
 */
class NopHoSoController extends Controller
{
    /**
     * Giao diện Wizard 4 bước nộp hồ sơ (GET /dvc/nop-ho-so/{tthc_id})
     */
    public function wizard(Request $request): Response
    {
        $user = auth('cong_dan');
        if (!$user) {
            Session::set('intended_url', $request->server('REQUEST_URI', '/'));
            Session::flash('info', 'Vui lòng đăng nhập qua VNeID hoặc tài khoản Doanh nghiệp để nộp hồ sơ trực tuyến.');
            return $this->redirect('/dang-nhap');
        }

        $tthcId = (int)$request->routeParam('tthc_id', 1);
        $db = Database::connection();

        // 1. Lấy thông tin TTHC
        $stmt = $db->prepare("SELECT tt.*, lv.ten_linh_vuc 
                              FROM thu_tuc_hanh_chinh tt 
                              LEFT JOIN dm_linh_vuc lv ON tt.linh_vuc_id = lv.id 
                              WHERE tt.id = :id LIMIT 1");
        $stmt->execute([':id' => $tthcId]);
        $tthc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tthc) {
            Session::flash('error', 'Thủ tục hành chính không tồn tại.');
            return $this->redirect('/dvc/thu-tuc');
        }

        // 2. Lấy thành phần hồ sơ yêu cầu
        $tpStmt = $db->prepare("SELECT * FROM thanh_phan_ho_so WHERE tthc_id = :tid ORDER BY thu_tu ASC");
        $tpStmt->execute([':tid' => $tthcId]);
        $thanhPhanHoSo = $tpStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Lấy cấu trúc E-Form mới nhất
        $efStmt = $db->prepare("SELECT * FROM bieu_mau_eform WHERE tthc_id = :tid AND trang_thai = 1 ORDER BY phien_ban DESC LIMIT 1");
        $efStmt->execute([':tid' => $tthcId]);
        $eformRecord = $efStmt->fetch(PDO::FETCH_ASSOC);

        $defaultSchema = [
            'title' => 'Tờ khai điện tử: ' . $tthc['ten_tthc'],
            'fields' => [
                ['id' => 'ho_ten', 'type' => 'text', 'label' => 'Họ và tên người nộp', 'required' => true, 'autofill_vneid' => 'ho_ten'],
                ['id' => 'so_cccd', 'type' => 'cccd', 'label' => 'Số CCCD / Định danh cá nhân', 'required' => true, 'autofill_vneid' => 'so_cccd'],
                ['id' => 'ngay_sinh', 'type' => 'date', 'label' => 'Ngày tháng năm sinh', 'required' => true, 'autofill_vneid' => 'ngay_sinh'],
                ['id' => 'gioi_tinh', 'type' => 'select', 'label' => 'Giới tính', 'required' => true, 'autofill_vneid' => 'gioi_tinh', 'options' => ['NAM' => 'Nam', 'NU' => 'Nữ']],
                ['id' => 'dia_chi_thuong_tru', 'type' => 'dvhc_address', 'label' => 'Địa chỉ thường trú', 'required' => true, 'autofill_vneid' => 'dia_chi_thuong_tru'],
                ['id' => 'noi_dung_de_nghi', 'type' => 'textarea', 'label' => 'Nội dung đề nghị giải quyết', 'required' => true],
            ],
        ];

        $schema = !empty($eformRecord['cau_truc_json']) ? json_decode($eformRecord['cau_truc_json'], true) : $defaultSchema;

        // 4. Lấy giấy tờ trong Kho dữ liệu cá nhân của công dân để tái sử dụng
        $khoStmt = $db->prepare("SELECT * FROM kho_du_lieu_cong_dan WHERE cong_dan_id = :cid AND trang_thai = 1 ORDER BY id DESC");
        $khoStmt->execute([':cid' => $user['id']]);
        $khoDuLieu = $khoStmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Danh sách cơ quan tiếp nhận phù hợp cấp thực hiện của TTHC
        $cap = $tthc['cap_thuc_hien'] ?? 'TINH';
        if ($cap === 'XA') {
            $donViList = $db->query("SELECT id, ten_don_vi, ma_dinh_danh FROM don_vi WHERE loai_don_vi = 'UBND_XA' AND trang_thai = 1 ORDER BY ten_don_vi ASC")->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($cap === 'HUYEN') {
            $donViList = $db->query("SELECT id, ten_don_vi, ma_dinh_danh FROM don_vi WHERE loai_don_vi IN ('UBND_HUYEN', 'BO_PHAN_MOT_CUA') AND trang_thai = 1 ORDER BY ten_don_vi ASC")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $donViList = $db->query("SELECT id, ten_don_vi, ma_dinh_danh FROM don_vi WHERE loai_don_vi IN ('SO_NGANH', 'UBND_TP') AND trang_thai = 1 ORDER BY ten_don_vi ASC")->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($donViList)) {
            $donViList = $db->query("SELECT id, ten_don_vi, ma_dinh_danh FROM don_vi WHERE trang_thai = 1 LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
        }

        // Lấy danh sách Quận / Huyện Hải Phòng phục vụ chọn ĐVHC 3 cấp
        $districts = $db->query("SELECT id, ten_dvhc FROM dm_dvhc WHERE cap_dvhc = 'HUYEN' AND trang_thai = 1 ORDER BY ten_dvhc ASC")->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('portal/nop_ho_so/wizard', [
            'pageTitle' => "Nộp hồ sơ trực tuyến: {$tthc['ten_tthc']} | Cổng DVC Hải Phòng",
            'tthc' => $tthc,
            'thanhPhanHoSo' => $thanhPhanHoSo,
            'schema' => $schema,
            'khoDuLieu' => $khoDuLieu,
            'donViList' => $donViList,
            'districts' => $districts,
            'currentUser' => $user,
        ]);
    }

    /**
     * Xử lý nộp hồ sơ trực tuyến (POST /dvc/nop-ho-so/{tthc_id})
     */
    public function submit(Request $request): Response
    {
        $user = auth('cong_dan');
        if (!$user) {
            return $this->redirect('/dang-nhap');
        }

        $tthcId = (int)$request->routeParam('tthc_id', 1);
        $db = Database::connection();

        // 1. Kiểm tra TTHC và Quy trình
        $tthc = $db->query("SELECT * FROM thu_tuc_hanh_chinh WHERE id = {$tthcId}")->fetch(PDO::FETCH_ASSOC);
        if (!$tthc) {
            Session::flash('error', 'Thủ tục hành chính không hợp lệ.');
            return $this->redirect('/dvc/thu-tuc');
        }

        $quyTrinh = $db->query("SELECT * FROM quy_trinh WHERE tthc_id = {$tthcId} AND is_active = 1 ORDER BY phien_ban DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $quyTrinhId = $quyTrinh['id'] ?? null;
        $buocDauTienId = null;
        if ($quyTrinhId) {
            $buocDauTienId = (int)$db->query("SELECT id FROM quy_trinh_buoc WHERE quy_trinh_id = {$quyTrinhId} AND loai_buoc = 'TIEP_NHAN' ORDER BY stt_buoc ASC LIMIT 1")->fetchColumn();
        }

        $donViTiepNhanId = (int)$request->input('don_vi_tiep_nhan_id', 1);
        $hinhThucNhanKq = (string)$request->input('hinh_thuc_nhan_kq', 'DIEN_TU');
        $diaChiNhanKq = trim((string)$request->input('dia_chi_nhan_kq', ''));

        // 2. Đọc và ký số dữ liệu E-Form
        $eformDataRaw = $request->input('eform_data', []);
        if (!is_array($eformDataRaw)) {
            $eformDataRaw = [];
        }

        // Bắt buộc giữ dữ liệu định danh từ VNeID để chống sửa đổi
        $eformDataRaw['ho_ten'] = $user['ho_ten'] ?? ($eformDataRaw['ho_ten'] ?? '');
        $eformDataRaw['so_cccd'] = $user['so_cccd'] ?? ($eformDataRaw['so_cccd'] ?? '');
        $eformDataRaw['ngay_sinh'] = $user['ngay_sinh'] ?? ($eformDataRaw['ngay_sinh'] ?? '');
        $eformDataRaw['gioi_tinh'] = $user['gioi_tinh'] ?? ($eformDataRaw['gioi_tinh'] ?? '');
        $eformDataRaw['dia_chi_thuong_tru'] = $user['dia_chi_thuong_tru'] ?? ($eformDataRaw['dia_chi_thuong_tru'] ?? '');

        // Ký số Mock E-Form
        $digitalSignature = EformSignerMock::sign(
            $eformDataRaw,
            (string)($user['so_cccd'] ?? $user['ma_so_thue'] ?? '031090001234'),
            (string)($user['ho_ten'] ?? 'Công dân Hải Phòng')
        );

        $eformFullPayload = [
            'data' => $eformDataRaw,
            'signature' => $digitalSignature,
        ];

        // 3. Sinh mã hồ sơ chuẩn QCVN 102:2016/BTTTT
        $maHoSo = MaHoSoGenerator::generate($donViTiepNhanId, (string)($tthc['ma_rut_gon'] ?? '0001'));

        $db->beginTransaction();
        try {
            // 4. Tạo bản ghi hồ sơ
            $thoiHanNgay = (int)($tthc['thoi_han_giai_quyet_ngay'] ?? 3);
            $tongGio = $thoiHanNgay * 8;
            $ngayHenTra = date('Y-m-d 17:00:00', strtotime("+{$thoiHanNgay} weekdays"));

            $stmtHs = $db->prepare("INSERT INTO ho_so (
                ma_ho_so, tthc_id, quy_trinh_id, buoc_hien_tai_id, don_vi_tiep_nhan_id, don_vi_hien_tai_id,
                cong_dan_id, kenh_nop, nguoi_nop_ho_ten, nguoi_nop_cccd, nguoi_nop_sdt, nguoi_nop_email, nguoi_nop_dia_chi,
                du_lieu_eform, trang_thai, ngay_nop, ngay_hen_tra, sla_mau, tong_thoi_han_gio, thoi_gian_da_dung_gio,
                hinh_thuc_nhan_kq, dia_chi_nhan_kq, created_at, updated_at
            ) VALUES (
                :ma, :tid, :qid, :bid, :dvid, :dvhtid,
                :cid, 'TRUC_TUYEN', :ten, :cccd, :sdt, :email, :dc,
                :eform, 'CHO_TIEP_NHAN', NOW(), :hentra, 'XANH', :gio, 0,
                :ht, :dckq, NOW(), NOW()
            )");

            $stmtHs->execute([
                ':ma' => $maHoSo,
                ':tid' => $tthcId,
                ':qid' => $quyTrinhId ?: null,
                ':bid' => $buocDauTienId ?: null,
                ':dvid' => $donViTiepNhanId,
                ':dvhtid' => $donViTiepNhanId,
                ':cid' => $user['id'],
                ':ten' => $user['ho_ten'] ?? 'Công dân',
                ':cccd' => $user['so_cccd'] ?? $user['ma_so_thue'] ?? '',
                ':sdt' => $user['so_dien_thoai'] ?? '',
                ':email' => $user['email'] ?? '',
                ':dc' => $user['dia_chi_thuong_tru'] ?? '',
                ':eform' => json_encode($eformFullPayload, JSON_UNESCAPED_UNICODE),
                ':hentra' => $ngayHenTra,
                ':gio' => $tongGio,
                ':ht' => $hinhThucNhanKq,
                ':dckq' => $diaChiNhanKq,
            ]);

            $hoSoId = (int)$db->lastInsertId();

            // 5. Xử lý tài liệu đính kèm (Upload mới hoặc chọn từ Kho dữ liệu)
            // (a) Xử lý tài liệu chọn từ Kho dữ liệu điện tử cá nhân
            $khoItemIds = $request->input('kho_tai_lieu_ids', []);
            if (is_array($khoItemIds)) {
                $insTlStmt = $db->prepare("INSERT INTO ho_so_tai_lieu (ho_so_id, thanh_phan_id, ten_goc, ten_luu, duong_dan, mime_type, kich_thuoc_byte, sha256, loai_tai_lieu, created_at) 
                                           VALUES (:hid, :tpid, :ten, :luu, :path, :mime, :size, :sha, 'DAU_VAO', NOW())");
                foreach ($khoItemIds as $thanhPhanId => $khoId) {
                    if ((int)$khoId > 0) {
                        $khoDoc = $db->query("SELECT * FROM kho_du_lieu_cong_dan WHERE id = " . (int)$khoId . " AND cong_dan_id = " . (int)$user['id'])->fetch(PDO::FETCH_ASSOC);
                        if ($khoDoc) {
                            $insTlStmt->execute([
                                ':hid' => $hoSoId,
                                ':tpid' => (int)$thanhPhanId,
                                ':ten' => $khoDoc['ten_tai_lieu'],
                                ':luu' => basename($khoDoc['duong_dan']),
                                ':path' => $khoDoc['duong_dan'],
                                ':mime' => 'application/pdf',
                                ':size' => 102400,
                                ':sha' => $khoDoc['sha256'] ?? '',
                            ]);
                        }
                    }
                }
            }

            // (b) Xử lý các tệp tải lên mới qua FileUploadSecurityService
            $files = $request->files();
            if (!empty($files['tep_dinh_kem']) && is_array($files['tep_dinh_kem']['name'])) {
                $insTlStmt = $db->prepare("INSERT INTO ho_so_tai_lieu (ho_so_id, thanh_phan_id, ten_goc, ten_luu, duong_dan, mime_type, kich_thuoc_byte, sha256, loai_tai_lieu, created_at) 
                                           VALUES (:hid, :tpid, :ten, :luu, :path, :mime, :size, :sha, 'DAU_VAO', NOW())");
                foreach ($files['tep_dinh_kem']['name'] as $tpid => $name) {
                    if (!empty($name) && $files['tep_dinh_kem']['error'][$tpid] === UPLOAD_ERR_OK) {
                        $singleFile = [
                            'name' => $files['tep_dinh_kem']['name'][$tpid],
                            'type' => $files['tep_dinh_kem']['type'][$tpid],
                            'tmp_name' => $files['tep_dinh_kem']['tmp_name'][$tpid],
                            'error' => $files['tep_dinh_kem']['error'][$tpid],
                            'size' => $files['tep_dinh_kem']['size'][$tpid],
                        ];
                        $uploadResult = FileUploadSecurityService::processUpload($singleFile, 'hoso');
                        $insTlStmt->execute([
                            ':hid' => $hoSoId,
                            ':tpid' => (int)$tpid,
                            ':ten' => $uploadResult['ten_goc'],
                            ':luu' => $uploadResult['ten_luu'],
                            ':path' => $uploadResult['duong_dan'],
                            ':mime' => $uploadResult['mime_type'],
                            ':size' => $uploadResult['kich_thuoc_byte'],
                            ':sha' => $uploadResult['sha256'],
                        ]);
                    }
                }
            }

            // Tìm cán bộ tiếp nhận của đơn vị hoặc gán tài khoản hệ thống (id = 1)
            $canBoId = (int)$db->query("SELECT id FROM nguoi_dung WHERE don_vi_chinh_id = {$donViTiepNhanId} AND is_deleted = 0 LIMIT 1")->fetchColumn();
            if ($canBoId <= 0) {
                $canBoId = 1;
            }

            // 6. Ghi nhật ký luân chuyển hồ sơ bước khởi tạo
            $stmtLc = $db->prepare("INSERT INTO ho_so_luan_chuyen (ho_so_id, tu_don_vi_id, den_don_vi_id, nguoi_chuyen_id, hanh_dong, trang_thai_truoc, trang_thai_sau, noi_dung_y_kien, thoi_gian_chuyen) 
                                    VALUES (:hid, :tudvid, :dendvid, :ncid, 'NOP_HO_SO_TRUC_TUYEN', 'MOI_TAO', 'CHO_TIEP_NHAN', :nd, NOW())");
            $stmtLc->execute([
                ':hid' => $hoSoId,
                ':tudvid' => $donViTiepNhanId,
                ':dendvid' => $donViTiepNhanId,
                ':ncid' => $canBoId,
                ':nd' => "Công dân nộp hồ sơ trực tuyến qua Cổng Dịch vụ công TP. Hải Phòng (Ký số E-Form định danh).",
            ]);

            $db->commit();

            // 7. Ghi Audit Log WORM
            AuditService::log(
                $user['id'],
                'CONG_DAN_NOP_HO_SO',
                'ho_so',
                $hoSoId,
                null,
                ['ma_ho_so' => $maHoSo, 'tthc' => $tthc['ten_tthc']]
            );

            // 8. Phát thông báo SMS Brandname & Zalo ZNS
            if (!empty($user['so_dien_thoai'])) {
                SmsZnsNotificationMock::notify(
                    $user['so_dien_thoai'],
                    $maHoSo,
                    "Nộp hồ sơ trực tuyến thành công - Mã {$maHoSo}",
                    "Cổng DVC Hải Phòng: Quý khách đã nộp thành công hồ sơ [{$tthc['ten_tthc']}]. Mã hồ sơ: {$maHoSo}. Hẹn trả: {$ngayHenTra}.",
                    $user['id']
                );
            }

            return $this->redirect("/dvc/nop-ho-so/thanh-cong/{$maHoSo}");

        } catch (\Throwable $e) {
            $db->rollBack();
            \App\Core\Logger::error("Nop ho so that bai: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            Session::flash('error', 'Có lỗi xảy ra khi nộp hồ sơ: ' . $e->getMessage());
            return $this->redirect("/dvc/nop-ho-so/{$tthcId}");
        }
    }

    /**
     * Màn hình nộp hồ sơ thành công (GET /dvc/nop-ho-so/thanh-cong/{ma_ho_so})
     */
    public function success(Request $request): Response
    {
        $maHoSo = (string)$request->routeParam('ma_ho_so', '');
        $db = Database::connection();

        $stmt = $db->prepare("SELECT hs.*, tt.ten_tthc, dv.ten_don_vi 
                              FROM ho_so hs 
                              LEFT JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                              LEFT JOIN don_vi dv ON hs.don_vi_tiep_nhan_id = dv.id 
                              WHERE hs.ma_ho_so = :ma LIMIT 1");
        $stmt->execute([':ma' => $maHoSo]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            return $this->redirect('/cong-dan/ho-so');
        }

        // Tạo chuỗi liên kết tra cứu nhanh cho QR code
        $lookupUrl = url("/tra-cuu?ma_ho_so=" . urlencode($maHoSo));

        return $this->view('portal/nop_ho_so/success', [
            'pageTitle' => "Nộp hồ sơ thành công: {$maHoSo} | Cổng DVC Hải Phòng",
            'hoSo' => $hoSo,
            'lookupUrl' => $lookupUrl,
        ]);
    }
}
