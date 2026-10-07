<?php $this->layout('layouts/admin', ['title' => $title ?? 'Theo dõi công văn đến và đi']) ?>

<?php $this->section('content') ?>
<div class="page-header" style="margin-bottom: 24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                <a href="<?= e(url('/admin/van-thu')) ?>" style="color:#64748B; text-decoration:none;">← Văn thư số</a>
            </div>
            <h1 style="font-size: 22px; font-weight: 700; color: #0B2E59; margin: 0;">
                📋 Sổ theo dõi Công văn Đến & Đi gắn với Hồ sơ
            </h1>
            <p style="color: #64748B; margin: 0; font-size: 14px;">
                Tra cứu, đối soát số ký hiệu, trích yếu và xuất báo cáo CSV UTF-8 có BOM
            </p>
        </div>
        <div>
            <?php if ($soId > 0): ?>
                <a href="<?= e(url('/admin/van-thu/cong-van/xuat-csv/' . $soId)) ?>" class="btn btn-primary" id="btn-xuat-csv">
                    📥 Xuất CSV sổ này (UTF-8 BOM)
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bộ lọc theo sổ và tìm kiếm -->
<div class="card" style="margin-bottom: 20px; padding: 14px;">
    <form method="GET" action="<?= e(url('/admin/van-thu/cong-van')) ?>" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
        <select name="so_id" class="form-control" style="max-width:280px;" onchange="this.form.submit()">
            <option value="0">-- Tất cả sổ văn bản --</option>
            <?php foreach ($soList as $s): ?>
                <option value="<?= e($s['id']) ?>" <?= ($soId === (int)$s['id']) ? 'selected' : '' ?>>
                    <?= e($s['ten_so']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="q" value="<?= e($q ?? '') ?>" class="form-control" placeholder="Tìm số ký hiệu, trích yếu, mã hồ sơ..." style="flex:1; min-width:200px;">
        <button type="submit" class="btn btn-primary">🔍 Lọc</button>
        <?php if ($soId > 0 || !empty($q)): ?>
            <a href="<?= e(url('/admin/van-thu/cong-van')) ?>" class="btn btn-secondary">Đặt lại</a>
        <?php endif; ?>
    </form>
</div>

<!-- Bảng công văn -->
<div class="card" style="padding:0; overflow:hidden;">
    <div class="table-responsive">
        <table class="table" style="margin-bottom:0;">
            <thead>
                <tr>
                    <th style="width: 60px; text-align:center;">STT</th>
                    <th style="width: 160px;">Số ký hiệu</th>
                    <th style="width: 120px;">Ngày ban hành</th>
                    <th>Trích yếu nội dung</th>
                    <th style="width: 150px;">Mã hồ sơ</th>
                    <th style="width: 130px;">Người ký</th>
                    <th style="width: 130px;">Ngày vào sổ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($congVanList)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px 20px; color:#64748B;">
                            Không tìm thấy công văn nào trong sổ văn bản này.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($congVanList as $cv): ?>
                        <tr>
                            <td style="text-align:center; font-weight:700; color:#64748B;"><?= e($cv['so_thu_tu']) ?></td>
                            <td style="font-weight:700; color:#0B2E59;">
                                <?= e($cv['so_ky_hieu']) ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($cv['ngay_ban_hanh'])) ?></td>
                            <td>
                                <div style="font-weight:500; color:#1E293B;"><?= e($cv['trich_yeu']) ?></div>
                                <div style="font-size:11px; color:#64748B; margin-top:2px;">Sổ: <?= e($cv['ten_so']) ?></div>
                            </td>
                            <td>
                                <?php if (!empty($cv['ma_ho_so'])): ?>
                                    <code style="color:#0284C7;"><?= e($cv['ma_ho_so']) ?></code>
                                <?php else: ?>
                                    <span style="color:#94A3B8;">---</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($cv['nguoi_ky'] ?? '---') ?></td>
                            <td style="font-size:12px; color:#64748B;"><?= date('d/m/Y H:i', strtotime($cv['ngay_vao_so'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection() ?>
