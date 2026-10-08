<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Integrations\Mock\BuuChinhGatewayMock;
use App\Integrations\Mock\EformSignerMock;
use App\Integrations\Mock\LgspGatewayMock;
use App\Integrations\Mock\SmsZnsNotificationMock;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\MaHoSoGenerator;
use App\Services\SlaEngine;
use App\Services\SoHoaService;
use App\Services\WorkflowEngine;
use PDO;

/**
 * MotCuaController – Phân hệ Bộ phận Một cửa điện tử (Cán bộ - PH02)
 * Căn cứ: NĐ 61/2018/NĐ-CP, NĐ 107/2021/NĐ-CP, TT 01/2018/TT-VPCP, QCVN 102:2016/BTTTT
 */
class MotCuaController extends Controller
{
    /**
     * Lấy ID đơn vị làm việc của cán bộ hiện tại
     */
    private function getCurrentDonViId(): int
    {
        $current = AuthService::currentDonVi();
        if (!empty($current['id'])) {
            return (int)$current['id'];
        }

        $user = AuthService::user();
        return (int)($user['don_vi_chinh_id'] ?? 1);
    }

    /**
     * Bàn làm việc Một cửa: Thẻ đếm KPI & Việc cần làm (GET /admin/mot-cua)
     */
    public function dashboard(Request $request): Response
    {
        $this->authorizeMotCua();
        $db = Database::connection();
        $donViId = $this->getCurrentDonViId();

        // 1. Thống kê 4 thẻ KPI
        // (a) Hồ sơ trực tuyến chờ tiếp nhận
        $stmtCho = $db->prepare("SELECT COUNT(*) FROM ho_so WHERE don_vi_tiep_nhan_id = :dvid AND trang_thai = 'CHO_TIEP_NHAN' AND is_deleted = 0");
        $stmtCho->execute([':dvid' => $donViId]);
        $countChoTiepNhan = (int)$stmtCho->fetchColumn();

        // (b) Tiếp nhận trong ngày hôm nay
        $stmtHomNay = $db->prepare("SELECT COUNT(*) FROM ho_so WHERE don_vi_tiep_nhan_id = :dvid AND DATE(ngay_tiep_nhan) = CURDATE() AND is_deleted = 0");
        $stmtHomNay->execute([':dvid' => $donViId]);
        $countTiepNhanHomNay = (int)$stmtHomNay->fetchColumn();

        // (c) Chờ trả kết quả (đã hoàn thành nhưng chưa trả)
        $stmtTra = $db->prepare("SELECT COUNT(*) FROM ho_so WHERE don_vi_tiep_nhan_id = :dvid AND trang_thai = 'HOAN_THANH' AND is_deleted = 0");
        $stmtTra->execute([':dvid' => $donViId]);
        $countChoTraKq = (int)$stmtTra->fetchColumn();

        // (d) Sắp quá hạn / Quá hạn (SLA Vàng hoặc Đỏ)
        $stmtSla = $db->prepare("SELECT COUNT(*) FROM ho_so WHERE don_vi_tiep_nhan_id = :dvid AND sla_mau IN ('VANG', 'DO') AND trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA', 'TU_CHOI', 'DA_HUY') AND is_deleted = 0");
        $stmtSla->execute([':dvid' => $donViId]);
        $countSapQuaHan = (int)$stmtSla->fetchColumn();

        // 2. Danh sách Việc cần làm (To-do list)
        // Lấy 8 hồ sơ trực tuyến mới nhất chờ Một cửa thụ lý
        $stmtTodoTrucTuyen = $db->prepare("SELECT hs.*, tt.ten_tthc 
                                          FROM ho_so hs 
                                          JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                                          WHERE hs.don_vi_tiep_nhan_id = :dvid AND hs.trang_thai = 'CHO_TIEP_NHAN' AND hs.is_deleted = 0 
                                          ORDER BY hs.id DESC LIMIT 8");
        $stmtTodoTrucTuyen->execute([':dvid' => $donViId]);
        $todoTrucTuyen = $stmtTodoTrucTuyen->fetchAll(PDO::FETCH_ASSOC);

        // Lấy 8 hồ sơ đã có kết quả chờ trả cho công dân
        $stmtTodoTraKq = $db->prepare("SELECT hs.*, tt.ten_tthc 
                                      FROM ho_so hs 
                                      JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                                      WHERE hs.don_vi_tiep_nhan_id = :dvid AND hs.trang_thai = 'HOAN_THANH' AND hs.is_deleted = 0 
                                      ORDER BY hs.ngay_hoan_thanh DESC LIMIT 8");
        $stmtTodoTraKq->execute([':dvid' => $donViId]);
        $todoTraKq = $stmtTodoTraKq->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/mot_cua/dashboard', [
            'pageTitle' => 'Bàn làm việc Bộ phận Một cửa | HP-eGov',
            'counts' => [
                'cho_tiep_nhan' => $countChoTiepNhan,
                'tiep_nhan_hom_nay' => $countTiepNhanHomNay,
                'cho_tra_kq' => $countChoTraKq,
                'sap_qua_han' => $countSapQuaHan,
            ],
            'todoTrucTuyen' => $todoTrucTuyen,
            'todoTraKq' => $todoTraKq,
        ]);
    }

    /**
     * Màn hình Tiếp nhận hồ sơ trực tiếp tại quầy (GET /admin/mot-cua/tiep-nhan)
     */
    public function tiepNhanForm(Request $request): Response
    {
        $this->authorizeMotCua();
        $db = Database::connection();
        $donViId = $this->getCurrentDonViId();
        $tthcId = (int)$request->query('tthc_id', 0);

        // Danh sách TTHC của cơ quan
        $tthcList = $db->query("SELECT id, ma_rut_gon, ma_tthc, ten_tthc, thoi_han_giai_quyet_ngay, phi_le_phi FROM thu_tuc_hanh_chinh WHERE trang_thai = 1 ORDER BY ma_rut_gon ASC")->fetchAll(PDO::FETCH_ASSOC);

        $selectedTthc = null;
        $thanhPhanHoSo = [];
        $eformSchema = null;

        if ($tthcId > 0) {
            $stmt = $db->prepare("SELECT * FROM thu_tuc_hanh_chinh WHERE id = :id AND trang_thai = 1 LIMIT 1");
            $stmt->execute([':id' => $tthcId]);
            $selectedTthc = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($selectedTthc) {
                // Lấy thành phần hồ sơ
                $stmtTp = $db->prepare("SELECT * FROM thanh_phan_ho_so WHERE tthc_id = :tid ORDER BY thu_tu ASC");
                $stmtTp->execute([':tid' => $tthcId]);
                $thanhPhanHoSo = $stmtTp->fetchAll(PDO::FETCH_ASSOC);

                // Lấy schema E-form
                $stmtEf = $db->prepare("SELECT cau_truc_json FROM bieu_mau_eform WHERE tthc_id = :tid AND trang_thai = 1 ORDER BY phien_ban DESC LIMIT 1");
                $stmtEf->execute([':tid' => $tthcId]);
                $efJson = $stmtEf->fetchColumn();
                if ($efJson) {
                    $eformSchema = json_decode((string)$efJson, true);
                }
            }
        }

        return $this->view('admin/mot_cua/tiep_nhan', [
            'pageTitle' => 'Tiếp nhận hồ sơ trực tiếp | Bộ phận Một cửa',
            'tthcList' => $tthcList,
            'selectedTthc' => $selectedTthc,
            'thanhPhanHoSo' => $thanhPhanHoSo,
            'eformSchema' => $eformSchema,
        ]);
    }

