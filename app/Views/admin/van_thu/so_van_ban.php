<?php $this->layout('layouts/admin', ['title' => $title ?? 'Quản lý sổ văn bản điện tử']) ?>

<?php $this->section('content') ?>
<div class="page-header" style="margin-bottom: 24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                <a href="<?= e(url('/admin/van-thu')) ?>" style="color:#64748B; text-decoration:none;">← Văn thư số</a>
            </div>
            <h1 style="font-size: 22px; font-weight: 700; color: #0B2E59; margin: 0;">
                📖 Quản lý Sổ văn bản điện tử (FR05.01)
            </h1>
            <p style="color: #64748B; margin: 0; font-size: 14px;">
                Cấu hình sổ văn bản đi, sổ văn bản đến, quy tắc ký hiệu và kiểm soát số thứ tự phát hành
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('modal-tao-so').style.display='flex';" id="btn-tao-so-moi">
                + Tạo sổ văn bản mới
            </button>
        </div>
    </div>
</div>

<div class="card" style="padding:0; overflow:hidden;">
    <div style="padding:14px 20px; background:#F8FAFC; border-bottom:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center;">
        <span style="font-weight:600; color:#1E293B;">
            Danh sách sổ văn bản năm <strong><?= e($nam) ?></strong>
        </span>
        <span style="font-size:12px; color:#64748B;">Tổng số: <?= count($soVanBanList) ?> sổ</span>
    </div>

    <div class="table-responsive">
        <table class="table" style="margin-bottom:0;">
            <thead>
                <tr>
                    <th style="width: 50px; text-align:center;">STT</th>
                    <th>Tên sổ văn bản</th>
                    <th style="width: 100px; text-align:center;">Loại sổ</th>
                    <th style="width: 120px; text-align:center;">Số hiện tại</th>
                    <th style="width: 160px;">Ký hiệu mẫu</th>
                    <th style="width: 140px; text-align:center;">Tổng văn bản</th>
                    <th style="width: 140px; text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($soVanBanList)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px 20px; color:#64748B;">
                            Chưa có sổ văn bản nào được khởi tạo cho năm <?= e($nam) ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($soVanBanList as $idx => $s): ?>
                        <tr>
                            <td style="text-align:center; color:#64748B;"><?= $idx + 1 ?></td>
                            <td style="font-weight:600; color:#0B2E59;">
                                <?= e($s['ten_so']) ?>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge" style="background:<?= $s['loai_so'] === 'DI' ? '#EFF6FF' : '#FDF4FF' ?>; color:<?= $s['loai_so'] === 'DI' ? '#1D4ED8' : '#7E22CE' ?>;">
                                    SỔ <?= e($s['loai_so']) ?>
                                </span>
                            </td>
                            <td style="text-align:center; font-weight:700; color:#C8102E; font-size:15px;">
                                <?= e($s['so_hien_tai']) ?>
                            </td>
                            <td>
                                <code style="color:#0284C7; font-size:13px;"><?= e($s['ky_hieu_mau']) ?></code>
                            </td>
                            <td style="text-align:center; font-weight:600;">
                                <?= e($s['tong_van_ban'] ?? 0) ?> văn bản
                            </td>
                            <td style="text-align:right;">
                                <a href="<?= e(url('/admin/van-thu/cong-van?so_id=' . $s['id'])) ?>" class="btn btn-sm btn-secondary">
                                    Xem chi tiết
                                </a>
                                <a href="<?= e(url('/admin/van-thu/cong-van/xuat-csv/' . $s['id'])) ?>" class="btn btn-sm btn-primary" title="Xuất CSV UTF-8 BOM">
                                    📥 CSV
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tạo sổ văn bản mới -->
<div id="modal-tao-so" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; border-radius:12px; width:100%; max-width:480px; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid #E2E8F0; padding-bottom:10px;">
            <h3 style="margin:0; font-size:18px; color:#0B2E59;">+ Tạo Sổ văn bản mới</h3>
            <button type="button" onclick="document.getElementById('modal-tao-so').style.display='none';" style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>

        <form method="POST" action="<?= e(url('/admin/van-thu/so-van-ban/tao')) ?>">
            <?= csrf_field() ?>
            <div style="margin-bottom:14px;">
                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:4px;">Tên sổ văn bản: <span style="color:#C8102E;">*</span></label>
                <input type="text" name="ten_so" class="form-control" required placeholder="Ví dụ: Sổ Quyết định hành chính năm 2026" style="width:100%;">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                <div>
                    <label style="font-weight:600; font-size:13px; display:block; margin-bottom:4px;">Loại sổ:</label>
                    <select name="loai_so" class="form-control" style="width:100%;">
                        <option value="DI">Sổ văn bản ĐI</option>
                        <option value="DEN">Sổ văn bản ĐẾN</option>
                    </select>
                </div>
                <div>
                    <label style="font-weight:600; font-size:13px; display:block; margin-bottom:4px;">Năm phát hành:</label>
                    <input type="number" name="nam" class="form-control" value="<?= date('Y') ?>" required style="width:100%;">
                </div>
            </div>

            <div style="margin-bottom:14px;">
                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:4px;">Ký hiệu mẫu: <span style="color:#C8102E;">*</span></label>
                <input type="text" name="ky_hieu_mau" class="form-control" value="/QĐ-UBND" required placeholder="Ví dụ: /QĐ-UBND hoặc /GPXD-SXD" style="width:100%;">
                <div style="font-size:11px; color:#64748B; margin-top:4px;">Số ký hiệu sẽ tự động sinh: {Số thứ tự}{Ký hiệu mẫu}</div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:4px;">Số bắt đầu:</label>
                <input type="number" name="so_hien_tai" class="form-control" value="0" style="width:100%;">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-tao-so').style.display='none';">Hủy</button>
                <button type="submit" class="btn btn-primary" id="btn-submit-tao-so">Tạo sổ</button>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
