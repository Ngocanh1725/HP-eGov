<?php $this->layout('layouts/portal', ['title' => $tthc['ten_tthc']]); ?>

<?php $this->startSection('content'); ?>
<div class="mb-4">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
        <a href="<?= e(url('/dvc/thu-tuc')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.85rem;">⬅ Danh mục DVC</a>
        <span class="text-muted">/</span>
        <span class="badge" style="background: #e2e8f0; color: #1e3a8a; font-weight: 700; font-family: monospace;">
            <?= e($tthc['ma_rut_gon'] ?? 'N/A') ?>
        </span>
        <?php if ($tthc['muc_do_dvc'] === 'TOAN_TRINH'): ?>
            <span class="badge badge-success">DVC Toàn trình</span>
        <?php else: ?>
            <span class="badge badge-info">DVC Một phần</span>
        <?php endif; ?>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1.5rem; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 300px;">
            <h1 style="font-size: 1.65rem; color: #0b2e59; margin: 0 0 0.5rem 0; line-height: 1.35;">
                <?= e($tthc['ten_tthc']) ?>
            </h1>
            <p class="text-muted" style="margin: 0; font-size: 0.85rem;">
                Mã thủ tục Quốc gia: <strong><?= e($tthc['ma_tthc']) ?></strong> · Lĩnh vực: <strong><?= e($tthc['ten_linh_vuc'] ?? 'Chung') ?></strong>
            </p>
        </div>
        <div>
            <a href="<?= e(url('/dvc/nop-ho-so/' . $tthc['id'])) ?>" class="btn btn-primary" style="padding: 0.75rem 1.75rem; font-size: 1rem; font-weight: 700; box-shadow: var(--shadow-md); display: inline-flex; align-items: center; gap: 0.5rem;">
                📝 Nộp hồ sơ trực tuyến
            </a>
        </div>
    </div>
</div>

<!-- THẺ TỔNG QUAN THÔNG TIN -->
<div class="row mb-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
    <div class="card p-3" style="background: #f8fafc; border-left: 4px solid #2563eb; border-radius: var(--radius-md);">
        <small class="text-muted">THỜI HẠN GIẢI QUYẾT</small>
        <div style="font-size: 1.25rem; font-weight: 700; color: #1e3a8a; margin-top: 0.25rem;">
            <?= e($tthc['thoi_han_giai_quyet_ngay']) ?> ngày làm việc
        </div>
    </div>
    <div class="card p-3" style="background: #f8fafc; border-left: 4px solid #16a34a; border-radius: var(--radius-md);">
        <small class="text-muted">PHÍ / LỆ PHÍ</small>
        <div style="font-size: 1.25rem; font-weight: 700; color: #15803d; margin-top: 0.25rem;">
            <?= number_format((float)($tthc['phi_le_phi'] ?? 0), 0, ',', '.') ?> đ
        </div>
    </div>
    <div class="card p-3" style="background: #f8fafc; border-left: 4px solid #d4a017; border-radius: var(--radius-md);">
        <small class="text-muted">CẤP THỰC HIỆN</small>
        <div style="font-size: 1.25rem; font-weight: 700; color: #b45309; margin-top: 0.25rem;">
            <?= e($tthc['cap_thuc_hien']) ?>
        </div>
    </div>
    <div class="card p-3" style="background: #f8fafc; border-left: 4px solid #0284c7; border-radius: var(--radius-md);">
        <small class="text-muted">CÁCH THỨC THỰC HIỆN</small>
        <div style="font-size: 1.1rem; font-weight: 700; color: #0369a1; margin-top: 0.25rem;">
            Trực tuyến hoặc Một cửa
        </div>
    </div>
</div>

