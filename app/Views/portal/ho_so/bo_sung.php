<?php $this->layout('layouts/portal', ['title' => "Bổ sung hồ sơ: {$hoSo['ma_ho_so']}"]); ?>

<?php $this->startSection('content'); ?>
<div class="mb-4">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
        <a href="<?= e(url('/cong-dan/ho-so/' . $hoSo['ma_ho_so'])) ?>" style="color: #64748b; text-decoration: none; font-size: 0.85rem;">⬅ Chi tiết hồ sơ</a>
        <span class="text-muted">/</span>
        <span style="font-family: monospace; font-weight: 700; color: #0b2e59; font-size: 0.9rem;"><?= e($hoSo['ma_ho_so']) ?></span>
    </div>
    <h1 style="font-size: 1.5rem; color: #b45309; margin: 0;">
        ⚠️ NỘP BỔ SUNG HỒ SƠ THEO YÊU CẦU CÁN BỘ
    </h1>
    <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
        Thủ tục: <strong><?= e($hoSo['ten_tthc']) ?></strong> · Mã hồ sơ: <strong><?= e($hoSo['ma_ho_so']) ?></strong>
    </p>
</div>

<!-- THẺ YÊU CẦU BỔ SUNG CỦA CÁN BỘ -->
<div class="card p-4 mb-4" style="background: #fffbeb; border-left: 5px solid #d97706; border-radius: var(--radius-md);">
    <div style="font-weight: 700; color: #92400e; font-size: 0.95rem; margin-bottom: 0.5rem;">
        📋 NỘI DUNG YÊU CẦU BỔ SUNG TỪ BỘ PHẬN MỘT CỬA / THỤ LÝ:
    </div>
    <div style="font-size: 0.9rem; color: #78350f; line-height: 1.5; background: #fff; padding: 1rem; border-radius: 6px; border: 1px solid #fde68a;">
        <?= nl2br(e($reqLc['noi_dung'] ?? 'Hồ sơ thiếu thành phần giấy tờ hợp lệ hoặc thông tin chưa rõ ràng. Vui lòng kiểm tra và bổ sung các tài liệu theo danh mục.')) ?>
    </div>
    <div style="font-size: 0.75rem; color: #b45309; margin-top: 0.5rem;">
        Thời điểm yêu cầu: <?= !empty($reqLc['created_at']) ? date('d/m/Y H:i', strtotime($reqLc['created_at'])) : '—' ?>
    </div>
</div>

<!-- FORM NỘP BỔ SUNG -->
<div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
    <form action="<?= e(url('/cong-dan/ho-so/' . $hoSo['ma_ho_so'] . '/bo-sung')) ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <h2 style="font-size: 1.1rem; color: #0b2e59; margin: 0 0 1rem 0; font-weight: 700; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
            📎 TẢI LÊN TÀI LIỆU, GIẤY TỜ BỔ SUNG
        </h2>

        <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
            <?php foreach ($thanhPhanHoSo as $tp): ?>
                <div style="border: 1px solid var(--border-color); border-radius: 6px; padding: 0.75rem 1rem; background: #fafafa;">
                    <label style="font-weight: 600; font-size: 0.85rem; color: #1e293b; display: block; margin-bottom: 0.35rem;">
                        <?= e($tp['ten_giay_to']) ?>
                    </label>
                    <input type="file" name="tep_bo_sung[<?= $tp['id'] ?>]" class="form-control" style="font-size: 0.85rem;" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png">
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-group mb-4">
            <label class="form-label" style="font-weight: 600;">Ý kiến giải trình / Ghi chú của người nộp: <span style="color: red;">*</span></label>
            <textarea name="ghi_chu_bo_sung" class="form-control" rows="3" required placeholder="Trình bày tóm tắt các tài liệu và nội dung đã hoàn thiện bổ sung theo yêu cầu..."></textarea>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center;">
            <a href="<?= e(url('/cong-dan/ho-so/' . $hoSo['ma_ho_so'])) ?>" class="btn btn-secondary">⬅ Hủy bỏ</a>
            <button type="submit" class="btn btn-warning" style="font-weight: 700; padding: 0.6rem 2rem;">
                🚀 Nộp hồ sơ bổ sung & Tiếp tục xử lý
            </button>
        </div>
    </form>
</div>
<?php $this->endSection(); ?>
