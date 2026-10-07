<?php $this->layout('layouts/admin', ['title' => "Thẩm tra hồ sơ " . ($hoSo['ma_ho_so'] ?? '')]) ?>

<?php $this->section('content') ?>
<div class="page-header" style="margin-bottom: 24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                <a href="<?= e(url('/admin/phe-duyet/phong')) ?>" style="color:#64748B; text-decoration:none;">← Danh sách</a>
                <span style="color:#CBD5E1;">/</span>
                <span style="font-size:14px; font-weight:600; color:#0284C7;"><?= e($hoSo['ma_ho_so']) ?></span>
            </div>
            <h1 style="font-size: 22px; font-weight: 700; color: #0B2E59; margin: 0;">
                Thẩm tra & Ký duyệt Bước 1: <?= e($hoSo['ten_tthc']) ?>
            </h1>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="button" class="btn btn-danger" onclick="document.getElementById('modal-tra-lai').style.display='flex';" id="btn-tra-lai">
                ↩ Trả lại chuyên viên
            </button>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('modal-ky-so').style.display='flex';" id="btn-ky-so-duyet">
                ✍️ Ký số Duyệt bước 1 (SmartCA)
            </button>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
    <!-- Cột trái: Thông tin hồ sơ & Dự thảo kết quả -->
    <div style="display:flex; flex-direction:column; gap:20px;">
        <!-- Thẻ thông tin chung -->
        <div class="card" style="padding:20px;">
            <h3 style="font-size:16px; font-weight:700; color:#0B2E59; margin-top:0; margin-bottom:16px; border-bottom:1px solid #E2E8F0; padding-bottom:10px;">
                📄 Thông tin hồ sơ & Người nộp
            </h3>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:14px;">
                <div>
                    <div style="font-size:12px; color:#64748B;">Mã hồ sơ:</div>
                    <div style="font-weight:700; color:#0B2E59; font-size:15px;"><?= e($hoSo['ma_ho_so']) ?></div>
                </div>
                <div>
                    <div style="font-size:12px; color:#64748B;">Họ tên người nộp:</div>
                    <div style="font-weight:600;"><?= e($hoSo['ho_ten_nguoi_nop']) ?></div>
                </div>
                <div>
                    <div style="font-size:12px; color:#64748B;">Số CCCD / Định danh:</div>
                    <div><?= e($hoSo['so_cccd_nguoi_nop'] ?? '---') ?></div>
                </div>
                <div>
                    <div style="font-size:12px; color:#64748B;">Số điện thoại:</div>
                    <div><?= e($hoSo['so_dien_thoai_nguoi_nop'] ?? '---') ?></div>
                </div>
                <div>
                    <div style="font-size:12px; color:#64748B;">Chuyên viên thụ lý:</div>
                    <div style="font-weight:600; color:#0284C7;"><?= e($hoSo['ten_chuyen_vien'] ?? 'Chưa gán') ?></div>
                </div>
                <div>
                    <div style="font-size:12px; color:#64748B;">Hạn giải quyết cam kết:</div>
                    <div style="font-weight:700; color:#C8102E;"><?= date('d/m/Y H:i', strtotime($hoSo['ngay_hen_tra'])) ?></div>
                </div>
            </div>
        </div>

        <!-- Thẻ xem trước dự thảo tờ trình & kết quả -->
        <div class="card" style="padding:20px;">
            <h3 style="font-size:16px; font-weight:700; color:#0B2E59; margin-top:0; margin-bottom:16px; border-bottom:1px solid #E2E8F0; padding-bottom:10px;">
                📑 Dự thảo tờ trình & Quyết định giải quyết
            </h3>
            <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:20px; font-family:'Times New Roman', serif; font-size:15px; line-height:1.6; color:#0F172A; min-height:280px; box-shadow:inset 0 1px 3px rgba(0,0,0,0.05);">
                <div style="text-align:center; margin-bottom:20px;">
                    <div style="font-weight:bold; font-size:13px; text-transform:uppercase;">ỦY BAN NHÂN DÂN THÀNH PHỐ HẢI PHÒNG</div>
                    <div style="font-weight:bold; font-size:14px; text-transform:uppercase; color:#C8102E;"><?= e($hoSo['ten_don_vi'] ?? 'CƠ QUAN TIẾP NHẬN') ?></div>
                    <div style="margin:4px 0;">-------------------</div>
                    <div style="font-size:13px; font-style:italic;">Hải Phòng, ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?></div>
                </div>

                <div style="text-align:center; font-weight:bold; font-size:16px; margin-bottom:14px; text-transform:uppercase; color:#0B2E59;">
                    TỜ TRÌNH ĐỀ XUẤT PHÊ DUYỆT KẾT QUẢ GIẢI QUYẾT TTHC
                </div>

                <div style="margin-bottom:12px;">
                    <strong>Kính gửi:</strong> Lãnh đạo <?= e($hoSo['ten_don_vi'] ?? 'Ủy ban nhân dân') ?>
                </div>

                <p style="text-indent:24px; margin-bottom:10px;">
                    Căn cứ hồ sơ thủ tục hành chính số <strong><?= e($hoSo['ma_ho_so']) ?></strong> về việc yêu cầu giải quyết: <em><?= e($hoSo['ten_tthc']) ?></em> của ông/bà <strong><?= e($hoSo['ho_ten_nguoi_nop']) ?></strong>;
                </p>
                <p style="text-indent:24px; margin-bottom:10px;">
                    Sau khi đối soát tính pháp lý và thẩm định nội dung, Chuyên viên thụ lý <strong><?= e($hoSo['ten_chuyen_vien'] ?? 'Chuyên viên thụ lý') ?></strong> báo cáo hồ sơ đầy đủ điều kiện giải quyết theo đúng quy định hiện hành.
                </p>
                <p style="text-indent:24px; margin-bottom:16px;">
                    Phòng chuyên môn kính trình Lãnh đạo cơ quan xem xét, phê duyệt và ký số ban hành kết quả giải quyết TTHC theo quy định.
                </p>

                <div style="display:flex; justify-content:space-between; margin-top:30px; padding:0 20px;">
                    <div style="text-align:center; font-size:13px;">
                        <strong>CHUYÊN VIÊN THẨM ĐỊNH</strong><br>
                        <span style="font-style:italic; font-size:12px;">(Đã đối soát & lập dự thảo)</span><br><br><br>
                        <strong><?= e($hoSo['ten_chuyen_vien'] ?? 'Chuyên viên') ?></strong>
                    </div>
                    <div style="text-align:center; font-size:13px;">
                        <strong>LÃNH ĐẠO PHÒNG CHUYÊN MÔN</strong><br>
                        <span style="font-style:italic; font-size:12px;">(Ký số duyệt bước 1)</span><br><br><br>
                        <span style="color:#0284C7; font-weight:600;">[CHỜ KÝ DUYỆT BẰNG SMARTCA]</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cột phải: Tài liệu đính kèm & Lịch sử luân chuyển -->
    <div style="display:flex; flex-direction:column; gap:20px;">
        <!-- Danh sách tài liệu đính kèm -->
        <div class="card" style="padding:16px;">
            <h4 style="font-size:14px; font-weight:700; color:#0B2E59; margin-top:0; margin-bottom:12px;">
                📎 Thành phần hồ sơ đính kèm (<?= count($taiLieuList) ?>)
            </h4>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <?php if (empty($taiLieuList)): ?>
                    <div style="font-size:13px; color:#64748B;">Không có tài liệu đính kèm.</div>
                <?php else: ?>
                    <?php foreach ($taiLieuList as $tl): ?>
                        <div style="padding:10px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:6px; font-size:13px;">
                            <div style="font-weight:600; color:#1E293B; margin-bottom:4px; word-break:break-all;">
                                <?= e($tl['ten_tai_lieu']) ?>
                            </div>
                            <div style="font-size:11px; color:#64748B;">
                                SHA-256: <code style="color:#0284C7;"><?= substr($tl['sha256_tep'] ?? '', 0, 16) ?>...</code>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Lịch sử luân chuyển -->
        <div class="card" style="padding:16px;">
            <h4 style="font-size:14px; font-weight:700; color:#0B2E59; margin-top:0; margin-bottom:12px;">
                🕒 Lịch sử luân chuyển hồ sơ
            </h4>
            <div style="display:flex; flex-direction:column; gap:12px; font-size:12px;">
                <?php foreach ($luanChuyen as $lc): ?>
                    <div style="border-left:2px solid #0284C7; padding-left:10px;">
                        <div style="font-weight:600; color:#0B2E59;">
                            <?= e($lc['nguoi_thao_tac'] ?? 'Hệ thống') ?>
                        </div>
                        <div style="color:#64748B; font-size:11px; margin-bottom:2px;">
                            <?= date('d/m/Y H:i', strtotime($lc['thoi_gian_chuyen'])) ?>
                        </div>
                        <div style="color:#334155; font-style:italic;">
                            <?= e($lc['noi_dung_y_kien'] ?? 'Chuyển bước') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ký số Duyệt bước 1 với PIN SmartCA -->
