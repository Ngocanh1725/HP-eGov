<?php $this->layout('layouts/portal', ['title' => $title ?? 'Kiểm tra Chữ ký số & Tính toàn vẹn kết quả TTHC']) ?>

<?php $this->section('content') ?>
<div class="container" style="max-width: 900px; margin: 30px auto; padding: 0 16px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #0B2E59; font-size: 26px; font-weight: 800; margin-bottom: 8px;">
            🔍 Tra cứu & Xác thực Chữ ký số Điện tử
        </h1>
        <p style="color: #64748B; font-size: 15px; max-width: 650px; margin: 0 auto;">
            Hệ thống Một cửa điện tử liên thông TP. Hải Phòng – Xác minh tính toàn vẹn tài liệu và chứng thư số theo Luật Giao dịch điện tử
        </p>
    </div>

    <!-- 2 phương thức kiểm tra -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <!-- Cách 1: Tra cứu theo mã -->
        <div class="card" style="padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #E2E8F0;">
            <h3 style="color: #0B2E59; font-size: 16px; font-weight: 700; margin-top: 0; margin-bottom: 14px; display:flex; align-items:center; gap:8px;">
                <span>🔢</span> Tra cứu theo Mã hồ sơ / Mã kết quả
            </h3>
            <form method="GET" action="<?= e(url('/kiem-tra-chu-ky')) ?>">
                <div style="margin-bottom: 14px;">
                    <label style="font-size: 13px; font-weight: 600; color: #475569; display:block; margin-bottom: 6px;">
                        Nhập Mã hồ sơ, Số quyết định hoặc Mã tra cứu:
                    </label>
                    <input type="text" name="ma_tra_cuu" value="<?= e($maTraCuu ?? '') ?>" class="form-control" placeholder="Ví dụ: 000.04.15.H21-..." required style="width: 100%;">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; font-weight: 600;" id="btn-tra-cuu-ma">
                    Tra cứu chữ ký số
                </button>
            </form>
        </div>

        <!-- Cách 2: Tải tệp lên kiểm tra trực tiếp -->
        <div class="card" style="padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #E2E8F0;">
            <h3 style="color: #0B2E59; font-size: 16px; font-weight: 700; margin-top: 0; margin-bottom: 14px; display:flex; align-items:center; gap:8px;">
                <span>📄</span> Tải tệp lên đối soát SHA-256
            </h3>
            <form method="POST" action="<?= e(url('/kiem-tra-chu-ky/verify-file')) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div style="margin-bottom: 10px;">
                    <label style="font-size: 13px; font-weight: 600; color: #475569; display:block; margin-bottom: 4px;">
                        Chọn tệp văn bản (PDF, DOCX...): <span style="color:#C8102E;">*</span>
                    </label>
                    <input type="file" name="file_tai_lieu" class="form-control" required style="width: 100%; font-size: 13px;">
                </div>
                <div style="margin-bottom: 14px;">
                    <label style="font-size: 12px; color: #64748B; display:block; margin-bottom: 4px;">
                        Tệp chữ ký .sig rời (nếu có):
                    </label>
                    <input type="file" name="file_chu_ky" class="form-control" style="width: 100%; font-size: 13px;">
                </div>
                <button type="submit" class="btn btn-secondary" style="width: 100%; font-weight: 600;" id="btn-kiem-tra-tep">
                    Kiểm tra tính toàn vẹn
                </button>
            </form>
        </div>
    </div>

    <!-- Kết quả kiểm tra -->
    <?php if (isset($result)): ?>
        <?php if (!empty($result['file_check'])): ?>
            <!-- Kết quả kiểm tra tệp -->
            <?php $chk = $result['check_result'] ?? []; ?>
            <div class="card" style="padding: 24px; border-left: 6px solid <?= !empty($chk['valid']) ? '#16A34A' : '#DC2626' ?>; margin-bottom: 30px;">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom: 16px;">
                    <span style="font-size: 32px;"><?= !empty($chk['valid']) ? '✅' : '❌' ?></span>
                    <div>
                        <h2 style="font-size: 18px; font-weight: 700; margin: 0; color: <?= !empty($chk['valid']) ? '#16A34A' : '#DC2626' ?>;">
                            <?= !empty($chk['valid']) ? 'TÀI LIỆU TOÀN VẸN & CHỮ KÝ SỐ HỢP LỆ' : 'CẢNH BÁO: CHỮ KÝ SỐ KHÔNG HỢP LỆ HOẶC TÀI LIỆU BỊ SỬA ĐỔI' ?>
                        </h2>
                        <div style="font-size: 13px; color: #64748B;">Tệp kiểm tra: <strong><?= e($result['file_name']) ?></strong></div>
                    </div>
                </div>

                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px; font-size: 13px; line-height: 1.8;">
                    <div><strong>Người / Tổ chức ký:</strong> <?= e($chk['signer'] ?? '---') ?></div>
                    <div><strong>Số Serial chứng thư:</strong> <code><?= e($chk['serial'] ?? '---') ?></code></div>
                    <div><strong>Nhà cung cấp CA:</strong> <?= e($chk['nha_cung_cap'] ?? 'SMARTCA / BAN_CO_YEU') ?></div>
                    <div><strong>Thời gian ký:</strong> <?= e($chk['signed_at'] ?? '---') ?></div>
                    <div><strong>Mã băm SHA-256 tệp hiện tại:</strong> <code style="word-break:break-all;"><?= e($chk['sha256_current'] ?? '') ?></code></div>
                    <?php if (!empty($chk['error'])): ?>
                        <div style="margin-top: 10px; color: #DC2626; font-weight: 600;">
                            Chi tiết lỗi: <?= e($chk['error']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif (!empty($result['tim_thay'])): ?>
            <!-- Kết quả tra cứu hồ sơ -->
            <?php $kq = $result['ket_qua']; ?>
            <div class="card" style="padding: 24px; border-left: 6px solid #16A34A; margin-bottom: 30px;">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom: 16px;">
                    <span style="font-size: 32px;">🏛️</span>
                    <div>
                        <h2 style="font-size: 18px; font-weight: 700; margin: 0; color: #0B2E59;">
                            KẾT QUẢ GIẢI QUYẾT TTHC ĐIỆN TỬ CHÍNH THỨC
                        </h2>
                        <div style="font-size: 13px; color: #64748B;">
                            Số quyết định: <strong><?= e($kq['so_quyet_dinh']) ?></strong> | Mã hồ sơ: <strong><?= e($kq['ma_ho_so']) ?></strong>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 20px; font-size: 13px;">
                    <div><strong>Thủ tục:</strong> <?= e($kq['ten_tthc']) ?></div>
                    <div><strong>Người nộp:</strong> <?= e($kq['ho_ten_nguoi_nop']) ?></div>
                    <div><strong>Cơ quan ban hành:</strong> <?= e($kq['ten_don_vi']) ?></div>
                    <div><strong>Ngày ký:</strong> <?= date('d/m/Y H:i', strtotime($kq['ngay_ky'])) ?></div>
                </div>

                <h4 style="font-size: 14px; font-weight: 700; color: #0B2E59; margin-bottom: 10px;">
                    Chuỗi chữ ký số xác thực:
                </h4>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php foreach ($result['chu_ky'] as $ck): ?>
                        <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 12px; font-size: 13px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 4px;">
                                <strong style="color: #166534;"><?= e($ck['ten_chu_the']) ?></strong>
                                <span class="badge" style="background:#DCFCE7; color:#15803D; font-weight:bold;">✓ Chứng thư số hợp lệ</span>
                            </div>
                            <div style="color: #374151; font-size: 12px;">Loại chữ ký: <?= e($ck['loai_chu_ky']) ?></div>
                            <div style="color: #374151; font-size: 12px;">Serial: <code><?= e($ck['so_serial']) ?></code> (<?= e($ck['nha_cung_cap']) ?>)</div>
                            <div style="color: #374151; font-size: 12px;">Thời gian ký: <?= e($ck['thoi_gian_ky']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>
            <div class="card" style="padding: 24px; text-align: center; color: #DC2626; border-left: 6px solid #DC2626; margin-bottom: 30px;">
                <div style="font-size: 28px; margin-bottom: 8px;">⚠️</div>
                <div style="font-weight: 700; font-size: 16px;"><?= e($result['message']) ?></div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php $this->endSection() ?>
