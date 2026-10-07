<?php
/**
 * View: Quản trị Mẫu Thông báo Đa kênh (§2.2 Báo cáo Kiến trúc)
 * HP-eGov - Trung tâm Phục vụ Hành chính công TP. Hải Phòng
 */
$this->layout('layouts/admin', ['title' => $title ?? 'Quản trị Mẫu Thông báo']);
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-navy fw-bold mb-1">
                <i class="bi bi-chat-square-quote-fill text-primary me-2"></i>Quản trị Mẫu Thông báo Đa kênh (Outbox Pattern)
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/admin/dashboard">Bàn làm việc</a></li>
                    <li class="breadcrumb-item">Cấu hình hệ thống</li>
                    <li class="breadcrumb-item active">Mẫu thông báo đa kênh</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="/admin/he-thong/hop-thu-mo-phong" class="btn btn-outline-primary">
                <i class="bi bi-phone me-1"></i>Hộp thư Mô phỏng Gateway
            </a>
        </div>
    </div>

    <!-- Hướng dẫn danh sách biến thay thế -->
    <div class="card border-0 shadow-sm mb-4 bg-light border-start border-primary border-4">
        <div class="card-body">
            <h6 class="fw-bold text-navy mb-2"><i class="bi bi-code-slash me-1"></i>Danh sách biến nội suy có sẵn trong mẫu tin nhắn:</h6>
            <div class="d-flex flex-wrap gap-2">
                <code>{ma_ho_so}</code> <span class="text-muted small me-2">- Mã hồ sơ QCVN 102</span>
                <code>{ten_tthc}</code> <span class="text-muted small me-2">- Tên thủ tục hành chính</span>
                <code>{ho_ten}</code> <span class="text-muted small me-2">- Họ tên người nộp</span>
                <code>{ngay_hen_tra}</code> <span class="text-muted small me-2">- Ngày giờ hẹn trả kết quả</span>
                <code>{link_tra_cuu}</code> <span class="text-muted small me-2">- Liên kết tra cứu tiến độ</span>
                <code>{so_van_ban}</code> <span class="text-muted small">- Số văn bản ban hành / Mẫu 04</span>
            </div>
        </div>
    </div>

    <!-- Danh sách Accordion các Mẫu thông báo -->
    <div class="accordion shadow-sm" id="accordionMauThongBao">
        <?php foreach ($templates as $idx => $tpl): ?>
            <div class="accordion-item border-0 mb-3 rounded overflow-hidden">
                <h2 class="accordion-header" id="heading_<?= $tpl['id'] ?>">
                    <button class="accordion-button <?= $idx === 0 ? '' : 'collapsed' ?> bg-white py-3 fw-bold text-navy" 
                            type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?= $tpl['id'] ?>">
                        <div class="d-flex align-items-center justify-content-between w-100 me-3">
                            <div>
                                <span class="badge bg-navy me-2"><?= e($tpl['ma_mau']) ?></span>
                                <?= e($tpl['ten_mau']) ?>
                            </div>
                            <div class="d-flex gap-2">
                                <span class="badge <?= $tpl['kenh_sms_active'] ? 'bg-success' : 'bg-secondary' ?>">SMS: <?= $tpl['kenh_sms_active'] ? 'BẬT' : 'TẮT' ?></span>
                                <span class="badge <?= $tpl['kenh_zalo_active'] ? 'bg-primary' : 'bg-secondary' ?>">Zalo: <?= $tpl['kenh_zalo_active'] ? 'BẬT' : 'TẮT' ?></span>
                                <span class="badge <?= $tpl['kenh_email_active'] ? 'bg-info' : 'bg-secondary' ?>">Email: <?= $tpl['kenh_email_active'] ? 'BẬT' : 'TẮT' ?></span>
                                <span class="badge <?= $tpl['kenh_inapp_active'] ? 'bg-warning text-dark' : 'bg-secondary' ?>">In-app: <?= $tpl['kenh_inapp_active'] ? 'BẬT' : 'TẮT' ?></span>
                            </div>
                        </div>
                    </button>
                </h2>
                <div id="collapse_<?= $tpl['id'] ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>" 
                     data-bs-parent="#accordionMauThongBao">
                    <div class="accordion-body bg-light p-4">
                        <form id="form_tpl_<?= $tpl['id'] ?>" onsubmit="event.preventDefault(); updateTemplate(<?= $tpl['id'] ?>);">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Tiêu đề mẫu thông báo</label>
                                    <input type="text" name="tieu_de_mau" class="form-control" value="<?= e($tpl['tieu_de_mau']) ?>" required>
                                </div>

                                <!-- Bật tắt kênh -->
                                <div class="col-md-12">
                                    <label class="form-label fw-bold d-block">Kích hoạt các kênh gửi tin:</label>
                                    <div class="d-flex flex-wrap gap-4 bg-white p-3 rounded border">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="kenh_sms_active" id="sms_<?= $tpl['id'] ?>" <?= $tpl['kenh_sms_active'] ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-semibold" for="sms_<?= $tpl['id'] ?>">SMS Brandname ("UBND TP HP")</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="kenh_zalo_active" id="zalo_<?= $tpl['id'] ?>" <?= $tpl['kenh_zalo_active'] ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-semibold" for="zalo_<?= $tpl['id'] ?>">Zalo ZNS (Trung tâm Phục vụ HCC)</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="kenh_email_active" id="email_<?= $tpl['id'] ?>" <?= $tpl['kenh_email_active'] ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-semibold" for="email_<?= $tpl['id'] ?>">Email điện tử</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="kenh_inapp_active" id="inapp_<?= $tpl['id'] ?>" <?= $tpl['kenh_inapp_active'] ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-semibold" for="inapp_<?= $tpl['id'] ?>">Thông báo In-app Cổng DVC</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nội dung từng kênh -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-navy"><i class="bi bi-chat-dots me-1"></i>Nội dung SMS Brandname</label>
                                    <textarea name="noi_dung_sms" class="form-control" rows="4" required><?= e($tpl['noi_dung_sms']) ?></textarea>
                                    <div class="form-text small">Khuyến nghị tiếng Việt không dấu để tối ưu 1 bản tin SMS.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-primary"><i class="bi bi-chat-square-text me-1"></i>Nội dung tin nhắn Zalo ZNS</label>
                                    <textarea name="noi_dung_zalo" class="form-control" rows="4" required><?= e($tpl['noi_dung_zalo']) ?></textarea>
                                    <div class="form-text small">Có hỗ trợ mã QR và liên kết tra cứu trực tiếp.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-info"><i class="bi bi-envelope me-1"></i>Nội dung Email (HTML)</label>
                                    <textarea name="noi_dung_email" class="form-control font-monospace small" rows="4"><?= e($tpl['noi_dung_email'] ?? '') ?></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-warning"><i class="bi bi-bell me-1"></i>Nội dung In-App Cổng DVC</label>
                                    <textarea name="noi_dung_inapp" class="form-control" rows="4" required><?= e($tpl['noi_dung_inapp']) ?></textarea>
                                </div>

                                <div class="col-12 text-end mt-3">
                                    <button type="submit" class="btn btn-navy px-4">
                                        <i class="bi bi-save me-1"></i>Lưu cấu hình mẫu tin
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function updateTemplate(id) {
    const form = document.getElementById('form_tpl_' + id);
    const formData = new FormData(form);
    formData.append('csrf_token', '<?= e(csrf_token()) ?>');

    fetch('/admin/he-thong/mau-thong-bao/' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert(d.message);
            window.location.reload();
        } else {
            alert(d.message || 'Lỗi khi cập nhật.');
        }
    })
    .catch(() => alert('Lỗi kết nối máy chủ.'));
}
</script>
