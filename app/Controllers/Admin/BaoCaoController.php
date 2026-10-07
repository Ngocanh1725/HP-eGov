<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\ReportingService;
use PDO;

/**
 * Class BaoCaoController – Phân hệ Báo cáo Thống kê Động (PH08)
 * Triển khai theo Thông tư 01/2018/TT-VPCP, Nghị định 107/2021/NĐ-CP, Quyết định 876/QĐ-BNV
 * Hỗ trợ bộ lọc động theo kỳ, đơn vị, lĩnh vực; Xuất CSV UTF-8 BOM và In A4 ngang chuẩn thể thức
 */
class BaoCaoController extends Controller
{
    /**
     * Danh mục các mẫu báo cáo thống kê hành chính
     */
    public function index(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        return $this->view('admin/bao_cao/index', [
            'title' => 'Trung tâm Báo cáo Thống kê Hành chính',
            'user' => $user,
        ]);
    }

    /**
     * Báo cáo tình hình giải quyết TTHC (Biểu mẫu 01 Thông tư 01/2018/TT-VPCP)
     */
    public function thongTu01(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $filters = [
            'ky' => $request->get('ky', 'thang_nay'),
            'tu_ngay' => $request->get('tu_ngay'),
            'den_ngay' => $request->get('den_ngay'),
            'don_vi_id' => $request->get('don_vi_id'),
            'linh_vuc_id' => $request->get('linh_vuc_id'),
        ];

        $reportData = ReportingService::getReportThongTu01($filters);

        // Xuất CSV UTF-8 BOM
        if ($request->get('export') === 'csv') {
            $headers = [
                'STT', 'Mã đơn vị', 'Tên cơ quan / Đơn vị',
                'Tồn kỳ trước', 'Tiếp nhận trực tiếp', 'Tiếp nhận trực tuyến', 'Tổng nhận trong kỳ',
                'Giải quyết trước hạn', 'Giải quyết đúng hạn', 'Giải quyết quá hạn', 'Tổng đã giải quyết',
                'Đang giải quyết trong hạn', 'Đang giải quyết quá hạn', 'Tổng đang giải quyết',
                'Tỷ lệ đúng hạn (%)'
            ];
            $rows = [];
            $stt = 1;
            foreach ($reportData['rows'] as $r) {
                $rows[] = [
                    $stt++,
                    $r['ma_dinh_danh'],
                    $r['ten_don_vi'],
                    $r['ton_ky_truoc'],
                    $r['tiep_nhan_truc_tiep'],
                    $r['tiep_nhan_truc_tuyen'],
                    $r['tong_so_nhan'],
                    $r['giai_quyet_truoc_han'],
                    $r['giai_quyet_dung_han'],
                    $r['giai_quyet_qua_han'],
                    $r['tong_da_giai_quyet'],
                    $r['dang_xu_ly_trong_han'],
                    $r['dang_xu_ly_qua_han'],
                    $r['tong_dang_giai_quyet'],
                    $r['ty_le_dung_han'] . '%',
                ];
            }
            // Dòng tổng cộng
            $totals = $reportData['totals'];
            $rows[] = [
                'TỔNG', '', 'TOÀN THÀNH PHỐ',
                $totals['ton_ky_truoc'],
                $totals['tiep_nhan_truc_tiep'],
                $totals['tiep_nhan_truc_tuyen'],
                $totals['tong_so_nhan'],
                $totals['giai_quyet_truoc_han'],
                $totals['giai_quyet_dung_han'],
                $totals['giai_quyet_qua_han'],
                $totals['tong_da_giai_quyet'],
                $totals['dang_xu_ly_trong_han'],
                $totals['dang_xu_ly_qua_han'],
                $totals['tong_dang_giai_quyet'],
                $totals['ty_le_dung_han'] . '%',
            ];

            $filename = 'Bao_cao_TT01_VPCP_' . date('Ymd_His') . '.csv';
            ReportingService::exportCsv($headers, $rows, $filename);
            return new Response('', 200);
        }

        // Bản in A4 ngang chuẩn thể thức
        if ($request->get('print') === '1') {
            return $this->view('admin/bao_cao/in_a4', [
                'title' => 'Báo cáo Tình hình Tiếp nhận & Giải quyết TTHC (Mẫu biểu 01 TT 01/2018/TT-VPCP)',
                'ten_bieu_mau' => 'Biểu mẫu 01',
                'tieu_de_bao_cao' => 'BÁO CÁO TÌNH HÌNH TIẾP NHẬN VÀ GIẢI QUYẾT THỦ TỤC HÀNH CHÍNH',
                'can_cu' => 'Ban hành kèm theo Thông tư số 01/2018/TT-VPCP ngày 23/11/2018 của Văn phòng Chính phủ',
                'report_data' => $reportData,
                'filters' => $filters,
                'view_name' => 'thong_tu_01',
            ]);
        }

        $commonData = $this->getCommonFilterOptions();

        return $this->view('admin/bao_cao/thong_tu_01', array_merge($reportData, $commonData, [
            'title' => 'Báo cáo theo Thông tư 01/2018/TT-VPCP',
            'filters' => $filters,
            'user' => $user,
        ]));
    }

