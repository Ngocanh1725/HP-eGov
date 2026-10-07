<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'In văn bản hành chính') ?> | HP-eGov Hải Phòng</title>
    
    <link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/print.css')) ?>">
    <style>
        .print-toolbar {
            background-color: var(--color-primary);
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body style="background-color:#525659;margin:0;">
    <!-- Thanh điều khiển in ấn trên màn hình (Tự động ẩn khi ra lệnh in) -->
    <div class="print-toolbar no-print">
        <div class="d-flex align-center gap-2">
            <strong>HP-eGov — Xem trước bản in A4 (Nghị định 30/2020/NĐ-CP)</strong>
        </div>
        <div class="d-flex align-center gap-2">
            <button type="button" class="btn btn-sm btn-secondary" onclick="window.history.back()">Quay lại</button>
            <button type="button" class="btn btn-sm btn-gold" onclick="window.print()">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1"/></svg>
                In văn bản (Ctrl + P)
            </button>
        </div>
    </div>

    <!-- Nội dung bản in khổ A4 -->
    <div class="print-preview-container">
        <?= $this->yieldSection('content') ?>
    </div>
</body>
</html>
