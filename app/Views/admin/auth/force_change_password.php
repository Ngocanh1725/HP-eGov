<?php $this->layout('layouts/auth', ['title' => 'Đổi mật khẩu lần đầu']); ?>

<?php $this->startSection('content'); ?>
<div style="margin-bottom: 1.5rem; text-align: center;">
    <div class="badge badge-warning" style="font-size: 0.8rem; padding: 0.35rem 0.8rem;">
        BẮT BUỘC ĐỔI MẬT KHẨU LẦN ĐẦU (AN TOÀN CẤP ĐỘ 3)
    </div>
    <p class="text-muted mt-2" style="font-size: 0.85rem;">
        Để bảo đảm an toàn thông tin, đồng chí vui lòng thiết lập mật khẩu mới tuân thủ chính sách bảo mật trước khi vào bàn làm việc.
    </p>
</div>

<form action="<?= e(url('/admin/doi-mat-khau-lan-dau')) ?>" method="POST" id="form-force-change-pwd">
    <?= csrf_field() ?>

    <div class="form-group mb-3">
        <label for="mat_khau_moi" class="form-label" style="font-weight: 600;">Mật khẩu mới</label>
        <input type="password" 
               id="mat_khau_moi" 
               name="mat_khau_moi" 
               class="form-control <?= !empty($errors['mat_khau_moi']) ? 'is-invalid' : '' ?>" 
               placeholder="Nhập mật khẩu mới..." 
               required 
               autofocus>
        <?php if (!empty($errors['mat_khau_moi'])): ?>
            <div class="invalid-feedback d-block"><?= e($errors['mat_khau_moi']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group mb-3">
        <label for="xac_nhan_mat_khau" class="form-label" style="font-weight: 600;">Xác nhận lại mật khẩu</label>
        <input type="password" 
               id="xac_nhan_mat_khau" 
               name="xac_nhan_mat_khau" 
               class="form-control <?= !empty($errors['xac_nhan_mat_khau']) ? 'is-invalid' : '' ?>" 
               placeholder="Nhập lại mật khẩu mới..." 
               required>
        <?php if (!empty($errors['xac_nhan_mat_khau'])): ?>
            <div class="invalid-feedback d-block"><?= e($errors['xac_nhan_mat_khau']) ?></div>
        <?php endif; ?>
    </div>

    <div class="p-3 mb-4" style="background: #f8fafc; border-radius: var(--radius-md); font-size: 0.8rem; border-left: 3px solid var(--color-primary);">
        <strong style="color: var(--color-primary);">Yêu cầu chính sách mật khẩu:</strong>
        <ul style="margin: 0.4rem 0 0 1.2rem; color: #475569; line-height: 1.5;">
            <li>Độ dài tối thiểu <strong>10 ký tự</strong></li>
            <li>Có ít nhất 1 chữ cái in hoa (A-Z) và 1 chữ thường (a-z)</li>
            <li>Có ít nhất 1 chữ số (0-9)</li>
            <li>Có ít nhất 1 ký tự đặc biệt (!@#$%^&*...)</li>
        </ul>
    </div>

    <button type="submit" id="btn-save-new-password" class="btn btn-primary w-100" style="padding: 0.75rem; font-weight: 700;">
        CẬP NHẬT MẬT KHẨU & VÀO HỆ THỐNG
    </button>
</form>

<div class="mt-4 pt-3 text-center" style="border-top: 1px solid var(--border-color); font-size: 0.85rem;">
    <a href="<?= e(url('/admin/dang-xuat')) ?>" class="text-danger" style="text-decoration: underline;">
        Hủy bỏ & Đăng xuất
    </a>
</div>
<?php $this->endSection(); ?>
