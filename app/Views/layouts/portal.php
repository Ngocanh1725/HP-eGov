<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Cổng Dịch vụ công TP. Hải Phòng') ?> | HP-eGov</title>
    <meta name="description" content="<?= e($description ?? 'Hệ thống thông tin giải quyết thủ tục hành chính & Một cửa điện tử liên thông TP. Hải Phòng') ?>">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <!-- Design System CSS (100% Cục bộ) -->
    <link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/layout.css')) ?>">
</head>
<body class="portal-layout">
    <?php if (getenv('APP_ENV') === 'staging' || \App\Core\Config::get('app.env') === 'staging'): ?>
    <div class="staging-global-banner" style="background:#d97706; color:#ffffff; font-weight:700; text-align:center; padding:6px 16px; font-size:12px; letter-spacing:0.5px; z-index:9999;">
        ⚠️ MÔI TRƯỜNG ĐÀO TẠO & THỰC HÀNH CÔNG NGHỆ (STAGING) — DỮ LIỆU THỬ NGHIỆM KHÔNG CÓ GIÁ TRỊ PHÁP LÝ
    </div>
    <?php endif; ?>
    <?php $this->partial('partials/header'); ?>


    <main class="portal-container">
        <?php $this->partial('partials/flash'); ?>
        <?= $this->yieldSection('content') ?>
    </main>

    <?php $this->partial('partials/footer'); ?>

    <!-- JavaScript thuần (Tuân thủ CSP, không inline script) -->
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
