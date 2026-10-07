<?php $this->layout('layouts/admin', ['title' => 'Hồ sơ cá nhân']); ?>

<?php $this->startSection('content'); ?>
<div class="content-header mb-4">
    <h1 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">HỒ SƠ CÁ NHÂN & LỊCH SỬ ĐĂNG NHẬP</h1>
    <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
        Quản lý thông tin định danh công chức, đổi mật khẩu và theo dõi nhật ký bảo mật
    </p>
</div>

<div class="row g-4" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Cột 1: Thông tin cán bộ -->
    <div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 1rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
            Thông tin định danh công chức
        </h3>
        <table class="table table-sm mb-3" style="width: 100%; font-size: 0.9rem;">
            <tr>
                <td style="width: 140px; color: #64748b; font-weight: 600;">Họ và tên:</td>
                <td><strong style="color: #0B2E59;"><?= e($user['ho_ten']) ?></strong></td>
            </tr>
            <tr>
                <td style="color: #64748b; font-weight: 600;">Tên đăng nhập:</td>
                <td><code><?= e($user['ten_dang_nhap']) ?></code></td>
            </tr>
            <tr>
                <td style="color: #64748b; font-weight: 600;">Số CCCD:</td>
                <td><?= e($user['so_cccd'] ?? 'Chưa cập nhật') ?></td>
            </tr>
            <tr>
                <td style="color: #64748b; font-weight: 600;">Email công vụ:</td>
                <td><?= e($user['email']) ?></td>
            </tr>
            <tr>
                <td style="color: #64748b; font-weight: 600;">Số điện thoại:</td>
                <td><?= e($user['so_dien_thoai'] ?? 'Chưa cập nhật') ?></td>
            </tr>
            <tr>
                <td style="color: #64748b; font-weight: 600;">Đơn vị chính:</td>
                <td><?= e($user['ten_don_vi'] ?? 'Chưa gán') ?></td>
            </tr>
            <tr>
                <td style="color: #64748b; font-weight: 600;">Chức danh:</td>
                <td><?= e($user['ten_chuc_danh'] ?? 'Chuyên viên') ?></td>
            </tr>
            <tr>
                <td style="color: #64748b; font-weight: 600;">Vai trò hệ thống:</td>
                <td>
                    <?php foreach ($roles as $r): ?>
                        <span class="badge badge-primary"><?= e($r) ?></span>
                    <?php endforeach; ?>
                </td>
            </tr>
        </table>

        <?php if (count($donViList) > 1): ?>
        <div class="mt-3 p-3" style="background: #f8fafc; border-radius: var(--radius-md); border: 1px dashed var(--color-primary);">
            <strong style="font-size: 0.85rem; color: var(--color-primary);">Đơn vị kiêm nhiệm công tác:</strong>
            <ul style="margin: 0.5rem 0 0 1.2rem; font-size: 0.85rem; color: #475569;">
                <?php foreach ($donViList as $dv): ?>
                    <li>
                        <?= e($dv['ten_don_vi']) ?>
                        <?php if ((int)$dv['id'] === (int)($currentDonVi['id'] ?? 0)): ?>
                            <strong style="color: var(--color-success);">(Đang chọn làm việc)</strong>
                        <?php else: ?>
                            <a href="<?= e(url('/admin/chuyen-don-vi/' . $dv['id'])) ?>" style="text-decoration: underline; margin-left: 0.5rem;">[Chuyển sang]</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- Cột 2: Đổi mật khẩu cá nhân -->
    <div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 1rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
            Đổi mật khẩu định kỳ
        </h3>
        <form action="<?= e(url('/admin/doi-mat-khau')) ?>" method="POST" id="form-change-pwd">
            <?= csrf_field() ?>

            <div class="form-group mb-3">
                <label for="mat_khau_cu" class="form-label" style="font-weight: 600; font-size: 0.85rem;">Mật khẩu hiện tại</label>
                <input type="password" 
                       id="mat_khau_cu" 
                       name="mat_khau_cu" 
                       class="form-control <?= !empty($errors['mat_khau_cu']) ? 'is-invalid' : '' ?>" 
                       required>
                <?php if (!empty($errors['mat_khau_cu'])): ?>
                    <div class="invalid-feedback d-block"><?= e($errors['mat_khau_cu']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group mb-3">
                <label for="mat_khau_moi" class="form-label" style="font-weight: 600; font-size: 0.85rem;">Mật khẩu mới</label>
                <input type="password" 
                       id="mat_khau_moi" 
                       name="mat_khau_moi" 
                       class="form-control <?= !empty($errors['mat_khau_moi']) ? 'is-invalid' : '' ?>" 
                       placeholder="Tối thiểu 10 ký tự, có hoa, thường, số, đặc biệt" 
                       required>
                <?php if (!empty($errors['mat_khau_moi'])): ?>
                    <div class="invalid-feedback d-block"><?= e($errors['mat_khau_moi']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group mb-3">
                <label for="xac_nhan_mat_khau" class="form-label" style="font-weight: 600; font-size: 0.85rem;">Xác nhận mật khẩu mới</label>
                <input type="password" 
                       id="xac_nhan_mat_khau" 
                       name="xac_nhan_mat_khau" 
                       class="form-control <?= !empty($errors['xac_nhan_mat_khau']) ? 'is-invalid' : '' ?>" 
                       required>
                <?php if (!empty($errors['xac_nhan_mat_khau'])): ?>
                    <div class="invalid-feedback d-block"><?= e($errors['xac_nhan_mat_khau']) ?></div>
                <?php endif; ?>
            </div>

            <div class="p-2 mb-3" style="background: #f8fafc; border-radius: 6px; font-size: 0.75rem; color: #64748b;">
                Chính sách: ≥ 10 ký tự, gồm ít nhất 1 chữ hoa, 1 chữ thường, 1 chữ số, 1 ký tự đặc biệt (!@#$%^&*).
            </div>

            <button type="submit" class="btn btn-primary w-100" style="padding: 0.65rem; font-weight: 700;">
                CẬP NHẬT MẬT KHẨU
            </button>
        </form>
    </div>
</div>

<!-- Lịch sử đăng nhập -->
<div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
    <h3 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 1rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
        Nhật ký 20 lần đăng nhập gần nhất (Bảo mật tài khoản)
    </h3>
    <table class="table mb-0" style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
        <thead style="background: #f8fafc; border-bottom: 2px solid var(--border-color);">
            <tr>
                <th style="padding: 0.6rem 1rem; text-align: left;">Thời gian</th>
                <th style="padding: 0.6rem 1rem; text-align: left;">Địa chỉ IP</th>
                <th style="padding: 0.6rem 1rem; text-align: left;">Trình duyệt / Thiết bị</th>
                <th style="padding: 0.6rem 1rem; text-align: center;">Trạng thái</th>
                <th style="padding: 0.6rem 1rem; text-align: left;">Chi tiết</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($loginHistory)): ?>
                <tr>
                    <td colspan="5" class="text-center p-3 text-muted">Chưa có nhật ký đăng nhập nào.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($loginHistory as $lh): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 0.6rem 1rem; white-space: nowrap;">
                        <?= e(date('d/m/Y H:i:s', strtotime($lh['created_at']))) ?>
                    </td>
                    <td style="padding: 0.6rem 1rem;">
                        <code><?= e($lh['dia_chi_ip']) ?></code>
                    </td>
                    <td style="padding: 0.6rem 1rem; font-size: 0.75rem; color: var(--text-muted); max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <?= e($lh['user_agent']) ?>
                    </td>
                    <td style="padding: 0.6rem 1rem; text-align: center;">
                        <?php if ($lh['trang_thai'] === 'THANH_CONG'): ?>
                            <span class="badge badge-success">Thành công</span>
                        <?php elseif ($lh['trang_thai'] === 'KHOA'): ?>
                            <span class="badge badge-danger">Bị khóa</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Thất bại</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.6rem 1rem; font-size: 0.8rem; color: var(--text-muted);">
                        <?= e($lh['ly_do'] ?? '') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $this->endSection(); ?>
