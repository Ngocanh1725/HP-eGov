<?php $this->layout('layouts/admin', ['title' => 'Quản trị Danh mục Thủ tục Hành chính']); ?>

<?php $this->startSection('content'); ?>
<div class="content-header mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">DANH MỤC THỦ TỤC HÀNH CHÍNH (TTHC)</h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Quản trị danh mục TTHC chuẩn hóa toàn thành phố Hải Phòng, liên thông CSDL TTHC Quốc gia
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="<?= e(url('/admin/danh-muc/tthc/dong-bo')) ?>" class="btn btn-warning" style="display: flex; align-items: center; gap: 0.4rem;">
            🔄 Đồng bộ CSDL TTHC Quốc gia
        </a>
        <a href="<?= e(url('/admin/danh-muc/tthc/export')) ?>" class="btn btn-outline-primary" style="display: flex; align-items: center; gap: 0.4rem;">
            📥 Xuất CSV (BOM)
        </a>
    </div>
</div>

<!-- Lọc dữ liệu -->
<div class="card mb-4 p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
    <form action="<?= e(url('/admin/danh-muc/tthc')) ?>" method="GET" class="row g-2" style="display: grid; grid-template-columns: 2fr 1.5fr 1.5fr 1.5fr auto; gap: 0.75rem; align-items: flex-end;">
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Tìm kiếm:</label>
            <input type="text" name="q" class="form-control" placeholder="Mã TTHC, mã 4 số, tên thủ tục..." value="<?= e($filters['q'] ?? '') ?>">
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
                <option value="TINH" <?= (($filters['cap'] ?? '') === 'TINH') ? 'selected' : '' ?>>Cấp Tỉnh/Thành phố</option>
                <option value="HUYEN" <?= (($filters['cap'] ?? '') === 'HUYEN') ? 'selected' : '' ?>>Cấp Quận/Huyện</option>
                <option value="XA" <?= (($filters['cap'] ?? '') === 'XA') ? 'selected' : '' ?>>Cấp Xã/Phường</option>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Mức độ DVC:</label>
            <select name="muc_do" class="form-control">
                <option value="">-- Tất cả mức độ --</option>
                <option value="TOAN_TRINH" <?= (($filters['muc_do'] ?? '') === 'TOAN_TRINH') ? 'selected' : '' ?>>DVC Toàn trình</option>
                <option value="MOT_PHAN" <?= (($filters['muc_do'] ?? '') === 'MOT_PHAN') ? 'selected' : '' ?>>DVC Một phần</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1rem;">Lọc dữ liệu</button>
        </div>
    </form>
</div>

<!-- Bảng TTHC -->
<div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow-x: auto;">
    <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 2px solid var(--border-color);">
            <tr>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Mã TTHC / Rút gọn</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem; min-width: 280px;">Tên Thủ tục Hành chính</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Lĩnh vực</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">Cấp</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">Mức độ DVC</th>
                <th style="padding: 0.75rem 1rem; text-align: right; font-size: 0.8rem;">Thời hạn</th>
                <th style="padding: 0.75rem 1rem; text-align: right; font-size: 0.8rem;">Phí / Lệ phí</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">Hồ sơ / E-Form</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tthcList)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        Không tìm thấy thủ tục hành chính nào phù hợp.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($tthcList as $row): ?>
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 0.15s ease;">
                        <td style="padding: 0.75rem 1rem;">
                            <div style="font-weight: 700; color: var(--color-primary);"><?= e($row['ma_rut_gon'] ?? 'N/A') ?></div>
                            <small class="text-muted" style="font-size: 0.75rem;"><?= e($row['ma_tthc']) ?></small>
                        </td>
                        <td style="padding: 0.75rem 1rem;">
                            <div style="font-weight: 600; color: #1e293b;"><?= e($row['ten_tthc']) ?></div>
                            <?php if (!empty($row['can_cu_phap_ly'])): ?>
                                <small class="text-muted" style="font-size: 0.75rem; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;" title="<?= e($row['can_cu_phap_ly']) ?>">
                                    ⚖️ <?= e($row['can_cu_phap_ly']) ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; font-size: 0.85rem;">
                            <?= e($row['ten_linh_vuc'] ?? 'Chưa phân loại') ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <span class="badge" style="background: #e2e8f0; color: #334155; font-size: 0.75rem;">
                                <?= e($row['cap_thuc_hien']) ?>
                            </span>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <?php if ($row['muc_do_dvc'] === 'TOAN_TRINH'): ?>
                                <span class="badge badge-success" style="font-size: 0.75rem;">Toàn trình</span>
                            <?php else: ?>
                                <span class="badge badge-info" style="font-size: 0.75rem;">Một phần</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 600; font-size: 0.85rem;">
                            <?= e($row['thoi_han_giai_quyet_ngay']) ?> ngày
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: right; font-size: 0.85rem;">
                            <?= number_format((float)($row['phi_le_phi'] ?? 0), 0, ',', '.') ?> đ
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <a href="<?= e(url('/admin/danh-muc/eform?tthc_id=' . $row['id'])) ?>" class="btn btn-sm btn-outline-info" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Thiết kế E-Form cho TTHC">
                                📝 E-Form
                            </a>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <div style="display: flex; gap: 0.25rem; justify-content: center;">
                                <a href="<?= e(url('/admin/quy-trinh?q=' . urlencode($row['ma_rut_gon'] ?? $row['ten_tthc']))) ?>" class="btn btn-sm btn-outline-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Xem quy trình xử lý">
                                    ⚙️ Quy trình
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $this->endSection(); ?>
