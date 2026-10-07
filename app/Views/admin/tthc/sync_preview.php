<?php $this->layout('layouts/admin', ['title' => 'Đối chiếu Đồng bộ CSDL TTHC Quốc gia']); ?>

<?php $this->startSection('content'); ?>
<div class="content-header mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">ĐỒNG BỘ CSDL TTHC QUỐC GIA (CSDL DVC QUỐC GIA)</h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            So sánh khác biệt từ Adapter CSDL Quốc gia (Mock) · Thời điểm đồng bộ: <strong><?= e($syncData['sync_time'] ?? date('d/m/Y H:i')) ?></strong>
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="<?= e(url('/admin/danh-muc/tthc')) ?>" class="btn btn-outline-secondary">
            ⬅ Quay lại danh mục
        </a>
        <form action="<?= e(url('/admin/danh-muc/tthc/dong-bo/ap-dung')) ?>" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn áp dụng các cập nhật này vào CSDL TP. Hải Phòng không?');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-success" style="display: flex; align-items: center; gap: 0.4rem; font-weight: 600;">
                ✅ Áp dụng tất cả thay đổi vào CSDL
            </button>
        </form>
    </div>
</div>

<!-- Tổng quan thống kê so sánh -->
<div class="row mb-4" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
    <div class="card p-3" style="background: #f0fdf4; border-left: 4px solid #16a34a; border-radius: var(--radius-md);">
        <div style="font-size: 0.85rem; color: #166534; font-weight: 600;">THỦ TỤC CÔNG BỐ MỚI</div>
        <div style="font-size: 1.75rem; font-weight: 700; color: #15803d; margin-top: 0.25rem;">
            <?= count($syncData['added'] ?? []) ?>
        </div>
        <small class="text-muted">Cần thêm mới vào danh mục TP</small>
    </div>
    <div class="card p-3" style="background: #eff6ff; border-left: 4px solid #2563eb; border-radius: var(--radius-md);">
        <div style="font-size: 0.85rem; color: #1e40af; font-weight: 600;">THỦ TỤC CẬP NHẬT / SỬA ĐỔI</div>
        <div style="font-size: 1.75rem; font-weight: 700; color: #1d4ed8; margin-top: 0.25rem;">
            <?= count($syncData['updated'] ?? []) ?>
        </div>
        <small class="text-muted">Thay đổi thời hạn, mức độ DVC hoặc phí</small>
    </div>
    <div class="card p-3" style="background: #fef2f2; border-left: 4px solid #dc2626; border-radius: var(--radius-md);">
        <div style="font-size: 0.85rem; color: #991b1b; font-weight: 600;">THỦ TỤC HẾT HIỆU LỰC / BÃI BỎ</div>
        <div style="font-size: 1.75rem; font-weight: 700; color: #b91c1c; margin-top: 0.25rem;">
            <?= count($syncData['deprecated'] ?? []) ?>
        </div>
        <small class="text-muted">Ngừng tiếp nhận hồ sơ mới</small>
    </div>
</div>