    /**
     * Báo cáo Số hóa Hồ sơ & Kết quả (Nghị định 107/2021/NĐ-CP)
     */
    public function soHoa(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $filters = [
            'ky' => $request->get('ky', 'thang_nay'),
            'tu_ngay' => $request->get('tu_ngay'),
            'den_ngay' => $request->get('den_ngay'),
            'don_vi_id' => $request->get('don_vi_id'),
        ];

        $reportData = ReportingService::getReportSoHoa($filters);

        if ($request->get('export') === 'csv') {
            $headers = ['STT', 'Đơn vị', 'Tổng tiếp nhận', 'Số hóa thành phần', 'Tỷ lệ SH thành phần (%)', 'Số hóa kết quả', 'Tỷ lệ SH kết quả (%)', 'Đồng bộ Kho dữ liệu'];
            $rows = [];
            $stt = 1;
            foreach ($reportData['rows'] as $r) {
                $rows[] = [
                    $stt++,
                    $r['ten_don_vi'],
                    $r['tong_tiep_nhan'],
                    $r['so_hoa_thanh_phan'],
                    $r['ty_le_thanh_phan'] . '%',
                    $r['so_hoa_ket_qua'],
                    $r['ty_le_ket_qua'] . '%',
                    $r['dong_bo_kho_du_lieu'],
                ];
            }
            ReportingService::exportCsv($headers, $rows, 'Bao_cao_So_hoa_ND107_' . date('Ymd_His') . '.csv');
            return new Response('', 200);
        }

        if ($request->get('print') === '1') {
            return $this->view('admin/bao_cao/in_a4', [
                'title' => 'Báo cáo Số hóa Hồ sơ & Kết quả TTHC (NĐ 107/2021/NĐ-CP)',
                'ten_bieu_mau' => 'Biểu NĐ 107',
                'tieu_de_bao_cao' => 'BÁO CÁO KẾT QUẢ SỐ HÓA HỒ SƠ VÀ KẾT QUẢ GIẢI QUYẾT TTHC',
                'can_cu' => 'Theo Nghị định số 107/2021/NĐ-CP ngày 06/12/2021 của Chính phủ',
                'report_data' => $reportData,
                'filters' => $filters,
                'view_name' => 'so_hoa',
            ]);
        }

        $commonData = $this->getCommonFilterOptions();

        return $this->view('admin/bao_cao/so_hoa', array_merge($reportData, $commonData, [
            'title' => 'Báo cáo Số hóa theo Nghị định 107/2021/NĐ-CP',
            'filters' => $filters,
            'user' => $user,
        ]));
    }

