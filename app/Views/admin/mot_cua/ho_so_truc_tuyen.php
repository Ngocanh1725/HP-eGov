<?php $this->layout('layouts/admin', ['title' => $pageTitle]); ?>

<?php $this->startSection('content'); ?>
<div class="mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
            <a href="<?= e(url('/admin/mot-cua')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.85rem;">⬅ Bàn làm việc Một cửa</a>
            <span class="text-muted">/</span>
            <span style="font-size: 0.85rem; font-weight: 600; color: #1e293b;">Hồ sơ trực tuyến</span>
        </div>
        <h1 style="font-size: 1.5rem; color: #0b2e59; margin: 0; font-weight: 800;">
            🌐 HỒ SƠ TRỰC TUYẾN CHỜ TIẾP NHẬN
        </h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.875rem;">
            Danh sách hồ sơ công dân nộp qua Cổng Dịch vụ công TP. Hải Phòng đang chờ Bộ phận Một cửa thẩm tra
        </p>
    </div>
</div>

<!-- THANH TÌM KIẾM -->
<div class="card p-3 mb-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
    <form method="GET" action="<?= e(url('/admin/mot-cua/ho-so-truc-tuyen')) ?>" style="display: flex; gap: 0.75rem;">
        <input type="text" name="q" class="form-control" value="<?= e($q ?? '') ?>" placeholder="Tìm theo mã hồ sơ, họ tên người nộp, số CCCD..." style="font-size: 0.9rem;">
        <button type="submit" class="btn btn-primary" style="white-space: nowrap; font-weight: 600;">
            🔍 Tìm kiếm
        </button>
        <?php if (!empty($q)): ?>
            <a href="<?= e(url('/admin/mot-cua/ho-so-truc-tuyen')) ?>" class="btn btn-secondary" style="white-space: nowrap;">Đặt lại</a>
        <?php endif; ?>
    </form>
</div>

<!-- BẢNG DANH SÁCH -->
<div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow: hidden;">
    <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 2px solid var(--border-color); font-size: 0.8rem; text-transform: uppercase; color: #475569;">
            <tr>
                <th style="padding: 0.75rem 1rem; width: 50px; text-align: center;">STT</th>
                <th style="padding: 0.75rem 1rem;">Mã hồ sơ</th>
                <th style="padding: 0.75rem 1rem;">Thủ tục hành chính</th>
                <th style="padding: 0.75rem 1rem;">Người nộp hồ sơ</th>
                <th style="padding: 0.75rem 1rem;">Thời gian gửi</th>
                <th style="padding: 0.75rem 1rem; text-align: center;">Trạng thái</th>
                <th style="padding: 0.75rem 1rem; text-align: center; width: 120px;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($hoSoList)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem; color: #64748b;">
                        🎉 Không có hồ sơ trực tuyến nào đang chờ xử lý!
                    </td>
                </tr>
            <?php else: ?>
                <?php $stt = 1; foreach ($hoSoList as $hs): ?>
                    <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.875rem;">
                        <td style="padding: 0.75rem 1rem; text-align: center; color: #64748b;"><?= $stt++ ?></td>
                        <td style="padding: 0.75rem 1rem;">
                            <a href="<?= e(url('/admin/mot-cua/ho-so-truc-tuyen/' . $hs['id'])) ?>" style="font-family: monospace; font-weight: 700; color: #1d4ed8; text-decoration: none;">
                                <?= e($hs['ma_ho_so']) ?>
                            </a>
                        </td>
                        <td style="padding: 0.75rem 1rem;">
                            <div style="font-weight: 600; color: #1e293b;"><?= e($hs['ten_tthc']) ?></div>
                            <small class="text-muted">Mã rút gọn: <?= e($hs['ma_rut_gon']) ?></small>
                        </td>
                        <td style="padding: 0.75rem 1rem;">
                            <div style="font-weight: 600;"><?= e($hs['nguoi_nop_ho_ten']) ?></div>
                            <div style="font-size: 0.75rem; color: #64748b;">CCCD: <?= e($hs['nguoi_nop_cccd']) ?> · <?= e($hs['nguoi_nop_sdt']) ?></div>
                        </td>
                        <td style="padding: 0.75rem 1rem; color: #64748b; font-size: 0.8rem;">
                            <?= date('d/m/Y H:i', strtotime($hs['ngay_nop'])) ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <span class="badge badge-info" style="font-size: 0.75rem;">Chờ tiếp nhận</span>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <a href="<?= e(url('/admin/mot-cua/ho-so-truc-tuyen/' . $hs['id'])) ?>" class="btn btn-sm btn-primary" style="font-weight: 600;">
                                Thẩm tra ➔
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $this->endSection(); ?>
