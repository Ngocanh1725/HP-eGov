<?php $this->layout('layouts/auth', ['title' => 'VNeID Sandbox']); ?>

<?php $this->startSection('content'); ?>
<div style="background: #991b1b; color: #fff; margin: -2.5rem -2rem 1.5rem -2rem; padding: 1.25rem 2rem; border-top-left-radius: var(--radius-lg); border-top-right-radius: var(--radius-lg); text-align: center;">
    <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; margin-bottom: 0.25rem;">
        <span style="font-size: 1.5rem;">🇻🇳</span>
        <strong style="letter-spacing: 1px; font-size: 1.05rem;">BỘ CÔNG AN – CỤC C06</strong>
    </div>
    <div style="font-size: 0.8rem; opacity: 0.9;">
        MÔI TRƯỜNG THỬ NGHIỆM ĐỊNH DANH ĐIỆN TỬ (VNeID SANDBOX MOCK)
    </div>
</div>

<div class="mb-3 p-2 text-center" style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 6px; font-size: 0.8rem; color: #92400e;">
    🔒 Luồng xác thực <strong>OIDC Authorization Code Flow</strong> (Đề án 06 Chính phủ)
</div>

<form action="<?= e(url('/auth/vneid/consent')) ?>" method="POST" id="form-vneid-sandbox">
    <input type="hidden" name="state" value="<?= e($state) ?>">

    <div class="mb-3">
        <label class="form-label" style="font-weight: 700; color: #1e293b;">
            1. Chọn công dân mẫu Hải Phòng để thử nghiệm nhanh:
        </label>
        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <?php foreach ($mockCitizens as $idx => $c): ?>
            <label style="display: flex; align-items: flex-start; gap: 0.6rem; padding: 0.6rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">
                <input type="radio" 
                       name="preset_citizen" 
                       value="<?= $idx ?>" 
                       <?= $idx === 0 ? 'checked' : '' ?> 
                       onchange="fillCitizen(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>)"
                       style="margin-top: 3px;">
                <div>
                    <strong style="color: #0B2E59;"><?= e($c['ho_ten']) ?></strong> 
                    <span class="badge badge-outline" style="font-size: 0.7rem;">CCCD: <?= e($c['so_cccd']) ?></span><br>
                    <span class="text-muted" style="font-size: 0.75rem;">
                        <?= e($c['gioi_tinh'] === 'NAM' ? 'Nam' : 'Nữ') ?> · <?= e(date('d/m/Y', strtotime($c['ngay_sinh']))) ?> · <?= e($c['dia_chi_thuong_tru']) ?>
                    </span>
                </div>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mb-3 pt-2" style="border-top: 1px dashed var(--border-color);">
        <label class="form-label" style="font-weight: 700; color: #1e293b;">
            2. Thông tin thuộc tính chia sẻ sang HP-eGov (Scope: openid profile id_card):
        </label>
        
        <div class="row g-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
            <div>
                <label style="font-size: 0.75rem; color: #64748b;">Số CCCD (12 số):</label>
                <input type="text" id="so_cccd" name="so_cccd" class="form-control" style="font-size: 0.85rem;" required>
            </div>
            <div>
                <label style="font-size: 0.75rem; color: #64748b;">Họ và tên:</label>
                <input type="text" id="ho_ten" name="ho_ten" class="form-control" style="font-size: 0.85rem;" required>
            </div>
            <div>
                <label style="font-size: 0.75rem; color: #64748b;">Ngày sinh (YYYY-MM-DD):</label>
                <input type="date" id="ngay_sinh" name="ngay_sinh" class="form-control" style="font-size: 0.85rem;">
            </div>
            <div>
                <label style="font-size: 0.75rem; color: #64748b;">Giới tính:</label>
                <select id="gioi_tinh" name="gioi_tinh" class="form-control" style="font-size: 0.85rem;">
                    <option value="NAM">Nam</option>
                    <option value="NU">Nữ</option>
                    <option value="KHAC">Khác</option>
                </select>
            </div>
        </div>

        <div class="mt-2">
            <label style="font-size: 0.75rem; color: #64748b;">Quê quán:</label>
            <input type="text" id="que_quan" name="que_quan" class="form-control" style="font-size: 0.85rem;">
        </div>

        <div class="mt-2">
            <label style="font-size: 0.75rem; color: #64748b;">Địa chỉ thường trú:</label>
            <input type="text" id="dia_chi_thuong_tru" name="dia_chi_thuong_tru" class="form-control" style="font-size: 0.85rem;">
        </div>

        <div class="row g-2 mt-1" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
            <div>
                <label style="font-size: 0.75rem; color: #64748b;">Số điện thoại:</label>
                <input type="text" id="so_dien_thoai" name="so_dien_thoai" class="form-control" style="font-size: 0.85rem;">
            </div>
            <div>
                <label style="font-size: 0.75rem; color: #64748b;">Email:</label>
                <input type="email" id="email" name="email" class="form-control" style="font-size: 0.85rem;">
            </div>
        </div>
    </div>

    <!-- Màn hình Consent / Xác nhận ủy quyền -->
    <div class="p-3 mb-4" style="background: #f1f5f9; border-radius: 6px; font-size: 0.8rem; color: #334155;">
        <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer;">
            <input type="checkbox" id="chk-consent" checked required style="margin-top: 3px;">
            <span>
                Tôi đồng ý ủy quyền cho hệ thống <strong>HP-eGov Hải Phòng</strong> truy xuất các thông tin định danh cá nhân nêu trên để thực hiện các thủ tục hành chính công.
            </span>
        </label>
    </div>

    <button type="submit" 
            id="btn-confirm-consent" 
            class="btn btn-danger w-100" 
            style="padding: 0.75rem; font-weight: 700; background: #C8102E; border-color: #C8102E;">
        ĐỒNG Ý CHIA SẺ & ĐĂNG NHẬP VÀO HP-eGov
    </button>
</form>

<div class="mt-3 text-center">
    <a href="<?= e(url('/dang-nhap')) ?>" style="font-size: 0.8rem; color: var(--text-muted);">
        ← Từ chối & Quay lại Cổng DVC
    </a>
</div>

<script>
function fillCitizen(c) {
    document.getElementById('so_cccd').value = c.so_cccd || '';
    document.getElementById('ho_ten').value = c.ho_ten || '';
    document.getElementById('ngay_sinh').value = c.ngay_sinh || '';
    document.getElementById('gioi_tinh').value = c.gioi_tinh || 'NAM';
    document.getElementById('que_quan').value = c.que_quan || '';
    document.getElementById('dia_chi_thuong_tru').value = c.dia_chi_thuong_tru || '';
    document.getElementById('so_dien_thoai').value = c.so_dien_thoai || '';
    document.getElementById('email').value = c.email || '';
}

// Khởi tạo công dân mặc định đầu tiên
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($mockCitizens[0])): ?>
    fillCitizen(<?= json_encode($mockCitizens[0]) ?>);
    <?php endif; ?>
});
</script>
<?php $this->endSection(); ?>
