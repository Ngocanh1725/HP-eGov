<?php $this->layout('layouts/admin', ['title' => $title ?? 'Lãnh đạo phòng - Thẩm tra & Ký số duyệt bước 1']) ?>

<?php $this->section('content') ?>
<div class="page-header" style="margin-bottom: 24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; color: #0B2E59; margin: 0 0 6px 0;">
                <span style="color:#C8102E;">📋</span> Thẩm tra & Ký số Duyệt Bước 1 (Lãnh đạo Phòng)
            </h1>
            <p style="color: #64748B; margin: 0; font-size: 14px;">
                Danh sách hồ sơ do Chuyên viên thẩm định chuyển trình phê duyệt tờ trình và dự thảo kết quả
            </p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="<?= e(url('/admin/thu-ly')) ?>" class="btn btn-secondary">
                ← Quay lại Hộp việc
            </a>
        </div>
    </div>
</div>

<!-- Thanh tìm kiếm & lọc -->
<div class="card" style="margin-bottom: 20px; padding: 16px;">
    <form method="GET" action="<?= e(url('/admin/phe-duyet/phong')) ?>" style="display:flex; gap:12px; flex-wrap:wrap;">
        <input type="text" name="q" value="<?= e($q ?? '') ?>" class="form-control" placeholder="Tìm theo mã hồ sơ, tên người nộp, thủ tục..." style="flex:1; min-width:240px;">
        <button type="submit" class="btn btn-primary" id="btn-tim-kiem">
            🔍 Tìm kiếm
        </button>
        <?php if (!empty($q)): ?>
            <a href="<?= e(url('/admin/phe-duyet/phong')) ?>" class="btn btn-secondary">Đặt lại</a>
        <?php endif; ?>
    </form>
</div>

<!-- Danh sách hồ sơ -->
<div class="card" style="padding:0; overflow:hidden;">
    <div style="padding:16px 20px; background:#F8FAFC; border-bottom:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center;">
        <span style="font-weight:600; color:#1E293B;">
            Tổng số: <strong style="color:#C8102E;"><?= count($hoSoList) ?></strong> hồ sơ chờ thẩm tra duyệt bước 1
        </span>
        <span style="font-size:12px; color:#64748B;">Sắp xếp theo hạn cam kết SLA gần nhất</span>
    </div>

    <div class="table-responsive">
        <table class="table" style="margin-bottom:0;">
            <thead>
                <tr>
                    <th style="width: 50px; text-align:center;">STT</th>
                    <th style="width: 170px;">Mã hồ sơ</th>
                    <th>Thủ tục hành chính</th>
                    <th style="width: 160px;">Người nộp</th>
                    <th style="width: 150px;">Chuyên viên</th>
                    <th style="width: 130px;">Hạn cam kết</th>
                    <th style="width: 100px; text-align:center;">Trạng thái ký</th>
                    <th style="width: 120px; text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($hoSoList)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:40px 20px; color:#64748B;">
                            <div style="font-size:36px; margin-bottom:10px;">🎉</div>
                            Hiện không có hồ sơ nào đang chờ Lãnh đạo phòng thẩm tra duyệt bước 1.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($hoSoList as $idx => $hs): ?>
                        <tr>
                            <td style="text-align:center; color:#64748B;"><?= $idx + 1 ?></td>
                            <td>
                                <a href="<?= e(url('/admin/phe-duyet/phong/' . $hs['id'])) ?>" style="font-weight:600; color:#0B2E59; text-decoration:none;">
                                    <?= e($hs['ma_ho_so']) ?>
                                </a>
                                <div style="font-size:11px; color:#64748B; margin-top:2px;">
                                    <?= e($hs['ten_don_vi'] ?? '') ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:500; color:#1E293B;">
                                    <?= e($hs['ten_tthc']) ?>
                                </div>
                                <div style="font-size:12px; color:#0284C7; margin-top:2px;">
                                    Bước: <?= e($hs['ten_buoc'] ?? 'Thẩm tra & Ký duyệt') ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:500;"><?= e($hs['ho_ten_nguoi_nop']) ?></div>
                                <div style="font-size:12px; color:#64748B;"><?= e($hs['so_dien_thoai_nguoi_nop'] ?? '') ?></div>
                            </td>
                            <td>
                                <div style="font-size:13px; color:#1E293B;">
                                    👤 <?= e($hs['ten_chuyen_vien'] ?? 'Chưa gán') ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size:13px; font-weight:600; color:#0B2E59;">
                                    <?= date('d/m/Y', strtotime($hs['ngay_hen_tra'])) ?>
                                </div>
                                <div style="font-size:11px; color:#64748B;">
                                    <?= date('H:i', strtotime($hs['ngay_hen_tra'])) ?>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <?php if ((int)$hs['da_ky_so'] > 0): ?>
                                    <span class="badge" style="background:#ECFDF5; color:#059669; border:1px solid #A7F3D0; font-size:11px;">
                                        ✓ Đã có chữ ký
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background:#FEF3C7; color:#D97706; border:1px solid #FDE68A; font-size:11px;">
                                        ⏳ Chờ ký duyệt
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <a href="<?= e(url('/admin/phe-duyet/phong/' . $hs['id'])) ?>" class="btn btn-sm btn-primary" id="btn-xem-hs-<?= e($hs['id']) ?>">
                                    Thẩm tra & Ký →
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
