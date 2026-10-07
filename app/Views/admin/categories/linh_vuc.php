<?php $this->layout('layouts/admin', ['title' => 'Danh mục Lĩnh vực TTHC']); ?>

<?php $this->startSection('content'); ?>
<div class="content-header mb-4" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">DANH MỤC LĨNH VỰC THỦ TỤC HÀNH CHÍNH</h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Phân loại các lĩnh vực quản lý nhà nước trên địa bàn TP. Hải Phòng
        </p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('modal-add-lv').style.display='flex'">
            ➕ Thêm Lĩnh vực
        </button>
    </div>
</div>

<div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow-x: auto;">
    <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 2px solid var(--border-color);">
            <tr>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem; width: 120px;">Mã lĩnh vực</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Tên lĩnh vực</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Mô tả phạm vi</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem; width: 140px;">Số lượng TTHC</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem; width: 100px;">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
            <tr style="border-bottom: 1px solid var(--border-color);">
                <td style="padding: 0.75rem 1rem; font-family: monospace; font-weight: 700; color: #0B2E59;">
                    <?= e($it['ma_linh_vuc']) ?>
                </td>
                <td style="padding: 0.75rem 1rem;">
                    <strong><?= e($it['ten_linh_vuc']) ?></strong>
                </td>
                <td style="padding: 0.75rem 1rem; font-size: 0.85rem; color: #64748b;">
                    <?= e($it['mo_ta'] ?? '') ?>
                </td>
                <td style="padding: 0.75rem 1rem; text-align: center;">
                    <span class="badge badge-primary"><?= (int)$it['so_luong_tthc'] ?> TTHC</span>
                </td>
                <td style="padding: 0.75rem 1rem; text-align: center;">
                    <?php if ((int)$it['trang_thai'] === 1): ?>
                        <span class="badge badge-success">Hoạt động</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Ngừng</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="modal-add-lv" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #fff; width: 450px; border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-lg);">
        <h3 style="margin-top: 0; color: var(--color-primary); font-size: 1.2rem;">➕ Thêm Lĩnh vực TTHC mới</h3>
        <form action="<?= e(url('/admin/danh-muc/linh-vuc')) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Mã lĩnh vực:</label>
                <input type="text" name="ma_linh_vuc" class="form-control" placeholder="Ví dụ: LV_LOGISTICS" required>
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Tên lĩnh vực:</label>
                <input type="text" name="ten_linh_vuc" class="form-control" placeholder="Tên lĩnh vực..." required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Mô tả:</label>
                <textarea name="mo_ta" class="form-control" rows="3" placeholder="Mô tả phạm vi quản lý..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('modal-add-lv').style.display='none'">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection(); ?>
