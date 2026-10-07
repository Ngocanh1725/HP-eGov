<?php $this->layout('layouts/admin', ['title' => $pageTitle]); ?>

<?php $this->startSection('content'); ?>
<div class="mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; color: #0b2e59; margin: 0; font-weight: 800;">
            🏛️ BÀN LÀM VIỆC BỘ PHẬN MỘT CỬA
        </h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.875rem;">
            Tiếp nhận hồ sơ trực tiếp, xử lý dịch vụ công trực tuyến & số hóa theo Nghị định 107/2021/NĐ-CP
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="<?= e(url('/admin/mot-cua/tiep-nhan')) ?>" class="btn btn-primary" style="font-weight: 700; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: var(--shadow-sm);">
            <span>➕</span> Tiếp nhận hồ sơ mới (Quét QR CCCD)
        </a>
        <a href="<?= e(url('/admin/mot-cua/so-theo-doi')) ?>" class="btn btn-secondary" style="font-weight: 600;">
            <span>📖</span> Sổ theo dõi hồ sơ (Mẫu 06)
        </a>
    </div>
</div>

<!-- 4 THẺ KPI ĐẾM THỐNG KÊ -->
<div class="row mb-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
    <!-- 1. Chờ tiếp nhận trực tuyến -->
    <a href="<?= e(url('/admin/mot-cua/ho-so-truc-tuyen')) ?>" style="text-decoration: none; color: inherit;">
        <div class="card p-3" style="background: #fff; border-left: 5px solid #2563eb; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Chờ tiếp nhận trực tuyến</span>
                <span style="font-size: 1.25rem;">🌐</span>
            </div>
            <div style="font-size: 1.85rem; font-weight: 800; color: #1e40af; margin-top: 0.5rem;">
                <?= number_format($counts['cho_tiep_nhan']) ?>
            </div>
            <div style="font-size: 0.75rem; color: #2563eb; margin-top: 0.25rem; font-weight: 600;">
                Hồ sơ nộp từ Cổng DVC ➔
            </div>
        </div>
    </a>

    <!-- 2. Tiếp nhận hôm nay -->
    <div class="card p-3" style="background: #fff; border-left: 5px solid #16a34a; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Tiếp nhận hôm nay</span>
            <span style="font-size: 1.25rem;">📥</span>
        </div>
        <div style="font-size: 1.85rem; font-weight: 800; color: #15803d; margin-top: 0.5rem;">
            <?= number_format($counts['tiep_nhan_hom_nay']) ?>
        </div>
        <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
            Cả trực tiếp và trực tuyến
        </div>
    </div>

    <!-- 3. Chờ trả kết quả -->
    <a href="<?= e(url('/admin/mot-cua/tra-ket-qua')) ?>" style="text-decoration: none; color: inherit;">
        <div class="card p-3" style="background: #fff; border-left: 5px solid #0284c7; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Chờ trả kết quả</span>
                <span style="font-size: 1.25rem;">🎁</span>
            </div>
            <div style="font-size: 1.85rem; font-weight: 800; color: #0369a1; margin-top: 0.5rem;">
                <?= number_format($counts['cho_tra_kq']) ?>
            </div>
            <div style="font-size: 0.75rem; color: #0284c7; margin-top: 0.25rem; font-weight: 600;">
                Đã hoàn thành chờ bàn giao ➔
            </div>
        </div>
    </a>

    <!-- 4. Sắp quá hạn / Quá hạn -->
    <div class="card p-3" style="background: #fff; border-left: 5px solid <?= $counts['sap_qua_han'] > 0 ? '#dc2626' : '#f59e0b' ?>; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Cảnh báo SLA hạn xử lý</span>
            <span style="font-size: 1.25rem;">⚠️</span>
        </div>
        <div style="font-size: 1.85rem; font-weight: 800; color: <?= $counts['sap_qua_han'] > 0 ? '#b91c1c' : '#b45309' ?>; margin-top: 0.5rem;">
            <?= number_format($counts['sap_qua_han']) ?>
        </div>
        <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
            Sắp hết hạn hoặc trễ hạn giải quyết
        </div>
    </div>