<div id="modal-ky-so" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; border-radius:12px; width:100%; max-width:460px; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid #E2E8F0; padding-bottom:10px;">
            <h3 style="margin:0; font-size:18px; color:#0B2E59; display:flex; align-items:center; gap:8px;">
                <span style="color:#0284C7;">🔐</span> Ký số Duyệt Bước 1 (SmartCA)
            </h3>
            <button type="button" onclick="document.getElementById('modal-ky-so').style.display='none';" style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>

        <form method="POST" action="<?= e(url("/admin/phe-duyet/phong/{$hoSo['id']}/ky-so")) ?>">
            <?= csrf_field() ?>
            <div style="margin-bottom:14px;">
                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:6px;">Ý kiến duyệt tờ trình:</label>
                <textarea name="y_kien" class="form-control" rows="2" style="width:100%;">Duyệt bước 1 - Kính trình Lãnh đạo cơ quan phê duyệt kết quả giải quyết TTHC.</textarea>
            </div>

            <div style="margin-bottom:20px; background:#F0F9FF; border:1px solid #BAE6FD; padding:14px; border-radius:8px;">
                <label style="font-weight:700; font-size:13px; color:#0369A1; display:block; margin-bottom:6px;">
                    Mã PIN SmartCA cá nhân: <span style="color:#C8102E;">*</span>
                </label>
                <input type="password" name="pin" id="input-pin-smartca" class="form-control" placeholder="Nhập 6 số PIN SmartCA (Mẫu: 123456)" maxlength="6" required style="font-size:18px; letter-spacing:4px; text-align:center;">
                <div style="font-size:11px; color:#64748B; margin-top:6px; text-align:center;">
                    Môi trường thử nghiệm: Mã PIN chuẩn là <strong>123456</strong>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-ky-so').style.display='none';">Hủy</button>
                <button type="submit" class="btn btn-primary" id="btn-xac-nhan-ky-so">
                    ✓ Xác nhận & Ký số OpenSSL
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Trả lại chuyên viên -->
<div id="modal-tra-lai" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; border-radius:12px; width:100%; max-width:460px; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid #E2E8F0; padding-bottom:10px;">
            <h3 style="margin:0; font-size:18px; color:#C8102E;">↩ Trả lại hồ sơ về Chuyên viên</h3>
            <button type="button" onclick="document.getElementById('modal-tra-lai').style.display='none';" style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>

        <form method="POST" action="<?= e(url("/admin/phe-duyet/phong/{$hoSo['id']}/tra-lai")) ?>">
            <?= csrf_field() ?>
            <div style="margin-bottom:20px;">
                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:6px;">Lý do yêu cầu hoàn thiện lại: <span style="color:#C8102E;">*</span></label>
                <textarea name="ly_do_tra_lai" class="form-control" rows="3" required placeholder="Nêu rõ nội dung cần chuyên viên chỉnh sửa, bổ sung..." style="width:100%;"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-tra-lai').style.display='none';">Hủy</button>
                <button type="submit" class="btn btn-danger" id="btn-xac-nhan-tra-lai">Trả lại hồ sơ</button>
            </div>
        </form>
    </div>
</div>

<?php $this->endSection() ?>
