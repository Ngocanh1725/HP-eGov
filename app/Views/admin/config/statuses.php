<?php $this->layout('layouts/admin', ['title' => 'Danh mục Trạng thái Hồ sơ TT 01/2018']); ?>

<?php $this->startSection('content'); ?>
<div class="content-header mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">DANH MỤC TRẠNG THÁI XỬ LÝ HỒ SƠ (THÔNG TƯ 01/2018/TT-VPCP)</h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Chuẩn hóa 9 trạng thái nghiệp vụ bắt buộc theo hướng dẫn thi hành Nghị định 61/2018/NĐ-CP của Văn phòng Chính phủ (Chỉ xem – Cố định quy chuẩn)
        </p>
    </div>
    <div>
        <span class="badge badge-info" style="font-size: 0.85rem; padding: 0.4rem 0.6rem;">
            🔒 Quy chuẩn Quốc gia (Read-only)
        </span>
    </div>
</div>

<div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow-x: auto;">
    <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 2px solid var(--border-color);">
            <tr>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem; width: 60px;">Thứ tự</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem; width: 180px;">Mã trạng thái</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem; width: 220px;">Tên trạng thái</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Ý nghĩa nghiệp vụ Một cửa & Liên thông</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem; width: 140px;">Huy hiệu hiển thị</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($statuses)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        Chưa nạp danh mục trạng thái.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($statuses as $st): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 0.75rem 1rem; text-align: center; font-weight: 700; color: #64748b;">
                            <?= e($st['thu_tu']) ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; font-family: monospace; font-weight: 700; color: var(--color-primary);">
                            <?= e($st['ma_trang_thai']) ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; font-weight: 600; color: #1e293b;">
                            <?= e($st['ten_trang_thai']) ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; font-size: 0.85rem; color: #475569;">
                            <?= e($st['mo_ta'] ?? 'Áp dụng xuyên suốt các bước xử lý theo Thông tư 01/2018/TT-VPCP') ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <?php 
                            $badgeClass = match($st['ma_trang_thai']) {
                                'MOI_TIEP_NHAN' => 'badge-info',
                                'DANG_XU_LY' => 'badge-warning',
                                'YEU_CAU_BO_SUNG' => 'badge-secondary',
                                'DA_PHE_DUYET', 'DA_TRA_KET_QUA' => 'badge-success',
                                'TU_CHOI' => 'badge-danger',
                                default => 'badge-secondary'
                            };
                            ?>
                            <span class="badge <?= $badgeClass ?>" style="font-size: 0.8rem; padding: 0.35rem 0.6rem;">
                                <?= e($st['ten_trang_thai']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $this->endSection(); ?>
