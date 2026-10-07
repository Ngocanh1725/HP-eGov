<?php
/**
 * Mẫu số 03 - PHIẾU TỪ CHỐI TIẾP NHẬN GIẢI QUYẾT HỒ SƠ
 * Ban hành kèm theo Thông tư số 01/2018/TT-VPCP ngày 23/11/2018 của Bộ trưởng, Chủ nhiệm Văn phòng Chính phủ
 */
$hoSo = $hoSo ?? [];
$nguoiDung = $nguoiDung ?? [];
$donVi = $donVi ?? [];

$ngayTiepNhan = !empty($hoSo['ngay_nop']) ? new DateTime($hoSo['ngay_nop']) : new DateTime();
$now = new DateTime();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mẫu số 03 - Phiếu từ chối tiếp nhận hồ sơ - <?= htmlspecialchars($hoSo['ma_ho_so'] ?? '') ?></title>
    <link rel="stylesheet" href="<?= asset('assets/css/print.css') ?>" media="print">
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 20mm 20mm 25mm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 13pt;
            line-height: 1.4;
            color: #000;
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
        }
        .page-container {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm 20mm 20mm 25mm;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            box-sizing: border-box;
            position: relative;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: top;
            text-align: center;
        }
        .org-name {
            font-size: 12pt;
            font-weight: normal;
            text-transform: uppercase;
        }
        .org-sub {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .national-title {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .national-motto {
            font-size: 13pt;
            font-weight: bold;
            border-bottom: 1px solid #000;
            display: inline-block;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }
        .doc-title {
            text-align: center;
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 25px 0 5px 0;
        }
        .doc-subtitle {
            text-align: center;
            font-size: 12pt;
            font-style: italic;
            margin-bottom: 25px;
        }
        .content p {
            margin: 6px 0;
            text-align: justify;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 35px;
        }
        .signature-table td {
            vertical-align: top;
            text-align: center;
            width: 50%;
        }
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-print {
            background-color: #dc3545;
            color: #fff;
            border: none;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .btn-back {
            background-color: #6c757d;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        @media print {
            body {
                background: none;
                padding: 0;
            }
            .page-container {
                box-shadow: none;
                margin: 0;
                width: 100%;
                min-height: auto;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" class="btn-print"><i class="bi bi-printer"></i> In Phiếu từ chối (A4)</button>
        <a href="<?= route('admin.motcua.hoSoTrucTuyen') ?>" class="btn-back">Quay lại danh sách</a>
    </div>

    <div class="page-container">
        <!-- Tiêu ngữ & Đơn vị -->
        <table class="header-table">
            <tr>
                <td style="width: 45%;">
                    <div class="org-name"><?= htmlspecialchars($donVi['ten_co_quan_cha'] ?? 'UBND THÀNH PHỐ HẢI PHÒNG') ?></div>
                    <div class="org-sub"><?= htmlspecialchars($donVi['ten_don_vi'] ?? 'BỘ PHẬN MỘT CỬA') ?></div>
                    <div style="font-size: 11pt; margin-top: 5px;">Số: ...../PTC-BPMC</div>
                </td>
                <td style="width: 55%;">
                    <div class="national-title">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                    <div class="national-motto">Độc lập - Tự do - Hạnh phúc</div>
                    <div style="font-size: 12pt; font-style: italic;">
                        Hải Phòng, ngày <?= $now->format('d') ?> tháng <?= $now->format('m') ?> năm <?= $now->format('Y') ?>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Tiêu đề -->
        <div class="doc-title">PHIẾU TỪ CHỐI TIẾP NHẬN GIẢI QUYẾT HỒ SƠ</div>
        <div class="doc-subtitle">(Mẫu số 03 - Ban hành kèm theo Thông tư số 01/2018/TT-VPCP ngày 23/11/2018 của Văn phòng Chính phủ)</div>

        <!-- Nội dung phiếu -->
        <div class="content">
            <p><strong>Bộ phận Một cửa:</strong> <?= htmlspecialchars($donVi['ten_don_vi'] ?? 'Bộ phận Tiếp nhận và Trả kết quả giải quyết TTHC') ?></p>
            <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($donVi['dia_chi'] ?? 'Trung tâm Hành chính') ?></p>
            <p><strong>Tiếp nhận hồ sơ của Ông/Bà (hoặc Tổ chức):</strong> <?= htmlspecialchars($hoSo['nguoi_nop_ho_ten'] ?? '..........................................................') ?></p>
            <p><strong>Số CMND/CCCD/Định danh:</strong> <?= htmlspecialchars($hoSo['nguoi_nop_cccd'] ?? '...........................') ?> &nbsp;&nbsp;&nbsp;&nbsp; <strong>Số điện thoại:</strong> <?= htmlspecialchars($hoSo['nguoi_nop_dien_thoai'] ?? '...........................') ?></p>
            <p><strong>Địa chỉ liên hệ:</strong> <?= htmlspecialchars($hoSo['nguoi_nop_dia_chi'] ?? '....................................................................................................') ?></p>
            <p><strong>Hồ sơ thủ tục:</strong> <?= htmlspecialchars($hoSo['ten_tthc'] ?? '') ?></p>
            <p><strong>Mã hồ sơ:</strong> <strong style="font-family: monospace; font-size: 14pt;"><?= htmlspecialchars($hoSo['ma_ho_so'] ?? '') ?></strong></p>
            <p><strong>Thời gian nộp:</strong> <?= $ngayTiepNhan->format('H') ?> giờ <?= $ngayTiepNhan->format('i') ?> phút, ngày <?= $ngayTiepNhan->format('d/m/Y') ?></p>

            <p style="margin-top: 15px;">Sau khi kiểm tra, đối chiếu hồ sơ với các quy định pháp luật hiện hành về thủ tục hành chính, Bộ phận Một cửa thông báo <strong>TỪ CHỐI TIẾP NHẬN</strong> hồ sơ của Ông/Bà (Tổ chức) vì lý do sau đây:</p>

            <!-- Lý do từ chối -->
            <div style="border: 1px solid #d9534f; background-color: #fdf7f7; padding: 15px 20px; margin: 15px 0; font-size: 13pt; min-height: 110px; white-space: pre-wrap; font-weight: 500;">
<?= htmlspecialchars($hoSo['ly_do_tu_choi'] ?? $hoSo['ly_do'] ?? 'Thủ tục hành chính không thuộc thẩm quyền tiếp nhận và giải quyết của cơ quan. Đề nghị công dân liên hệ cơ quan có thẩm quyền phù hợp.') ?>
            </div>

            <p>Bộ phận Một cửa thông báo để Ông/Bà (Tổ chức) được biết. Xin trân trọng cảm ơn./.</p>
        </div>

        <!-- Chữ ký -->
        <table class="signature-table">
            <tr>
                <td>
                    <strong>NGƯỜI NỘP HỒ SƠ</strong><br>
                    <span style="font-size: 11pt; font-style: italic;">(Ký, ghi rõ họ tên nếu nhận trực tiếp)</span>
                    <br><br><br><br><br>
                    <strong><?= htmlspecialchars($hoSo['nguoi_nop_ho_ten'] ?? '') ?></strong>
                </td>
                <td>
                    <strong>NGƯỜI TIẾP NHẬN HỒ SƠ</strong><br>
                    <span style="font-size: 11pt; font-style: italic;">(Ký, ghi rõ họ tên)</span>
                    <br><br><br><br><br>
                    <strong><?= htmlspecialchars($nguoiDung['ho_ten'] ?? 'Cán bộ Một cửa') ?></strong>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
