<?php $this->layout('layouts/admin', ['title' => 'Quản trị Công dân & Doanh nghiệp']); ?>

<?php $this->startSection('content'); ?>
<div class="content-header mb-4" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">DANH SÁCH TÀI KHOẢN CÔNG DÂN & DOANH NGHIỆP</h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Theo dõi tài khoản định danh VNeID và Doanh nghiệp nộp hồ sơ DVC
        </p>
    </div>
</div>

<div class="card mb-4 p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
    <form action="<?= e(url('/admin/he-thong/cong-dan')) ?>" method="GET" class="row g-2" style="display: grid; grid-template-columns: 3fr 1fr auto; gap: 0.75rem; align-items: flex-end;">
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Tìm kiếm:</label>
            <input type="text" name="q" class="form-control" placeholder="Số CCCD, Mã số thuế, Họ tên, Tên doanh nghiệp, SĐT..." value="<?= e($filters['q'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Loại đối tượng:</label>
            <select name="type" class="form-control">
                <option value="">-- Tất cả --</option>
                <option value="CA_NHAN" <?= (($filters['type'] ?? '') === 'CA_NHAN') ? 'selected' : '' ?>>Công dân (Cá nhân)</option>
                <option value="TO_CHUC" <?= (($filters['type'] ?? '') === 'TO_CHUC') ? 'selected' : '' ?>>Doanh nghiệp (Tổ chức)</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1rem;">Lọc danh sách</button>
        </div>
    </form>
</div>

<div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow-x: auto;">
    <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 2px solid var(--border-color);">
            <tr>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">ID</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Đối tượng</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Định danh (CCCD / MST)</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Thông tin liên hệ</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">Nguồn xác thực</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">Trạng thái</th>
                <th style="padding: 0.75rem 1rem; text-align: right; font-size: 0.8rem;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($citizens)): ?>
                <tr>
                    <td colspan="7" class="text-center p-4 text-muted">Không tìm thấy tài khoản nào.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($citizens as $c): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 0.75rem 1rem; font-size: 0.85rem; color: var(--text-muted);">#<?= $c['id'] ?></td>
                    <td style="padding: 0.75rem 1rem;">
                        <strong style="color: var(--color-primary);"><?= e($c['ho_ten']) ?></strong><br>
                        <?php if ($c['loai_chu_the'] === 'TO_CHUC'): ?>
                            <span class="badge badge-info" style="font-size: 0.7rem;">Doanh nghiệp</span>
                        <?php else: ?>
                            <span class="badge badge-secondary" style="font-size: 0.7rem;">Cá nhân</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.75rem 1rem; font-size: 0.85rem;">
                        <?php if (!empty($c['so_cccd'])): ?>
                            <span>CCCD: <code><?= e($c['so_cccd']) ?></code></span><br>
                        <?php endif; ?>
                        <?php if (!empty($c['ma_so_thue'])): ?>
                            <span>MST: <code><?= e($c['ma_so_thue']) ?></code></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.75rem 1rem; font-size: 0.85rem;">
                        <span>📞 <?= e($c['so_dien_thoai'] ?? 'Chưa có') ?></span><br>
                        <span class="text-muted" style="font-size: 0.75rem;">✉️ <?= e($c['email'] ?? 'Chưa có') ?></span>
                    </td>
                    <td style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">
                        <?php if (!empty($c['vneid_sub'])): ?>
                            <span class="badge badge-danger">🇻🇳 VNeID C06</span>
                        <?php else: ?>
                            <span class="badge badge-outline">Tài khoản Cổng</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.75rem 1rem; text-align: center;">
                        <?php if ((int)$c['trang_thai'] === 1): ?>
                            <span class="badge badge-success">Hoạt động</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Đã khóa</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.75rem 1rem; text-align: right;">
                        <form action="<?= e(url('/admin/he-thong/cong-dan/' . $c['id'] . '/trang-thai')) ?>" method="POST" style="display: inline-block;">
                            <?= csrf_field() ?>
                            <?php if ((int)$c['trang_thai'] === 1): ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Khóa</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-sm btn-outline-success">Mở khóa</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $this->endSection(); ?>