    /**
     * Báo cáo Hồ sơ Quá hạn kèm Văn bản xin lỗi Mẫu 04
     */
    public function quaHan(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $filters = [
            'ky' => $request->get('ky', 'thang_nay'),
            'tu_ngay' => $request->get('tu_ngay'),
            'den_ngay' => $request->get('den_ngay'),
            'don_vi_id' => $request->get('don_vi_id'),
        ];

        $reportData = ReportingService::getReportQuaHan($filters);

        if ($request->get('export') === 'csv') {
            $headers = ['STT', 'Mã hồ sơ', 'Tên thủ tục', 'Đơn vị giải quyết', 'Chuyên viên', 'Người nộp', 'Hạn trả', 'Số giờ trễ', 'Có Mẫu 04', 'Số văn bản', 'Hạn mới', 'Lý do'];
            $rows = [];
            $stt = 1;
            foreach ($reportData['rows'] as $r) {
                $rows[] = [
                    $stt++,
                    $r['ma_ho_so'],
                    $r['ten_tthc'],
                    $r['ten_don_vi'],
                    $r['ten_chuyen_vien'] ?? 'Chưa phân công',
                    $r['nguoi_nop_ho_ten'],
                    $r['ngay_hen_tra'],
                    $r['so_gio_tre_han'] . ' giờ',
                    $r['da_co_mau_04'] ? 'ĐÃ CÓ' : 'CHƯA CÓ',
                    $r['so_van_ban'] ?? '',
                    $r['ngay_hen_moi'] ?? '',
                    $r['ly_do_qua_han'] ?? '',
                ];
            }
            ReportingService::exportCsv($headers, $rows, 'Bao_cao_Qua_han_Mau04_' . date('Ymd_His') . '.csv');
            return new Response('', 200);
        }

        if ($request->get('print') === '1') {
            return $this->view('admin/bao_cao/in_a4', [
                'title' => 'Báo cáo Danh sách Hồ sơ Quá hạn & Ban hành Mẫu 04',
                'ten_bieu_mau' => 'Biểu Quá hạn',
                'tieu_de_bao_cao' => 'BÁO CÁO TÌNH HÌNH HỒ SƠ QUÁ HẠN VÀ BAN HÀNH VĂN BẢN XIN LỖI',
                'can_cu' => 'Theo quy định tại Điều 12 Nghị định 61/2018/NĐ-CP và Thông tư 01/2018/TT-VPCP',
                'report_data' => $reportData,
                'filters' => $filters,
                'view_name' => 'qua_han',
            ]);
        }

        $commonData = $this->getCommonFilterOptions();

        return $this->view('admin/bao_cao/qua_han', array_merge($reportData, $commonData, [
            'title' => 'Báo cáo Hồ sơ Quá hạn & Mẫu 04',
            'filters' => $filters,
            'user' => $user,
        ]));
    }

    /**
     * Báo cáo Mức độ Hài lòng SIPAS (theo Quyết định 876/QĐ-BNV)
     */
    public function haiLong(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $filters = [
            'ky' => $request->get('ky', 'thang_nay'),
            'tu_ngay' => $request->get('tu_ngay'),
            'den_ngay' => $request->get('den_ngay'),
        ];

        $reportData = ReportingService::getReportHaiLong($filters);

        if ($request->get('export') === 'csv') {
            $headers = ['STT', 'Mã hồ sơ', 'Thủ tục hành chính', 'Đơn vị tiếp nhận', 'Tiếp cận (1-5)', 'Thủ tục (1-5)', 'Công chức (1-5)', 'Kết quả (1-5)', 'Phản ánh (1-5)', 'Điểm TB', 'Ý kiến góp ý', 'Ngày đánh giá'];
            $rows = [];
            $stt = 1;
            foreach ($reportData['list'] as $r) {
                $rows[] = [
                    $stt++,
                    $r['ma_ho_so'],
                    $r['ten_tthc'],
                    $r['ten_don_vi'],
                    $r['diem_tiep_can'],
                    $r['diem_thu_tuc'],
                    $r['diem_cong_chuc'],
                    $r['diem_ket_qua'],
                    $r['diem_phan_anh'],
                    $r['diem_trung_binh'],
                    $r['y_kien_dong_gop'] ?? '',
                    $r['ngay_danh_gia'],
                ];
            }
            ReportingService::exportCsv($headers, $rows, 'Bao_cao_Hai_long_SIPAS_' . date('Ymd_His') . '.csv');
            return new Response('', 200);
        }

        if ($request->get('print') === '1') {
            return $this->view('admin/bao_cao/in_a4', [
                'title' => 'Báo cáo Khảo sát Mức độ Hài lòng SIPAS',
                'ten_bieu_mau' => 'Biểu SIPAS',
                'tieu_de_bao_cao' => 'BÁO CÁO KẾT QUẢ ĐO LƯỜNG SỰ HÀI LÒNG CỦA NGƯỜI DÂN, TỔ CHỨC (SIPAS)',
                'can_cu' => 'Theo Quyết định số 876/QĐ-BNV ngày 10/11/2022 của Bộ Nội vụ',
                'report_data' => $reportData,
                'filters' => $filters,
                'view_name' => 'hai_long',
            ]);
        }

        $commonData = $this->getCommonFilterOptions();

        return $this->view('admin/bao_cao/hai_long', array_merge($reportData, $commonData, [
            'title' => 'Báo cáo Hài lòng SIPAS (QĐ 876/QĐ-BNV)',
            'filters' => $filters,
            'user' => $user,
        ]));
    }

