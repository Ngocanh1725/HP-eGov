<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'In Báo cáo Thống kê') ?> | HP-eGov</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 15mm 15mm 15mm;
        }
        * {
            box-sizing: border-box;
            font-family: "Times New Roman", Times, serif;
        }
        body {
            background: #f8fafc;
            color: #111827;
            margin: 0;
            padding: 20px;
            font-size: 13pt;
            line-height: 1.35;
        }
        .print-container {
            background: #ffffff;
            max-width: 1100px;
            margin: 0 auto;
            padding: 25px 35px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .header-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
            padding: 0;
        }
        .agency-name {
            font-size: 12pt;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }
        .agency-sub {
            font-size: 12pt;
            text-align: center;
            font-weight: bold;
        }
        .nation-title {
            font-size: 12pt;
            text-align: center;
            font-weight: bold;
        }
        .nation-motto {
            font-size: 13pt;
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #111;
            display: inline-block;
            padding-bottom: 2px;
        }
        .report-title {
            text-align: center;
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        .report-sub {
            text-align: center;
            font-size: 12pt;
            font-style: italic;
            margin-bottom: 20px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 25px;
            font-size: 10.5pt;
        }
        .data-table th, .data-table td {
            border: 1px solid #333333;
            padding: 5px 6px;
            text-align: center;
        }
        .data-table th {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        .text-start { text-align: left !important; }
        .text-end { text-align: right !important; }
        .fw-bold { font-weight: bold; }
        
        .footer-table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .footer-table td {
            vertical-align: top;
            text-align: center;
            width: 50%;
        }
        .signature-space {
            height: 70px;
        }
        /* Thanh công cụ in phía trên */
        .no-print-bar {
            background: #0B2E59;
            color: #ffffff;
            padding: 10px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px;
        }
        .btn-print {
            background: #10b981;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-close-window {
            background: rgba(255,255,255,0.2);
            color: #ffffff;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .print-container {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="print-container">
        <!-- Thanh công cụ in ấn (ẩn khi in) -->
        <div class="no-print-bar">
            <div>
                <strong>HP-eGov | Chế độ In Báo cáo chuẩn Khổ A4 Ngang</strong> (Landscape)
            </div>
            <div>
                <button class="btn-print" onclick="window.print()">🖨️ In ngay (Print / Ctrl+P)</button>
                <button class="btn-close-window" onclick="window.close()">Đóng</button>
            </div>
        </div>

        <!-- Tiêu ngữ & Cơ quan hành chính -->
        <table class="header-table">
            <tr>
                <td style="width: 45%;">
                    <div class="agency-name">ỦY BAN NHÂN DÂN THÀNH PHỐ HẢI PHÒNG</div>
                    <div class="agency-sub">HỆ THỐNG MỘT CỬA ĐIỆN TỬ LIÊN THÔNG</div>
                    <div style="text-align:center; font-size:11pt; margin-top:4px;">Số: ......./BC-UBND</div>
                </td>
                <td style="width: 55%; text-align: center;">
                    <div class="nation-title">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                    <div class="nation-motto">Độc lập - Tự do - Hạnh phúc</div>
                    <div style="font-size: 11pt; font-style: italic; margin-top: 6px;">
                        Hải Phòng, ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Tiêu đề báo cáo -->
        <div class="report-title"><?= e($tieu_de_bao_cao) ?></div>
        <?php if (!empty($can_cu)): ?>
            <div style="text-align: center; font-size: 11pt; font-style: italic; margin-bottom: 4px;">
                (<?= e($can_cu) ?>)
            </div>
        <?php endif; ?>
        <div class="report-sub">
            Kỳ báo cáo: <?= e($report_data['date_range']['label'] ?? 'Toàn thời gian') ?>
        </div>

        <!-- Bảng số liệu cụ thể theo từng loại báo cáo -->
        <?php if (($view_name ?? '') === 'thong_tu_01'): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 35px;">STT</th>
                        <th rowspan="2" style="width: 90px;">Mã ĐV</th>
                        <th rowspan="2" class="text-start">Cơ quan / Đơn vị thực hiện</th>
                        <th rowspan="2">Tồn kỳ trước</th>
                        <th colspan="3">Tiếp nhận mới trong kỳ</th>
                        <th colspan="4">Kết quả đã giải quyết</th>
                        <th colspan="3">Đang giải quyết</th>
                        <th rowspan="2">Tỷ lệ đúng hạn (%)</th>
                    </tr>
                    <tr>
                        <th>Trực tiếp</th>
                        <th>Trực tuyến</th>
                        <th>Tổng nhận</th>
                        <th>Trước hạn</th>
                        <th>Đúng hạn</th>
                        <th>Quá hạn</th>
                        <th>Tổng GQ</th>
                        <th>Trong hạn</th>
                        <th>Quá hạn</th>
                        <th>Tổng đang xử lý</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rows = $report_data['rows'] ?? [];
                    $totals = $report_data['totals'] ?? [];
                    foreach ($rows as $idx => $r): 
                    ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><?= e($r['ma_dinh_danh']) ?></td>
                            <td class="text-start fw-bold"><?= e($r['ten_don_vi']) ?></td>
                            <td><?= number_format((int)$r['ton_ky_truoc']) ?></td>
                            <td><?= number_format((int)$r['tiep_nhan_truc_tiep']) ?></td>
                            <td><?= number_format((int)$r['tiep_nhan_truc_tuyen']) ?></td>
                            <td class="fw-bold"><?= number_format((int)$r['tong_so_nhan']) ?></td>
                            <td><?= number_format((int)$r['giai_quyet_truoc_han']) ?></td>
                            <td><?= number_format((int)$r['giai_quyet_dung_han']) ?></td>
                            <td><?= number_format((int)$r['giai_quyet_qua_han']) ?></td>
                            <td class="fw-bold"><?= number_format((int)$r['tong_da_giai_quyet']) ?></td>
                            <td><?= number_format((int)$r['dang_xu_ly_trong_han']) ?></td>
                            <td><?= number_format((int)$r['dang_xu_ly_qua_han']) ?></td>
                            <td class="fw-bold"><?= number_format((int)$r['tong_dang_giai_quyet']) ?></td>
                            <td class="fw-bold"><?= number_format((float)$r['ty_le_dung_han'], 1) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="fw-bold" style="background:#f3f4f6;">
                        <td colspan="3">TỔNG CỘNG TOÀN THÀNH PHỐ</td>
                        <td><?= number_format((int)($totals['ton_ky_truoc'] ?? 0)) ?></td>
                        <td><?= number_format((int)($totals['tiep_nhan_truc_tiep'] ?? 0)) ?></td>
                        <td><?= number_format((int)($totals['tiep_nhan_truc_tuyen'] ?? 0)) ?></td>
                        <td><?= number_format((int)($totals['tong_so_nhan'] ?? 0)) ?></td>
                        <td><?= number_format((int)($totals['giai_quyet_truoc_han'] ?? 0)) ?></td>
                        <td><?= number_format((int)($totals['giai_quyet_dung_han'] ?? 0)) ?></td>
                        <td><?= number_format((int)($totals['giai_quyet_qua_han'] ?? 0)) ?></td>
                        <td><?= number_format((int)($totals['tong_da_giai_quyet'] ?? 0)) ?></td>
                        <td><?= number_format((int)($totals['dang_xu_ly_trong_han'] ?? 0)) ?></td>
                        <td><?= number_format((int)($totals['dang_xu_ly_qua_han'] ?? 0)) ?></td>
                        <td><?= number_format((int)($totals['tong_dang_giai_quyet'] ?? 0)) ?></td>
                        <td><?= number_format((float)($totals['ty_le_dung_han'] ?? 100), 1) ?>%</td>
                    </tr>
                </tbody>
            </table>
        <?php elseif (($view_name ?? '') === 'so_hoa'): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">STT</th>
                        <th class="text-start">Cơ quan / Đơn vị</th>
                        <th>Tổng tiếp nhận</th>
                        <th>Số hóa thành phần (Đầu vào)</th>
                        <th>Tỷ lệ SH thành phần (%)</th>
                        <th>Số hóa kết quả điện tử</th>
                        <th>Tỷ lệ SH kết quả (%)</th>
                        <th>Đồng bộ Kho dữ liệu cá nhân</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($report_data['rows'] ?? []) as $idx => $r): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td class="text-start fw-bold"><?= e($r['ten_don_vi']) ?></td>
                            <td class="fw-bold"><?= number_format((int)$r['tong_tiep_nhan']) ?></td>
                            <td><?= number_format((int)$r['so_hoa_thanh_phan']) ?></td>
                            <td class="fw-bold"><?= number_format((float)$r['ty_le_thanh_phan'], 1) ?>%</td>
                            <td><?= number_format((int)$r['so_hoa_ket_qua']) ?></td>
                            <td class="fw-bold"><?= number_format((float)$r['ty_le_ket_qua'], 1) ?>%</td>
                            <td><?= number_format((int)$r['dong_bo_kho_du_lieu']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (($view_name ?? '') === 'qua_han'): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 35px;">STT</th>
                        <th>Mã hồ sơ</th>
                        <th class="text-start">Thủ tục hành chính</th>
                        <th>Đơn vị thụ lý</th>
                        <th>Chuyên viên</th>
                        <th>Người nộp</th>
                        <th>Hạn trả ban đầu</th>
                        <th>Trễ hạn</th>
                        <th>Mẫu 04</th>
                        <th>Lý do quá hạn & Hạn mới</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($report_data['rows'] ?? []) as $idx => $r): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td class="fw-bold"><?= e($r['ma_ho_so']) ?></td>
                            <td class="text-start"><?= e($r['ten_tthc']) ?></td>
                            <td><?= e($r['ten_don_vi']) ?></td>
                            <td><?= e($r['ten_chuyen_vien'] ?? 'Chưa phân công') ?></td>
                            <td><?= e($r['nguoi_nop_ho_ten']) ?></td>
                            <td><?= date('d/m/Y', strtotime($r['ngay_hen_tra'])) ?></td>
                            <td class="fw-bold"><?= (int)$r['so_gio_tre_han'] ?>h</td>
                            <td><?= (int)$r['da_co_mau_04'] ? 'ĐÃ CÓ (Số ' . e($r['so_van_ban'] ?? '') . ')' : 'CHƯA' ?></td>
                            <td><?= e($r['ly_do_qua_han'] ?? 'Chưa có giải trình') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (($view_name ?? '') === 'hai_long'): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">STT</th>
                        <th>Mã hồ sơ</th>
                        <th class="text-start">Thủ tục hành chính</th>
                        <th>Đơn vị tiếp nhận</th>
                        <th>Tiếp cận (1-5)</th>
                        <th>Thủ tục (1-5)</th>
                        <th>Công chức (1-5)</th>
                        <th>Tiến độ (1-5)</th>
                        <th>Phản ánh (1-5)</th>
                        <th>Điểm TB</th>
                        <th>Ý kiến phản hồi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($report_data['list'] ?? []) as $idx => $item): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td class="fw-bold"><?= e($item['ma_ho_so']) ?></td>
                            <td class="text-start"><?= e($item['ten_tthc']) ?></td>
                            <td><?= e($item['ten_don_vi']) ?></td>
                            <td><?= $item['diem_tiep_can'] ?></td>
                            <td><?= $item['diem_thu_tuc'] ?></td>
                            <td><?= $item['diem_cong_chuc'] ?></td>
                            <td><?= $item['diem_ket_qua'] ?></td>
                            <td><?= $item['diem_phan_anh'] ?></td>
                            <td class="fw-bold"><?= $item['diem_trung_binh'] ?></td>
                            <td class="text-start"><?= e($item['y_kien_dong_gop'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Chữ ký người lập biểu và Lãnh đạo cơ quan -->
        <table class="footer-table">
            <tr>
                <td>
                    <div class="fw-bold">NGƯỜI LẬP BIỂU</div>
                    <div style="font-style: italic; font-size: 11pt;">(Ký, ghi rõ họ tên)</div>
                    <div class="signature-space"></div>
                    <div class="fw-bold"><?= e($_SESSION['user']['ho_ten'] ?? 'Cán bộ thống kê') ?></div>
                </td>
                <td>
                    <div class="fw-bold">THỦ TRƯỞNG CƠ QUAN / ĐƠN VỊ</div>
                    <div style="font-style: italic; font-size: 11pt;">(Ký, đóng dấu điện tử / số)</div>
                    <div class="signature-space"></div>
                    <div class="fw-bold">[ĐÃ KÝ SỐ ĐIỆN TỬ]</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
