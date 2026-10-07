<?php $this->layout('layouts/portal', ['title' => 'Kho dữ liệu điện tử cá nhân']); ?>

<?php $this->startSection('content'); ?>
<div class="mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.55rem; color: #0b2e59; margin: 0;">KHO DỮ LIỆU ĐIỆN TỬ CÁ NHÂN / TỔ CHỨC</h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Kho lưu trữ các giấy tờ, kết quả TTHC đã được số hóa và ký số điện tử của Quý khách theo Nghị định 107/2021/NĐ-CP
        </p>
    </div>
    <a href="<?= e(url('/dvc/thu-tuc')) ?>" class="btn btn-primary" style="font-size: 0.85rem; font-weight: 600;">
        ➕ Nộp TTHC & Tái sử dụng giấy tờ
    </a>
</div>

<div class="card p-3 mb-4" style="background: #eff6ff; border-left: 4px solid #2563eb; border-radius: var(--radius-md);">
    <div style="font-weight: 700; color: #1e40af; font-size: 0.9rem;">
        💡 NGUYÊN TẮC: NGƯỜI DÂN KHÔNG PHẢI NỘP LẠI GIẤY TỜ ĐÃ ĐƯỢC CƠ QUAN NHÀ NƯỚC SỐ HÓA
    </div>
    <p style="font-size: 0.85rem; color: #334155; margin: 0.25rem 0 0 0; line-height: 1.45;">
        Mọi kết quả giải quyết TTHC và các giấy tờ thành phần đã được kiểm tra, ký số sẽ được lưu trữ an toàn trọn đời trong Kho dữ liệu này. Khi nộp hồ sơ trực tuyến mới, Quý khách chỉ cần chọn giấy tờ từ danh sách dưới đây mà không phải quét lại.
    </p>
</div>

<div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow-x: auto;">
    <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; font-size: 0.8rem; border-bottom: 2px solid var(--border-color);">
            <tr>
                <th style="padding: 0.75rem 1rem; text-align: left;">Tên tài liệu / Giấy tờ số hóa</th>
                <th style="padding: 0.75rem 1rem; text-align: left; width: 180px;">Mã loại giấy tờ</th>
                <th style="padding: 0.75rem 1rem; text-align: left; width: 180px;">Nguồn cấp / Cơ quan</th>
                <th style="padding: 0.75rem 1rem; text-align: center; width: 130px;">Ngày số hóa</th>
                <th style="padding: 0.75rem 1rem; text-align: center; width: 130px;">Thời hạn hiệu lực</th>
                <th style="padding: 0.75rem 1rem; text-align: center; width: 120px;">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem; color: #64748b;">
                        Kho dữ liệu của Quý khách hiện chưa có tài liệu nào. Sau khi hoàn thành giải quyết các TTHC, kết quả điện tử sẽ tự động đồng bộ về đây.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $it): ?>
                    <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.85rem;">
                        <td style="padding: 0.75rem 1rem;">
                            <div style="font-weight: 700; color: #1e293b;"><?= e($it['ten_tai_lieu']) ?></div>
                            <small class="text-muted" style="font-family: monospace; font-size: 0.7rem;">
                                SHA-256: <?= substr($it['sha256'] ?? '', 0, 20) ?>...
                            </small>
                        </td>
                        <td style="padding: 0.75rem 1rem; font-family: monospace; font-size: 0.8rem;">
                            <?= e($it['ma_loai_giay_to'] ?? 'GIAY_TO_SO_HOA') ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; font-size: 0.8rem; color: #475569;">
                            <?= e($it['nguon_cap'] ?? 'UBND TP Hải Phòng') ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center; color: #64748b;">
                            <?= date('d/m/Y', strtotime($it['ngay_so_hoa'])) ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <?= !empty($it['ngay_het_han']) ? date('d/m/Y', strtotime($it['ngay_het_han'])) : 'Vô thời hạn' ?>
                        </td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <?php if ($it['trang_thai']): ?>
                                <span class="badge badge-success" style="font-size: 0.75rem;">Còn hiệu lực</span>
                            <?php else: ?>
                                <span class="badge badge-secondary" style="font-size: 0.75rem;">Hết hiệu lực</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $this->endSection(); ?>