    /**
     * Báo cáo Hồ sơ Trực tuyến Toàn trình & Một phần
     */
    public function trucTuyen(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $filters = [
            'ky' => $request->get('ky', 'thang_nay'),
        ];

        $reportData = ReportingService::getReportTrucTuyen($filters);

        if ($request->get('export') === 'csv') {
            $headers = ['STT', 'Đơn vị', 'Tổng hồ sơ', 'Tổng trực tuyến', 'Toàn trình', 'Tỷ lệ toàn trình (%)', 'Một phần', 'Trực tiếp'];
            $rows = [];
            $stt = 1;
            foreach ($reportData['rows'] as $r) {
                $rows[] = [
                    $stt++,
                    $r['ten_don_vi'],
                    $r['tong_so'],
                    $r['tong_truc_tuyen'],
                    $r['toan_trinh'],
                    $r['ty_le_toan_trinh'] . '%',
                    $r['mot_phan'],
                    $r['truc_tiep'],
                ];
            }
            ReportingService::exportCsv($headers, $rows, 'Bao_cao_Truc_tuyen_Toan_trinh_' . date('Ymd_His') . '.csv');
            return new Response('', 200);
        }

        $commonData = $this->getCommonFilterOptions();

        return $this->view('admin/bao_cao/truc_tuyen', array_merge($reportData, $commonData, [
            'title' => 'Báo cáo Hồ sơ Trực tuyến Toàn trình / Một phần',
            'filters' => $filters,
            'user' => $user,
        ]));
    }

    /**
     * Báo cáo Phối hợp Liên thông giữa các cơ quan
     */
    public function lienThong(Request $request): Response
    {
        $user = AuthService::user(AuthService::GUARD_CAN_BO);
        if (!$user) {
            return $this->redirect('/admin/dang-nhap');
        }

        $filters = [
            'ky' => $request->get('ky', 'thang_nay'),
        ];

        $reportData = ReportingService::getReportLienThong($filters);

        if ($request->get('export') === 'csv') {
            $headers = ['STT', 'Đơn vị gửi luân chuyển', 'Đơn vị tiếp nhận phối hợp', 'Số lượt luân chuyển', 'Thời gian xử lý TB (giờ)'];
            $rows = [];
            $stt = 1;
            foreach ($reportData['rows'] as $r) {
                $rows[] = [
                    $stt++,
                    $r['don_vi_gui'],
                    $r['don_vi_nhan'],
                    $r['so_luot_chuyen'],
                    round((float)$r['thoi_gian_trung_binh_gio'], 1) . ' giờ',
                ];
            }
            ReportingService::exportCsv($headers, $rows, 'Bao_cao_Lien_thong_' . date('Ymd_His') . '.csv');
            return new Response('', 200);
        }

        $commonData = $this->getCommonFilterOptions();

        return $this->view('admin/bao_cao/lien_thong', array_merge($reportData, $commonData, [
            'title' => 'Báo cáo Phối hợp Liên thông giữa các Cơ quan',
            'filters' => $filters,
            'user' => $user,
        ]));
    }

    /**
     * Lấy danh sách options cho các bộ lọc dùng chung
     */
    private function getCommonFilterOptions(): array
    {
        $db = Database::connection();
        $donViList = $db->query("SELECT id, ten_don_vi, loai_don_vi FROM don_vi WHERE loai_don_vi IN ('SO_NGANH', 'UBND_HUYEN') ORDER BY loai_don_vi, id")->fetchAll(PDO::FETCH_ASSOC);
        $linhVucList = $db->query("SELECT id, ma_linh_vuc, ten_linh_vuc FROM dm_linh_vuc ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'don_vi_list' => $donViList,
            'linh_vuc_list' => $linhVucList,
        ];
    }
}
