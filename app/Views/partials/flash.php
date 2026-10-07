<?php
$successMsg = flash('success');
$errorMsg   = flash('error');
$warningMsg = flash('warning');
$infoMsg    = flash('info');
?>

<?php if ($successMsg): ?>
    <div class="toast toast-success show mb-3" style="position:static;transform:none;max-width:100%;pointer-events:auto;" role="alert">
        <div style="font-size:1.2rem;font-weight:bold;color:var(--color-sla-green);">✔</div>
        <div class="toast-content">
            <div class="toast-title">Thành công</div>
            <div class="toast-message"><?= e($successMsg) ?></div>
        </div>
    </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="toast toast-error show mb-3" style="position:static;transform:none;max-width:100%;pointer-events:auto;" role="alert">
        <div style="font-size:1.2rem;font-weight:bold;color:var(--color-sla-red);">✖</div>
        <div class="toast-content">
            <div class="toast-title">Thông báo lỗi</div>
            <div class="toast-message"><?= e($errorMsg) ?></div>
        </div>
    </div>
<?php endif; ?>

<?php if ($warningMsg): ?>
    <div class="toast toast-warning show mb-3" style="position:static;transform:none;max-width:100%;pointer-events:auto;" role="alert">
        <div style="font-size:1.2rem;font-weight:bold;color:var(--color-sla-yellow);">⚠</div>
        <div class="toast-content">
            <div class="toast-title">Cảnh báo</div>
            <div class="toast-message"><?= e($warningMsg) ?></div>
        </div>
    </div>
<?php endif; ?>

<?php if ($infoMsg): ?>
    <div class="toast toast-info show mb-3" style="position:static;transform:none;max-width:100%;pointer-events:auto;" role="alert">
        <div style="font-size:1.2rem;font-weight:bold;color:var(--color-primary);">ℹ</div>
        <div class="toast-content">
            <div class="toast-title">Thông tin</div>
            <div class="toast-message"><?= e($infoMsg) ?></div>
        </div>
    </div>
<?php endif; ?>