    /**
     * API Đối soát thông tin CCCD gắn chip với C06 qua LGSP (POST /admin/mot-cua/api/verify-cccd)
     */
    public function apiVerifyCccd(Request $request): Response
    {
        $this->authorizeMotCua();
        $cccd = trim((string)$request->input('so_cccd', ''));
        $hoTen = trim((string)$request->input('ho_ten', ''));
        $ngaySinh = trim((string)$request->input('ngay_sinh', ''));

        if (empty($cccd) || strlen($cccd) !== 12) {
            return Response::json([
                'success' => false,
                'message' => 'Số CCCD phải gồm đúng 12 chữ số theo quy chuẩn Bộ Công an.',
            ], 422);
        }

        $result = LgspGatewayMock::verifyCitizen($cccd, $hoTen, $ngaySinh);
        return Response::json($result);
    }

    /**
     * Lưu hồ sơ tiếp nhận trực tiếp, cấp mã QCVN 102 và số hóa NĐ 107/2021 (POST /admin/mot-cua/tiep-nhan)
     */
    public function submitTiepNhan(Request $request): Response
    {
        $this->authorizeMotCua();
        $user = AuthService::user();
        $canBoId = (int)$user['id'];
        $donViId = $this->getCurrentDonViId();
        $db = Database::connection();

        $tthcId = (int)$request->input('tthc_id', 0);
        $hoTen = trim((string)$request->input('ho_ten', ''));
        $soCccd = trim((string)$request->input('so_cccd', ''));
        $soDienThoai = trim((string)$request->input('so_dien_thoai', ''));
        $email = trim((string)$request->input('email', ''));
        $diaChi = trim((string)$request->input('dia_chi', ''));
        $hinhThucNhanKq = (string)$request->input('hinh_thuc_nhan_kq', 'TRUC_TIEP');
        $diaChiNhanKq = trim((string)$request->input('dia_chi_nhan_kq', ''));

        if ($tthcId <= 0 || empty($hoTen) || empty($soCccd)) {
            Session::flash('error', 'Vui lòng nhập đầy đủ thông tin TTHC, Họ tên và Số CCCD của người nộp.');
            return $this->redirect('/admin/mot-cua/tiep-nhan?tthc_id=' . $tthcId);
        }

        // Lấy thông tin TTHC và Quy trình
        $stmtTthc = $db->prepare("SELECT * FROM thu_tuc_hanh_chinh WHERE id = :id");
        $stmtTthc->execute([':id' => $tthcId]);
        $tthc = $stmtTthc->fetch(PDO::FETCH_ASSOC);
        if (!$tthc) {
            Session::flash('error', 'Thủ tục hành chính không hợp lệ.');
            return $this->redirect('/admin/mot-cua/tiep-nhan');
        }

        $quyTrinh = $db->query("SELECT * FROM quy_trinh WHERE tthc_id = {$tthcId} AND is_active = 1 ORDER BY phien_ban DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $quyTrinhId = $quyTrinh['id'] ?? null;
        $buocDauTienId = null;
        if ($quyTrinhId) {
            $buocDauTienId = (int)$db->query("SELECT id FROM quy_trinh_buoc WHERE quy_trinh_id = {$quyTrinhId} AND loai_buoc = 'TIEP_NHAN' ORDER BY stt_buoc ASC LIMIT 1")->fetchColumn();
        }

        // 1. Tìm hoặc tự động tạo tài khoản công dân trong hệ thống
        $stmtCd = $db->prepare("SELECT id FROM cong_dan WHERE so_cccd = :cccd LIMIT 1");
        $stmtCd->execute([':cccd' => $soCccd]);
        $congDanId = (int)$stmtCd->fetchColumn();

        if ($congDanId <= 0) {
            $insCd = $db->prepare("INSERT INTO cong_dan (loai_chu_the, ho_ten, so_cccd, so_dien_thoai, email, dia_chi_thuong_tru, trang_thai, created_at, updated_at) 
                                   VALUES ('CA_NHAN', :ten, :cccd, :sdt, :email, :dc, 1, NOW(), NOW())");
            $insCd->execute([
                ':ten' => $hoTen,
                ':cccd' => $soCccd,
                ':sdt' => $soDienThoai,
                ':email' => $email,
                ':dc' => $diaChi,
            ]);
            $congDanId = (int)$db->lastInsertId();
        }

        // 2. Cấp mã hồ sơ chuẩn QCVN 102:2016/BTTTT
        $maHoSo = MaHoSoGenerator::generate($donViId, (string)($tthc['ma_rut_gon'] ?? '0001'));

        // 3. Tính ngày hẹn trả theo SlaEngine (trừ ngày nghỉ lễ và cuối tuần)
        $thoiHanNgay = (int)($tthc['thoi_han_giai_quyet_ngay'] ?? 3);
        $ngayHenTra = SlaEngine::calculateNgayHenTra(date('Y-m-d H:i:s'), $thoiHanNgay);
        $tongGio = $thoiHanNgay * 8;

        // Dữ liệu E-Form
        $eformData = $request->input('eform_data', []);
        $eformFullPayload = [
            'data' => is_array($eformData) ? $eformData : [],
            'tiep_nhan_boi' => $user['ho_ten'],
            'kenh' => 'TRUC_TIEP',
        ];

        $db->beginTransaction();
        try {
            // 4. Tạo bản ghi hồ sơ
            $stmtHs = $db->prepare("INSERT INTO ho_so (
                ma_ho_so, tthc_id, quy_trinh_id, buoc_hien_tai_id, don_vi_tiep_nhan_id, don_vi_hien_tai_id,
                cong_dan_id, kenh_nop, nguoi_nop_ho_ten, nguoi_nop_cccd, nguoi_nop_sdt, nguoi_nop_email, nguoi_nop_dia_chi,
                du_lieu_eform, trang_thai, ngay_nop, ngay_tiep_nhan, ngay_hen_tra, sla_mau, tong_thoi_han_gio, thoi_gian_da_dung_gio,
                hinh_thuc_nhan_kq, dia_chi_nhan_kq, created_at, updated_at
            ) VALUES (
                :ma, :tid, :qid, :bid, :dvid, :dvhtid,
                :cid, 'TRUC_TIEP', :ten, :cccd, :sdt, :email, :dc,
                :eform, 'MOI_TIEP_NHAN', NOW(), NOW(), :hentra, 'XANH', :gio, 0,
                :ht, :dckq, NOW(), NOW()
            )");

            $stmtHs->execute([
                ':ma' => $maHoSo,
                ':tid' => $tthcId,
                ':qid' => $quyTrinhId ?: null,
                ':bid' => $buocDauTienId ?: null,
                ':dvid' => $donViId,
                ':dvhtid' => $donViId,
                ':cid' => $congDanId,
                ':ten' => $hoTen,
                ':cccd' => $soCccd,
                ':sdt' => $soDienThoai,
                ':email' => $email,
                ':dc' => $diaChi,
                ':eform' => json_encode($eformFullPayload, JSON_UNESCAPED_UNICODE),
                ':hentra' => $ngayHenTra,
                ':gio' => $tongGio,
                ':ht' => $hinhThucNhanKq,
                ':dckq' => $diaChiNhanKq,
            ]);

            $hoSoId = (int)$db->lastInsertId();

            // 5. Thực hiện quy trình số hóa 4 bước theo NĐ 107/2021/NĐ-CP
            $files = $request->files();
            $xacNhanBanChinhList = $request->input('xac_nhan_ban_chinh', []);

            if (!empty($files['tep_so_hoa']) && is_array($files['tep_so_hoa']['name'])) {
                foreach ($files['tep_so_hoa']['name'] as $thanhPhanId => $fileName) {
                    if (!empty($fileName) && $files['tep_so_hoa']['error'][$thanhPhanId] === UPLOAD_ERR_OK) {
                        $isBanChinh = !empty($xacNhanBanChinhList[$thanhPhanId]);
                        $singleFile = [
                            'name' => $files['tep_so_hoa']['name'][$thanhPhanId],
                            'type' => $files['tep_so_hoa']['type'][$thanhPhanId],
                            'tmp_name' => $files['tep_so_hoa']['tmp_name'][$thanhPhanId],
                            'error' => $files['tep_so_hoa']['error'][$thanhPhanId],
                            'size' => $files['tep_so_hoa']['size'][$thanhPhanId],
                        ];

                        $stmtTpName = $db->prepare("SELECT ten_giay_to FROM thanh_phan_ho_so WHERE id = :tpid");
                        $stmtTpName->execute([':tpid' => (int)$thanhPhanId]);
                        $tenGiayTo = (string)$stmtTpName->fetchColumn() ?: $fileName;

                        SoHoaService::processDigitization(
                            $hoSoId,
                            (int)$thanhPhanId,
                            $singleFile,
                            $canBoId,
                            $congDanId,
                            $isBanChinh,
                            $tenGiayTo
                        );
                    }
                }
            }

            // 6. Ghi nhật ký luân chuyển
            $stmtLc = $db->prepare("INSERT INTO ho_so_luan_chuyen (
                ho_so_id, tu_don_vi_id, den_don_vi_id, nguoi_chuyen_id, hanh_dong, 
                trang_thai_truoc, trang_thai_sau, noi_dung_y_kien, thoi_gian_chuyen
            ) VALUES (
                :hid, :tudvid, :dendvid, :ncid, 'TIEP_NHAN_TRUC_TIEP', 
                'MOI_TAO', 'MOI_TIEP_NHAN', :nd, NOW()
            )");
            $stmtLc->execute([
                ':hid' => $hoSoId,
                ':tudvid' => $donViId,
                ':dendvid' => $donViId,
                ':ncid' => $canBoId,
                ':nd' => "Cán bộ Một cửa ({$user['ho_ten']}) tiếp nhận trực tiếp và hoàn tất số hóa đầu vào NĐ 107/2021.",
            ]);

            $db->commit();

            // 7. Ghi Audit Log WORM
            AuditService::log(
                $canBoId,
                'MOT_CUA_TIEP_NHAN_TRUC_TIEP',
                'ho_so',
                $hoSoId,
                null,
                ['ma_ho_so' => $maHoSo, 'tthc' => $tthc['ten_tthc']]
            );

            // 8. Tự động chuyển bước quy trình tiếp theo qua WorkflowEngine
            WorkflowEngine::transition($hoSoId, $canBoId, 'CHUYEN', 'Tiếp nhận Một cửa hoàn tất, chuyển thụ lý phòng chuyên môn.');

            // 9. Phát thông báo SMS/Zalo mock
            if (!empty($soDienThoai)) {
                SmsZnsNotificationMock::notify(
                    $soDienThoai,
                    $maHoSo,
                    "Tiếp nhận hồ sơ Một cửa - Mã {$maHoSo}",
                    "UBND TP Hải Phòng: Đã tiếp nhận hồ sơ [{$tthc['ten_tthc']}]. Hẹn trả kết quả: {$ngayHenTra}.",
                    $congDanId
                );
            }

            Session::flash('success', "Tiếp nhận hồ sơ thành công! Mã hồ sơ: {$maHoSo}");
            return $this->redirect("/admin/mot-cua/in-giay-hen/{$hoSoId}");

        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Có lỗi khi tiếp nhận hồ sơ: ' . $e->getMessage());
            return $this->redirect("/admin/mot-cua/tiep-nhan?tthc_id=" . $tthcId);
        }
    }

    /**
     * Mẫu In Giấy tiếp nhận hồ sơ và hẹn trả kết quả (Mẫu số 01 TT 01/2018/TT-VPCP, GET /admin/mot-cua/in-giay-hen/{id})
     */
    public function inGiayHen(Request $request): Response
    {
        if (!AuthService::check('can_bo')) {
            return $this->redirect('/admin/dang-nhap');
        }
        $id = (int)$request->routeParam('id', 0);
        $db = Database::connection();

        $stmt = $db->prepare("SELECT hs.*, tt.ten_tthc, tt.ma_rut_gon, dv.ten_don_vi, dv.ma_dinh_danh 
                              FROM ho_so hs 
                              JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                              JOIN don_vi dv ON hs.don_vi_tiep_nhan_id = dv.id 
                              WHERE hs.id = :id AND hs.is_deleted = 0 LIMIT 1");
        $stmt->execute([':id' => $id]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            Session::flash('error', 'Không tìm thấy hồ sơ.');
            return $this->redirect('/admin/mot-cua');
        }

        // Lấy danh sách thành phần giấy tờ
        $stmtTl = $db->prepare("SELECT tl.*, tp.ten_giay_to 
                                FROM ho_so_tai_lieu tl 
                                LEFT JOIN thanh_phan_ho_so tp ON tl.thanh_phan_id = tp.id 
                                WHERE tl.ho_so_id = :hid");
        $stmtTl->execute([':hid' => $id]);
        $taiLieuList = $stmtTl->fetchAll(PDO::FETCH_ASSOC);

        $currentUser = AuthService::user();

        return $this->view('admin/mot_cua/in_giay_hen', [
            'pageTitle' => "In Giấy tiếp nhận & Hẹn trả: {$hoSo['ma_ho_so']}",
            'hoSo' => $hoSo,
            'taiLieuList' => $taiLieuList,
            'canBo' => $currentUser,
        ]);
    }

    /**
     * Danh sách hồ sơ trực tuyến chờ Một cửa duyệt (GET /admin/mot-cua/ho-so-truc-tuyen)
     */
    public function danhSachTrucTuyen(Request $request): Response
    {
        $this->authorizeMotCua();
        $db = Database::connection();
        $donViId = $this->getCurrentDonViId();
        $q = trim((string)$request->query('q', ''));

        $sql = "SELECT hs.*, tt.ten_tthc, tt.ma_rut_gon 
                FROM ho_so hs 
                JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                WHERE hs.don_vi_tiep_nhan_id = :dvid AND hs.trang_thai = 'CHO_TIEP_NHAN' AND hs.is_deleted = 0";
        $params = [':dvid' => $donViId];

        if (!empty($q)) {
            $sql .= " AND (hs.ma_ho_so LIKE :q OR hs.nguoi_nop_ho_ten LIKE :q OR hs.nguoi_nop_cccd LIKE :q)";
            $params[':q'] = "%{$q}%";
        }

        $sql .= " ORDER BY hs.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $hoSoList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/mot_cua/ho_so_truc_tuyen', [
            'pageTitle' => 'Hồ sơ trực tuyến chờ tiếp nhận | Bộ phận Một cửa',
            'hoSoList' => $hoSoList,
            'q' => $q,
        ]);
    }

    /**
     * Chi tiết hồ sơ trực tuyến để thẩm tra (GET /admin/mot-cua/ho-so-truc-tuyen/{id})
     */
    public function chiTietTrucTuyen(Request $request): Response
    {
        $this->authorizeMotCua();
        $id = (int)$request->routeParam('id', 0);
        $db = Database::connection();

        $stmt = $db->prepare("SELECT hs.*, tt.ten_tthc, tt.thoi_han_giai_quyet_ngay, tt.phi_le_phi, dv.ten_don_vi 
                              FROM ho_so hs 
                              JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                              JOIN don_vi dv ON hs.don_vi_tiep_nhan_id = dv.id 
                              WHERE hs.id = :id AND hs.is_deleted = 0 LIMIT 1");
        $stmt->execute([':id' => $id]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            Session::flash('error', 'Không tìm thấy hồ sơ.');
            return $this->redirect('/admin/mot-cua/ho-so-truc-tuyen');
        }

        $stmtTl = $db->prepare("SELECT tl.*, tp.ten_giay_to 
                                FROM ho_so_tai_lieu tl 
                                LEFT JOIN thanh_phan_ho_so tp ON tl.thanh_phan_id = tp.id 
                                WHERE tl.ho_so_id = :hid");
        $stmtTl->execute([':hid' => $id]);
        $taiLieuList = $stmtTl->fetchAll(PDO::FETCH_ASSOC);

        $eformDecoded = !empty($hoSo['du_lieu_eform']) ? json_decode($hoSo['du_lieu_eform'], true) : [];

        return $this->view('admin/mot_cua/chi_tiet_truc_tuyen', [
            'pageTitle' => "Thẩm tra hồ sơ trực tuyến: {$hoSo['ma_ho_so']}",
            'hoSo' => $hoSo,
            'taiLieuList' => $taiLieuList,
            'eformData' => $eformDecoded['data'] ?? [],
            'signature' => $eformDecoded['signature'] ?? null,
        ]);
    }

    /**
     * Xác nhận Tiếp nhận hồ sơ trực tuyến (POST /admin/mot-cua/ho-so-truc-tuyen/{id}/tiep-nhan)
     */
    public function tiepNhanTrucTuyen(Request $request): Response
    {
        $this->authorizeMotCua();
        $id = (int)$request->routeParam('id', 0);
        $user = AuthService::user();
        $canBoId = (int)$user['id'];
        $db = Database::connection();

        $stmt = $db->prepare("SELECT hs.*, tt.thoi_han_giai_quyet_ngay, tt.ten_tthc 
                              FROM ho_so hs 
                              JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                              WHERE hs.id = :id AND hs.is_deleted = 0 LIMIT 1");
        $stmt->execute([':id' => $id]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo || $hoSo['trang_thai'] !== 'CHO_TIEP_NHAN') {
            Session::flash('error', 'Hồ sơ không ở trạng thái Chờ tiếp nhận.');
            return $this->redirect('/admin/mot-cua/ho-so-truc-tuyen');
        }

        $thoiHanNgay = (int)($hoSo['thoi_han_giai_quyet_ngay'] ?? 3);
        $ngayHenTra = SlaEngine::calculateNgayHenTra(date('Y-m-d H:i:s'), $thoiHanNgay);

        $db->beginTransaction();
        try {
            $upd = $db->prepare("UPDATE ho_so SET 
                trang_thai = 'MOI_TIEP_NHAN',
                ngay_tiep_nhan = NOW(),
                ngay_hen_tra = :hentra,
                updated_at = NOW()
                WHERE id = :id");
            $upd->execute([':hentra' => $ngayHenTra, ':id' => $id]);

            // Ghi nhật ký luân chuyển
            $db->prepare("INSERT INTO ho_so_luan_chuyen (
                ho_so_id, tu_don_vi_id, den_don_vi_id, nguoi_chuyen_id, hanh_dong, 
                trang_thai_truoc, trang_thai_sau, noi_dung_y_kien, thoi_gian_chuyen
            ) VALUES (
                :hid, :dvid, :dvid, :ncid, 'TIEP_NHAN_TRUC_TUYEN', 
                'CHO_TIEP_NHAN', 'MOI_TIEP_NHAN', :nd, NOW()
            )")->execute([
                ':hid' => $id,
                ':dvid' => $hoSo['don_vi_tiep_nhan_id'],
                ':ncid' => $canBoId,
                ':nd' => "Cán bộ Một cửa ({$user['ho_ten']}) phê duyệt tiếp nhận hồ sơ trực tuyến hợp lệ.",
            ]);

            $db->commit();

            // Chuyển bước quy trình
            WorkflowEngine::transition($id, $canBoId, 'CHUYEN', 'Tiếp nhận hồ sơ trực tuyến, chuyển thụ lý.');

            // Thông báo công dân
            if (!empty($hoSo['nguoi_nop_sdt'])) {
                SmsZnsNotificationMock::notify(
                    $hoSo['nguoi_nop_sdt'],
                    $hoSo['ma_ho_so'],
                    "Hồ sơ đã được tiếp nhận - Mã {$hoSo['ma_ho_so']}",
                    "Hồ sơ [{$hoSo['ten_tthc']}] của Quý khách đã được tiếp nhận chính thức. Hẹn trả: {$ngayHenTra}.",
                    $hoSo['cong_dan_id']
                );
            }

            Session::flash('success', "Đã tiếp nhận hồ sơ {$hoSo['ma_ho_so']} thành công!");
            return $this->redirect('/admin/mot-cua/ho-so-truc-tuyen');

        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Có lỗi khi tiếp nhận hồ sơ: ' . $e->getMessage());
            return $this->redirect('/admin/mot-cua/ho-so-truc-tuyen/' . $id);
        }
    }

    /**
     * Yêu cầu bổ sung hồ sơ trực tuyến (POST /admin/mot-cua/ho-so-truc-tuyen/{id}/yeu-cau-bo-sung)
     */
    public function yeuCauBoSung(Request $request): Response
    {
        $this->authorizeMotCua();
        $id = (int)$request->routeParam('id', 0);
        $user = AuthService::user();
        $canBoId = (int)$user['id'];
        $noiDung = trim((string)$request->input('noi_dung_yeu_cau', ''));

        if (empty($noiDung)) {
            Session::flash('error', 'Vui lòng nêu rõ nội dung yêu cầu bổ sung theo quy định.');
            return $this->redirect('/admin/mot-cua/ho-so-truc-tuyen/' . $id);
        }

        $db = Database::connection();
        $hoSo = $db->query("SELECT * FROM ho_so WHERE id = {$id} AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            Session::flash('error', 'Không tìm thấy hồ sơ.');
            return $this->redirect('/admin/mot-cua/ho-so-truc-tuyen');
        }

        $db->beginTransaction();
        try {
            $db->prepare("UPDATE ho_so SET trang_thai = 'CHO_BO_SUNG', updated_at = NOW() WHERE id = :id")->execute([':id' => $id]);

            $db->prepare("INSERT INTO ho_so_luan_chuyen (
                ho_so_id, tu_don_vi_id, den_don_vi_id, nguoi_chuyen_id, hanh_dong, 
                trang_thai_truoc, trang_thai_sau, noi_dung_y_kien, thoi_gian_chuyen
            ) VALUES (
                :hid, :dvid, :dvid, :ncid, 'YEU_CAU_BO_SUNG', 
                :ttt, 'CHO_BO_SUNG', :nd, NOW()
            )")->execute([
                ':hid' => $id,
                ':dvid' => $hoSo['don_vi_tiep_nhan_id'],
                ':ncid' => $canBoId,
                ':ttt' => $hoSo['trang_thai'],
                ':nd' => "Yêu cầu bổ sung: " . $noiDung,
            ]);

            $db->commit();

            if (!empty($hoSo['nguoi_nop_sdt'])) {
                SmsZnsNotificationMock::notify(
                    $hoSo['nguoi_nop_sdt'],
                    $hoSo['ma_ho_so'],
                    "Yêu cầu bổ sung hồ sơ - Mã {$hoSo['ma_ho_so']}",
                    "Hồ sơ của Quý khách cần bổ sung thông tin: {$noiDung}. Vui lòng đăng nhập Cổng DVC để hoàn thiện.",
                    $hoSo['cong_dan_id']
                );
            }

            Session::flash('success', "Đã gửi yêu cầu bổ sung hồ sơ tới công dân.");
            return $this->redirect('/admin/mot-cua/ho-so-truc-tuyen/' . $id);

        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Lỗi: ' . $e->getMessage());
            return $this->redirect('/admin/mot-cua/ho-so-truc-tuyen/' . $id);
        }
    }

    /**
     * In Phiếu yêu cầu bổ sung hoàn thiện hồ sơ (Mẫu số 02 TT 01/2018/TT-VPCP, GET /admin/mot-cua/in-phieu-bo-sung/{id})
     */
    public function inPhieuBoSung(Request $request): Response
    {
        $this->authorizeMotCua();
        $id = (int)$request->routeParam('id', 0);
        $db = Database::connection();

        $stmt = $db->prepare("SELECT hs.*, tt.ten_tthc, dv.ten_don_vi 
                              FROM ho_so hs 
                              JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                              JOIN don_vi dv ON hs.don_vi_tiep_nhan_id = dv.id 
                              WHERE hs.id = :id AND hs.is_deleted = 0 LIMIT 1");
        $stmt->execute([':id' => $id]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            Session::flash('error', 'Không tìm thấy hồ sơ.');
            return $this->redirect('/admin/mot-cua');
        }

        $stmtLc = $db->prepare("SELECT * FROM ho_so_luan_chuyen WHERE ho_so_id = :hid AND trang_thai_sau = 'CHO_BO_SUNG' ORDER BY id DESC LIMIT 1");
        $stmtLc->execute([':hid' => $id]);
        $reqLc = $stmtLc->fetch(PDO::FETCH_ASSOC);

        return $this->view('admin/mot_cua/in_phieu_bo_sung', [
            'pageTitle' => "Phiếu yêu cầu bổ sung: {$hoSo['ma_ho_so']}",
            'hoSo' => $hoSo,
            'reqLc' => $reqLc,
            'canBo' => AuthService::user(),
        ]);
    }

    /**
     * Từ chối tiếp nhận hồ sơ trực tuyến (POST /admin/mot-cua/ho-so-truc-tuyen/{id}/tu-choi)
     */
    public function tuChoiTiepNhan(Request $request): Response
    {
        $this->authorizeMotCua();
        $id = (int)$request->routeParam('id', 0);
        $user = AuthService::user();
        $canBoId = (int)$user['id'];
        $lyDo = trim((string)$request->input('ly_do_tu_choi', ''));

        if (empty($lyDo)) {
            Session::flash('error', 'Bắt buộc phải nhập lý do từ chối tiếp nhận hồ sơ.');
            return $this->redirect('/admin/mot-cua/ho-so-truc-tuyen/' . $id);
        }

        $db = Database::connection();
        $hoSo = $db->query("SELECT * FROM ho_so WHERE id = {$id} AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            Session::flash('error', 'Không tìm thấy hồ sơ.');
            return $this->redirect('/admin/mot-cua/ho-so-truc-tuyen');
        }

        $db->beginTransaction();
        try {
            $db->prepare("UPDATE ho_so SET trang_thai = 'TU_CHOI', updated_at = NOW() WHERE id = :id")->execute([':id' => $id]);

            $db->prepare("INSERT INTO ho_so_luan_chuyen (
                ho_so_id, tu_don_vi_id, den_don_vi_id, nguoi_chuyen_id, hanh_dong, 
                trang_thai_truoc, trang_thai_sau, noi_dung_y_kien, thoi_gian_chuyen
            ) VALUES (
                :hid, :dvid, :dvid, :ncid, 'TU_CHOI_TIEP_NHAN', 
                :ttt, 'TU_CHOI', :nd, NOW()
            )")->execute([
                ':hid' => $id,
                ':dvid' => $hoSo['don_vi_tiep_nhan_id'],
                ':ncid' => $canBoId,
                ':ttt' => $hoSo['trang_thai'],
                ':nd' => "Từ chối tiếp nhận: " . $lyDo,
            ]);

            $db->commit();

            if (!empty($hoSo['nguoi_nop_sdt'])) {
                SmsZnsNotificationMock::notify(
                    $hoSo['nguoi_nop_sdt'],
                    $hoSo['ma_ho_so'],
                    "Thông báo từ chối tiếp nhận hồ sơ - Mã {$hoSo['ma_ho_so']}",
                    "Hồ sơ của Quý khách không đủ điều kiện tiếp nhận. Lý do: {$lyDo}.",
                    $hoSo['cong_dan_id']
                );
            }

            Session::flash('success', "Đã từ chối tiếp nhận hồ sơ.");
            return $this->redirect('/admin/mot-cua/in-phieu-tu-choi/' . $id);

        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Lỗi: ' . $e->getMessage());
            return $this->redirect('/admin/mot-cua/ho-so-truc-tuyen/' . $id);
        }
    }

    /**
     * In Phiếu từ chối tiếp nhận giải quyết hồ sơ (Mẫu số 03 TT 01/2018/TT-VPCP, GET /admin/mot-cua/in-phieu-tu-choi/{id})
     */
    public function inPhieuTuChoi(Request $request): Response
    {
        $this->authorizeMotCua();
        $id = (int)$request->routeParam('id', 0);
        $db = Database::connection();

        $stmt = $db->prepare("SELECT hs.*, tt.ten_tthc, dv.ten_don_vi 
                              FROM ho_so hs 
                              JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                              JOIN don_vi dv ON hs.don_vi_tiep_nhan_id = dv.id 
                              WHERE hs.id = :id AND hs.is_deleted = 0 LIMIT 1");
        $stmt->execute([':id' => $id]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            Session::flash('error', 'Không tìm thấy hồ sơ.');
            return $this->redirect('/admin/mot-cua');
        }

        $stmtLc = $db->prepare("SELECT * FROM ho_so_luan_chuyen WHERE ho_so_id = :hid AND trang_thai_sau = 'TU_CHOI' ORDER BY id DESC LIMIT 1");
        $stmtLc->execute([':hid' => $id]);
        $rejectLc = $stmtLc->fetch(PDO::FETCH_ASSOC);

        return $this->view('admin/mot_cua/in_phieu_tu_choi', [
            'pageTitle' => "Phiếu từ chối tiếp nhận: {$hoSo['ma_ho_so']}",
            'hoSo' => $hoSo,
            'rejectLc' => $rejectLc,
            'canBo' => AuthService::user(),
        ]);
    }

    /**
     * Danh sách hồ sơ HOAN_THANH chờ trả kết quả (GET /admin/mot-cua/tra-ket-qua)
     */
    public function traKetQua(Request $request): Response
    {
        $this->authorizeMotCua();
        $db = Database::connection();
        $donViId = $this->getCurrentDonViId();
        $q = trim((string)$request->query('q', ''));

        $sql = "SELECT hs.*, tt.ten_tthc, xl.so_van_ban as so_vb_xin_loi 
                FROM ho_so hs 
                JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                LEFT JOIN van_ban_xin_loi xl ON hs.id = xl.ho_so_id 
                WHERE hs.don_vi_tiep_nhan_id = :dvid AND hs.trang_thai = 'HOAN_THANH' AND hs.is_deleted = 0";
        $params = [':dvid' => $donViId];

        if (!empty($q)) {
            $sql .= " AND (hs.ma_ho_so LIKE :q OR hs.nguoi_nop_ho_ten LIKE :q)";
            $params[':q'] = "%{$q}%";
        }

        $sql .= " ORDER BY hs.ngay_hoan_thanh DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $hoSoList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/mot_cua/tra_ket_qua', [
            'pageTitle' => 'Trả kết quả giải quyết TTHC | Bộ phận Một cửa',
            'hoSoList' => $hoSoList,
            'q' => $q,
        ]);
    }

