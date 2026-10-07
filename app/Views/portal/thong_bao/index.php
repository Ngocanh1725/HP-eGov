<?php $this->layout('layouts/portal', ['title' => 'Trung tâm thông báo']); ?>

<?php $this->startSection('content'); ?>
<div class="mb-4">
    <h1 style="font-size: 1.55rem; color: #0b2e59; margin: 0 0 0.25rem 0;">
        🔔 TRUNG TÂM THÔNG BÁO CỦA CÔNG DÂN
    </h1>
    <p class="text-muted" style="margin: 0; font-size: 0.85rem;">
        Cập nhật biến động trạng thái hồ sơ, thông báo bổ sung và kết quả xử lý TTHC
    </p>
</div>

<div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow: hidden;">
    <?php if (empty($notifications)): ?>
        <div class="p-5 text-center text-muted">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📭</div>
            <div style="font-weight: 600;">Hộp thư thông báo đang trống.</div>
            <small>Khi có cập nhật về hồ sơ nộp, các thông báo sẽ được lưu trữ tại đây.</small>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column;">
            <?php foreach ($notifications as $nb): ?>
                <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                    <div>
                        <div style="font-weight: 700; color: #1e293b; font-size: 0.95rem; margin-bottom: 0.25rem;">
                            <?= e($nb['tieu_de']) ?>
                        </div>
                        <div style="font-size: 0.85rem; color: #475569; line-height: 1.45;">
                            <?= e($nb['noi_dung']) ?>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                            🕒 <?= date('d/m/Y H:i', strtotime($nb['created_at'])) ?>
                        </small>
                    </div>

                    <?php if (!empty($nb['lien_ket'])): ?>
                        <div style="flex-shrink: 0;">
                            <a href="<?= e(url($nb['lien_ket'])) ?>" class="btn btn-sm btn-outline-primary" style="font-size: 0.8rem;">
                                Xem hồ sơ ➔
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php $this->endSection(); ?>
