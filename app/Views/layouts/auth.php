<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Đăng nhập hệ thống') ?> | HP-eGov Hải Phòng</title>
    <meta name="description" content="Đăng nhập Cổng Dịch vụ công & Một cửa điện tử liên thông TP. Hải Phòng">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/layout.css')) ?>">
    <style>
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background: linear-gradient(135deg, #071F3D 0%, #0B2E59 60%, #164682 100%);
            position: relative;
        }
        .auth-card {
            background-color: var(--bg-surface);
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            max-width: 480px;
            width: 100%;
            padding: 2.5rem 2rem;
            border-top: 5px solid var(--color-accent-gold);
            position: relative;
            z-index: 2;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-logo {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
        }
        .auth-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--color-primary);
            text-transform: uppercase;
            margin-bottom: 0.25rem;
        }
        .auth-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <svg class="auth-logo" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="48" fill="#C8102E" stroke="#D4A017" stroke-width="3"/>
                <circle cx="50" cy="50" r="38" fill="none" stroke="#D4A017" stroke-width="1.5" stroke-dasharray="4,2"/>
                <polygon points="50,22 56,38 73,38 59,48 64,64 50,54 36,64 41,48 27,38 44,38" fill="#FFD700"/>
            </svg>
            <h1 class="auth-title">UBND THÀNH PHỐ HẢI PHÒNG</h1>
            <p class="auth-subtitle">Hệ thống Một cửa điện tử liên thông (HP-eGov)</p>
        </div>

        <?php $this->partial('partials/flash'); ?>
        <?= $this->yieldSection('content') ?>
    </div>

    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
