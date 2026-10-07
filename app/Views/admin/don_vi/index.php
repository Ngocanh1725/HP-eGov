<?php $this->layout('layouts/admin', ['title' => 'Cây Tổ chức Đơn vị']); ?>

<?php $this->startSection('content'); ?>
<div class="content-header mb-4" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">CÂY TỔ CHỨC ĐƠN VỊ & PHÒNG BAN</h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Phân cấp cơ quan, sở ban ngành, quận huyện, phòng chuyên môn và Bộ phận Một cửa
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <button type="button" class="btn btn-primary" onclick="document.getElementById('modal-add-donvi').style.display='flex'">
            ➕ Thêm cơ quan / Đơn vị
        </button>
        <a href="<?= e(url('/admin/danh-muc/don-vi/export')) ?>" class="btn btn-outline-primary">
            📥 Xuất CSV
        </a>
    </div>
</div>

<div class="card mb-4 p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
    <form action="<?= e(url('/admin/danh-muc/don-vi')) ?>" method="GET" class="row g-2" style="display: grid; grid-template-columns: 2fr 1.5fr auto; gap: 0.75rem; align-items: flex-end;">
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Tìm kiếm:</label>
            <input type="text" name="q" class="form-control" placeholder="Mã định danh, Tên đơn vị..." value="<?= e($filters['q'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Loại hình cơ quan:</label>
            <select name="loai" class="form-control">
                <option value="">-- Tất cả --</option>
                <option value="UBND_TP" <?= (($filters['loai'] ?? '') === 'UBND_TP') ? 'selected' : '' ?>>UBND Thành phố</option>
                <option value="SO_NGANH" <?= (($filters['loai'] ?? '') === 'SO_NGANH') ? 'selected' : '' ?>>Sở, Ban, Ngành</option>
                <option value="UBND_HUYEN" <?= (($filters['loai'] ?? '') === 'UBND_HUYEN') ? 'selected' : '' ?>>UBND Quận / Huyện</option>
                <option value="PHONG_BAN" <?= (($filters['loai'] ?? '') === 'PHONG_BAN') ? 'selected' : '' ?>>Phòng ban chuyên môn</option>
                <option value="BO_PHAN_MOT_CUA" <?= (($filters['loai'] ?? '') === 'BO_PHAN_MOT_CUA') ? 'selected' : '' ?>>Bộ phận Một cửa</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1rem;">Lọc dữ liệu</button>
        </div>
    </form>
</div>

<div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow-x: auto;">
    <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 2px solid var(--border-color);">
            <tr>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Mã định danh (QCVN 102)</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Tên cơ quan / Đơn vị</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Loại hình</th>
                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem;">Cơ quan cấp trên</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">Cấp bậc</th>
                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.8rem;">Trạng thái</th>
                <th style="padding: 0.75rem 1rem; text-align: right; font-size: 0.8rem;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($donViList as $dv): ?>
            <tr style="border-bottom: 1px solid var(--border-color);">
                <td style="padding: 0.75rem 1rem; font-family: monospace; font-weight: 700; color: #0B2E59;">
                    <?= e($dv['ma_dinh_danh']) ?>
                </td>
                <td style="padding: 0.75rem 1rem;">
                    <span style="display: inline-block; width: <?= (max(0, (int)$dv['cap_do'] - 1) * 16) ?>px;"></span>
                    <?= ((int)$dv['cap_do'] > 1) ? '└─ ' : '' ?>
                    <strong><?= e($dv['ten_don_vi']) ?></strong>
                </td>
                <td style="padding: 0.75rem 1rem; font-size: 0.85rem;">
                    <span class="badge badge-primary"><?= e($dv['loai_don_vi']) ?></span>
                </td>
                <td style="padding: 0.75rem 1rem; font-size: 0.85rem;">
                    <?= e($dv['ten_cha'] ?? 'UBND TP Hải Phòng') ?>
                </td>
                <td style="padding: 0.75rem 1rem; text-align: center; font-size: 0.85rem;">
                    Cấp <?= e($dv['cap_do']) ?>
                </td>
                <td style="padding: 0.75rem 1rem; text-align: center;">
                    <?php if ((int)$dv['trang_thai'] === 1): ?>
                        <span class="badge badge-success">Hoạt động</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Ngừng</span>
                    <?php endif; ?>
                </td>
                <td style="padding: 0.75rem 1rem; text-align: right;">
                    <form action="<?= e(url('/admin/danh-muc/don-vi/' . $dv['id'] . '/trang-thai')) ?>" method="POST" style="display: inline-block;">
                        <?= csrf_field() ?>
                        <?php if ((int)$dv['trang_thai'] === 1): ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">Ngừng</button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-sm btn-outline-success">Bật</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Thêm Đơn vị -->
<div id="modal-add-donvi" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #fff; width: 550px; border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: var(--shadow-lg);">
        <h3 style="margin-top: 0; color: var(--color-primary); font-size: 1.25rem;">
            ➕ Thêm Cơ quan / Đơn vị mới
        </h3>
        <form action="<?= e(url('/admin/danh-muc/don-vi')) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Cơ quan cấp trên:</label>
                <select id="modal_parent_id" name="parent_id" class="form-control" onchange="fetchSuggestedCode()">
                    <option value="">-- Thuộc UBND Thành phố Hải Phòng --</option>
                    <?php foreach ($parents as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= e($p['ten_don_vi']) ?> (<?= e($p['ma_dinh_danh']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Loại hình cơ quan:</label>
                <select name="loai_don_vi" class="form-control" required>
                    <option value="PHONG_BAN">Phòng ban chuyên môn</option>
                    <option value="BO_PHAN_MOT_CUA">Bộ phận Một cửa</option>
                    <option value="SO_NGANH">Sở / Ban / Ngành</option>
                    <option value="UBND_HUYEN">UBND Quận / Huyện</option>
                    <option value="UBND_XA">UBND Xã / Phường</option>
                </select>
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">
                    Mã định danh (QCVN 102:2016/BTTTT):
                    <span id="code-loading" style="font-weight: normal; color: var(--color-primary); display: none;">(Đang gợi ý...)</span>
                </label>
                <input type="text" id="modal_ma_dinh_danh" name="ma_dinh_danh" class="form-control" placeholder="Ví dụ: 000.04.15.H21.01" required>
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Tên cơ quan / đơn vị:</label>
                <input type="text" name="ten_don_vi" class="form-control" placeholder="Nhập tên đầy đủ..." required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Địa chỉ trụ sở:</label>
                <input type="text" name="dia_chi" class="form-control" placeholder="Địa chỉ trụ sở làm việc...">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('modal-add-donvi').style.display='none'">
                    Hủy bỏ
                </button>
                <button type="submit" class="btn btn-primary" style="font-weight: 700;">
                    Thêm cơ quan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function fetchSuggestedCode() {
    const parentId = document.getElementById('modal_parent_id').value;
    const loading = document.getElementById('code-loading');
    loading.style.display = 'inline';

    fetch('<?= e(url('/admin/danh-muc/don-vi/goi-y-ma')) ?>?parent_id=' + parentId)
        .then(res => res.json())
        .then(data => {
            document.getElementById('modal_ma_dinh_danh').value = data.suggested_code;
            loading.style.display = 'none';
        })
        .catch(() => {
            loading.style.display = 'none';
        });
}
</script>
<?php $this->endSection(); ?>
