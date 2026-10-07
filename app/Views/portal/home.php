<?php $this->layout('layouts/portal', ['title' => 'Cổng Dịch vụ công & Một cửa điện tử liên thông TP. Hải Phòng']); ?>

<?php $this->startSection('content'); ?>
<!-- HERO BANNER & TÌM KIẾM TTHC -->
<section style="background: linear-gradient(135deg, #0b2e59 0%, #1e40af 60%, #1e3a8a 100%); color: #fff; padding: 3.5rem 1.5rem; border-radius: var(--radius-lg); margin-bottom: 2.5rem; box-shadow: var(--shadow-lg); text-align: center; position: relative; overflow: hidden;">
    <div style="position: absolute; right: -50px; bottom: -50px; width: 300px; height: 300px; background: rgba(212, 160, 23, 0.1); border-radius: 50%; pointer-events: none;"></div>
    <div style="position: absolute; left: -30px; top: -30px; width: 200px; height: 200px; background: rgba(200, 16, 46, 0.15); border-radius: 50%; pointer-events: none;"></div>

    <div style="max-width: 850px; margin: 0 auto; position: relative; z-index: 1;">
        <span class="badge" style="background: #d4a017; color: #0b2e59; font-weight: 700; font-size: 0.85rem; padding: 0.4rem 0.8rem; border-radius: 20px; text-transform: uppercase; margin-bottom: 1rem; display: inline-block;">
            ⭐ HỆ THỐNG GIẢI QUYẾT TTHC THÀNH PHỐ HẢI PHÒNG
        </span>
        <h1 style="font-size: 2.3rem; font-weight: 800; line-height: 1.25; margin-bottom: 1rem; color: #ffffff;">
            Phục vụ Người dân & Doanh nghiệp<br>Nhanh chóng – Minh bạch – Toàn trình
        </h1>
        <p style="font-size: 1.05rem; opacity: 0.9; margin-bottom: 2rem; max-width: 700px; margin-left: auto; margin-right: auto;">
            Tích hợp tài khoản định danh điện tử VNeID (C06), số hóa thành phần hồ sơ theo Nghị định 107/2021/NĐ-CP và liên thông thông suốt 15 quận/huyện, sở ngành.
        </p>

        <!-- FORM TÌM KIẾM NHANH TTHC -->
        <form action="<?= e(url('/dvc/thu-tuc')) ?>" method="GET" style="display: flex; gap: 0.5rem; background: #fff; padding: 0.5rem; border-radius: 12px; box-shadow: var(--shadow-md); max-width: 750px; margin: 0 auto;">
            <input type="text" name="q" placeholder="Nhập tên thủ tục hành chính, từ khóa hoặc mã thủ tục (VD: Đất đai, Lái xe, Xây dựng...)" 
                   style="flex: 1; border: none; padding: 0.75rem 1rem; font-size: 0.95rem; outline: none; border-radius: 8px; color: #1e293b;" required>
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.75rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; border-radius: 8px;">
                🔍 Tìm kiếm
            </button>
        </form>

        <!-- Lĩnh vực tìm kiếm nhanh -->
        <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap; margin-top: 1.25rem;">
            <span style="font-size: 0.85rem; opacity: 0.8; align-self: center;">Lĩnh vực phổ biến:</span>
            <?php foreach ($linhVucList as $lv): ?>
                <a href="<?= e(url('/dvc/thu-tuc?linh_vuc_id=' . $lv['id'])) ?>" class="badge" 
                   style="background: rgba(255,255,255,0.15); color: #fff; text-decoration: none; padding: 0.35rem 0.65rem; border-radius: 14px; font-size: 0.75rem; transition: background 0.2s ease;">
                    <?= e($lv['ten_linh_vuc']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 4 THẺ THỐNG KÊ CÔNG KHAI -->
<section class="mb-5">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.25rem;">
        <div class="card p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border-top: 4px solid #2563eb;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;">TỔNG HỒ SƠ TIẾP NHẬN</span>
                <span style="font-size: 1.25rem;">📂</span>
            </div>
            <div style="font-size: 1.85rem; font-weight: 800; color: #1e3a8a; margin-top: 0.5rem;">
                <?= number_format($stats['total_ho_so'], 0, ',', '.') ?>
            </div>
            <small style="color: #16a34a; font-weight: 600; font-size: 0.75rem;">▲ Tăng trưởng hồ sơ số hóa toàn TP</small>
        </div>

        <div class="card p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border-top: 4px solid #16a34a;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;">TỶ LỆ GIẢI QUYẾT ĐÚNG HẠN</span>
                <span style="font-size: 1.25rem;">⏱️</span>
            </div>
            <div style="font-size: 1.85rem; font-weight: 800; color: #166534; margin-top: 0.5rem;">
                <?= $stats['ty_le_dung_han'] ?>%
            </div>
            <small style="color: #64748b; font-size: 0.75rem;">Chỉ tiêu cam kết $\ge 98.0\%$</small>
        </div>

        <div class="card p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border-top: 4px solid #d4a017;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;">MỨC ĐỘ HÀI LÒNG (SIPAS)</span>
                <span style="font-size: 1.25rem;">⭐</span>
            </div>
            <div style="font-size: 1.85rem; font-weight: 800; color: #b45309; margin-top: 0.5rem;">
                <?= $stats['ty_le_hai_long'] ?>%
            </div>
            <small style="color: #64748b; font-size: 0.75rem;">Đo lường 5 tiêu chí QĐ 876/QĐ-BNV</small>
        </div>

        <div class="card p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border-top: 4px solid #0284c7;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;">DVC TRỰC TUYẾN TOÀN TRÌNH</span>
                <span style="font-size: 1.25rem;">🌐</span>
            </div>
            <div style="font-size: 1.85rem; font-weight: 800; color: #0369a1; margin-top: 0.5rem;">
                <?= $stats['dvc_toan_trinh'] ?> TTHC
            </div>
            <small style="color: #64748b; font-size: 0.75rem;">Không cần đến Một cửa giấy</small>
        </div>
    </div>
</section>

<!-- DỊCH VỤ CÔNG NỔI BẬT & PHỔ BIẾN -->
<section class="mb-5">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
        <div>
            <h2 style="font-size: 1.4rem; color: #0b2e59; margin: 0; font-weight: 700;">DỊCH VỤ CÔNG NỔI BẬT ĐƯỢC QUAN TÂM</h2>
            <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">Các thủ tục hành chính có tần suất giải quyết cao và hỗ trợ nộp trực tuyến 100%</p>
        </div>
        <a href="<?= e(url('/dvc/thu-tuc')) ?>" class="btn btn-outline-primary" style="font-size: 0.85rem;">
            Xem tất cả thủ tục ➔
        </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem;">
        <?php foreach ($tthcHot as $tt): ?>
            <div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; justify-content: space-between; border: 1px solid var(--border-color); transition: transform 0.15s ease, box-shadow 0.15s ease;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                        <span class="badge" style="background: #e0f2fe; color: #0369a1; font-weight: 700; font-size: 0.75rem;">
                            <?= e($tt['ma_rut_gon'] ?? 'N/A') ?>
                        </span>
                        <?php if ($tt['muc_do_dvc'] === 'TOAN_TRINH'): ?>
                            <span class="badge badge-success" style="font-size: 0.7rem;">Toàn trình</span>
                        <?php else: ?>
                            <span class="badge badge-info" style="font-size: 0.7rem;">Một phần</span>
                        <?php endif; ?>
                    </div>
                    <h3 style="font-size: 1rem; font-weight: 700; margin: 0 0 0.5rem 0; color: #1e293b; line-height: 1.4;">
                        <a href="<?= e(url('/dvc/thu-tuc/' . ($tt['ma_rut_gon'] ?? $tt['id']))) ?>" style="color: inherit; text-decoration: none;">
                            <?= e($tt['ten_tthc']) ?>
                        </a>
                    </h3>
                    <div style="font-size: 0.8rem; color: #64748b; margin-bottom: 1rem;">
                        🏛️ Cấp: <strong><?= e($tt['cap_thuc_hien']) ?></strong> · ⏱️ Thời hạn: <strong><?= e($tt['thoi_han_giai_quyet_ngay']) ?> ngày</strong>
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; margin-top: 1rem; border-top: 1px dashed #e2e8f0; padding-top: 1rem;">
                    <a href="<?= e(url('/dvc/thu-tuc/' . ($tt['ma_rut_gon'] ?? $tt['id']))) ?>" class="btn btn-sm btn-outline-secondary" style="flex: 1; text-align: center;">
                        Xem chi tiết
                    </a>
                    <a href="<?= e(url('/dvc/nop-ho-so/' . $tt['id'])) ?>" class="btn btn-sm btn-primary" style="flex: 1; text-align: center; font-weight: 600;">
                        📝 Nộp hồ sơ
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- TIN TỨC & THÔNG BÁO HẢI PHÒNG -->
<section class="mb-4">
    <div style="border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1rem;">
        <h2 style="font-size: 1.25rem; color: #0b2e59; margin: 0; font-weight: 700;">TIN TỨC & THÔNG BÁO HÀNH CHÍNH</h2>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem;">
        <?php foreach ($tinTuc as $tin): ?>
            <div class="card p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
                <span style="font-size: 0.75rem; color: #dc2626; font-weight: 700;">📅 <?= e($tin['ngay']) ?></span>
                <h3 style="font-size: 0.95rem; font-weight: 700; margin: 0.35rem 0 0.5rem 0; color: #1e293b; line-height: 1.35;">
                    <?= e($tin['tieu_de']) ?>
                </h3>
                <p style="font-size: 0.8rem; color: #64748b; margin: 0; line-height: 1.4;">
                    <?= e($tin['tom_tat']) ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php $this->endSection(); ?>
