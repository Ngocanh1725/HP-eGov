<?php $this->layout('layouts/admin', ['title' => $title ?? 'Văn thư số - Đóng dấu phát hành']) ?>

<?php $this->section('content') ?>
<div class="page-header" style="margin-bottom: 24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; color: #0B2E59; margin: 0 0 6px 0;">
                <span style="color:#C8102E;">🔏</span> Quản trị Văn thư số & Phát hành (PH05)
            </h1>
            <p style="color: #64748B; margin: 0; font-size: 14px;">
                Cấp số công văn tự động, đóng dấu số cơ quan, đẩy kết quả vào Kho số hóa công dân
            </p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="<?= e(url('/admin/van-thu/so-van-ban')) ?>" class="btn btn-secondary">
                📖 Quản lý sổ văn bản
            </a>
            <a href="<?= e(url('/admin/van-thu/cong-van')) ?>" class="btn btn-secondary">
                📋 Sổ công văn đến/đi
            </a>
        </div>
    </div>
</div>

<!-- Bộ lọc tìm kiếm -->
<div class="card" style="margin-bottom: 20px; padding: 14px;">
    <form method="GET" action="<?= e(url('/admin/van-thu')) ?>" style="display:flex; gap:12px; flex-wrap:wrap;">
        <input type="text" name="q" value="<?= e($q ?? '') ?>" class="form-control" placeholder="Tìm theo mã hồ sơ, người nộp, thủ tục..." style="flex:1; min-width:240px;">
        <button type="submit" class="btn btn-primary">🔍 Tìm kiếm</button>
        <?php if (!empty($q)): ?>
            <a href="<?= e(url('/admin/van-thu')) ?>" class="btn btn-secondary">Đặt lại</a>
        <?php endif; ?>
    </form>
</div>

<!-- Danh sách hồ sơ chờ đóng dấu -->
<div class="card" style="padding:0; overflow:hidden;">
    <div style="padding:14px 20px; background:#F8FAFC; border-bottom:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center;">
        <span style="font-weight:600; color:#1E293B;">
            Tổng số: <strong style="color:#C8102E;"><?= count($hoSoList) ?></strong> hồ sơ đã được Lãnh đạo ký duyệt, chờ đóng dấu số
        </span>
        <span style="font-size:12px; color:#64748B;">Đóng dấu số sẽ tự động hoàn tất và chuyển kết quả vào Kho công dân</span>
    </div>

    <div class="table-responsive">
        <table class="table" style="margin-bottom:0;">
            <thead>
                <tr>
                    <th style="width: 50px; text-align:center;">STT</th>
                    <th style="width: 170px;">Mã hồ sơ</th>
                    <th>Thủ tục hành chính</th>
                    <th style="width: 160px;">Người nộp</th>
                    <th style="width: 140px;">Lãnh đạo ký</th>
                    <th style="width: 130px;">Hạn cam kết</th>
                    <th style="width: 130px; text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($hoSoList)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px 20px; color:#64748B;">
                            <div style="font-size:36px; margin-bottom:10px;">🎉</div>
                            Hiện không có hồ sơ nào đang chờ Văn thư đóng dấu phát hành.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($hoSoList as $idx => $hs): ?>
                        <tr>
                            <td style="text-align:center; color:#64748B;"><?= $idx + 1 ?></td>
                            <td>
                                <a href="<?= e(url('/admin/van-thu/dong-dau/' . $hs['id'])) ?>" style="font-weight:700; color:#0B2E59; text-decoration:none;">
                                    <?= e($hs['ma_ho_so']) ?>
                                </a>
                                <div style="font-size:11px; color:#64748B; margin-top:2px;">
                                    <?= e($hs['ten_don_vi'] ?? '') ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#1E293B;">
                                    <?= e($hs['ten_tthc']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:500;"><?= e($hs['ho_ten_nguoi_nop']) ?></div>
                                <div style="font-size:11px; color:#64748B;"><?= e($hs['so_dien_thoai_nguoi_nop'] ?? '') ?></div>
                            </td>
                            <td>
                                <div style="color:#059669; font-weight:600; font-size:12px;">
                                    ✓ Đã ký phát hành
                                </div>
                                <div style="font-size:11px; color:#64748B;">
                                    <?= !empty($hs['thoi_gian_ld_ky']) ? date('d/m/Y H:i', strtotime($hs['thoi_gian_ld_ky'])) : '---' ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size:13px; font-weight:600; color:#0B2E59;">
                                    <?= date('d/m/Y', strtotime($hs['ngay_hen_tra'])) ?>
                                </div>
                            </td>
                            <td style="text-align:right;">
                                <a href="<?= e(url('/admin/van-thu/dong-dau/' . $hs['id'])) ?>" class="btn btn-sm btn-primary" id="btn-dong-dau-<?= e($hs['id']) ?>">
                                    🔏 Đóng dấu số →
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection() ?>
