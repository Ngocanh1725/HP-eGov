<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\FileUploadSecurityService;
use PDO;

/**
 * HoSoCaNhanController – Quản lý Hồ sơ, Bổ sung, Kho dữ liệu số hóa và Đánh giá SIPAS của công dân
 */
class HoSoCaNhanController extends Controller
{
    /**
     * Danh sách hồ sơ của tôi (GET /cong-dan/ho-so)
     */
    public function index(Request $request): Response
    {
        $user = auth('cong_dan');
        $db = Database::connection();
        $status = trim((string)$request->query('status', ''));
        $q = trim((string)$request->query('q', ''));

        $sql = "SELECT hs.*, tt.ten_tthc, dv.ten_don_vi 
                FROM ho_so hs 
                LEFT JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                LEFT JOIN don_vi dv ON hs.don_vi_tiep_nhan_id = dv.id 
                WHERE hs.cong_dan_id = :cid AND hs.is_deleted = 0";
        $params = [':cid' => $user['id']];

        if (!empty($status)) {
            $sql .= " AND hs.trang_thai = :st";
            $params[':st'] = $status;
        }
        if (!empty($q)) {
            $sql .= " AND (hs.ma_ho_so LIKE :q OR tt.ten_tthc LIKE :q)";
            $params[':q'] = "%{$q}%";
        }

        $sql .= " ORDER BY hs.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $hoSoList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Đếm theo từng trạng thái để làm tab thống kê
        $countStmt = $db->prepare("SELECT trang_thai, count(*) as total FROM ho_so WHERE cong_dan_id = :cid AND is_deleted = 0 GROUP BY trang_thai");
        $countStmt->execute([':cid' => $user['id']]);
        $counts = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return $this->view('portal/ho_so/index', [
            'pageTitle' => 'Hồ sơ của tôi | Cổng DVC Hải Phòng',
            'hoSoList' => $hoSoList,
            'counts' => $counts,
            'currentStatus' => $status,
            'q' => $q,
            'currentUser' => $user,
        ]);
    }

