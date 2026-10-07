<?php $this->layout('layouts/portal', ['title' => "Nộp hồ sơ trực tuyến: {$tthc['ten_tthc']}"]); ?>

<?php $this->startSection('content'); ?>
<div class="mb-4">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
        <a href="<?= e(url('/dvc/thu-tuc')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.85rem;">⬅ Danh mục DVC</a>
        <span class="text-muted">/</span>
        <span class="badge" style="background: #e2e8f0; color: #1e3a8a; font-weight: 700; font-family: monospace;">
            <?= e($tthc['ma_rut_gon'] ?? 'N/A') ?>
        </span>
    </div>
    <h1 style="font-size: 1.55rem; color: #0b2e59; margin: 0;">
        NỘP HỒ SƠ TRỰC TUYẾN: <?= e($tthc['ten_tthc']) ?>
    </h1>
    <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
        Quy trình tiếp nhận tự động 4 bước · Liên thông cơ quan hành chính TP. Hải Phòng
    </p>
</div>

<!-- STEPPER 4 BƯỚC -->
<div class="card p-3 mb-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
    <div style="display: flex; justify-content: space-between; align-items: center; position: relative;">
        <!-- Thanh kẻ nối -->
        <div style="position: absolute; top: 18px; left: 10%; right: 10%; height: 2px; background: #e2e8f0; z-index: 0;"></div>

        <div id="step-nav-1" class="step-nav-item active" onclick="goToStep(1)" style="position: relative; z-index: 1; text-align: center; cursor: pointer; flex: 1;">
            <div class="step-circle" style="width: 36px; height: 36px; border-radius: 50%; background: #0b2e59; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 0.25rem;">1</div>
            <div style="font-size: 0.8rem; font-weight: 600; color: #0b2e59;">Cơ quan tiếp nhận</div>
        </div>
        <div id="step-nav-2" class="step-nav-item" onclick="goToStep(2)" style="position: relative; z-index: 1; text-align: center; cursor: pointer; flex: 1;">
            <div class="step-circle" style="width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 0.25rem;">2</div>
            <div style="font-size: 0.8rem; font-weight: 600; color: #64748b;">Điền E-Form VNeID</div>
        </div>
        <div id="step-nav-3" class="step-nav-item" onclick="goToStep(3)" style="position: relative; z-index: 1; text-align: center; cursor: pointer; flex: 1;">
            <div class="step-circle" style="width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 0.25rem;">3</div>
            <div style="font-size: 0.8rem; font-weight: 600; color: #64748b;">Đính kèm hồ sơ</div>
        </div>
        <div id="step-nav-4" class="step-nav-item" onclick="goToStep(4)" style="position: relative; z-index: 1; text-align: center; cursor: pointer; flex: 1;">
            <div class="step-circle" style="width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 0.25rem;">4</div>
            <div style="font-size: 0.8rem; font-weight: 600; color: #64748b;">Xem lại & Ký số</div>
        </div>
    </div>
</div>

