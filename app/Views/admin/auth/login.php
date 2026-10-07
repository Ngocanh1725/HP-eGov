<?php $this->layout('layouts/auth', ['title' => 'Đăng nhập Cán bộ']); ?>

<?php $this->startSection('content'); ?>
<div style="margin-bottom: 1.5rem; text-align: center;">
    <div class="badge badge-primary" style="font-size: 0.8rem; padding: 0.35rem 0.8rem;">
        CỔNG ĐĂNG NHẬP DÀNH RIÊNG CHO CÁN BỘ CÔNG CHỨC
    </div>
</div>

<form action="<?= e(url('/admin/dang-nhap')) ?>" method="POST" id="form-admin-login">
    <?= csrf_field() ?>

    <div class="form-group mb-3">
        <label for="ten_dang_nhap" class="form-label" style="font-weight: 600;">Tên đăng nhập hoặc Email công vụ</label>
        <div class="input-icon-wrapper" style="position: relative;">
            <input type="text" 
                   id="ten_dang_nhap" 
                   name="ten_dang_nhap" 
                   class="form-control <?= !empty($errors['ten_dang_nhap']) ? 'is-invalid' : '' ?>" 
                   placeholder="Ví dụ: motcua.hongbang" 
                   value="<?= e($old['ten_dang_nhap'] ?? '') ?>" 
                   required 
                   autofocus>
        </div>
        <?php if (!empty($errors['ten_dang_nhap'])): ?>
            <div class="invalid-feedback d-block"><?= e($errors['ten_dang_nhap']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group mb-3">
        <label for="mat_khau" class="form-label" style="font-weight: 600;">Mật khẩu</label>
        <div style="position: relative;">
            <input type="password" 
                   id="mat_khau" 
                   name="mat_khau" 
                   class="form-control" 
                   placeholder="Nhập mật khẩu..." 
                   required>
            <button type="button" 
                    id="toggle-password" 
                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 0.85rem;"
                    tabindex="-1">
                Hiện
            </button>
        </div>
    </div>

    <?php if (!empty($requireCaptcha)): ?>
    <div class="form-group mb-3 p-3" style="background: var(--bg-body); border-radius: var(--radius-md); border: 1px dashed var(--color-primary-light);">
        <label for="captcha_code" class="form-label" style="font-weight: 600; color: var(--color-accent-red);">
            Xác thực bảo mật (Nhập sai quá 3 lần)
        </label>
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
            <img src="<?= e(url('/captcha')) ?>" 
                 id="captcha-img" 
                 alt="Mã Captcha" 
                 style="height: 40px; border-radius: 6px; border: 1px solid var(--border-color); cursor: pointer;" 
                 title="Bấm vào ảnh để đổi mã khác">
            <button type="button" 
                    id="btn-refresh-captcha" 
                    class="btn btn-sm btn-outline-secondary" 
                    style="font-size: 0.8rem; padding: 0.4rem 0.6rem;">
                🔄 Đổi mã
            </button>
        </div>
        <input type="text" 
               id="captcha_code" 
               name="captcha_code" 
               class="form-control" 
               placeholder="Nhập các ký tự trong hình trên" 
               maxlength="6" 
               autocomplete="off" 
               required>
    </div>
    <?php endif; ?>

    <div class="mb-4" style="display: flex; justify-content: space-between; align-items: center;">
        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted); cursor: pointer;">
            <input type="checkbox" name="ghi_nho" value="1"> Ghi nhớ đăng nhập
        </label>
        <span style="font-size: 0.85rem; color: var(--text-muted);">Khóa sau 5 lần sai</span>
    </div>

    <button type="submit" id="btn-submit-login" class="btn btn-primary w-100" style="padding: 0.75rem; font-weight: 700; font-size: 1rem;">
        ĐĂNG NHẬP CÁN BỘ
    </button>
</form>

<div class="mt-4 pt-3 text-center" style="border-top: 1px solid var(--border-color); font-size: 0.85rem;">
    <p class="text-muted mb-2">Đồng chí là Công dân hoặc Doanh nghiệp nộp hồ sơ?</p>
    <a href="<?= e(url('/dang-nhap')) ?>" class="text-primary font-semibold" style="text-decoration: underline;">
        Chuyển đến Cổng Đăng nhập Công dân & Doanh nghiệp
    </a>
</div>

<div class="mt-3 p-2 text-center" style="background: #f8fafc; border-radius: 6px; font-size: 0.75rem; color: #64748b;">
    <strong>Gợi ý tài khoản demo:</strong> <code>sysadmin</code> | <code>motcua.hongbang</code> | <code>chuyenvien.hongbang</code> | <code>ld.hongbang</code><br>
    Mật khẩu chung: <code>Hpegov@2026</code>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggle-password');
    const pwdInput = document.getElementById('mat_khau');
    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', function() {
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                toggleBtn.textContent = 'Ẩn';
            } else {
                pwdInput.type = 'password';
                toggleBtn.textContent = 'Hiện';
            }
        });
    }

    const captchaImg = document.getElementById('captcha-img');
    const refreshBtn = document.getElementById('btn-refresh-captcha');
    function reloadCaptcha() {
        if (captchaImg) {
            captchaImg.src = '<?= e(url('/captcha')) ?>?t=' + new Date().getTime();
        }
    }
    if (captchaImg) captchaImg.addEventListener('click', reloadCaptcha);
    if (refreshBtn) refreshBtn.addEventListener('click', reloadCaptcha);
});
</script>
<?php $this->endSection(); ?>
