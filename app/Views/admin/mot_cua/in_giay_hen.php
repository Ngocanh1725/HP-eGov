<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset('fonts/be-vietnam-pro.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/print.css')) ?>">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 13pt;
            line-height: 1.4;
            color: #000;
            background: #f4f6fa;
            margin: 0;
            padding: 20px;
        }
        .page-container {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm 15mm 20mm 20mm;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
        }
        .no-print-bar {
            width: 210mm;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background: #0b2e59;
            color: #fff;
            border: none;
            padding: 8px 18px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-back {
            background: #64748b;
            color: #fff;
            text-decoration: none;
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 4px;
        }
        table.meta-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.meta-header td {
            vertical-align: top;
            text-align: center;
        }
        .header-title-main {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12pt;
        }
        .header-title-sub {
            font-size: 11pt;
            font-weight: bold;
        }
        .doc-title {
            text-align: center;
            font-weight: bold;
            font-size: 15pt;
            text-transform: uppercase;
            margin: 20px 0 5px 0;
        }
        .doc-code {
            text-align: center;
            font-style: italic;
            font-size: 11pt;
            margin-bottom: 20px;
        }
        .info-row {
            margin-bottom: 6px;
        }
        table.content-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table.content-table th, table.content-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 12pt;
        }
        table.content-table th {
            background: #f2f2f2;
            text-align: center;
        }
        table.signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        table.signature-table td {
            text-align: center;
            vertical-align: top;
            width: 50%;
        }
        .qr-section {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #000;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print-bar { display: none !important; }
            .page-container {
                box-shadow: none;
                margin: 0;
                width: 100%;
                min-height: auto;
                padding: 15mm 10mm 15mm 15mm;
            }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <a href="<?= e(url('/admin/mot-cua/tiep-nhan')) ?>" class="btn-back">⬅ Quay lại bàn tiếp nhận</a>
    <div>
        <button onclick="window.print()" class="btn-print">🖨️ IN GIẤY HẸN (A4)</button>
    </div>
</div>

<div class="page-container">
    <!-- Header chuẩn thể thức văn bản hành chính Việt Nam -->
    <table class="meta-header">
        <tr>
            <td style="width: 45%;">
                <div class="header-title-main">UBND THÀNH PHỐ HẢI PHÒNG</div>
                <div class="header-title-sub"><?= mb_strtoupper(e($hoSo['ten_don_vi']), 'UTF-8') ?></div>
                <div style="font-size: 11pt; font-weight: bold;">BỘ PHẬN TIẾP NHẬN & TRẢ KẾT QUẢ</div>
                <div style="font-size: 10pt; margin-top: 4px;">Mã định danh: <?= e($hoSo['ma_dinh_danh']) ?></div>
            </td>
            <td style="width: 55%;">
                <div class="header-title-main">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                <div style="font-weight: bold; font-size: 12pt;">Độc lập - Tự do - Hạnh phúc</div>
                <div style="margin-top: 2px;">-------------------------</div>
                <div style="font-style: italic; font-size: 11pt; margin-top: 4px;">
                    Hải Phòng, ngày <?= date('d', strtotime($hoSo['ngay_tiep_nhan'])) ?> tháng <?= date('m', strtotime($hoSo['ngay_tiep_nhan'])) ?> năm <?= date('Y', strtotime($hoSo['ngay_tiep_nhan'])) ?>
                </div>
            </td>
        </tr>
    </table>

    <div class="doc-title">GIẤY TIẾP NHẬN HỒ SƠ VÀ HẸN TRẢ KẾT QUẢ</div>
    <div class="doc-code">
        Mã số hồ sơ: <strong><?= e($hoSo['ma_ho_so']) ?></strong><br>
        <small>(Mẫu số 01 ban hành kèm theo Thông tư số 01/2018/TT-VPCP ngày 23/11/2018 của Văn phòng Chính phủ)</small>
    </div>

    <!-- Thông tin người nộp -->
    <div class="info-row">
        Bộ phận Tiếp nhận và Trả kết quả: <strong><?= e($hoSo['ten_don_vi']) ?></strong>
    </div>
    <div class="info-row">
        Tiếp nhận hồ sơ của: <strong><?= e($hoSo['nguoi_nop_ho_ten']) ?></strong>
    </div>
    <div class="info-row">
        Số CCCD/Định danh cá nhân: <strong><?= e($hoSo['nguoi_nop_cccd']) ?></strong>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        Số điện thoại: <strong><?= e($hoSo['nguoi_nop_sdt'] ?: '.....................') ?></strong>
    </div>
    <div class="info-row">
        Địa chỉ thường trú: <?= e($hoSo['nguoi_nop_dia_chi'] ?: '....................................................................................................') ?>
    </div>
    <div class="info-row">
        Nội dung yêu cầu giải quyết: <strong><?= e($hoSo['ten_tthc']) ?></strong>
    </div>

    <!-- Thành phần hồ sơ tiếp nhận -->
    <table class="content-table">
        <thead>
            <tr>
                <th style="width: 35px;">STT</th>
                <th>Tên thành phần hồ sơ, giấy tờ</th>
                <th style="width: 70px;">Số lượng</th>
                <th style="width: 100px;">Bản chính / Sao</th>
                <th style="width: 110px;">Số hóa NĐ 107</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($taiLieuList)): ?>
                <tr>
                    <td style="text-align: center;">1</td>
                    <td>Tờ khai điện tử theo mẫu quy định (E-Form ký số)</td>
                    <td style="text-align: center;">01</td>
                    <td style="text-align: center;">Bản điện tử</td>
                    <td style="text-align: center;">✔ Đã số hóa</td>
                </tr>
            <?php else: ?>
                <?php $stt = 1; foreach ($taiLieuList as $tl): ?>
                    <tr>
                        <td style="text-align: center;"><?= $stt++ ?></td>
                        <td><?= e($tl['ten_giay_to'] ?: $tl['ten_goc']) ?></td>
                        <td style="text-align: center;">01</td>
                        <td style="text-align: center;">Bản chính</td>
                        <td style="text-align: center;">
                            <?= ($tl['loai_tai_lieu'] === 'SO_HOA' || !empty($tl['chu_ky_so_id'])) ? '✔ Đã số hóa' : 'Bản quét' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Thời gian hẹn trả -->
    <div class="info-row" style="margin-top: 15px;">
        1. Thời gian tiếp nhận hồ sơ: <strong><?= date('H', strtotime($hoSo['ngay_tiep_nhan'])) ?></strong> giờ <strong><?= date('i', strtotime($hoSo['ngay_tiep_nhan'])) ?></strong> phút, ngày <strong><?= date('d/m/Y', strtotime($hoSo['ngay_tiep_nhan'])) ?></strong>.
    </div>
    <div class="info-row">
        2. Thời gian hẹn trả kết quả: <strong><?= !empty($hoSo['ngay_hen_tra']) ? date('H', strtotime($hoSo['ngay_hen_tra'])) : '17' ?></strong> giờ <strong><?= !empty($hoSo['ngay_hen_tra']) ? date('i', strtotime($hoSo['ngay_hen_tra'])) : '00' ?></strong> phút, ngày <strong><?= !empty($hoSo['ngay_hen_tra']) ? date('d/m/Y', strtotime($hoSo['ngay_hen_tra'])) : '...' ?></strong>.
    </div>
    <div class="info-row">
        3. Hình thức nhận kết quả: <strong>
            <?php 
            echo match($hoSo['hinh_thuc_nhan_kq']) {
                'TRUC_TIEP' => 'Trực tiếp tại Bộ phận Tiếp nhận và Trả kết quả',
                'BUU_DIEN' => 'Chuyển phát qua Dịch vụ Bưu chính công ích VNPost',
                'DIEN_TU' => 'Bản điện tử qua Cổng Dịch vụ công TP. Hải Phòng',
                default => 'Trực tiếp'
            };
            ?>
        </strong>
    </div>

    <!-- Bảng chữ ký -->
    <table class="signature-table">
        <tr>
            <td>
                <strong>NGƯỜI NỘP HỒ SƠ</strong><br>
                <em>(Ký và ghi rõ họ tên)</em>
                <div style="height: 60px;"></div>
                <strong><?= e($hoSo['nguoi_nop_ho_ten']) ?></strong>
            </td>
            <td>
                <strong>NGƯỜI TIẾP NHẬN HỒ SƠ</strong><br>
                <em>(Ký, đóng dấu hoặc ký số)</em>
                <div style="height: 60px;"></div>
                <strong><?= e($canBo['ho_ten'] ?? 'Cán bộ Một cửa') ?></strong>
            </td>
        </tr>
    </table>

    <!-- Phần QR Code tra cứu -->
    <div class="qr-section">
        <div id="qrcode" style="width: 75px; height: 75px;"></div>
        <div style="font-size: 10pt; line-height: 1.3;">
            <strong>MÃ TRA CỨU TIẾN ĐỘ HỒ SƠ</strong><br>
            Quét mã QR bằng điện thoại hoặc tra cứu tại:<br>
            <code><?= e(url('/tra-cuu?ma_ho_so=' . $hoSo['ma_ho_so'])) ?></code>
        </div>
    </div>
</div>

<script src="<?= e(asset('vendor/qrcode/qrcode.min.js')) ?>"></script>
<script>
window.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode"), {
        text: "<?= e(url('/tra-cuu?ma_ho_so=' . $hoSo['ma_ho_so'])) ?>",
        width: 75,
        height: 75,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
    });
});
</script>
</body>
</html>
