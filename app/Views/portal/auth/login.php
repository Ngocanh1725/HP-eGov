<?php $this->layout('layouts/auth', ['title' => 'Đăng nhập Cổng Dịch vụ công']); ?>

<?php $this->startSection('content'); ?>
<div style="margin-bottom: 1.5rem; text-align: center;">
    <div class="badge badge-success" style="font-size: 0.8rem; padding: 0.35rem 0.8rem;">
        CỔNG DỊCH VỤ CÔNG TP. HẢI PHÒNG
    </div>
</div>

<!-- Tabs chọn đối tượng -->
<div class="tabs mb-4" style="display: flex; border-bottom: 2px solid var(--border-color); justify-content: center; gap: 1rem;">
    <button type="button" 
            id="tab-btn-citizen" 
            class="tab-btn active" 
            onclick="switchAuthTab('citizen')"
            style="padding: 0.6rem 1.2rem; font-weight: 700; border: none; background: none; border-bottom: 3px solid var(--color-primary); color: var(--color-primary); cursor: pointer;">
        CÔNG DÂN (VNeID)
    </button>
    <button type="button" 
            id="tab-btn-business" 
            class="tab-btn" 
            onclick="switchAuthTab('business')"
            style="padding: 0.6rem 1.2rem; font-weight: 600; border: none; background: none; border-bottom: 3px solid transparent; color: var(--text-muted); cursor: pointer;">
        DOANH NGHIỆP / TỔ CHỨC
    </button>
</div>

<!-- Khối 1: Đăng nhập Công dân qua VNeID -->
<div id="auth-panel-citizen" class="auth-panel">
    <div class="p-3 mb-4 text-center" style="background: #fef2f2; border-radius: var(--radius-md); border: 1px solid #fee2e2;">
        <svg style="width: 48px; height: 48px; margin-bottom: 0.5rem;" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="20" fill="#C8102E"/>
            <path d="M50 18L76 29V52C76 68 65 82 50 86C35 82 24 68 24 52V29L50 18Z" fill="#D4A017"/>
            <polygon points="50,33 54,44 65,44 56,51 60,62 50,55 40,62 44,51 35,44 46,44" fill="#C8102E"/>
        </svg>
        <h4 style="font-size: 1rem; font-weight: 700; color: #991b1b; margin-bottom: 0.25rem;">
            Đăng nhập bằng Định danh điện tử Quốc gia
        </h4>
        <p style="font-size: 0.8rem; color: #7f1d1d; margin: 0;">
            Theo Nghị định 59/2022/NĐ-CP và Đề án 06, công dân sử dụng tài khoản VNeID để nộp hồ sơ TTHC toàn trình.
        </p>
    </div>

    <a href="<?= e(url('/auth/vneid/login')) ?>" 
       id="btn-login-vneid" 
       class="btn btn-danger w-100" 
       style="padding: 0.85rem; font-weight: 700; font-size: 1rem; background: #C8102E; border-color: #C8102E; display: flex; align-items: center; justify-content: center; gap: 0.6rem;">
        <span>🇻🇳</span>
        <span>ĐĂNG NHẬP VỚI TÀI KHOẢN VNeID</span>
    </a>
    <p class="text-center mt-2" style="font-size: 0.75rem; color: var(--text-muted);">
        Chuyển hướng đến Cổng xác thực định danh VNeID Sandbox (Bộ Công an)
    </p>
</div>

<!-- Khối 2: Đăng nhập Doanh nghiệp qua MST -->
<div id="auth-panel-business" class="auth-panel" style="display: none;">
    <form action="<?= e(url('/dang-nhap-doanh-nghiep')) ?>" method="POST" id="form-business-login">
        <?= csrf_field() ?>

        <div class="form-group mb-3">
            <label for="ma_so_thue" class="form-label" style="font-weight: 600;">Mã số thuế doanh nghiệp (MST)</label>
            <input type="text" 
                   id="ma_so_thue" 
                   name="ma_so_thue" 
                   class="form-control <?= !empty($errors['ma_so_thue']) ? 'is-invalid' : '' ?>" 
                   placeholder="Ví dụ: 0201234567" 
                   value="<?= e($old['ma_so_thue'] ?? '') ?>" 
                   required>
            <?php if (!empty($errors['ma_so_thue'])): ?>
                <div class="invalid-feedback d-block"><?= e($errors['ma_so_thue']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group mb-4">
            <label for="mat_khau_dn" class="form-label" style="font-weight: 600;">Mật khẩu tài khoản</label>
            <input type="password" 
                   id="mat_khau_dn" 
                   name="mat_khau" 
                   class="form-control" 
                   placeholder="Nhập mật khẩu..." 
                   required>
        </div>

        <button type="submit" id="btn-submit-business" class="btn btn-primary w-100" style="padding: 0.75rem; font-weight: 700;">
            ĐĂNG NHẬP DOANH NGHIỆP
        </button>
    </form>
    <div class="mt-2 text-center" style="font-size: 0.75rem; color: var(--text-muted);">
        Demo: Doanh nghiệp mẫu trong CSDL (hoặc đăng ký cấp mới)
    </div>
</div>

<div class="mt-4 pt-3 text-center" style="border-top: 1px solid var(--border-color); font-size: 0.85rem;">
    <p class="text-muted mb-2">Đồng chí là Cán bộ công chức xử lý hồ sơ?</p>
    <a href="<?= e(url('/admin/dang-nhap')) ?>" class="text-primary font-semibold" style="text-decoration: underline;">
        Chuyển đến Cổng Đăng nhập Cán bộ nghiệp vụ
    </a>
</div>

<div class="mt-2 text-center">
    <a href="<?= e(url('/')) ?>" style="font-size: 0.85rem; color: var(--text-muted);">
        ← Quay lại Trang chủ Cổng DVC Hải Phòng
    </a>
</div>

<script>
function switchAuthTab(type) {
    const btnCitizen = document.getElementById('tab-btn-citizen');
    const btnBusiness = document.getElementById('tab-btn-business');
    const panelCitizen = document.getElementById('auth-panel-citizen');
    const panelBusiness = document.getElementById('auth-panel-business');

    if (type === 'citizen') {
        btnCitizen.style.borderBottomColor = 'var(--color-primary)';
        btnCitizen.style.color = 'var(--color-primary)';
        btnBusiness.style.borderBottomColor = 'transparent';
        btnBusiness.style.color = 'var(--text-muted)';
        panelCitizen.style.display = 'block';
        panelBusiness.style.display = 'none';
    } else {
        btnBusiness.style.borderBottomColor = 'var(--color-primary)';
        btnBusiness.style.color = 'var(--color-primary)';
        btnCitizen.style.borderBottomColor = 'transparent';
        btnCitizen.style.color = 'var(--text-muted)';
        panelCitizen.style.display = 'none';
        panelBusiness.style.display = 'block';
    }
}

// Nếu có old tab = business thì tự chuyển
<?php if (($old['tab'] ?? '') === 'doanh-nghiep'): ?>
switchAuthTab('business');
<?php endif; ?>
</script>
<?php $this->endSection(); ?>
