<?php $this->layout('layouts/admin', ['title' => 'Tham số Hệ thống & Lịch làm việc']); ?>

<?php $this->startSection('content'); ?>
<div class="content-header mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">THAM SỐ HỆ THỐNG & GIỜ LÀM VIỆC</h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Cấu hình thời gian làm việc (buổi sáng, buổi chiều), khung giờ SLA, hạn mức lưu trữ và các biến vận hành toàn thành phố
        </p>
    </div>
</div>

<div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow-x: auto;">
    <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 2px solid var(--border-color);">
            <tr>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem; width: 220px;">Mã cấu hình</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem; width: 220px;">Tên tham số</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem; width: 120px;">Kiểu dữ liệu</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Giá trị cấu hình</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Mô tả ý nghĩa</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem; width: 100px;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($configs)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        Chưa có tham số cấu hình nào.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($configs as $cfg): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 0.75rem 1rem; font-family: monospace; font-weight: 700; color: var(--color-primary);">
                            <?= e($cfg['ma_cau_hinh']) ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; font-weight: 600; color: #1e293b;">
                            <?= e($cfg['ten_cau_hinh'] ?? $cfg['ma_cau_hinh']) ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <span class="badge" style="background: #e2e8f0; color: #475569; font-size: 0.75rem; font-family: monospace;">
                                <?= e($cfg['kieu_du_lieu'] ?? 'string') ?>
                            </span>
                        </td>
                        <td style="padding: 0.75rem 1rem;">
                            <div style="max-width: 380px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: monospace; background: #f8fafc; padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid #e2e8f0; font-size: 0.85rem;" title="<?= e($cfg['gia_tri']) ?>">
                                <?= e($cfg['gia_tri']) ?>
                            </div>
                        </td>
                        <td style="padding: 0.75rem 1rem; font-size: 0.8rem; color: #64748b;">
                            <?= e($cfg['mo_ta'] ?? '—') ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <button type="button" class="btn btn-sm btn-outline-primary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick='openEditConfig(<?= json_encode($cfg, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                ✏️ Sửa
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Chỉnh sửa tham số cấu hình -->
<div id="modal-edit-config" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
    <div style="background: #fff; width: 100%; max-width: 550px; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); overflow: hidden;">
        <div style="padding: 1rem 1.5rem; background: var(--color-primary); color: #fff; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem; color: #fff;">Chỉnh sửa Tham số Hệ thống</h3>
            <button type="button" onclick="document.getElementById('modal-edit-config').style.display='none'" style="background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form id="form-edit-config" method="POST" style="padding: 1.5rem;">
            <?= csrf_field() ?>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Mã cấu hình:</label>
                <input type="text" id="cfg_ma" class="form-control" readonly style="background: #f1f5f9; font-family: monospace;">
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Tên tham số:</label>
                <input type="text" id="cfg_ten" class="form-control" readonly style="background: #f1f5f9;">
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Kiểu dữ liệu:</label>
                <span id="cfg_kieu" class="badge" style="background: #e2e8f0; color: #334155;">string</span>
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Giá trị cấu hình mới: <span style="color: red;">*</span></label>
                <textarea name="gia_tri" id="cfg_val" class="form-control" rows="3" required style="font-family: monospace; font-size: 0.9rem;"></textarea>
                <small class="text-muted" id="cfg_hint">Kiểm tra hợp lệ định dạng theo kiểu dữ liệu trước khi lưu.</small>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-edit-config').style.display='none'">Đóng</button>
                <button type="submit" class="btn btn-primary">Lưu tham số & Ghi phiên bản</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditConfig(cfg) {
    document.getElementById('cfg_ma').value = cfg.ma_cau_hinh;
    document.getElementById('cfg_ten').value = cfg.ten_cau_hinh || cfg.ma_cau_hinh;
    document.getElementById('cfg_kieu').textContent = cfg.kieu_du_lieu || 'string';
    document.getElementById('cfg_val').value = cfg.gia_tri || '';
    
    let hint = 'Giá trị chuỗi thông thường.';
    if (cfg.kieu_du_lieu === 'integer') hint = 'Vui lòng chỉ nhập số nguyên.';
    if (cfg.kieu_du_lieu === 'json') hint = 'Vui lòng nhập cú pháp JSON hợp lệ (VD: {"sang": "07:30-11:30"}).';
    document.getElementById('cfg_hint').textContent = hint;

    document.getElementById('form-edit-config').action = '<?= url('/admin/danh-muc/cau-hinh/') ?>/' + cfg.id;
    document.getElementById('modal-edit-config').style.display = 'flex';
}
</script>
<?php $this->endSection(); ?>
