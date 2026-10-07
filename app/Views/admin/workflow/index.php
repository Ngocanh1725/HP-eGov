<?php $this->layout('layouts/admin', ['title' => 'Quản trị Quy trình Xử lý (BPMN-lite)']); ?>

<?php $this->startSection('content'); ?>
<div class="content-header mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">QUẢN TRỊ QUY TRÌNH XỬ LÝ (BPMN-LITE)</h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Phân hệ PH10: Định nghĩa quy trình xử lý Một cửa, thụ lý chuyên môn, ký số phê duyệt và liên thông ngang liên Sở / liên cấp Hải Phòng
        </p>
    </div>
</div>

<!-- Tìm kiếm -->
<div class="card mb-4 p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
    <form action="<?= e(url('/admin/quy-trinh')) ?>" method="GET" style="display: flex; gap: 1rem; align-items: center;">
        <input type="text" name="q" class="form-control" placeholder="Tìm theo tên quy trình, mã thủ tục hoặc tên TTHC..." value="<?= e($q ?? '') ?>" style="max-width: 500px;">
        <button type="submit" class="btn btn-primary">Tìm kiếm</button>
        <?php if (!empty($q)): ?>
            <a href="<?= e(url('/admin/quy-trinh')) ?>" class="btn btn-outline-secondary">Xóa tìm</a>
        <?php endif; ?>
    </form>
</div>

<!-- Bảng Quy trình -->
<div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow-x: auto;">
    <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 2px solid var(--border-color);">
            <tr>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem; width: 140px;">Mã TTHC</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem; min-width: 250px;">Thủ tục Hành chính</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Tên Quy trình Xử lý</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem; width: 100px;">Phiên bản</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem; width: 100px;">Số bước</th>
                <th style="padding: 0.75rem 1rem; text-align: right; font-size: 0.8rem; width: 140px;">Thời hạn (Giờ)</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem; width: 120px;">Trạng thái</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem; width: 180px;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($workflows)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        Chưa có quy trình nào được khởi tạo.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($workflows as $wf): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 0.75rem 1rem;">
                            <span class="badge" style="background: #e2e8f0; color: var(--color-primary); font-family: monospace; font-size: 0.8rem;">
                                <?= e($wf['ma_rut_gon'] ?? 'N/A') ?>
                            </span>
                        </td>
                        <td style="padding: 0.75rem 1rem;">
                            <div style="font-weight: 600; color: #1e293b;"><?= e($wf['ten_tthc'] ?? 'TTHC chung') ?></div>
                            <small class="text-muted" style="font-size: 0.75rem;">SLA TTHC: <?= e($wf['thoi_han_giai_quyet_ngay'] ?? 0) ?> ngày (<?= ((int)($wf['thoi_han_giai_quyet_ngay'] ?? 0)) * 8 ?> giờ)</small>
                        </td>
                        <td style="padding: 0.75rem 1rem; font-weight: 600;">
                            <a href="<?= e(url('/admin/quy-trinh/' . $wf['id'])) ?>" style="color: var(--color-primary); text-decoration: none;">
                                <?= e($wf['ten_quy_trinh']) ?>
                            </a>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <span class="badge" style="background: #eff6ff; color: #1d4ed8; font-weight: 700; font-size: 0.8rem;">
                                v<?= e($wf['phien_ban']) ?>
                            </span>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center; font-weight: 600;">
                            <?= e($wf['so_buoc']) ?> bước
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 600;">
                            <?= e($wf['tong_thoi_han_gio']) ?> giờ
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <?php if ($wf['is_active']): ?>
                                <span class="badge badge-success" style="font-size: 0.75rem;">Đang áp dụng</span>
                            <?php else: ?>
                                <span class="badge badge-secondary" style="font-size: 0.75rem;">Tạm ngưng</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <div style="display: flex; gap: 0.4rem; justify-content: center;">
                                <a href="<?= e(url('/admin/quy-trinh/' . $wf['id'])) ?>" class="btn btn-sm btn-outline-primary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                    📐 Thiết kế & Sơ đồ
                                </a>
                                <form action="<?= e(url('/admin/quy-trinh/' . $wf['id'] . '/nhan-ban-phien-ban')) ?>" method="POST" onsubmit="return confirm('Tạo phiên bản mới (v<?= ((int)$wf['phien_ban']) + 1 ?>)? Hồ sơ đang chạy sẽ giữ nguyên phiên bản cũ.');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-warning" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Tạo phiên bản v<?= ((int)$wf['phien_ban']) + 1 ?>">
                                        📋 Bản mới
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $this->endSection(); ?>
