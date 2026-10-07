<?php $this->layout('layouts/admin', ['title' => 'Quản trị Đơn vị Hành chính']); ?>

<?php $this->startSection('content'); ?>
<div class="content-header mb-4" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">DANH MỤC ĐƠN VỊ HÀNH CHÍNH (ĐVHC 3 CẤP)</h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Quản trị 15 quận/huyện/thành phố và xã/phường Hải Phòng; quản lý hiệu lực và hỗ trợ sáp nhập ĐVHC
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <button type="button" class="btn btn-warning" onclick="document.getElementById('modal-merge-dvhc').style.display='flex'">
            🔀 Sáp nhập ĐVHC
        </button>
        <a href="<?= e(url('/admin/danh-muc/dvhc/export')) ?>" class="btn btn-outline-primary">
            📥 Xuất CSV (BOM)
        </a>
    </div>
</div>

<!-- Lọc dữ liệu -->
<div class="card mb-4 p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
    <form action="<?= e(url('/admin/danh-muc/dvhc')) ?>" method="GET" class="row g-2" style="display: grid; grid-template-columns: 2fr 1.5fr 2fr auto; gap: 0.75rem; align-items: flex-end;">
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Tìm kiếm:</label>
            <input type="text" name="q" class="form-control" placeholder="Mã ĐVHC, Tên quận/huyện/xã..." value="<?= e($filters['q'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Cấp hành chính:</label>
            <select name="cap" class="form-control">
                <option value="">-- Tất cả các cấp --</option>
                <option value="TINH" <?= (($filters['cap'] ?? '') === 'TINH') ? 'selected' : '' ?>>Cấp Tỉnh (TP Hải Phòng)</option>
                <option value="HUYEN" <?= (($filters['cap'] ?? '') === 'HUYEN') ? 'selected' : '' ?>>Cấp Huyện (Quận / Huyện / TP)</option>
                <option value="XA" <?= (($filters['cap'] ?? '') === 'XA') ? 'selected' : '' ?>>Cấp Xã (Xã / Phường / Thị trấn)</option>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Thuộc Quận / Huyện:</label>
            <select name="parent_id" class="form-control">
                <option value="">-- Tất cả quận huyện --</option>
                <?php foreach ($districts as $dst): ?>
                    <option value="<?= $dst['id'] ?>" <?= ((int)($filters['parent_id'] ?? 0) === (int)$dst['id']) ? 'selected' : '' ?>>
                        <?= e($dst['ten_dvhc']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1rem;">Lọc dữ liệu</button>
        </div>
    </form>
</div>

<!-- Bảng ĐVHC -->
<div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow-x: auto;">
    <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 2px solid var(--border-color);">
            <tr>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Mã ĐVHC</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Tên Đơn vị Hành chính</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Cấp bậc / Loại hình</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Trực thuộc</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">Thời hạn hiệu lực</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">Trạng thái</th>
                <th style="padding: 0.75rem 1rem; text-align: right; font-size: 0.8rem;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dvhcList as $d): ?>
            <tr style="border-bottom: 1px solid var(--border-color);">
                <td style="padding: 0.75rem 1rem; font-family: monospace; font-weight: 700; color: #0B2E59;">
                    <?= e($d['ma_dvhc']) ?>
                </td>
                <td style="padding: 0.75rem 1rem;">
                    <strong><?= e($d['ten_dvhc']) ?></strong>
                </td>
                <td style="padding: 0.75rem 1rem; font-size: 0.85rem;">
                    <span class="badge badge-outline"><?= e($d['cap_dvhc']) ?></span>
                    <span class="text-muted" style="margin-left: 0.25rem; font-size: 0.75rem;"><?= e($d['loai_dvhc']) ?></span>
                </td>
                <td style="padding: 0.75rem 1rem; font-size: 0.85rem;">
                    <?= e($d['ten_cha'] ?? 'TP Hải Phòng') ?>
                </td>
                <td style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">
                    Từ: <?= e(date('d/m/Y', strtotime($d['hieu_luc_tu']))) ?>
                    <?php if (!empty($d['hieu_luc_den'])): ?>
                        <br><span style="color: var(--color-danger);">Đến: <?= e(date('d/m/Y', strtotime($d['hieu_luc_den']))) ?></span>
                    <?php endif; ?>
                </td>
                <td style="padding: 0.75rem 1rem; text-align: center;">
                    <?php if ((int)$d['trang_thai'] === 1): ?>
                        <span class="badge badge-success">Hiệu lực</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Đã ngừng</span>
                    <?php endif; ?>
                </td>
                <td style="padding: 0.75rem 1rem; text-align: right;">
                    <form action="<?= e(url('/admin/danh-muc/dvhc/' . $d['id'] . '/trang-thai')) ?>" method="POST" style="display: inline-block;">
                        <?= csrf_field() ?>
                        <?php if ((int)$d['trang_thai'] === 1): ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Ngừng hiệu lực">Ngừng</button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-sm btn-outline-success" title="Kích hoạt lại">Bật</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Sáp nhập ĐVHC -->
<div id="modal-merge-dvhc" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #fff; width: 550px; border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: var(--shadow-lg);">
        <h3 style="margin-top: 0; color: var(--color-primary); font-size: 1.25rem;">
            🔀 Nghiệp vụ Sáp nhập Đơn vị Hành chính
        </h3>
        <p class="text-muted" style="font-size: 0.85rem;">
            Đóng hiệu lực ĐVHC cũ và chuyển giao trách nhiệm hành chính sang ĐVHC mới tiếp nhận (lưu lịch sử kiểm toán).
        </p>
        <form action="<?= e(url('/admin/danh-muc/dvhc/sap-nhap')) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Đơn vị cũ bị sáp nhập (đóng hiệu lực):</label>
                <select name="don_vi_cu_id" class="form-control" required>
                    <option value="">-- Chọn đơn vị cũ --</option>
                    <?php foreach ($dvhcList as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= e($d['ten_dvhc']) ?> (<?= e($d['ma_dvhc']) ?> - <?= e($d['cap_dvhc']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Đơn vị mới tiếp nhận quản lý:</label>
                <select name="don_vi_moi_id" class="form-control" required>
                    <option value="">-- Chọn đơn vị mới --</option>
                    <?php foreach ($dvhcList as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= e($d['ten_dvhc']) ?> (<?= e($d['ma_dvhc']) ?> - <?= e($d['cap_dvhc']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Ngày có hiệu lực sáp nhập:</label>
                <input type="date" name="ngay_hieu_luc" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Căn cứ pháp lý (Nghị quyết / Quyết định):</label>
                <input type="text" name="can_cu_phap_ly" class="form-control" placeholder="Ví dụ: Nghị quyết số 1232/NQ-UBTVQH15...">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('modal-merge-dvhc').style.display='none'">
                    Hủy bỏ
                </button>
                <button type="submit" class="btn btn-warning" style="font-weight: 700;">
                    Xác nhận Sáp nhập
                </button>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection(); ?>
