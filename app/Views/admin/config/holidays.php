<?php $this->layout('layouts/admin', ['title' => 'Quản lý Ngày nghỉ lễ']); ?>

<?php $this->startSection('content'); ?>
<div class="content-header mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">DANH MỤC NGÀY NGHỈ LỄ & TẾT (LỊCH NĂM)</h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Căn cứ tính hạn xử lý SLA của hồ sơ (loại trừ thứ 7, CN và các ngày nghỉ lễ chính thức theo Bộ luật Lao động)
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <button type="button" class="btn btn-primary" onclick="document.getElementById('modal-add-holiday').style.display='flex'">
            ➕ Thêm ngày nghỉ
        </button>
        <form action="<?= e(url('/admin/danh-muc/ngay-nghi/nhan-ban')) ?>" method="POST" onsubmit="return confirm('Nhân bản toàn bộ ngày nghỉ của năm <?= e($currentYear) ?> sang năm <?= e($currentYear + 1) ?>?');" style="display: inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="source_year" value="<?= e($currentYear) ?>">
            <button type="submit" class="btn btn-warning">
                📋 Nhân bản sang năm <?= e($currentYear + 1) ?>
            </button>
        </form>
    </div>
</div>

<!-- Lọc theo Năm -->
<div class="card mb-4 p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; align-items: center; justify-content: space-between;">
    <div style="display: flex; align-items: center; gap: 1rem;">
        <span style="font-weight: 600; font-size: 0.9rem;">Chọn năm hiển thị:</span>
        <div style="display: flex; gap: 0.5rem;">
            <?php foreach ($years as $y): ?>
                <a href="<?= e(url('/admin/danh-muc/ngay-nghi?nam=' . $y)) ?>" 
                   class="btn btn-sm <?= ((int)$y === (int)$currentYear) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    Năm <?= e($y) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="text-muted" style="font-size: 0.85rem;">
        Tổng số ngày nghỉ năm <?= e($currentYear) ?>: <strong><?= count($holidays) ?> ngày</strong>
    </div>
</div>

<!-- Bảng danh sách ngày nghỉ -->
<div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow-x: auto;">
    <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 2px solid var(--border-color);">
            <tr>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem; width: 60px;">STT</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Ngày nghỉ</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Tên dịp nghỉ lễ / Tết</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">Loại ngày nghỉ</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Ghi chú</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($holidays)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        Chưa có lịch ngày nghỉ lễ nào cho năm <?= e($currentYear) ?>. Bạn có thể thêm mới hoặc nhân bản từ năm trước!
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($holidays as $i => $h): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 0.75rem 1rem; text-align: center; color: #64748b;"><?= $i + 1 ?></td>
                        <td style="padding: 0.75rem 1rem; font-weight: 700; color: #dc2626;">
                            📅 <?= date('d/m/Y', strtotime($h['ngay'])) ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; font-weight: 600; color: #1e293b;">
                            <?= e($h['ten_ngay_nghi']) ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <span class="badge" style="background: #fee2e2; color: #991b1b; font-size: 0.75rem;">
                                <?= e($h['loai'] ?? 'LE_TET') ?>
                            </span>
                        </td>
                        <td style="padding: 0.75rem 1rem; font-size: 0.85rem; color: #64748b;">
                            <?= e($h['ghi_chu'] ?? '—') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Thêm Ngày Nghỉ Lễ -->
<div id="modal-add-holiday" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
    <div style="background: #fff; width: 100%; max-width: 500px; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); overflow: hidden;">
        <div style="padding: 1rem 1.5rem; background: var(--color-primary); color: #fff; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem; color: #fff;">Thêm Ngày nghỉ lễ mới</h3>
            <button type="button" onclick="document.getElementById('modal-add-holiday').style.display='none'" style="background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form action="<?= e(url('/admin/danh-muc/ngay-nghi')) ?>" method="POST" style="padding: 1.5rem;">
            <?= csrf_field() ?>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Ngày nghỉ: <span style="color: red;">*</span></label>
                <input type="date" name="ngay" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Tên dịp nghỉ lễ: <span style="color: red;">*</span></label>
                <input type="text" name="ten_ngay_nghi" class="form-control" placeholder="Ví dụ: Tết Dương lịch, Giỗ Tổ Hùng Vương..." required>
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Loại ngày nghỉ:</label>
                <select name="loai" class="form-control">
                    <option value="LE_TET">Nghỉ Lễ / Tết theo luật</option>
                    <option value="NGHI_BU">Nghỉ bù</option>
                    <option value="QUOC_TANG">Quốc tang</option>
                </select>
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Ghi chú:</label>
                <textarea name="ghi_chu" class="form-control" rows="2" placeholder="Ghi chú văn bản quy định..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-add-holiday').style.display='none'">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu ngày nghỉ</button>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection(); ?>