</div>

<!-- VIỆC CẦN LÀM (TO-DO LIST) -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;" class="mb-4">
    <!-- Cột trái: Hồ sơ trực tuyến chờ tiếp nhận -->
    <div class="card p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1rem;">
            <h2 style="font-size: 1rem; color: #0b2e59; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <span>🌐</span> HỒ SƠ TRỰC TUYẾN CHỜ TIẾP NHẬN
            </h2>
            <a href="<?= e(url('/admin/mot-cua/ho-so-truc-tuyen')) ?>" style="font-size: 0.8rem; color: #2563eb; text-decoration: none; font-weight: 600;">Xem tất cả (<?= $counts['cho_tiep_nhan'] ?>) ➔</a>
        </div>

        <?php if (empty($todoTrucTuyen)): ?>
            <div style="text-align: center; padding: 2rem; color: #64748b; font-size: 0.85rem;">
                🎉 Hiện tại không có hồ sơ trực tuyến nào đang chờ tiếp nhận!
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($todoTrucTuyen as $hs): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; background: #f8fafc;">
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <a href="<?= e(url('/admin/mot-cua/ho-so-truc-tuyen/' . $hs['id'])) ?>" style="font-weight: 700; color: #1e40af; font-size: 0.85rem; font-family: monospace; text-decoration: none;">
                                    <?= e($hs['ma_ho_so']) ?>
                                </a>
                                <span class="badge badge-info" style="font-size: 0.65rem;">Trực tuyến</span>
                            </div>
                            <div style="font-size: 0.8rem; color: #334155; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?= e($hs['ten_tthc']) ?>
                            </div>
                            <div style="font-size: 0.75rem; color: #64748b;">
                                Người nộp: <?= e($hs['nguoi_nop_ho_ten']) ?> · Gửi lúc: <?= date('d/m/Y H:i', strtotime($hs['ngay_nop'])) ?>
                            </div>
                        </div>
                        <div>
                            <a href="<?= e(url('/admin/mot-cua/ho-so-truc-tuyen/' . $hs['id'])) ?>" class="btn btn-sm btn-primary" style="white-space: nowrap;">
                                Thẩm tra ➔
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cột phải: Hồ sơ hoàn thành chờ trả kết quả -->
    <div class="card p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1rem;">
            <h2 style="font-size: 1rem; color: #0b2e59; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <span>🎁</span> HỒ SƠ ĐÃ HOÀN THÀNH CHỜ TRẢ KẾT QUẢ
            </h2>
            <a href="<?= e(url('/admin/mot-cua/tra-ket-qua')) ?>" style="font-size: 0.8rem; color: #0284c7; text-decoration: none; font-weight: 600;">Xem tất cả (<?= $counts['cho_tra_kq'] ?>) ➔</a>
        </div>

        <?php if (empty($todoTraKq)): ?>
            <div style="text-align: center; padding: 2rem; color: #64748b; font-size: 0.85rem;">
                Không có hồ sơ nào đang chờ trả kết quả.
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($todoTraKq as $hs): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; background: #f0fdf4;">
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="font-weight: 700; color: #15803d; font-size: 0.85rem; font-family: monospace;">
                                    <?= e($hs['ma_ho_so']) ?>
                                </span>
                                <span class="badge badge-success" style="font-size: 0.65rem;">Hoàn thành</span>
                            </div>
                            <div style="font-size: 0.8rem; color: #334155; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?= e($hs['ten_tthc']) ?>
                            </div>
                            <div style="font-size: 0.75rem; color: #64748b;">
                                Nhận: <strong><?= e($hs['hinh_thuc_nhan_kq']) ?></strong> · Người nộp: <?= e($hs['nguoi_nop_ho_ten']) ?>
                            </div>
                        </div>
                        <div>
                            <a href="<?= e(url('/admin/mot-cua/tra-ket-qua?q=' . urlencode($hs['ma_ho_so']))) ?>" class="btn btn-sm btn-success" style="white-space: nowrap;">
                                Trả kết quả ➔
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $this->endSection(); ?>