<!-- FORM NỘP HỒ SƠ CHÍNH -->
<form id="wizard-form" action="<?= e(url('/dvc/nop-ho-so/' . $tthc['id'])) ?>" method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <!-- ===================================================================== -->
    <!-- BƯỚC 1: CHỌN CƠ QUAN TIẾP NHẬN (ĐVHC 3 CẤP) -->
    <!-- ===================================================================== -->
    <div id="wizard-step-1" class="wizard-step card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <h2 style="font-size: 1.15rem; color: #0b2e59; margin: 0 0 1rem 0; font-weight: 700; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
            🏛️ BƯỚC 1: CHỌN CƠ QUAN TIẾP NHẬN HỒ SƠ
        </h2>

        <div class="form-group mb-3">
            <label class="form-label" style="font-weight: 600;">Cơ quan / Đơn vị tiếp nhận giải quyết: <span style="color: red;">*</span></label>
            <select name="don_vi_tiep_nhan_id" id="don_vi_tiep_nhan_id" class="form-control" style="font-size: 0.95rem; font-weight: 500;" required>
                <?php foreach ($donViList as $dv): ?>
                    <option value="<?= $dv['id'] ?>">
                        <?= e($dv['ten_don_vi']) ?> (Mã ĐD: <?= e($dv['ma_dinh_danh']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Hồ sơ sẽ được chuyển trực tiếp về Bộ phận Một cửa của đơn vị này trên hệ thống HP-eGov.</small>
        </div>

        <div class="card p-3 mb-3" style="background: #eff6ff; border-left: 4px solid #2563eb; border-radius: 6px;">
            <div style="font-weight: 700; color: #1e40af; font-size: 0.85rem;">THÔNG TIN THỦ TỤC ÁP DỤNG</div>
            <div style="font-size: 0.85rem; color: #334155; margin-top: 0.25rem;">
                - Tên TTHC: <strong><?= e($tthc['ten_tthc']) ?></strong><br>
                - Cấp giải quyết: <strong><?= e($tthc['cap_thuc_hien']) ?></strong><br>
                - Thời hạn: <strong><?= e($tthc['thoi_han_giai_quyet_ngay']) ?> ngày làm việc</strong><br>
                - Phí / lệ phí: <strong><?= number_format((float)($tthc['phi_le_phi'] ?? 0), 0, ',', '.') ?> đ</strong>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 1.5rem;">
            <button type="button" class="btn btn-primary" onclick="goToStep(2)" style="padding: 0.6rem 1.5rem; font-weight: 600;">
                Tiếp tục: Điền E-Form ➔
            </button>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- BƯỚC 2: ĐIỀN E-FORM ĐỘNG & AUTO-FILL VNEID C06 -->
    <!-- ===================================================================== -->
    <div id="wizard-step-2" class="wizard-step card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: none;">
        <h2 style="font-size: 1.15rem; color: #0b2e59; margin: 0 0 1rem 0; font-weight: 700; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
            📝 BƯỚC 2: ĐIỀN BIỂU MẪU ĐIỆN TỬ (E-FORM)
        </h2>

        <div class="card p-3 mb-4" style="background: #f0fdf4; border-left: 4px solid #16a34a; border-radius: 6px;">
            <div style="font-weight: 700; color: #166534; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>🔒</span> TỰ ĐỘNG ĐIỀN THÔNG TIN TỪ VNeID (C06 BỘ CÔNG AN)
            </div>
            <div style="font-size: 0.8rem; color: #15803d; margin-top: 0.25rem;">
                Các thông tin định danh cá nhân đã được tự động điền và khóa chỉnh sửa nhằm đảm bảo tính xác thực tuyệt đối với CSDL Quốc gia về Dân cư.
            </div>
        </div>

        <!-- Render các trường E-Form -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach (($schema['fields'] ?? []) as $field): ?>
                <?php 
                $fieldId = (string)($field['id'] ?? $field['name'] ?? 'field_' . uniqid());
                $vneidAttr = (string)($field['vneid_field'] ?? $field['autofill_vneid'] ?? '');
                $isAutofill = (!empty($vneidAttr) || !empty($field['readonly'])) && in_array($vneidAttr ?: $fieldId, ['ho_ten', 'nguoi_nop_ho_ten', 'so_cccd', 'nguoi_nop_cccd', 'ngay_sinh', 'dia_chi_thuong_tru', 'nguoi_nop_dia_chi']);
                
                // Trích xuất giá trị auto-fill từ VNeID
                $val = '';
                if ($isAutofill) {
                    $attrKey = !empty($vneidAttr) ? $vneidAttr : $fieldId;
                    $val = match($attrKey) {
                        'ho_ten', 'nguoi_nop_ho_ten' => $currentUser['ho_ten'] ?? '',
                        'so_cccd', 'nguoi_nop_cccd' => $currentUser['so_cccd'] ?? $currentUser['ma_so_thue'] ?? '',
                        'ngay_sinh' => $currentUser['ngay_sinh'] ?? '',
                        'gioi_tinh' => $currentUser['gioi_tinh'] ?? '',
                        'so_dien_thoai', 'nguoi_nop_sdt' => $currentUser['so_dien_thoai'] ?? '',
                        'dia_chi_thuong_tru', 'nguoi_nop_dia_chi' => $currentUser['dia_chi_thuong_tru'] ?? '',
                        default => (string)($currentUser[$attrKey] ?? '')
                    };
                }
                ?>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">
                        <?= e($field['label']) ?>
                        <?php if (!empty($field['required'])): ?>
                            <span style="color: red;">*</span>
                        <?php endif; ?>
                        <?php if ($isAutofill): ?>
                            <span class="badge badge-success" style="font-size: 0.7rem; margin-left: 0.5rem;">
                                🔒 VNeID Auto-fill (Khóa sửa)
                            </span>
                        <?php endif; ?>
                    </label>

                    <?php if ($isAutofill): ?>
                        <!-- Trường Auto-fill bị khóa sửa -->
                        <input type="text" class="form-control" value="<?= e($val) ?>" readonly style="background: #f1f5f9; font-weight: 600; color: #1e293b; border-color: #cbd5e1;">
                        <input type="hidden" name="eform_data[<?= e($fieldId) ?>]" value="<?= e($val) ?>">
                    <?php elseif ($field['type'] === 'textarea'): ?>
                        <textarea name="eform_data[<?= e($fieldId) ?>]" class="form-control" rows="3" placeholder="<?= e($field['placeholder'] ?? '') ?>" <?= !empty($field['required']) ? 'required' : '' ?>></textarea>
                    <?php elseif ($field['type'] === 'select'): ?>
                        <select name="eform_data[<?= e($fieldId) ?>]" class="form-control" <?= !empty($field['required']) ? 'required' : '' ?>>
                            <option value="">-- Chọn --</option>
                            <?php foreach (($field['options'] ?? []) as $optK => $optV): ?>
                                <option value="<?= e($optK) ?>"><?= e($optV) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($field['type'] === 'dvhc_address'): ?>
                        <input type="text" name="eform_data[<?= e($fieldId) ?>]" class="form-control" placeholder="<?= e($field['placeholder'] ?? 'Số nhà, đường phố, Xã/Phường, Quận/Huyện...') ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                    <?php else: ?>
                        <input type="<?= ($field['type'] === 'date' ? 'date' : ($field['type'] === 'number' ? 'number' : 'text')) ?>" 
                               name="eform_data[<?= e($fieldId) ?>]" class="form-control" 
                               placeholder="<?= e($field['placeholder'] ?? '') ?>" 
                               <?= !empty($field['required']) ? 'required' : '' ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; justify-content: space-between; margin-top: 1.5rem;">
            <button type="button" class="btn btn-secondary" onclick="goToStep(1)">⬅ Quay lại Bước 1</button>
            <button type="button" class="btn btn-primary" onclick="goToStep(3)" style="font-weight: 600;">Tiếp tục: Đính kèm hồ sơ ➔</button>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- BƯỚC 3: ĐÍNH KÈM THÀNH PHẦN HỒ SƠ (KHO DỮ LIỆU & TẢI MỚI) -->
    <!-- ===================================================================== -->
    <div id="wizard-step-3" class="wizard-step card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: none;">
        <h2 style="font-size: 1.15rem; color: #0b2e59; margin: 0 0 1rem 0; font-weight: 700; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
            📎 BƯỚC 3: ĐÍNH KÈM THÀNH PHẦN HỒ SƠ & GIẤY TỜ
        </h2>

        <div class="card p-3 mb-4" style="background: #eff6ff; border-left: 4px solid #2563eb; border-radius: 6px;">
            <div style="font-weight: 700; color: #1d4ed8; font-size: 0.85rem;">💡 TÁI SỬ DỤNG GIẤY TỜ TỪ KHO DỮ LIỆU ĐIỆN TỬ CÁ NHÂN</div>
            <div style="font-size: 0.8rem; color: #334155; margin-top: 0.25rem;">
                Quý khách có thể chọn trực tiếp các tài liệu đã được số hóa trước đây mà không cần nộp lại bản quét mới (theo Nghị định 107/2021/NĐ-CP).
            </div>
        </div>

        <!-- Danh sách từng thành phần giấy tờ -->
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <?php foreach ($thanhPhanHoSo as $tp): ?>
                <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; background: #fafafa;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                        <div style="font-weight: 700; color: #1e293b; font-size: 0.9rem;">
                            <?= e($tp['ten_giay_to']) ?>
                            <?php if ($tp['bat_buoc']): ?>
                                <span style="color: red;">*</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?= $tp['bat_buoc'] ? '<span class="badge badge-danger" style="font-size: 0.7rem;">Bắt buộc</span>' : '<span class="badge badge-secondary" style="font-size: 0.7rem;">Tùy chọn</span>' ?>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <!-- Cách 1: Chọn từ Kho dữ liệu -->
                        <div>
                            <label style="font-size: 0.75rem; color: #1d4ed8; font-weight: 600;">📁 Chọn từ Kho dữ liệu số hóa:</label>
                            <select name="kho_tai_lieu_ids[<?= $tp['id'] ?>]" class="form-control" style="font-size: 0.8rem; padding: 0.35rem 0.5rem;">
                                <option value="">-- Không sử dụng kho --</option>
                                <?php foreach ($khoDuLieu as $kd): ?>
                                    <option value="<?= $kd['id'] ?>">
                                        <?= e($kd['ten_tai_lieu']) ?> (Cấp: <?= date('d/m/Y', strtotime($kd['ngay_so_hoa'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Cách 2: Tải lên tệp mới -->
                        <div>
                            <label style="font-size: 0.75rem; color: #475569; font-weight: 600;">📤 Hoặc tải tệp mới (PDF, DOC, DOCX, JPG $\le 10$MB):</label>
                            <input type="file" name="tep_dinh_kem[<?= $tp['id'] ?>]" class="form-control" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; justify-content: space-between; margin-top: 1.5rem;">
            <button type="button" class="btn btn-secondary" onclick="goToStep(2)">⬅ Quay lại Bước 2</button>
            <button type="button" class="btn btn-primary" onclick="goToStep(4)" style="font-weight: 600;">Tiếp tục: Xem lại & Ký số ➔</button>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- BƯỚC 4: XEM LẠI, HÌNH THỨC NHẬN KẾT QUẢ & KÝ SỐ E-FORM -->
    <!-- ===================================================================== -->
    <div id="wizard-step-4" class="wizard-step card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: none;">
        <h2 style="font-size: 1.15rem; color: #0b2e59; margin: 0 0 1rem 0; font-weight: 700; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
            ✍️ BƯỚC 4: XEM LẠI, CHỌN NHẬN KẾT QUẢ & KÝ SỐ
        </h2>

        <!-- Chọn hình thức nhận kết quả -->
        <div class="card p-3 mb-4" style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px;">
            <label style="font-weight: 700; font-size: 0.9rem; color: #1e293b; margin-bottom: 0.5rem; display: block;">
                Hình thức nhận kết quả giải quyết TTHC: <span style="color: red;">*</span>
            </label>

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer;">
                    <input type="radio" name="hinh_thuc_nhan_kq" value="DIEN_TU" checked onchange="toggleAddressInput(false)">
                    <span><strong>1. Bản điện tử có ký số cơ quan:</strong> Trả trực tiếp vào Kho dữ liệu điện tử cá nhân của Cổng DVC (Khuyên dùng - Nhanh nhất)</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer;">
                    <input type="radio" name="hinh_thuc_nhan_kq" value="TRUC_TIEP" onchange="toggleAddressInput(false)">
                    <span><strong>2. Trực tiếp:</strong> Nhận bản giấy tại Bộ phận Một cửa của cơ quan giải quyết</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer;">
                    <input type="radio" name="hinh_thuc_nhan_kq" value="BUU_DIEN" onchange="toggleAddressInput(true)">
                    <span><strong>3. Dịch vụ Bưu chính công ích VNPost:</strong> Chuyển phát kết quả tận nơi</span>
                </label>
            </div>

            <div id="box-dia-chi-vnpost" style="display: none; margin-top: 0.75rem; border-top: 1px dashed #cbd5e1; padding-top: 0.75rem;">
                <label style="font-size: 0.8rem; font-weight: 600;">Địa chỉ nhận kết quả (Bưu điện chuyển phát):</label>
                <input type="text" name="dia_chi_nhan_kq" class="form-control" placeholder="Số nhà, đường phố, phường/xã, quận/huyện, số điện thoại người nhận..." value="<?= e($currentUser['dia_chi_thuong_tru'] ?? '') ?>">
            </div>
        </div>

        <!-- Khung Ký số E-Form -->
        <div class="card p-3 mb-4" style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-weight: 700; color: #166534; font-size: 0.95rem;">
                        🔐 KÝ SỐ ĐIỆN TỬ BIỂU MẪU E-FORM
                    </div>
                    <div style="font-size: 0.8rem; color: #15803d; margin-top: 2px;">
                        Người ký: <strong><?= e($currentUser['ho_ten'] ?? 'Công dân') ?></strong> (Số ĐDCN: <?= e($currentUser['so_cccd'] ?? $currentUser['ma_so_thue'] ?? '031090001234') ?>) · SmartCA VNPT Demo
                    </div>
                </div>
                <div style="text-align: right;">
                    <span class="badge badge-success" style="font-size: 0.8rem; padding: 0.4rem 0.75rem;">
                        ✅ Sẵn sàng ký số RSA-SHA256
                    </span>
                </div>
            </div>
        </div>

        <!-- Cam kết -->
        <div class="mb-4">
            <label style="display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.85rem; cursor: pointer;">
                <input type="checkbox" required checked style="margin-top: 3px;">
                <span>Tôi xin cam đoan các thông tin kê khai trên đây và các tài liệu đính kèm là hoàn toàn chính xác, đúng sự thật và chịu trách nhiệm hoàn toàn trước pháp luật về tính hợp pháp của hồ sơ.</span>
            </label>
        </div>

        <div style="display: flex; justify-content: space-between; margin-top: 1.5rem;">
            <button type="button" class="btn btn-secondary" onclick="goToStep(3)">⬅ Quay lại Bước 3</button>
            <button type="submit" class="btn btn-success" style="padding: 0.75rem 2rem; font-weight: 700; font-size: 1rem; box-shadow: var(--shadow-md);">
                🚀 KÝ SỐ & NỘP HỒ SƠ CHÍNH THỨC
            </button>
        </div>
    </div>
</form>

<script>
function goToStep(step) {
    document.querySelectorAll('.wizard-step').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.step-nav-item').forEach(el => {
        el.querySelector('.step-circle').style.background = '#e2e8f0';
        el.querySelector('.step-circle').style.color = '#64748b';
    });

    document.getElementById('wizard-step-' + step).style.display = 'block';
    const activeNav = document.getElementById('step-nav-' + step);
    activeNav.querySelector('.step-circle').style.background = '#0b2e59';
    activeNav.querySelector('.step-circle').style.color = '#fff';

    window.scrollTo({ top: 150, behavior: 'smooth' });
}

function toggleAddressInput(show) {
    document.getElementById('box-dia-chi-vnpost').style.display = show ? 'block' : 'none';
}
</script>
<?php $this->endSection(); ?>
