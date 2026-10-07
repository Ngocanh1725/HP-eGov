<?php $this->layout('layouts/admin', ['title' => 'Quản trị Cán bộ']); ?>

<?php $this->startSection('content'); ?>
<div class="content-header mb-4" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">QUẢN TRỊ CÁN BỘ & TÀI KHOẢN (PH11)</h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Quản lý tài khoản công chức, mở khóa đăng nhập, reset mật khẩu và phân quyền RBAC
        </p>
    </div>
</div>

<!-- Bộ lọc tìm kiếm -->
<div class="card mb-4 p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
    <form action="<?= e(url('/admin/he-thong/nguoi-dung')) ?>" method="GET" class="row g-2" style="display: grid; grid-template-columns: 2fr 2fr 1fr auto; gap: 0.75rem; align-items: flex-end;">
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Từ khóa tìm kiếm:</label>
            <input type="text" name="q" class="form-control" placeholder="Tên, tài khoản, CCCD, email..." value="<?= e($filters['q'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Đơn vị công tác:</label>
            <select name="don_vi_id" class="form-control">
                <option value="">-- Tất cả đơn vị --</option>
                <?php foreach ($donViList as $dv): ?>
                    <option value="<?= $dv['id'] ?>" <?= ((int)($filters['don_vi_id'] ?? 0) === (int)$dv['id']) ? 'selected' : '' ?>>
                        <?= e($dv['ten_don_vi']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Trạng thái:</label>
            <select name="status" class="form-control">
                <option value="">-- Tất cả --</option>
                <option value="1" <?= (($filters['status'] ?? '') === '1') ? 'selected' : '' ?>>Đang hoạt động</option>
                <option value="0" <?= (($filters['status'] ?? '') === '0') ? 'selected' : '' ?>>Đã khóa</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1rem;">Lọc dữ liệu</button>
        </div>
    </form>
</div>

<!-- Bảng danh sách cán bộ -->
<div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow-x: auto;">
    <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 2px solid var(--border-color);">
            <tr>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">ID</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Cán bộ công chức</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Đơn vị / Chức danh</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Vai trò RBAC</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">Bảo mật</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">Trạng thái</th>
                <th style="padding: 0.75rem 1rem; text-align: right; font-size: 0.8rem;">Thao tác quản trị</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="text-center p-4 text-muted">Không tìm thấy cán bộ nào thỏa mãn điều kiện tìm kiếm.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 0.75rem 1rem; font-size: 0.85rem; color: var(--text-muted);">#<?= $u['id'] ?></td>
                    <td style="padding: 0.75rem 1rem;">
                        <strong style="color: var(--color-primary);"><?= e($u['ho_ten']) ?></strong><br>
                        <span class="text-muted" style="font-size: 0.8rem;">Tài khoản: <code><?= e($u['ten_dang_nhap']) ?></code></span>
                    </td>
                    <td style="padding: 0.75rem 1rem; font-size: 0.85rem;">
                        <?= e($u['ten_don_vi'] ?? 'Chưa gán') ?><br>
                        <span class="text-muted" style="font-size: 0.75rem;"><?= e($u['ten_chuc_danh'] ?? '') ?></span>
                    </td>
                    <td style="padding: 0.75rem 1rem; font-size: 0.8rem;">
                        <span class="badge badge-primary"><?= e($u['ds_vai_tro'] ?? 'Chưa có vai trò') ?></span>
                    </td>
                    <td style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">
                        <?php if (!empty($u['khoa_den']) && strtotime($u['khoa_den']) > time()): ?>
                            <span class="badge badge-danger">🔒 Khóa 15p (sai <?= $u['so_lan_sai'] ?> lần)</span>
                        <?php elseif ($u['so_lan_sai'] > 0): ?>
                            <span class="badge badge-warning">Sai <?= $u['so_lan_sai'] ?>/5 lần</span>
                        <?php else: ?>
                            <span class="text-muted">Bình thường</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.75rem 1rem; text-align: center;">
                        <?php if ((int)$u['trang_thai'] === 1): ?>
                            <span class="badge badge-success">Hoạt động</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Vô hiệu</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.75rem 1rem; text-align: right; white-space: nowrap;">
                        <!-- Mở khóa nếu bị khóa tạm thời -->
                        <?php if (!empty($u['khoa_den']) && strtotime($u['khoa_den']) > time()): ?>
                        <form action="<?= e(url('/admin/he-thong/nguoi-dung/' . $u['id'] . '/mo-khoa')) ?>" method="POST" style="display: inline-block;">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Mở khóa tài khoản ngay lập tức">
                                🔓 Mở khóa
                            </button>
                        </form>
                        <?php endif; ?>

                        <!-- Reset mật khẩu -->
                        <form action="<?= e(url('/admin/he-thong/nguoi-dung/' . $u['id'] . '/reset-mat-khau')) ?>" method="POST" style="display: inline-block;" onsubmit="return confirm('Đồng chí có chắc chắn muốn đặt lại mật khẩu của cán bộ này về mặc định Hpegov@2026?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-info" title="Reset mật khẩu về Hpegov@2026">
                                🔑 Reset MK
                            </button>
                        </form>

                        <!-- Khóa / Mở khóa hoạt động -->
                        <?php if ((int)$u['id'] !== (int)auth()['id']): ?>
                        <form action="<?= e(url('/admin/he-thong/nguoi-dung/' . $u['id'] . '/trang-thai')) ?>" method="POST" style="display: inline-block;">
                            <?= csrf_field() ?>
                            <?php if ((int)$u['trang_thai'] === 1): ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Khóa tài khoản này">
                                    ⛔ Khóa
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Kích hoạt tài khoản này">
                                    ✅ Bật
                                </button>
                            <?php endif; ?>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $this->endSection(); ?>
