<?php $this->layout('layouts/portal', ['title' => 'Danh mục Dịch vụ công trực tuyến']); ?>

<?php $this->startSection('content'); ?>
<div class="mb-4">
    <h1 style="font-size: 1.6rem; color: #0b2e59; margin: 0 0 0.25rem 0;">DANH MỤC THỦ TỤC HÀNH CHÍNH & DỊCH VỤ CÔNG</h1>
    <p class="text-muted" style="margin: 0; font-size: 0.9rem;">
        Hệ thống cung cấp dịch vụ công trực tuyến Một phần và Toàn trình trên toàn địa bàn thành phố Hải Phòng
    </p>
</div>

<!-- KHUNG TÌM KIẾM & BỘ LỌC ĐA CHIỀU -->
<div class="card p-3 mb-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
    <form action="<?= e(url('/dvc/thu-tuc')) ?>" method="GET" style="display: grid; grid-template-columns: 2fr 1.5fr 1.5fr 1.5fr auto; gap: 0.75rem; align-items: flex-end;">
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Từ khóa tìm kiếm:</label>
            <input type="text" name="q" class="form-control" placeholder="Tên TTHC, mã thủ tục..." value="<?= e($filters['q'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Lĩnh vực:</label>
            <select name="linh_vuc_id" class="form-control">
                <option value="">-- Tất cả lĩnh vực --</option>
                <?php foreach ($linhVucList as $lv): ?>
                    <option value="<?= $lv['id'] ?>" <?= ((int)($filters['linh_vuc_id'] ?? 0) === (int)$lv['id']) ? 'selected' : '' ?>>
                        <?= e($lv['ten_linh_vuc']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Cấp thực hiện:</label>
            <select name="cap" class="form-control">
                <option value="">-- Tất cả cấp --</option>
                <option value="TINH" <?= (($filters['cap'] ?? '') === 'TINH') ? 'selected' : '' ?>>Cấp Tỉnh / TP</option>
                <option value="HUYEN" <?= (($filters['cap'] ?? '') === 'HUYEN') ? 'selected' : '' ?>>Cấp Quận / Huyện</option>
                <option value="XA" <?= (($filters['cap'] ?? '') === 'XA') ? 'selected' : '' ?>>Cấp Xã / Phường</option>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Mức độ DVC:</label>
            <select name="muc_do" class="form-control">
                <option value="">-- Tất cả mức độ --</option>
                <option value="TOAN_TRINH" <?= (($filters['muc_do'] ?? '') === 'TOAN_TRINH') ? 'selected' : '' ?>>Toàn trình (100%)</option>
                <option value="MOT_PHAN" <?= (($filters['muc_do'] ?? '') === 'MOT_PHAN') ? 'selected' : '' ?>>Một phần</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1.25rem;">Lọc thủ tục</button>
        </div>
    </form>
</div>

<!-- DANH SÁCH THỦ TỤC -->
<div style="display: flex; flex-direction: column; gap: 1rem;">
    <?php if (empty($tthcList)): ?>
        <div class="card p-5 text-center" style="background: #fff; border-radius: var(--radius-md);">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔍</div>
            <h3 style="color: #64748b; font-size: 1.1rem; margin: 0;">Không tìm thấy thủ tục hành chính nào phù hợp với bộ lọc</h3>
            <p class="text-muted" style="margin-top: 0.25rem;">Vui lòng thử tìm kiếm với từ khóa khác hoặc xóa bớt tiêu chí lọc.</p>
        </div>
    <?php else: ?>
        <?php foreach ($tthcList as $tt): ?>
            <div class="card p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; border: 1px solid var(--border-color);">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                        <span class="badge" style="background: #e2e8f0; color: #1e3a8a; font-weight: 700; font-family: monospace;">
                            <?= e($tt['ma_rut_gon'] ?? 'N/A') ?>
                        </span>
                        <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 0.75rem;">
                            <?= e($tt['ten_linh_vuc'] ?? 'Chung') ?>
                        </span>
                        <?php if ($tt['muc_do_dvc'] === 'TOAN_TRINH'): ?>
                            <span class="badge badge-success" style="font-size: 0.75rem;">DVC Toàn trình</span>
                        <?php else: ?>
                            <span class="badge badge-info" style="font-size: 0.75rem;">DVC Một phần</span>
                        <?php endif; ?>
                    </div>
                    <h2 style="font-size: 1.05rem; font-weight: 700; margin: 0 0 0.35rem 0;">
                        <a href="<?= e(url('/dvc/thu-tuc/' . ($tt['ma_rut_gon'] ?? $tt['id']))) ?>" style="color: #0b2e59; text-decoration: none;">
                            <?= e($tt['ten_tthc']) ?>
                        </a>
                    </h2>
                    <div style="font-size: 0.8rem; color: #64748b; display: flex; gap: 1rem; flex-wrap: wrap;">
                        <span>🏛️ Cấp: <strong><?= e($tt['cap_thuc_hien']) ?></strong></span>
                        <span>⏱️ Thời hạn: <strong><?= e($tt['thoi_han_giai_quyet_ngay']) ?> ngày làm việc</strong></span>
                        <span>💰 Phí/Lệ phí: <strong><?= number_format((float)($tt['phi_le_phi'] ?? 0), 0, ',', '.') ?> đ</strong></span>
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; flex-shrink: 0;">
                    <a href="<?= e(url('/dvc/thu-tuc/' . ($tt['ma_rut_gon'] ?? $tt['id']))) ?>" class="btn btn-sm btn-outline-secondary">
                        Chi tiết
                    </a>
                    <a href="<?= e(url('/dvc/nop-ho-so/' . $tt['id'])) ?>" class="btn btn-sm btn-primary" style="font-weight: 600;">
                        📝 Nộp trực tuyến
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php $this->endSection(); ?>
