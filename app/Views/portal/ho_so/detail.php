<?php $this->layout('layouts/portal', ['title' => "Tiến trình hồ sơ: {$hoSo['ma_ho_so']}"]); ?>

<?php $this->startSection('content'); ?>
<div class="mb-4">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
        <a href="<?= e(url('/cong-dan/ho-so')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.85rem;">⬅ Danh sách hồ sơ</a>
        <span class="text-muted">/</span>
        <span style="font-family: monospace; font-weight: 700; color: #0b2e59; font-size: 0.9rem;"><?= e($hoSo['ma_ho_so']) ?></span>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
        <div>
            <h1 style="font-size: 1.45rem; color: #0b2e59; margin: 0 0 0.35rem 0;">
                <?= e($hoSo['ten_tthc']) ?>
            </h1>
            <p class="text-muted" style="margin: 0; font-size: 0.85rem;">
                Cơ quan giải quyết: <strong><?= e($hoSo['ten_don_vi']) ?></strong> · Ngày nộp: <strong><?= date('d/m/Y H:i', strtotime($hoSo['ngay_nop'])) ?></strong>
            </p>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <?php if ($hoSo['trang_thai'] === 'CHO_BO_SUNG'): ?>
                <a href="<?= e(url('/cong-dan/ho-so/' . $hoSo['ma_ho_so'] . '/bo-sung')) ?>" class="btn btn-warning" style="font-weight: 700;">
                    ⚠️ Nộp hồ sơ bổ sung ngay
                </a>
            <?php endif; ?>

            <?php if (in_array($hoSo['trang_thai'], ['HOAN_THANH', 'DA_TRA_KET_QUA'], true) && empty($sipas)): ?>
                <a href="<?= e(url('/cong-dan/ho-so/' . $hoSo['ma_ho_so'] . '/danh-gia-sipas')) ?>" class="btn btn-gold" style="font-weight: 700;">
                    ⭐ Đánh giá hài lòng (SIPAS)
                </a>
            <?php elseif (!empty($sipas)): ?>
                <span class="badge badge-success" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;">
                    ⭐ Đã đánh giá SIPAS: <?= e($sipas['diem_trung_binh']) ?>/5.0
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- STEPPER 5 BƯỚC QUY CHUẨN THÔNG TƯ 01/2018/TT-VPCP -->
<?php
$currentStatus = $hoSo['trang_thai'];
$stepNum = match($currentStatus) {
    'CHO_TIEP_NHAN', 'MOI_TIEP_NHAN' => 1,
    'CHO_BO_SUNG' => 1,
    'DANG_XU_LY' => 3,
    'CHO_PHE_DUYET' => 4,
    'HOAN_THANH', 'DA_TRA_KET_QUA' => 5,
    default => 2
};
?>
<div class="card p-3 mb-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
    <div style="display: flex; justify-content: space-between; align-items: center; position: relative;">
        <div style="position: absolute; top: 18px; left: 8%; right: 8%; height: 2px; background: #e2e8f0; z-index: 0;"></div>

        <?php 
        $stepsInfo = [
            1 => 'B1: Tiếp nhận Một cửa',
            2 => 'B2: Chuyển thụ lý',
            3 => 'B3: Thẩm định chuyên môn',
            4 => 'B4: Phê duyệt ký số',
            5 => 'B5: Trả kết quả'
        ];
        ?>
        <?php foreach ($stepsInfo as $num => $title): ?>
            <?php 
            $isDone = ($num < $stepNum) || ($num === 5 && in_array($currentStatus, ['HOAN_THANH', 'DA_TRA_KET_QUA'], true));
            $isActive = ($num === $stepNum && !in_array($currentStatus, ['HOAN_THANH', 'DA_TRA_KET_QUA'], true));
            $circleBg = $isDone ? '#16a34a' : ($isActive ? '#0b2e59' : '#e2e8f0');
            $circleColor = ($isDone || $isActive) ? '#fff' : '#64748b';
            $textColor = $isActive ? '#0b2e59' : ($isDone ? '#16a34a' : '#64748b');
            ?>
            <div style="position: relative; z-index: 1; text-align: center; flex: 1;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: <?= $circleBg ?>; color: <?= $circleColor ?>; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 0.25rem;">
                    <?= $isDone ? '✓' : $num ?>
                </div>
                <div style="font-size: 0.75rem; font-weight: 600; color: <?= $textColor ?>;">
                    <?= $title ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="row mb-4" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- CỘT TRÁI: TIMELINE LUÂN CHUYỂN CÔNG KHAI -->
    <div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <h2 style="font-size: 1.15rem; color: #0b2e59; margin: 0 0 1rem 0; font-weight: 700; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
            ⏱️ TIẾN TRÌNH LUÂN CHUYỂN HỒ SƠ CÔNG KHAI
        </h2>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($timeline as $lc): ?>
                <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                    <div style="background: #e0f2fe; color: #0284c7; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.75rem; margin-top: 2px;">
                        ●
                    </div>
                    <div style="flex: 1; border-bottom: 1px dashed #e2e8f0; padding-bottom: 0.75rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 700; color: #1e293b; font-size: 0.85rem;">
                                <?= e($lc['hanh_dong']) ?>
                            </span>
                            <span style="font-size: 0.75rem; color: #64748b;">
                                <?= !empty($lc['thoi_gian_chuyen']) ? date('d/m/Y H:i', strtotime($lc['thoi_gian_chuyen'])) : date('d/m/Y H:i') ?>
                            </span>
                        </div>
                        <div style="font-size: 0.8rem; color: #475569; margin-top: 2px;">
                            <?= e($lc['noi_dung_y_kien'] ?? $lc['noi_dung'] ?? 'Hệ thống luân chuyển tiếp nhận.') ?>
                        </div>
                        <?php $ttSau = $lc['trang_thai_sau'] ?? $lc['den_trang_thai'] ?? ''; ?>
                        <?php if (!empty($ttSau)): ?>
                            <span class="badge" style="background: #f1f5f9; color: #334155; font-size: 0.7rem; margin-top: 0.25rem;">
                                Trạng thái: <?= e($ttSau) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CỘT PHẢI: THÔNG TIN HẸN TRẢ & TÀI LIỆU KẾT QUẢ -->
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <!-- Thẻ Hạn trả kết quả -->
        <div class="card p-3" style="background: #f8fafc; border-left: 4px solid #16a34a; border-radius: var(--radius-md);">
            <div style="font-weight: 700; color: #166534; font-size: 0.85rem;">THỜI HẠN GIẢI QUYẾT (SLA)</div>
            <div style="font-size: 1.15rem; font-weight: 800; color: #15803d; margin-top: 0.25rem;">
                <?= !empty($hoSo['ngay_hen_tra']) ? date('d/m/Y H:i', strtotime($hoSo['ngay_hen_tra'])) : 'Đang xử lý' ?>
            </div>
            <small class="text-muted">Tổng thời hạn cam kết: <?= e($hoSo['tong_thoi_han_gio']) ?> giờ làm việc</small>
        </div>

        <!-- Thẻ Danh sách tài liệu đính kèm -->
        <div class="card p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
            <h3 style="font-size: 0.95rem; font-weight: 700; color: #0b2e59; margin: 0 0 0.75rem 0;">
                📎 Tài liệu đính kèm (<?= count($taiLieuList) ?>)
            </h3>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <?php foreach ($taiLieuList as $tl): ?>
                    <div style="font-size: 0.8rem; padding: 0.4rem; background: #f8fafc; border-radius: 4px; border: 1px solid #e2e8f0;">
                        <div style="font-weight: 600; color: #1e293b;"><?= e($tl['ten_goc']) ?></div>
                        <small style="color: #64748b; font-family: monospace; font-size: 0.7rem;">
                            SHA-256: <?= substr($tl['sha256'] ?? '', 0, 16) ?>...
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
