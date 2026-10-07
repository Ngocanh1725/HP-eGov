<?php $this->layout('layouts/admin', ['title' => "Đóng dấu phát hành " . ($hoSo['ma_ho_so'] ?? '')]) ?>

<?php $this->section('content') ?>
<div class="page-header" style="margin-bottom: 24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                <a href="<?= e(url('/admin/van-thu')) ?>" style="color:#64748B; text-decoration:none;">← Danh sách đóng dấu</a>
                <span style="color:#CBD5E1;">/</span>
                <span style="font-size:14px; font-weight:600; color:#0B2E59;"><?= e($hoSo['ma_ho_so']) ?></span>
            </div>
            <h1 style="font-size: 22px; font-weight: 700; color: #0B2E59; margin: 0;">
                Đóng dấu số cơ quan & Phát hành điện tử: <?= e($hoSo['ten_tthc']) ?>
            </h1>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
    <!-- Cột trái: Form cấp số và đóng dấu -->
    <div style="display:flex; flex-direction:column; gap:20px;">
        <div class="card" style="padding:24px;">
            <h3 style="font-size:16px; font-weight:700; color:#0B2E59; margin-top:0; margin-bottom:16px; border-bottom:1px solid #E2E8F0; padding-bottom:10px;">
                🔏 Thông tin cấp số & Đóng dấu điện tử
            </h3>

            <form method="POST" action="<?= e(url("/admin/van-thu/dong-dau/{$hoSo['id']}")) ?>">
                <?= csrf_field() ?>

                <div style="margin-bottom:16px;">
                    <label style="font-weight:700; font-size:13px; display:block; margin-bottom:6px; color:#0B2E59;">
                        Chọn Sổ văn bản phát hành: <span style="color:#C8102E;">*</span>
                    </label>
                    <select name="so_van_ban_id" class="form-control" required style="width:100%; font-size:14px;">
                        <option value="">-- Chọn sổ văn bản phù hợp --</option>
                        <?php foreach ($soVanBanList as $svb): ?>
                            <option value="<?= e($svb['id']) ?>">
                                <?= e($svb['ten_so']) ?> (Năm <?= e($svb['nam']) ?> | Đã cấp đến số: <?= e($svb['so_hien_tai']) ?> | Ký hiệu: <?= e($svb['ky_hieu_mau']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div style="font-size:11px; color:#64748B; margin-top:4px;">
                        Hệ thống sẽ tự động khóa hàng transaction và cấp số thứ tự kế tiếp không trùng lặp.
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label style="font-weight:700; font-size:13px; display:block; margin-bottom:6px; color:#0B2E59;">
                        Trích yếu nội dung văn bản:
                    </label>
                    <textarea name="trich_yeu" class="form-control" rows="2" style="width:100%;">Quyết định giải quyết TTHC: <?= e($hoSo['ten_tthc']) ?> cho công dân <?= e($hoSo['ho_ten_nguoi_nop']) ?></textarea>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="font-weight:700; font-size:13px; display:block; margin-bottom:6px; color:#0B2E59;">
                        Người ký phê duyệt:
                    </label>
                    <input type="text" name="nguoi_ky" class="form-control" value="Lãnh đạo cơ quan" style="width:100%;">
                </div>

                <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:8px; padding:16px; margin-bottom:24px;">
                    <div style="font-weight:700; color:#166534; font-size:14px; margin-bottom:6px;">
                        ⚡ Tự động hóa sau khi đóng dấu:
                    </div>
                    <ul style="margin:0; padding-left:20px; font-size:13px; color:#166534; line-height:1.6;">
                        <li>Ký số tổ chức bằng con dấu điện tử X.509 Ban Cơ yếu Chính phủ của cơ quan.</li>
                        <li>Chuyển trạng thái hồ sơ sang <strong>HOÀN THÀNH</strong>.</li>
                        <li>Tự động đẩy tệp kết quả điện tử vào <strong>Kho dữ liệu công dân</strong> (Nguồn: CAP_MOI_DVC, hạn 5 năm).</li>
                        <li>Bắn thông báo in-app và tin nhắn SMS Brandname / Zalo ZNS cho người nộp.</li>
                    </ul>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px;">
                    <a href="<?= e(url('/admin/van-thu')) ?>" class="btn btn-secondary">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary" id="btn-xac-nhan-dong-dau" style="background:#C8102E; border-color:#C8102E; padding:10px 24px; font-weight:700;">
                        🔏 Xác nhận Cấp số & Đóng dấu số cơ quan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cột phải: Thông tin hồ sơ & Chữ ký Lãnh đạo đã có -->
    <div style="display:flex; flex-direction:column; gap:20px;">
        <div class="card" style="padding:16px;">
            <h4 style="font-size:14px; font-weight:700; color:#0B2E59; margin-top:0; margin-bottom:12px;">
                📋 Hồ sơ giải quyết
            </h4>
            <div style="font-size:13px; line-height:1.6;">
                <div><strong>Mã:</strong> <?= e($hoSo['ma_ho_so']) ?></div>
                <div><strong>Thủ tục:</strong> <?= e($hoSo['ten_tthc']) ?></div>
                <div><strong>Người nộp:</strong> <?= e($hoSo['ho_ten_nguoi_nop']) ?></div>
                <div><strong>Hạn trả:</strong> <?= date('d/m/Y H:i', strtotime($hoSo['ngay_hen_tra'])) ?></div>
            </div>
        </div>

        <div class="card" style="padding:16px;">
            <h4 style="font-size:14px; font-weight:700; color:#0B2E59; margin-top:0; margin-bottom:12px;">
                ✍️ Chữ ký số đã xác lập
            </h4>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <?php foreach ($chuKyList as $ck): ?>
                    <div style="padding:10px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:6px; font-size:12px;">
                        <div style="font-weight:700; color:#0B2E59;"><?= e($ck['ten_chu_the']) ?></div>
                        <div style="color:#059669; font-weight:600; font-size:11px;">✓ <?= e($ck['loai_chu_ky']) ?></div>
                        <div style="color:#64748B; font-size:11px;">Serial: <code><?= e($ck['so_serial']) ?></code></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