<!-- NỘI DUNG CHI TIẾT CÁC PHẦN -->
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- 1. Thành phần hồ sơ -->
    <div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <h2 style="font-size: 1.15rem; color: #0b2e59; margin: 0 0 1rem 0; font-weight: 700; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
            📄 1. THÀNH PHẦN HỒ SƠ YÊU CẦU
        </h2>
        <?php if (empty($thanhPhanHoSo)): ?>
            <p class="text-muted">Thành phần hồ sơ áp dụng theo quy định chuẩn của biểu mẫu tờ khai điện tử E-Form.</p>
        <?php else: ?>
            <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8fafc; font-size: 0.8rem; border-bottom: 1px solid var(--border-color);">
                    <tr>
                        <th style="padding: 0.65rem 0.75rem; text-align: center; width: 50px;">STT</th>
                        <th style="padding: 0.65rem 0.75rem; text-align: left;">Tên giấy tờ, tài liệu</th>
                        <th style="padding: 0.65rem 0.75rem; text-align: center; width: 100px;">Bản chính</th>
                        <th style="padding: 0.65rem 0.75rem; text-align: center; width: 100px;">Bản sao</th>
                        <th style="padding: 0.65rem 0.75rem; text-align: center; width: 120px;">Bắt buộc</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($thanhPhanHoSo as $i => $tp): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.85rem;">
                            <td style="padding: 0.65rem 0.75rem; text-align: center; color: #64748b;"><?= $i + 1 ?></td>
                            <td style="padding: 0.65rem 0.75rem; font-weight: 600; color: #1e293b;">
                                <?= e($tp['ten_giay_to']) ?>
                            </td>
                            <td style="padding: 0.65rem 0.75rem; text-align: center;"><?= e($tp['so_ban_chinh'] ?? 1) ?></td>
                            <td style="padding: 0.65rem 0.75rem; text-align: center;"><?= e($tp['so_ban_sao'] ?? 0) ?></td>
                            <td style="padding: 0.65rem 0.75rem; text-align: center;">
                                <?= $tp['bat_buoc'] ? '<span class="badge badge-danger">Bắt buộc</span>' : '<span class="badge badge-secondary">Tùy chọn</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- 2. Trình tự thực hiện -->
    <div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <h2 style="font-size: 1.15rem; color: #0b2e59; margin: 0 0 1rem 0; font-weight: 700; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
            🗺️ 2. TRÌNH TỰ THỰC HIỆN & CÁC BƯỚC QUY TRÌNH
        </h2>
        <?php if (!empty($buocList)): ?>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($buocList as $b): ?>
                    <div style="display: flex; gap: 1rem; align-items: flex-start; padding: 0.75rem; background: #fafafa; border-radius: 6px; border: 1px solid #e2e8f0;">
                        <span style="background: #0b2e59; color: #fff; font-weight: 700; font-size: 0.8rem; border-radius: 50%; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <?= e($b['stt_buoc']) ?>
                        </span>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #1e293b; font-size: 0.9rem;"><?= e($b['ten_buoc']) ?></div>
                            <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                                Đơn vị: <strong><?= e($b['ten_don_vi'] ?? 'Cơ quan tiếp nhận') ?></strong> · Vai trò: <strong><?= e($b['vai_tro_xu_ly']) ?></strong> · Thời hạn: <strong><?= e($b['thoi_han_gio']) ?> giờ</strong>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="font-size: 0.85rem; color: #475569; margin: 0; line-height: 1.5;">
                <strong>Bước 1:</strong> Nộp hồ sơ trực tuyến qua Cổng DVC Hải Phòng hoặc trực tiếp tại Bộ phận Một cửa.<br>
                <strong>Bước 2:</strong> Cán bộ Một cửa tiếp nhận, kiểm tra tính hợp lệ và cấp Giấy tiếp nhận hẹn trả kết quả.<br>
                <strong>Bước 3:</strong> Phòng chuyên môn thụ lý, lấy ý kiến phối hợp liên ngành (nếu có) và dự thảo kết quả.<br>
                <strong>Bước 4:</strong> Lãnh đạo cơ quan xem xét phê duyệt bằng chữ ký số SmartCA/USB Token.<br>
                <strong>Bước 5:</strong> Văn thư đóng dấu số và trả kết quả cho người dân qua dịch vụ Bưu chính công ích VNPost hoặc Kho dữ liệu điện tử.
            </p>
        <?php endif; ?>
    </div>

    <!-- 3. Căn cứ pháp lý -->
    <?php if (!empty($tthc['can_cu_phap_ly'])): ?>
        <div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
            <h2 style="font-size: 1.15rem; color: #0b2e59; margin: 0 0 0.75rem 0; font-weight: 700; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
                ⚖️ 3. CĂN CỨ PHÁP LÝ
            </h2>
            <div style="font-size: 0.85rem; color: #334155; line-height: 1.6;">
                <?= nl2br(e($tthc['can_cu_phap_ly'])) ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="text-center mt-4">
    <a href="<?= e(url('/dvc/nop-ho-so/' . $tthc['id'])) ?>" class="btn btn-primary" style="padding: 0.75rem 2.5rem; font-size: 1.05rem; font-weight: 700; border-radius: 8px;">
        Nộp hồ sơ trực tuyến ngay
    </a>
</div>
<?php $this->endSection(); ?>
