<?php
/**
 * View: In bản chính Phiếu Xin lỗi Mẫu số 04 (Thông tư 01/2018/TT-VPCP)
 * HP-eGov - Trung tâm Phục vụ Hành chính công TP. Hải Phòng
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu xin lỗi và hẹn lại ngày trả kết quả (Mẫu số 04) - <?= e($data['ma_ho_so']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 20mm 20mm 25mm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 14pt;
            line-height: 1.5;
            color: #000;
            background-color: #f8fafc;
            margin: 0;
            padding: 20px;
        }
        .page-container {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm 20mm 20mm 25mm;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            box-sizing: border-box;
            position: relative;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-justify { text-align: justify; }
        .fw-bold { font-weight: bold; }
        .fst-italic { font-style: italic; }
        .text-uppercase { text-transform: uppercase; }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: top;
            padding: 0;
        }
        .line-solid {
            width: 130px;
            height: 1px;
            background-color: #000;
            margin: 4px auto 8px;
        }
        .title-doc {
            font-size: 16pt;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 5px;
            text-align: center;
        }
        .subtitle-doc {
            font-size: 13pt;
            font-style: italic;
            text-align: center;
            margin-bottom: 25px;
        }
        .content-p {
            text-indent: 1.27cm;
            margin-bottom: 12px;
            text-align: justify;
        }
        .stamp-box {
            border: 2px dashed #0B2E59;
            background-color: #f0f7ff;
            color: #0B2E59;
            padding: 10px;
            border-radius: 6px;
            display: inline-block;
            font-size: 11pt;
            line-height: 1.3;
            text-align: left;
            margin-top: 15px;
        }

        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-print {
            background-color: #0B2E59;
            color: #fff;
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .btn-print:hover { background-color: #071f3d; }
        .btn-back {
            background-color: #e2e8f0;
            color: #334155;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            margin-right: 10px;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .page-container { box-shadow: none; padding: 0; margin: 0; width: 100%; min-height: auto; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="/admin/sla/danh-sach-qua-han" class="btn-back"><i class="bi bi-arrow-left"></i> Quay lại</a>
    <button class="btn-print" onclick="window.print()"><i class="bi bi-printer"></i> In văn bản (Ctrl + P)</button>
</div>

<div class="page-container">
    <table class="header-table">
        <tr>
            <td style="width: 45%; text-align: center;">
                <div class="text-uppercase fw-bold" style="font-size: 12pt;"><?= e($data['ten_don_vi']) ?></div>
                <div style="font-size: 12pt;">Số: <strong><?= e($data['vbxl_so']) ?></strong></div>
            </td>
            <td style="width: 55%; text-align: center;">
                <div class="fw-bold" style="font-size: 13pt;">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                <div class="fw-bold fst-italic" style="font-size: 13pt;">Độc lập - Tự do - Hạnh phúc</div>
                <div class="line-solid"></div>
                <div class="fst-italic" style="font-size: 13pt;">
                    Hải Phòng, ngày <?= date('d', strtotime($data['vbxl_ngay_ky'])) ?> tháng <?= date('m', strtotime($data['vbxl_ngay_ky'])) ?> năm <?= date('Y', strtotime($data['vbxl_ngay_ky'])) ?>
                </div>
            </td>
        </tr>
    </table>

    <div class="title-doc">PHIẾU XIN LỖI VÀ HẸN LẠI NGÀY TRẢ KẾT QUẢ</div>
    <div class="subtitle-doc">(Mẫu số 04 ban hành kèm theo Thông tư số 01/2018/TT-VPCP ngày 23/11/2018 của Văn phòng Chính phủ)</div>

    <div style="margin-bottom: 20px;">
        <strong>Kính gửi:</strong> Ông/Bà <strong><?= e($data['ho_ten_nguoi_nop']) ?></strong><br>
        <?php if (!empty($data['dia_chi_nguoi_nop'])): ?>
            Địa chỉ: <?= e($data['dia_chi_nguoi_nop']) ?><br>
        <?php endif; ?>
        Số điện thoại liên hệ: <?= e($data['so_dien_thoai_nguoi_nop'] ?? '---') ?>
    </div>

    <p class="content-p">
        Ngày <?= !empty($data['ngay_tiepNhan']) ? date('d/m/Y', strtotime($data['ngay_tiepNhan'])) : date('d/m/Y') ?>, <?= e($data['ten_don_vi']) ?> đã tiếp nhận giải quyết hồ sơ thủ tục hành chính của Quý Ông/Bà:
    </p>
    <p style="margin-left: 1.27cm; margin-bottom: 12px;">
        - Thủ tục: <strong><?= e($data['ten_tthc']) ?></strong><br>
        - Mã hồ sơ hành chính: <strong><?= e($data['ma_ho_so']) ?></strong><br>
        - Thời gian hẹn trả kết quả trước đây: <strong><?= !empty($data['ngay_hen_tra']) ? date('d/m/Y H:i', strtotime($data['ngay_hen_tra'])) : '---' ?></strong>.
    </p>

    <p class="content-p">
        Tuy nhiên, do nguyên nhân: <em><?= e($data['vbxl_ly_do']) ?></em>; nên <?= e($data['ten_don_vi']) ?> chưa thể hoàn thành giải quyết hồ sơ đúng theo thời gian đã hẹn trước đây.
    </p>

    <p class="content-p">
        <?= e($data['ten_don_vi']) ?> trân trọng gửi lời xin lỗi chân thành đến Quý Ông/Bà về sự chậm trễ này, và xin trân trọng hẹn lại ngày trả kết quả giải quyết hồ sơ vào:
    </p>

    <p style="text-align: center; font-size: 15pt; font-weight: bold; margin: 20px 0; color: #0B2E59;">
        <?= date('H\h i\p - \N\g\à\y d/m/Y', strtotime($data['vbxl_ngay_hen_moi'])) ?>
    </p>

    <p class="content-p">
        Rất mong nhận được sự thông cảm và chia sẻ của Quý Ông/Bà./.
    </p>

    <table style="width: 100%; margin-top: 30px; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; vertical-align: top; font-size: 11pt;">
                <strong><em>Nơi nhận:</em></strong><br>
                - Như trên;<br>
                - Cổng DVC TP. Hải Phòng;<br>
                - Lưu: VT, HS.
            </td>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <div class="fw-bold text-uppercase" style="font-size: 13pt;">
                    <?= !empty($data['ten_chuc_danh']) ? e($data['ten_chuc_danh']) : 'LÃNH ĐẠO CƠ QUAN' ?>
                </div>
                <div class="fst-italic" style="font-size: 11pt; margin-bottom: 10px;">(Ký số điện tử)</div>

                <div class="stamp-box">
                    <div style="font-weight: bold; color: #b91c1c;">
                        <i class="bi bi-patch-check-fill"></i> ĐÃ KÝ SỐ ĐIỆN TỬ
                    </div>
                    <div><strong>Ký bởi:</strong> <?= e($data['ten_nguoi_ky'] ?? 'Lãnh đạo cơ quan') ?></div>
                    <div><strong>Cơ quan:</strong> <?= e($data['ten_don_vi']) ?></div>
                    <div><strong>Thời gian:</strong> <?= date('d/m/Y H:i:s', strtotime($data['vbxl_ngay_ky'])) ?></div>
                    <div style="font-size: 9pt; color: #64748b; margin-top: 4px;">Thuật toán: SHA256withRSA · Khóa bảo mật 2048-bit</div>
                </div>

                <div class="fw-bold" style="font-size: 13pt; margin-top: 15px;">
                    <?= e($data['ten_nguoi_ky'] ?? '') ?>
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
