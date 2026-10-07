<?php $this->layout('layouts/admin', ['title' => "Phê duyệt hồ sơ " . ($hoSo['ma_ho_so'] ?? '')]) ?>

<?php $this->section('content') ?>
<div class="page-header" style="margin-bottom: 24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                <a href="<?= e(url('/admin/phe-duyet/co-quan')) ?>" style="color:#64748B; text-decoration:none;">← Danh sách</a>
                <span style="color:#CBD5E1;">/</span>
                <span style="font-size:14px; font-weight:600; color:#0B2E59;"><?= e($hoSo['ma_ho_so']) ?></span>
            </div>
            <h1 style="font-size: 22px; font-weight: 700; color: #0B2E59; margin: 0;">
                Ký số Ban hành Kết quả: <?= e($hoSo['ten_tthc']) ?>
            </h1>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-tra-lai').style.display='flex';">
                ↩ Trả lại phòng
            </button>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('modal-ky-co-quan').style.display='flex';" id="btn-ky-phat-hanh">
                ✍️ Ký số Phát hành (SmartCA)
            </button>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
    <!-- Cột trái: Văn bản kết quả giải quyết & Xem trước chữ ký -->
    <div style="display:flex; flex-direction:column; gap:20px;">
        <div class="card" style="padding:20px;">
            <h3 style="font-size:16px; font-weight:700; color:#0B2E59; margin-top:0; margin-bottom:16px; border-bottom:1px solid #E2E8F0; padding-bottom:10px;">
                📜 Quyết định giải quyết TTHC (Dự thảo phát hành)
            </h3>
            <div style="background:#FFFFFF; border:2px solid #CBD5E1; border-radius:8px; padding:30px; font-family:'Times New Roman', serif; font-size:15px; line-height:1.6; color:#0F172A; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">
                <div style="display:flex; justify-content:space-between; margin-bottom:24px;">
                    <div style="text-align:center; width:45%;">
                        <div style="font-size:13px; font-weight:bold; text-transform:uppercase;">ỦY BAN NHÂN DÂN THÀNH PHỐ HẢI PHÒNG</div>
                        <div style="font-size:14px; font-weight:bold; text-transform:uppercase; color:#0B2E59;"><?= e($hoSo['ten_don_vi'] ?? 'CƠ QUAN PHÁT HÀNH') ?></div>
                        <div style="font-size:13px; margin-top:4px;">Số: .... /QĐ-UBND</div>
                    </div>
                    <div style="text-align:center; width:50%;">
                        <div style="font-size:13px; font-weight:bold; text-transform:uppercase;">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                        <div style="font-size:13px; font-weight:bold;">Độc lập - Tự do - Hạnh phúc</div>
                        <div style="margin:4px 0;">-----------------------</div>
                        <div style="font-size:13px; font-style:italic;">Hải Phòng, ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?></div>
                    </div>
                </div>

                <div style="text-align:center; font-weight:bold; font-size:17px; margin-bottom:16px; text-transform:uppercase; color:#C8102E;">
                    QUYẾT ĐỊNH
                </div>
                <div style="text-align:center; font-weight:bold; font-size:14px; margin-bottom:20px;">
                    Về việc giải quyết thủ tục: <?= e($hoSo['ten_tthc']) ?>
                </div>

                <div style="margin-bottom:12px; font-weight:bold; text-align:center;">
                    THỦ TRƯỞNG <?= mb_strtoupper($hoSo['ten_don_vi'] ?? 'CƠ QUAN', 'UTF-8') ?>
                </div>

                <p style="text-indent:24px; margin-bottom:8px;">
                    Căn cứ Luật Tổ chức chính quyền địa phương và các văn bản quy phạm pháp luật liên quan;
                </p>
                <p style="text-indent:24px; margin-bottom:8px;">
                    Xét đề nghị của Trưởng phòng chuyên môn tại Tờ trình về việc giải quyết hồ sơ <strong><?= e($hoSo['ma_ho_so']) ?></strong>;
                </p>

                <div style="text-align:center; font-weight:bold; margin:16px 0;">QUYẾT ĐỊNH:</div>

                <p style="text-indent:24px; margin-bottom:10px;">
                    <strong>Điều 1.</strong> Chấp thuận giải quyết thủ tục <em><?= e($hoSo['ten_tthc']) ?></em> cho cá nhân/tổ chức: <strong><?= e($hoSo['ho_ten_nguoi_nop']) ?></strong> (Số CCCD: <?= e($hoSo['so_cccd_nguoi_nop'] ?? '---') ?>).
                </p>
                <p style="text-indent:24px; margin-bottom:10px;">
                    <strong>Điều 2.</strong> Quyết định này có hiệu lực kể từ ngày ký. Bộ phận Văn thư số có trách nhiệm vào sổ và đóng dấu phát hành điện tử theo quy định.
                </p>

                <!-- Vùng chữ ký số trực quan -->
                <div style="display:flex; justify-content:flex-end; margin-top:30px; padding-right:20px;">
                    <div style="text-align:center; min-width:260px;">
                        <div style="font-weight:bold; font-size:14px; margin-bottom:6px; text-transform:uppercase;">
                            LÃNH ĐẠO CƠ QUAN
                        </div>
                        <div style="font-size:12px; font-style:italic; color:#64748B; margin-bottom:16px;">
                            (Đã ký số điện tử SmartCA)
                        </div>

                        <!-- Khối hiển thị tem ký số -->
                        <div style="display:inline-block; border:2px solid #0284C7; border-radius:8px; padding:10px 14px; background:#F0F9FF; text-align:left; box-shadow:0 2px 4px rgba(2,132,199,0.15);">
                            <div style="color:#0284C7; font-weight:bold; font-size:12px; margin-bottom:4px;">
                                ✓ ĐÃ KÝ SỐ PHÊ DUYỆT
                            </div>
                            <div style="font-size:13px; font-weight:700; color:#0F172A;">
                                <?= e($user['ho_ten'] ?? 'Lãnh đạo cơ quan') ?>
                            </div>
                            <div style="font-size:11px; color:#64748B;">
                                SmartCA / Ban Cơ yếu Chính phủ
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cột phải: Lịch sử chữ ký số & Thao tác -->
    <div style="display:flex; flex-direction:column; gap:20px;">
        <!-- Chuỗi chữ ký số trên hồ sơ -->
        <div class="card" style="padding:16px;">
            <h4 style="font-size:14px; font-weight:700; color:#0B2E59; margin-top:0; margin-bottom:14px;">
                🔐 Chuỗi Chữ ký số trên Hồ sơ
            </h4>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <?php if (empty($chuKyList)): ?>
                    <div style="font-size:12px; color:#64748B;">Chưa có chữ ký số nào được xác lập.</div>
                <?php else: ?>
                    <?php foreach ($chuKyList as $ck): ?>
                        <div style="padding:10px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:6px; font-size:12px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                <strong style="color:#0B2E59;"><?= e($ck['ten_chu_the']) ?></strong>
                                <span class="badge" style="background:#ECFDF5; color:#059669; font-size:10px;">✓ Hợp lệ</span>
                            </div>
                            <div style="color:#64748B; font-size:11px;">Loại: <?= e($ck['loai_chu_ky']) ?></div>
                            <div style="color:#64748B; font-size:11px;">Serial: <code><?= e($ck['so_serial']) ?></code></div>
                            <div style="color:#64748B; font-size:11px;">Thời gian: <?= e($ck['thoi_gian_ky']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ký số Phát hành Lãnh đạo cơ quan -->
<div id="modal-ky-co-quan" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; border-radius:12px; width:100%; max-width:460px; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid #E2E8F0; padding-bottom:10px;">
            <h3 style="margin:0; font-size:18px; color:#0B2E59;">
                🏛️ Ký số Phát hành Kết quả TTHC
            </h3>
            <button type="button" onclick="document.getElementById('modal-ky-co-quan').style.display='none';" style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>

        <form method="POST" action="<?= e(url("/admin/phe-duyet/co-quan/{$hoSo['id']}/ky-so")) ?>">
            <?= csrf_field() ?>
            <div style="margin-bottom:14px;">
                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:6px;">Ý kiến chỉ đạo phê duyệt:</label>
                <input type="text" name="y_kien" class="form-control" value="Phê duyệt ban hành kết quả giải quyết TTHC." style="width:100%;">
            </div>

            <div style="margin-bottom:20px; background:#F0F9FF; border:1px solid #BAE6FD; padding:16px; border-radius:8px;">
                <label style="font-weight:700; font-size:13px; color:#0369A1; display:block; margin-bottom:6px;">
                    Mã PIN SmartCA Lãnh đạo cơ quan: <span style="color:#C8102E;">*</span>
                </label>
                <input type="password" name="pin" class="form-control" placeholder="Nhập PIN (Mẫu: 123456)" maxlength="6" required style="font-size:20px; letter-spacing:4px; text-align:center;">
                <div style="font-size:11px; color:#64748B; margin-top:6px; text-align:center;">
                    Hệ thống sẽ ký số bằng OpenSSL và chuyển Văn thư số đóng dấu phát hành
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-ky-co-quan').style.display='none';">Hủy</button>
                <button type="submit" class="btn btn-primary" id="btn-submit-ky-phat-hanh">
                    ✓ Phê duyệt & Ký số
                </button>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
