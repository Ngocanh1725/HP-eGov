<?php $this->layout('layouts/portal', ['title' => 'Hồ sơ của tôi']); ?>

<?php $this->startSection('content'); ?>
<div class="mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.55rem; color: #0b2e59; margin: 0;">QUẢN LÝ HỒ SƠ DỊCH VỤ CÔNG CỦA TÔI</h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Theo dõi tiến độ, bổ sung giấy tờ và nhận kết quả giải quyết TTHC điện tử
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="<?= e(url('/cong-dan/kho-du-lieu')) ?>" class="btn btn-outline-info" style="font-size: 0.85rem;">
            📁 Kho dữ liệu số hóa
        </a>
        <a href="<?= e(url('/dvc/thu-tuc')) ?>" class="btn btn-primary" style="font-size: 0.85rem; font-weight: 600;">
            ➕ Nộp hồ sơ mới
        </a>
    </div>
</div>

<!-- TAB TRẠNG THÁI & TÌM KIẾM -->
<div class="card p-3 mb-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?= e(url('/cong-dan/ho-so')) ?>" class="btn btn-sm <?= empty($currentStatus) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                Tất cả (<?= array_sum($counts) ?>)
            </a>
            <a href="<?= e(url('/cong-dan/ho-so?status=CHO_TIEP_NHAN')) ?>" class="btn btn-sm <?= ($currentStatus === 'CHO_TIEP_NHAN') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                Chờ tiếp nhận (<?= $counts['CHO_TIEP_NHAN'] ?? 0 ?>)
            </a>
            <a href="<?= e(url('/cong-dan/ho-so?status=DANG_XU_LY')) ?>" class="btn btn-sm <?= ($currentStatus === 'DANG_XU_LY') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                Đang xử lý (<?= $counts['DANG_XU_LY'] ?? 0 ?>)
            </a>
            <a href="<?= e(url('/cong-dan/ho-so?status=CHO_BO_SUNG')) ?>" class="btn btn-sm <?= ($currentStatus === 'CHO_BO_SUNG') ? 'btn-warning' : 'btn-outline-warning' ?>">
                ⚠️ Cần bổ sung (<?= $counts['CHO_BO_SUNG'] ?? 0 ?>)
            </a>
            <a href="<?= e(url('/cong-dan/ho-so?status=HOAN_THANH')) ?>" class="btn btn-sm <?= ($currentStatus === 'HOAN_THANH') ? 'btn-success' : 'btn-outline-success' ?>">
                Hoàn thành (<?= $counts['HOAN_THANH'] ?? 0 ?>)
            </a>
        </div>

        <form action="<?= e(url('/cong-dan/ho-so')) ?>" method="GET" style="display: flex; gap: 0.5rem;">
            <input type="text" name="q" placeholder="Mã hồ sơ, tên thủ tục..." value="<?= e($q) ?>" class="form-control" style="font-size: 0.85rem; width: 220px;">
            <button type="submit" class="btn btn-sm btn-primary">Tìm</button>
        </form>
    </div>

    <!-- BẢNG HỒ SƠ -->
    <div style="overflow-x: auto;">
        <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc; font-size: 0.8rem; border-bottom: 2px solid var(--border-color);">
                <tr>
                    <th style="padding: 0.75rem 1rem; text-align: left; width: 200px;">Mã hồ sơ</th>
                    <th style="padding: 0.75rem 1rem; text-align: left;">Thủ tục hành chính / Cơ quan</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; width: 120px;">Ngày nộp</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; width: 130px;">Hạn trả kết quả</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; width: 130px;">Trạng thái</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; width: 150px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($hoSoList)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem; color: #64748b;">
                            Bạn chưa có hồ sơ nào trong mục này.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($hoSoList as $hs): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.85rem;">
                            <td style="padding: 0.75rem 1rem;">
                                <a href="<?= e(url('/cong-dan/ho-so/' . $hs['ma_ho_so'])) ?>" style="font-family: monospace; font-weight: 700; color: #0b2e59; text-decoration: none;">
                                    <?= e($hs['ma_ho_so']) ?>
                                </a>
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <div style="font-weight: 600; color: #1e293b;"><?= e($hs['ten_tthc']) ?></div>
                                <small class="text-muted" style="font-size: 0.75rem;">🏛️ <?= e($hs['ten_don_vi']) ?></small>
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center; color: #64748b;">
                                <?= date('d/m/Y', strtotime($hs['ngay_nop'])) ?>
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center; font-weight: 600;">
                                <?= !empty($hs['ngay_hen_tra']) ? date('d/m/Y', strtotime($hs['ngay_hen_tra'])) : '—' ?>
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                <?php 
                                $badgeClass = match($hs['trang_thai']) {
                                    'CHO_TIEP_NHAN' => 'badge-info',
                                    'MOI_TIEP_NHAN' => 'badge-info',
                                    'DANG_XU_LY' => 'badge-warning',
                                    'CHO_BO_SUNG' => 'badge-danger',
                                    'HOAN_THANH', 'DA_TRA_KET_QUA' => 'badge-success',
                                    default => 'badge-secondary'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>" style="font-size: 0.75rem;">
                                    <?= e($hs['trang_thai']) ?>
                                </span>
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                <div style="display: flex; gap: 0.35rem; justify-content: center;">
                                    <a href="<?= e(url('/cong-dan/ho-so/' . $hs['ma_ho_so'])) ?>" class="btn btn-sm btn-outline-primary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        Xem tiến độ
                                    </a>
                                    <?php if ($hs['trang_thai'] === 'CHO_BO_SUNG'): ?>
                                        <a href="<?= e(url('/cong-dan/ho-so/' . $hs['ma_ho_so'] . '/bo-sung')) ?>" class="btn btn-sm btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 700;">
                                            Bổ sung
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