    /**
     * Chi tiết tiến trình hồ sơ công dân (GET /cong-dan/ho-so/{ma_ho_so})
     * Kiểm tra chặt chẽ quyền sở hữu chống IDOR (Công dân A không được xem hồ sơ B)
     */
    public function show(Request $request): Response
    {
        $user = auth('cong_dan');
        if (!$user) {
            return Response::html("<h1>403 Forbidden - Truy cập bị từ chối</h1><p>Bạn không có quyền xem thông tin hồ sơ này theo quy chế bảo mật HP-eGov.</p>", 403);
        }

        $maHoSo = (string)$request->routeParam('ma_ho_so', '');
        $db = Database::connection();

        $stmt = $db->prepare("SELECT hs.*, tt.ten_tthc, tt.ma_rut_gon, dv.ten_don_vi 
                              FROM ho_so hs 
                              LEFT JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                              LEFT JOIN don_vi dv ON hs.don_vi_tiep_nhan_id = dv.id 
                              WHERE hs.ma_ho_so = :ma AND hs.is_deleted = 0 LIMIT 1");
        $stmt->execute([':ma' => $maHoSo]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            Session::flash('error', 'Không tìm thấy hồ sơ yêu cầu.');
            return $this->redirect('/cong-dan/ho-so');
        }

        // KIỂM TRA BẢO MẬT CHỐNG IDOR: Hồ sơ phải thuộc về công dân đang đăng nhập
        if ((int)$hoSo['cong_dan_id'] !== (int)$user['id']) {
            AuditService::log(
                $user['id'],
                'TRUY_CAP_TRAI_PHEP_HO_SO_IDOR',
                'ho_so',
                $hoSo['id'],
                null,
                ['ma_ho_so' => $maHoSo, 'user_actual' => $user['id'], 'user_owner' => $hoSo['cong_dan_id']]
            );
            return Response::html("<h1>403 Forbidden - Truy cập bị từ chối</h1><p>Bạn không có quyền xem thông tin hồ sơ này theo quy chế bảo mật HP-eGov.</p>", 403);
        }

        // Lấy danh sách tài liệu đính kèm
        $tlStmt = $db->prepare("SELECT tl.*, tp.ten_giay_to 
                                FROM ho_so_tai_lieu tl 
                                LEFT JOIN thanh_phan_ho_so tp ON tl.thanh_phan_id = tp.id 
                                WHERE tl.ho_so_id = :hid ORDER BY tl.id ASC");
        $tlStmt->execute([':hid' => $hoSo['id']]);
        $taiLieuList = $tlStmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy timeline luân chuyển CÔNG KHAI (Ẩn các ý kiến/ghi chú thẩm định nội bộ của cán bộ)
        $lcStmt = $db->prepare("SELECT lc.* FROM ho_so_luan_chuyen lc 
                                WHERE lc.ho_so_id = :hid 
                                ORDER BY lc.id ASC");
        $lcStmt->execute([':hid' => $hoSo['id']]);
        $timeline = $lcStmt->fetchAll(PDO::FETCH_ASSOC);

        // Kiểm tra xem hồ sơ đã được đánh giá SIPAS chưa
        $sipas = $db->query("SELECT id, diem_trung_binh FROM khao_sat_sipas WHERE ho_so_id = {$hoSo['id']}")->fetch(PDO::FETCH_ASSOC);

        return $this->view('portal/ho_so/detail', [
            'pageTitle' => "Tiến trình hồ sơ: {$maHoSo} | Cổng DVC Hải Phòng",
            'hoSo' => $hoSo,
            'taiLieuList' => $taiLieuList,
            'timeline' => $timeline,
            'sipas' => $sipas,
        ]);
    }

    /**
     * Màn hình Bổ sung hồ sơ (GET /cong-dan/ho-so/{ma_ho_so}/bo-sung)
     */
    public function boSungForm(Request $request): Response
    {
        $user = auth('cong_dan');
        $maHoSo = (string)$request->routeParam('ma_ho_so', '');
        $db = Database::connection();

        $stmt = $db->prepare("SELECT hs.*, tt.ten_tthc FROM ho_so hs 
                              LEFT JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                              WHERE hs.ma_ho_so = :ma AND hs.cong_dan_id = :cid AND hs.is_deleted = 0");
        $stmt->execute([':ma' => $maHoSo, ':cid' => $user['id']]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo || $hoSo['trang_thai'] !== 'CHO_BO_SUNG') {
            Session::flash('error', 'Hồ sơ không ở trạng thái Chờ bổ sung.');
            return $this->redirect('/cong-dan/ho-so');
        }

        // Lấy thông báo yêu cầu bổ sung gần nhất trong timeline
        $reqLc = $db->query("SELECT * FROM ho_so_luan_chuyen WHERE ho_so_id = {$hoSo['id']} AND trang_thai_sau = 'CHO_BO_SUNG' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        // Lấy danh sách thành phần hồ sơ
        $tpStmt = $db->prepare("SELECT * FROM thanh_phan_ho_so WHERE tthc_id = :tid ORDER BY thu_tu ASC");
        $tpStmt->execute([':tid' => $hoSo['tthc_id']]);
        $thanhPhanHoSo = $tpStmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('portal/ho_so/bo_sung', [
            'pageTitle' => "Bổ sung hồ sơ: {$maHoSo} | Cổng DVC Hải Phòng",
            'hoSo' => $hoSo,
            'reqLc' => $reqLc,
            'thanhPhanHoSo' => $thanhPhanHoSo,
        ]);
    }

    /**
     * Xử lý nộp bổ sung hồ sơ (POST /cong-dan/ho-so/{ma_ho_so}/bo-sung)
     */
    public function submitBoSung(Request $request): Response
    {
        $user = auth('cong_dan');
        $maHoSo = (string)$request->routeParam('ma_ho_so', '');
        $db = Database::connection();

        $stmt = $db->prepare("SELECT * FROM ho_so WHERE ma_ho_so = :ma AND cong_dan_id = :cid AND is_deleted = 0");
        $stmt->execute([':ma' => $maHoSo, ':cid' => $user['id']]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo || $hoSo['trang_thai'] !== 'CHO_BO_SUNG') {
            Session::flash('error', 'Hồ sơ không ở trạng thái Chờ bổ sung.');
            return $this->redirect('/cong-dan/ho-so');
        }

        $ghiChuBoSung = trim((string)$request->input('ghi_chu_bo_sung', 'Công dân đã hoàn thiện các tài liệu bổ sung theo yêu cầu.'));

        $db->beginTransaction();
        try {
            // Lưu các tài liệu bổ sung mới
            $files = $request->files();
            if (!empty($files['tep_bo_sung']) && is_array($files['tep_bo_sung']['name'])) {
                $insTlStmt = $db->prepare("INSERT INTO ho_so_tai_lieu (ho_so_id, thanh_phan_id, ten_goc, ten_luu, duong_dan, mime_type, kich_thuoc_byte, sha256, loai_tai_lieu, created_at) 
                                           VALUES (:hid, :tpid, :ten, :luu, :path, :mime, :size, :sha, 'BO_SUNG', NOW())");
                foreach ($files['tep_bo_sung']['name'] as $tpid => $name) {
                    if (!empty($name) && $files['tep_bo_sung']['error'][$tpid] === UPLOAD_ERR_OK) {
                        $singleFile = [
                            'name' => $files['tep_bo_sung']['name'][$tpid],
                            'type' => $files['tep_bo_sung']['type'][$tpid],
                            'tmp_name' => $files['tep_bo_sung']['tmp_name'][$tpid],
                            'error' => $files['tep_bo_sung']['error'][$tpid],
                            'size' => $files['tep_bo_sung']['size'][$tpid],
                        ];
                        $uploadResult = FileUploadSecurityService::processUpload($singleFile, 'hoso');
                        $insTlStmt->execute([
                            ':hid' => $hoSo['id'],
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

            // Chuyển trạng thái hồ sơ quay lại DANG_XU_LY
            $db->prepare("UPDATE ho_so SET trang_thai = 'DANG_XU_LY', updated_at = NOW() WHERE id = :hid")
               ->execute([':hid' => $hoSo['id']]);

            $canBoId = (int)$db->query("SELECT id FROM nguoi_dung WHERE don_vi_chinh_id = " . (int)($hoSo['don_vi_hien_tai_id'] ?? 1) . " AND is_deleted = 0 LIMIT 1")->fetchColumn();
            if ($canBoId <= 0) {
                $canBoId = 1;
            }

            // Ghi nhật ký luân chuyển
            $db->prepare("INSERT INTO ho_so_luan_chuyen (ho_so_id, tu_don_vi_id, den_don_vi_id, nguoi_chuyen_id, hanh_dong, trang_thai_truoc, trang_thai_sau, noi_dung_y_kien, thoi_gian_chuyen) 
                          VALUES (:hid, :dvid, :dvid, :ncid, 'NOP_BO_SUNG', 'CHO_BO_SUNG', 'DANG_XU_LY', :nd, NOW())")
               ->execute([
                   ':hid' => $hoSo['id'],
                   ':dvid' => $hoSo['don_vi_hien_tai_id'] ?? $hoSo['don_vi_tiep_nhan_id'] ?? 1,
                   ':ncid' => $canBoId,
                   ':nd' => $ghiChuBoSung
               ]);

            $db->commit();

            AuditService::log(
                $user['id'],
                'CONG_DAN_NOP_BO_SUNG',
                'ho_so',
                $hoSo['id'],
                ['trang_thai' => 'CHO_BO_SUNG'],
                ['trang_thai' => 'DANG_XU_LY', 'ghi_chu' => $ghiChuBoSung]
            );

            Session::flash('success', "Đã nộp bổ sung hồ sơ {$maHoSo} thành công. Hồ sơ đã chuyển về trạng thái Đang xử lý.");
            return $this->redirect("/cong-dan/ho-so/{$maHoSo}");

        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Lỗi khi nộp bổ sung: ' . $e->getMessage());
            return $this->redirect("/cong-dan/ho-so/{$maHoSo}/bo-sung");
        }
    }

    /**
     * Kho dữ liệu điện tử cá nhân / doanh nghiệp (GET /cong-dan/kho-du-lieu)
     */
    public function khoDuLieu(Request $request): Response
    {
        $user = auth('cong_dan');
        $db = Database::connection();

        $stmt = $db->prepare("SELECT * FROM kho_du_lieu_cong_dan WHERE cong_dan_id = :cid ORDER BY id DESC");
        $stmt->execute([':cid' => $user['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('portal/ho_so/kho_du_lieu', [
            'pageTitle' => 'Kho dữ liệu điện tử cá nhân | Cổng DVC Hải Phòng',
            'items' => $items,
            'currentUser' => $user,
        ]);
    }

    /**
     * Màn hình Đánh giá SIPAS (GET /cong-dan/ho-so/{ma_ho_so}/danh-gia-sipas)
     */
    public function sipasForm(Request $request): Response
    {
        $user = auth('cong_dan');
        $maHoSo = (string)$request->routeParam('ma_ho_so', '');
        $db = Database::connection();

        $stmt = $db->prepare("SELECT hs.*, tt.ten_tthc, dv.ten_don_vi 
                              FROM ho_so hs 
                              LEFT JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                              LEFT JOIN don_vi dv ON hs.don_vi_tiep_nhan_id = dv.id 
                              WHERE hs.ma_ho_so = :ma AND hs.cong_dan_id = :cid");
        $stmt->execute([':ma' => $maHoSo, ':cid' => $user['id']]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            return $this->redirect('/cong-dan/ho-so');
        }

        // Kiểm tra xem đã hoàn thành chưa
        if (!in_array($hoSo['trang_thai'], ['HOAN_THANH', 'DA_TRA_KET_QUA'], true)) {
            Session::flash('error', 'Chỉ có thể đánh giá hài lòng sau khi hồ sơ đã có kết quả hoàn thành.');
            return $this->redirect("/cong-dan/ho-so/{$maHoSo}");
        }

        // Kiểm tra đã đánh giá chưa (chỉ 1 lần/hồ sơ)
        $existing = $db->query("SELECT id FROM khao_sat_sipas WHERE ho_so_id = {$hoSo['id']}")->fetchColumn();
        if ($existing) {
            Session::flash('info', 'Hồ sơ này đã được thực hiện đánh giá khảo sát SIPAS trước đó.');
            return $this->redirect("/cong-dan/ho-so/{$maHoSo}");
        }

        return $this->view('portal/ho_so/sipas', [
            'pageTitle' => "Khảo sát hài lòng SIPAS: {$maHoSo} | Cổng DVC Hải Phòng",
            'hoSo' => $hoSo,
        ]);
    }

    /**
     * Lưu phiếu đánh giá SIPAS (POST /cong-dan/ho-so/{ma_ho_so}/danh-gia-sipas)
     */
    public function submitSipas(Request $request): Response
    {
        $user = auth('cong_dan');
        $maHoSo = (string)$request->routeParam('ma_ho_so', '');
        $db = Database::connection();

        $stmt = $db->prepare("SELECT * FROM ho_so WHERE ma_ho_so = :ma AND cong_dan_id = :cid");
        $stmt->execute([':ma' => $maHoSo, ':cid' => $user['id']]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            return $this->redirect('/cong-dan/ho-so');
        }

        // Chặn đánh giá lặp lại (1 lần/hồ sơ)
        $existing = $db->query("SELECT id FROM khao_sat_sipas WHERE ho_so_id = {$hoSo['id']}")->fetchColumn();
        if ($existing) {
            Session::flash('error', 'Hồ sơ đã được đánh giá trước đó.');
            return $this->redirect("/cong-dan/ho-so/{$maHoSo}");
        }

        $d1 = max(1, min(5, (int)$request->input('diem_tiep_can', 5)));
        $d2 = max(1, min(5, (int)$request->input('diem_thu_tuc', 5)));
        $d3 = max(1, min(5, (int)$request->input('diem_cong_chuc', 5)));
        $d4 = max(1, min(5, (int)$request->input('diem_ket_qua', 5)));
        $d5 = max(1, min(5, (int)$request->input('diem_phan_anh', 5)));
        $avg = round(($d1 + $d2 + $d3 + $d4 + $d5) / 5, 2);
        $yKien = trim((string)$request->input('y_kien_dong_gop', ''));

        $insStmt = $db->prepare("INSERT INTO khao_sat_sipas 
            (ho_so_id, cong_dan_id, diem_tiep_can, diem_thu_tuc, diem_cong_chuc, diem_ket_qua, diem_phan_anh, diem_trung_binh, y_kien_dong_gop, created_at) 
            VALUES (:hid, :cid, :d1, :d2, :d3, :d4, :d5, :avg, :yk, NOW())");
        $insStmt->execute([
            ':hid' => $hoSo['id'],
            ':cid' => $user['id'],
            ':d1' => $d1,
            ':d2' => $d2,
            ':d3' => $d3,
            ':d4' => $d4,
            ':d5' => $d5,
            ':avg' => $avg,
            ':yk' => $yKien,
        ]);

        AuditService::log(
            $user['id'],
            'DANH_GIA_SIPAS',
            'khao_sat_sipas',
            (int)$db->lastInsertId(),
            null,
            ['ma_ho_so' => $maHoSo, 'diem_trung_binh' => $avg]
        );

        Session::flash('success', "Cảm ơn Quý khách đã hoàn thành đánh giá mức độ hài lòng SIPAS ({$avg}/5 sao)!");
        return $this->redirect("/cong-dan/ho-so/{$maHoSo}");
    }
}