<!-- 1. Danh sách thủ tục mới -->
<div class="card mb-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow: hidden;">
    <div class="card-header p-3" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
        <h3 style="font-size: 1rem; margin: 0; color: #166534; display: flex; align-items: center; gap: 0.5rem;">
            <span>🟢</span> 1. Danh sách Thủ tục Công bố mới từ CSDL Quốc gia
        </h3>
        <span class="badge badge-success"><?= count($syncData['added'] ?? []) ?> thủ tục</span>
    </div>
    <div style="overflow-x: auto;">
        <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
            <thead style="background: #fafafa; font-size: 0.8rem; border-bottom: 1px solid var(--border-color);">
                <tr>
                    <th style="padding: 0.75rem 1rem; text-align: left;">Mã TTHC / Rút gọn</th>
                    <th style="padding: 0.75rem 1rem; text-align: left;">Tên Thủ tục</th>
                    <th style="padding: 0.75rem 1rem; text-align: center;">Cấp</th>
                    <th style="padding: 0.75rem 1rem; text-align: center;">Mức độ</th>
                    <th style="padding: 0.75rem 1rem; text-align: right;">Thời hạn</th>
                    <th style="padding: 0.75rem 1rem; text-align: right;">Phí / Lệ phí</th>
                    <th style="padding: 0.75rem 1rem; text-align: left;">Căn cứ pháp lý</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($syncData['added'])): ?>
                    <tr><td colspan="7" style="padding: 1rem; text-align: center; color: #64748b;">Không có thủ tục mới nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($syncData['added'] as $item): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.85rem;">
                            <td style="padding: 0.75rem 1rem; font-weight: 600; color: var(--color-primary);">
                                <?= e($item['ma_rut_gon']) ?><br>
                                <small class="text-muted"><?= e($item['ma_tthc']) ?></small>
                            </td>
                            <td style="padding: 0.75rem 1rem; font-weight: 600;"><?= e($item['ten_thu_tuc']) ?></td>
                            <td style="padding: 0.75rem 1rem; text-align: center;"><span class="badge" style="background: #e2e8f0;"><?= e($item['cap_thuc_hien']) ?></span></td>
                            <td style="padding: 0.75rem 1rem; text-align: center;"><span class="badge badge-success"><?= e($item['muc_do']) ?></span></td>
                            <td style="padding: 0.75rem 1rem; text-align: right;"><?= e($item['thoi_han_ngay']) ?> ngày</td>
                            <td style="padding: 0.75rem 1rem; text-align: right;"><?= number_format((float)$item['phi_le_phi'], 0, ',', '.') ?> đ</td>
                            <td style="padding: 0.75rem 1rem; font-size: 0.75rem; color: #475569;"><?= e($item['can_cu_phap_ly']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 2. Danh sách thủ tục sửa đổi (So sánh Khác biệt DIFF) -->
<div class="card mb-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow: hidden;">
    <div class="card-header p-3" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
        <h3 style="font-size: 1rem; margin: 0; color: #1e40af; display: flex; align-items: center; gap: 0.5rem;">
            <span>🔄</span> 2. Danh sách Thủ tục Sửa đổi & So sánh Khác biệt (Diff)
        </h3>
        <span class="badge badge-info"><?= count($syncData['updated'] ?? []) ?> thủ tục</span>
    </div>
    <div style="overflow-x: auto;">
        <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
            <thead style="background: #fafafa; font-size: 0.8rem; border-bottom: 1px solid var(--border-color);">
                <tr>
                    <th style="padding: 0.75rem 1rem; text-align: left;">Mã TTHC / Tên</th>
                    <th style="padding: 0.75rem 1rem; text-align: center;">Mức độ DVC (Cũ ➔ Mới)</th>
                    <th style="padding: 0.75rem 1rem; text-align: center;">Thời hạn xử lý (Cũ ➔ Mới)</th>
                    <th style="padding: 0.75rem 1rem; text-align: center;">Phí / Lệ phí (Cũ ➔ Mới)</th>
                    <th style="padding: 0.75rem 1rem; text-align: left;">Lý do điều chỉnh</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($syncData['updated'])): ?>
                    <tr><td colspan="5" style="padding: 1rem; text-align: center; color: #64748b;">Không có thủ tục nào thay đổi thông số.</td></tr>
                <?php else: ?>
                    <?php foreach ($syncData['updated'] as $item): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.85rem;">
                            <td style="padding: 0.75rem 1rem;">
                                <div style="font-weight: 700; color: var(--color-primary);"><?= e($item['ma_tthc']) ?></div>
                                <div style="font-weight: 500;"><?= e($item['ten_thu_tuc']) ?></div>
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                <span style="text-decoration: line-through; color: #94a3b8; font-size: 0.8rem;"><?= e($item['muc_do_cu']) ?></span>
                                <span style="margin: 0 0.25rem;">➔</span>
                                <span class="badge badge-success"><?= e($item['muc_do_moi']) ?></span>
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                <span style="text-decoration: line-through; color: #94a3b8;"><?= e($item['thoi_han_cu']) ?> ngày</span>
                                <span style="margin: 0 0.25rem;">➔</span>
                                <strong style="color: #16a34a;"><?= e($item['thoi_han_moi']) ?> ngày</strong>
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                <span style="text-decoration: line-through; color: #94a3b8;"><?= number_format((float)$item['phi_cu'], 0, ',', '.') ?> đ</span>
                                <span style="margin: 0 0.25rem;">➔</span>
                                <strong style="color: #2563eb;"><?= number_format((float)$item['phi_moi'], 0, ',', '.') ?> đ</strong>
                            </td>
                            <td style="padding: 0.75rem 1rem; font-size: 0.8rem; color: #475569;">
                                📜 <?= e($item['thay_doi'] ?? $item['ly_do_thay_doi'] ?? 'Cập nhật theo công bố Quốc gia') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 3. Danh sách thủ tục bãi bỏ -->
<div class="card mb-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow: hidden;">
    <div class="card-header p-3" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
        <h3 style="font-size: 1rem; margin: 0; color: #991b1b; display: flex; align-items: center; gap: 0.5rem;">
            <span>🔴</span> 3. Danh sách Thủ tục Bãi bỏ / Hết hiệu lực
        </h3>
        <span class="badge badge-danger"><?= count($syncData['deprecated'] ?? []) ?> thủ tục</span>
    </div>
    <div style="overflow-x: auto;">
        <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
            <thead style="background: #fafafa; font-size: 0.8rem; border-bottom: 1px solid var(--border-color);">
                <tr>
                    <th style="padding: 0.75rem 1rem; text-align: left;">Mã TTHC</th>
                    <th style="padding: 0.75rem 1rem; text-align: left;">Tên Thủ tục</th>
                    <th style="padding: 0.75rem 1rem; text-align: center;">Ngày hết hiệu lực</th>
                    <th style="padding: 0.75rem 1rem; text-align: left;">Căn cứ bãi bỏ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($syncData['deprecated'])): ?>
                    <tr><td colspan="4" style="padding: 1rem; text-align: center; color: #64748b;">Không có thủ tục nào bị bãi bỏ.</td></tr>
                <?php else: ?>
                    <?php foreach ($syncData['deprecated'] as $item): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.85rem;">
                            <td style="padding: 0.75rem 1rem; font-weight: 700; color: #dc2626;"><?= e($item['ma_tthc']) ?></td>
                            <td style="padding: 0.75rem 1rem; font-weight: 500;"><?= e($item['ten_thu_tuc']) ?></td>
                            <td style="padding: 0.75rem 1rem; text-align: center; color: #b91c1c; font-weight: 600;"><?= e($item['ngay_het_hieu_luc']) ?></td>
                            <td style="padding: 0.75rem 1rem; font-size: 0.8rem; color: #64748b;"><?= e($item['van_ban_bai_bo']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