    /**
     * Xác nhận Trả kết quả: Trực tiếp tại quầy hoặc gửi VNPost (POST /admin/mot-cua/tra-ket-qua/{id})
     */
    public function submitTraKetQua(Request $request): Response
    {
        $this->authorizeMotCua();
        $id = (int)$request->routeParam('id', 0);
        $user = AuthService::user();
        $canBoId = (int)$user['id'];
        $db = Database::connection();

        $hinhThuc = (string)$request->input('hinh_thuc_tra', 'TRUC_TIEP'); // 'TRUC_TIEP' hoặc 'BUU_DIEN'
        $nguoiNhan = trim((string)$request->input('nguoi_nhan_ho_ten', ''));
        $cccdNhan = trim((string)$request->input('nguoi_nhan_cccd', ''));
        $diaChiBuuDien = trim((string)$request->input('dia_chi_buu_dien', ''));
        $sdtNhan = trim((string)$request->input('so_dien_thoai_nhan', ''));

        $hoSo = $db->query("SELECT * FROM ho_so WHERE id = {$id} AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        if (!$hoSo || $hoSo['trang_thai'] !== 'HOAN_THANH') {
            Session::flash('error', 'Hồ sơ không ở trạng thái Hoàn thành chờ trả kết quả.');
            return $this->redirect('/admin/mot-cua/tra-ket-qua');
        }

        $db->beginTransaction();
        try {
            $maVanDon = null;
            $ghiChuHanhDong = '';

            if ($hinhThuc === 'BUU_DIEN') {
                // Tạo vận đơn bưu điện VNPost qua mock adapter
                $shipment = BuuChinhGatewayMock::createShipment(
                    $hoSo['ma_ho_so'],
                    $nguoiNhan ?: $hoSo['nguoi_nop_ho_ten'],
                    $diaChiBuuDien ?: ($hoSo['dia_chi_nhan_kq'] ?: $hoSo['nguoi_nop_dia_chi']),
                    $sdtNhan ?: $hoSo['nguoi_nop_sdt']
                );
                $maVanDon = $shipment['ma_van_don'];
                $ghiChuHanhDong = "Đã chuyển phát kết quả qua Bưu điện VNPost. Mã vận đơn: {$maVanDon}.";
            } else {
                $ghiChuHanhDong = "Đã trả kết quả trực tiếp tại Bộ phận Một cửa. Người nhận: {$nguoiNhan} (CCCD: {$cccdNhan}).";
            }

            // Cập nhật hồ sơ
            $upd = $db->prepare("UPDATE ho_so SET 
                trang_thai = 'DA_TRA_KET_QUA',
                ngay_tra_ket_qua = NOW(),
                ma_van_don_vnpost = :mvd,
                updated_at = NOW()
                WHERE id = :id");
            $upd->execute([':mvd' => $maVanDon, ':id' => $id]);

            // Ghi nhật ký luân chuyển
            $db->prepare("INSERT INTO ho_so_luan_chuyen (
                ho_so_id, tu_don_vi_id, den_don_vi_id, nguoi_chuyen_id, hanh_dong, 
                trang_thai_truoc, trang_thai_sau, noi_dung_y_kien, thoi_gian_chuyen
            ) VALUES (
                :hid, :dvid, :dvid, :ncid, 'TRA_KET_QUA', 
                'HOAN_THANH', 'DA_TRA_KET_QUA', :nd, NOW()
            )")->execute([
                ':hid' => $id,
                ':dvid' => $hoSo['don_vi_tiep_nhan_id'],
                ':ncid' => $canBoId,
                ':nd' => $ghiChuHanhDong,
            ]);

            $db->commit();

            // Gửi thông báo hoàn tất cho công dân
            if (!empty($hoSo['nguoi_nop_sdt'])) {
                $thongBaoMsg = ($hinhThuc === 'BUU_DIEN')
                    ? "Hồ sơ {$hoSo['ma_ho_so']} đã được gửi qua VNPost. Mã vận đơn: {$maVanDon}."
                    : "Hồ sơ {$hoSo['ma_ho_so']} đã được nhận bàn giao kết quả tại Bộ phận Một cửa.";
                SmsZnsNotificationMock::notify(
                    $hoSo['nguoi_nop_sdt'],
                    $hoSo['ma_ho_so'],
                    "Hoàn tất trả kết quả - Mã {$hoSo['ma_ho_so']}",
                    $thongBaoMsg,
                    $hoSo['cong_dan_id']
                );
            }

            Session::flash('success', "Đã xác nhận trả kết quả thành công cho hồ sơ {$hoSo['ma_ho_so']}.");
            return $this->redirect('/admin/mot-cua/tra-ket-qua');

        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Có lỗi khi trả kết quả: ' . $e->getMessage());
            return $this->redirect('/admin/mot-cua/tra-ket-qua');
        }
    }

    /**
     * Đề xuất hủy hồ sơ (POST /admin/mot-cua/de-xuat-huy/{id})
     */
    public function deXuatHuy(Request $request): Response
    {
        $this->authorizeMotCua();
        $id = (int)$request->routeParam('id', 0);
        $user = AuthService::user();
        $canBoId = (int)$user['id'];
        $canCuHuy = trim((string)$request->input('can_cu_huy', ''));
        $bienBanGiaiTrinh = trim((string)$request->input('bien_ban_giai_trinh', ''));

        if (empty($canCuHuy) || empty($bienBanGiaiTrinh)) {
            Session::flash('error', 'Bắt buộc phải nhập căn cứ pháp lý và biên bản giải trình đề xuất hủy hồ sơ.');
            return $this->redirect('/admin/mot-cua');
        }

        $db = Database::connection();
        $hoSo = $db->query("SELECT * FROM ho_so WHERE id = {$id} AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            Session::flash('error', 'Không tìm thấy hồ sơ.');
            return $this->redirect('/admin/mot-cua');
        }

        $db->beginTransaction();
        try {
            $upd = $db->prepare("UPDATE ho_so SET 
                can_cu_huy = :cc,
                bien_ban_giai_trinh = :bb,
                trang_thai = 'CHO_DUYET_HUY',
                updated_at = NOW()
                WHERE id = :id");
            $upd->execute([':cc' => $canCuHuy, ':bb' => $bienBanGiaiTrinh, ':id' => $id]);

            // Ghi nhật ký luân chuyển
            $db->prepare("INSERT INTO ho_so_luan_chuyen (
                ho_so_id, tu_don_vi_id, den_don_vi_id, nguoi_chuyen_id, hanh_dong, 
                trang_thai_truoc, trang_thai_sau, noi_dung_y_kien, thoi_gian_chuyen
            ) VALUES (
                :hid, :dvid, :dvid, :ncid, 'DE_XUAT_HUY', 
                :ttt, 'CHO_DUYET_HUY', :nd, NOW()
            )")->execute([
                ':hid' => $id,
                ':dvid' => $hoSo['don_vi_tiep_nhan_id'],
                ':ncid' => $canBoId,
                ':ttt' => $hoSo['trang_thai'],
                ':nd' => "Cán bộ Một cửa đề xuất hủy hồ sơ. Căn cứ: {$canCuHuy}. Lý do: {$bienBanGiaiTrinh}",
            ]);

            $db->commit();

            Session::flash('success', "Đã gửi đề xuất hủy hồ sơ {$hoSo['ma_ho_so']} tới Lãnh đạo cơ quan xem xét duyệt.");
            return $this->redirect('/admin/mot-cua');

        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Lỗi: ' . $e->getMessage());
            return $this->redirect('/admin/mot-cua');
        }
    }

    /**
     * Sổ theo dõi hồ sơ Một cửa (Mẫu số 06 TT 01/2018/TT-VPCP, GET /admin/mot-cua/so-theo-doi)
     */
    public function soTheoDoi(Request $request): Response
    {
        $this->authorizeMotCua();
        $db = Database::connection();
        $donViId = $this->getCurrentDonViId();

        $tuNgay = trim((string)$request->query('tu_ngay', date('Y-m-01')));
        $denNgay = trim((string)$request->query('den_ngay', date('Y-m-d')));
        $tthcId = (int)$request->query('tthc_id', 0);
        $trangThai = trim((string)$request->query('trang_thai', ''));

        $sql = "SELECT hs.*, tt.ten_tthc, tt.ma_rut_gon 
                FROM ho_so hs 
                JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                WHERE hs.don_vi_tiep_nhan_id = :dvid AND hs.is_deleted = 0";
        $params = [':dvid' => $donViId];

        if (!empty($tuNgay)) {
            $sql .= " AND DATE(hs.ngay_tiep_nhan) >= :tu";
            $params[':tu'] = $tuNgay;
        }
        if (!empty($denNgay)) {
            $sql .= " AND DATE(hs.ngay_tiep_nhan) <= :den";
            $params[':den'] = $denNgay;
        }
        if ($tthcId > 0) {
            $sql .= " AND hs.tthc_id = :tid";
            $params[':tid'] = $tthcId;
        }
        if (!empty($trangThai)) {
            $sql .= " AND hs.trang_thai = :st";
            $params[':st'] = $trangThai;
        }

        $sql .= " ORDER BY hs.ngay_tiep_nhan DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $hoSoList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Danh sách TTHC để lọc
        $tthcList = $db->query("SELECT id, ten_tthc, ma_rut_gon FROM thu_tuc_hanh_chinh WHERE trang_thai = 1 ORDER BY ten_tthc ASC")->fetchAll(PDO::FETCH_ASSOC);

        $donVi = $db->query("SELECT ten_don_vi FROM don_vi WHERE id = {$donViId}")->fetch(PDO::FETCH_ASSOC);

        return $this->view('admin/mot_cua/so_theo_doi', [
            'pageTitle' => 'Sổ theo dõi hồ sơ Một cửa (Mẫu số 06) | HP-eGov',
            'hoSoList' => $hoSoList,
            'tthcList' => $tthcList,
            'donVi' => $donVi,
            'filters' => [
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay,
                'tthc_id' => $tthcId,
                'trang_thai' => $trangThai,
            ],
        ]);
    }

    /**
     * Xuất Sổ theo dõi hồ sơ dạng CSV UTF-8 có BOM (GET /admin/mot-cua/so-theo-doi/export)
     */
    public function exportSoTheoDoi(Request $request): Response
    {
        $this->authorizeMotCua();
        $db = Database::connection();
        $donViId = $this->getCurrentDonViId();

        $tuNgay = trim((string)$request->query('tu_ngay', date('Y-m-01')));
        $denNgay = trim((string)$request->query('den_ngay', date('Y-m-d')));

        $sql = "SELECT hs.*, tt.ten_tthc, tt.ma_rut_gon 
                FROM ho_so hs 
                JOIN thu_tuc_hanh_chinh tt ON hs.tthc_id = tt.id 
                WHERE hs.don_vi_tiep_nhan_id = :dvid AND hs.is_deleted = 0";
        $params = [':dvid' => $donViId];

        if (!empty($tuNgay)) {
            $sql .= " AND DATE(hs.ngay_tiep_nhan) >= :tu";
            $params[':tu'] = $tuNgay;
        }
        if (!empty($denNgay)) {
            $sql .= " AND DATE(hs.ngay_tiep_nhan) <= :den";
            $params[':den'] = $denNgay;
        }

        $sql .= " ORDER BY hs.ngay_tiep_nhan ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = [
            'STT', 'Mã hồ sơ', 'Tên thủ tục hành chính', 'Người nộp hồ sơ', 
            'Số CCCD/ĐD', 'Số điện thoại', 'Ngày tiếp nhận', 'Ngày hẹn trả', 
            'Trạng thái', 'Ngày trả kết quả', 'Hình thức nhận', 'Mã vận đơn VNPost'
        ];

        $output = "\xEF\xBB\xBF"; // UTF-8 BOM
        $output .= implode(',', array_map(fn($h) => '"' . str_replace('"', '""', $h) . '"', $headers)) . "\r\n";

        $stt = 1;
        foreach ($records as $r) {
            $row = [
                $stt++,
                $r['ma_ho_so'],
                $r['ten_tthc'],
                $r['nguoi_nop_ho_ten'],
                $r['nguoi_nop_cccd'],
                $r['nguoi_nop_sdt'],
                !empty($r['ngay_tiep_nhan']) ? date('d/m/Y H:i', strtotime($r['ngay_tiep_nhan'])) : '',
                !empty($r['ngay_hen_tra']) ? date('d/m/Y H:i', strtotime($r['ngay_hen_tra'])) : '',
                $r['trang_thai'],
                !empty($r['ngay_tra_ket_qua']) ? date('d/m/Y H:i', strtotime($r['ngay_tra_ket_qua'])) : '',
                $r['hinh_thuc_nhan_kq'],
                $r['ma_van_don_vnpost'] ?? '',
            ];
            $output .= implode(',', array_map(fn($c) => '"' . str_replace('"', '""', (string)$c) . '"', $row)) . "\r\n";
        }

        $filename = "So_Theo_Doi_Mot_Cua_" . date('Ymd_His') . ".csv";
        return Response::downloadContent($output, $filename, 'text/csv; charset=UTF-8');
    }

    /**
     * Kiểm tra phân quyền cán bộ Một cửa
     */
    private function authorizeMotCua(): void
    {
        if (!AuthService::check('can_bo')) {
            throw new \RuntimeException("Yêu cầu đăng nhập tài khoản cán bộ công chức.");
        }

        // Cán bộ phải có vai trò CB_MOT_CUA hoặc SYSADMIN
        if (!AuthService::hasRole('CB_MOT_CUA') && !AuthService::hasRole('SYSADMIN')) {
            throw new \RuntimeException("Bạn không có quyền truy cập vào Phân hệ Một cửa điện tử (Yêu cầu vai trò CB_MOT_CUA).");
        }
    }
}
