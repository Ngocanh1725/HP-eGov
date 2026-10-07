<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Bàn làm việc Cán bộ') ?> | HP-eGov Hải Phòng</title>
    <meta name="description" content="Phân hệ xử lý hành chính dành cho cán bộ Một cửa, Thụ lý, Lãnh đạo TP. Hải Phòng">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <!-- Design System CSS (100% Cục bộ) -->
    <link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/layout.css')) ?>">
</head>
<body class="admin-layout">
    <!-- Sidebar thu gọn được -->
    <?php $this->partial('partials/sidebar'); ?>

    <!-- Vùng giao diện chính -->
    <div class="admin-main">
        <?php $this->partial('partials/topbar'); ?>

        <main class="admin-content">
            <?php $this->partial('partials/breadcrumb', ['breadcrumbs' => $breadcrumbs ?? []]); ?>
            <?php $this->partial('partials/flash'); ?>
            <?= $this->yieldSection('content') ?>
        </main>
    </div>

    <!-- Container thông báo Toast -->
    <div class="toast-container"></div>

    <!-- Thư viện Vendor cục bộ & App JS -->
    <script src="<?= e(asset('vendor/chartjs/chart.umd.js')) ?>"></script>
    <script src="<?= e(asset('vendor/qrcode/qrcode.min.js')) ?>"></script>
    <script src="<?= e(asset('vendor/html5-qrcode/html5-qrcode.min.js')) ?>"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
