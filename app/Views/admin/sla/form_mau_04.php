<?php
/**
 * View: Soạn thảo & Ký số Phiếu xin lỗi Mẫu số 04 (TT 01/2018/TT-VPCP)
 * HP-eGov - Trung tâm Phục vụ Hành chính công TP. Hải Phòng
 */
$this->layout('layouts/admin', ['title' => $title ?? 'Lập Văn bản xin lỗi Mẫu 04']);
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-navy fw-bold mb-1">
                <i class="bi bi-file-earmark-medical-fill text-danger me-2"></i>Lập Phiếu Xin lỗi & Hẹn lại Ngày trả kết quả (Mẫu số 04)
            </h1>
            <div class="text-muted small">Căn cứ: Thông tư số 01/2018/TT-VPCP và Nghị định 61/2018/NĐ-CP của Chính phủ</div>
        </div>
        <div>
            <a href="/admin/sla/danh-sach-qua-han" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Cột trái: Thông tin hồ sơ & Form soạn thảo -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-navy text-white py-3">
                    <h5 class="card-title mb-0 fs-6 fw-bold">
                        <i class="bi bi-info-circle me-2"></i>Thông tin Hồ sơ Quá hạn SLA
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small">Mã định danh hồ sơ:</label>
                            <div class="fw-bold fs-6 text-navy"><?= e($hoSo['ma_ho_so']) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small">Người nộp hồ sơ:</label>
                            <div class="fw-bold"><?= e($hoSo['ho_ten_nguoi_nop']) ?> (<?= e($hoSo['so_cccd_nguoi_nop'] ?? '') ?>)</div>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small">Thủ tục hành chính:</label>
                            <div class="fw-semibold text-secondary"><?= e($hoSo['ten_tthc']) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small">Thời điểm tiếp nhận:</label>
                            <div><?= !empty($hoSo['ngay_tiepNhan']) ? date('d/m/Y H:i', strtotime($hoSo['ngay_tiepNhan'])) : '---' ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small">Ngày hẹn trả trước đây:</label>
                            <div class="text-danger fw-bold"><?= !empty($hoSo['ngay_hen_tra']) ? date('d/m/Y H:i', strtotime($hoSo['ngay_hen_tra'])) : '---' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fs-6 fw-bold text-navy">
                        <i class="bi bi-pencil-square me-2"></i>Nội dung Phiếu Xin lỗi
                    </h5>
                </div>
                <div class="card-body">
                    <form id="formMau04" onsubmit="event.preventDefault(); openSmartCaModal();">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Số, ký hiệu văn bản xin lỗi <span class="text-danger">*</span></label>
                            <input type="text" id="so_van_ban" name="so_van_ban" class="form-control" 
                                   value="<?= e($hoSo['vbxl_so'] ?? $soGoiY) ?>" required>
                            <div class="form-text small">Ví dụ: 12/XL-UBND hoặc XL-1007/UBND-VP</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lý do chưa thể trả kết quả đúng hẹn <span class="text-danger">*</span></label>
                            <select class="form-select mb-2" onchange="if(this.value) document.getElementById('ly_do_qua_han').value = this.value;">
                                <option value="">-- Chọn lý do mẫu chuẩn hóa --</option>
                                <option value="Hồ sơ có tính chất phức tạp, cần thêm thời gian phối hợp xác minh thực địa và đối soát dữ liệu với các cơ quan chuyên môn liên quan.">Hồ sơ phức tạp, cần xác minh thực địa liên ngành</option>
                                <option value="Hệ thống cơ sở dữ liệu chuyên ngành đang trong quá trình bảo trì kỹ thuật, việc truyền nhận dữ liệu xác thực bị gián đoạn ngoài ý muốn.">Gián đoạn kết nối CSDL chuyên ngành</option>
                                <option value="Số lượng hồ sơ tiếp nhận trong kỳ tăng đột biến, cơ quan đang tập trung tối đa nhân lực để giải quyết dứt điểm cho công dân.">Lượng hồ sơ tăng đột biến trong kỳ</option>
                            </select>
                            <textarea id="ly_do_qua_han" name="ly_do_qua_han" rows="4" class="form-control" 
                                      placeholder="Ghi rõ lý do quá hạn giải quyết..." required><?= e($hoSo['vbxl_ly_do'] ?? 'Hồ sơ có tính chất phức tạp, cần thêm thời gian phối hợp thẩm tra thực địa và đối soát cơ sở dữ liệu chuyên ngành giữa các sở, ban, ngành liên quan để đảm bảo quyền lợi hợp pháp tối đa cho công dân.') ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Ngày hẹn trả lại kết quả mới <span class="text-danger">*</span></label>
                            <input type="datetime-local" id="ngay_hen_moi" name="ngay_hen_moi" class="form-control" 
                                   value="<?= date('Y-m-d\TH:i', strtotime($hoSo['vbxl_ngay_hen_moi'] ?? $ngayHenMoiGoiY)) ?>" required>
                            <div class="form-text small text-primary">
                                <i class="bi bi-clock me-1"></i>Hệ thống đã tự động tính toán loại trừ ngày nghỉ lễ và cuối tuần theo chuẩn SLA.
                            </div>
                        </div>

                        <div class="alert alert-warning border-0 small d-flex align-items-center">
                            <i class="bi bi-shield-lock-fill fs-4 me-3 text-warning"></i>
                            <div>
                                <strong>Lưu ý nghiệp vụ:</strong> Việc ban hành Mẫu 04 thuộc thẩm quyền của Lãnh đạo cơ quan. Hệ thống sẽ tự động ký số OpenSSL bằng chứng thư số của Lãnh đạo và gửi thông báo đa kênh (SMS Brandname, Zalo ZNS, Email) cho công dân.
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="/admin/sla/danh-sach-qua-han" class="btn btn-light border">Hủy bỏ</a>
                            <button type="submit" class="btn btn-danger px-4 fw-bold">
                                <i class="bi bi-pen-fill me-1"></i>Xác nhận & Ký số ban hành
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cột phải: Bản xem trước thể thức A4 hành chính -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold small text-uppercase text-secondary">
                        <i class="bi bi-file-earmark-text me-1"></i>Xem trước Thể thức Mẫu số 04
                    </span>
                    <span class="badge bg-danger">Dự thảo</span>
                </div>
                <div class="card-body p-4 bg-white" style="font-family: 'Times New Roman', Times, serif; font-size: 13px; line-height: 1.5; border: 1px solid #e2e8f0;">
                    <div class="text-center mb-3">
                        <div class="fw-bold text-uppercase" style="font-size: 11px;"><?= e($hoSo['ten_don_vi']) ?></div>
                        <div class="fw-bold" style="font-size: 12px;">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                        <div class="fst-italic" style="font-size: 11px;">Độc lập - Tự do - Hạnh phúc</div>
                        <div style="font-size: 10px; border-bottom: 1px solid #333; width: 140px; margin: 3px auto 8px;"></div>
                        <div class="fst-italic text-muted" style="font-size: 11px;">Hải Phòng, ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?></div>
                    </div>

                    <div class="text-center my-3">
                        <div class="fw-bold text-uppercase" style="font-size: 14px;">PHIẾU XIN LỖI VÀ HẸN LẠI NGÀY TRẢ KẾT QUẢ</div>
                        <div class="fst-italic" style="font-size: 11px;">(Mẫu số 04 - Thông tư số 01/2018/TT-VPCP)</div>
                    </div>

                    <div class="mb-2">
                        <strong>Kính gửi:</strong> Ông/Bà <?= e($hoSo['ho_ten_nguoi_nop']) ?>
                    </div>
                    <div class="mb-2" style="text-align: justify;">
                        Ngày <?= !empty($hoSo['ngay_tiepNhan']) ? date('d/m/Y', strtotime($hoSo['ngay_tiepNhan'])) : date('d/m/Y') ?>, <?= e($hoSo['ten_don_vi']) ?> đã tiếp nhận giải quyết hồ sơ thủ tục hành chính mã số <strong><?= e($hoSo['ma_ho_so']) ?></strong> (<?= e($hoSo['ten_tthc']) ?>).
                    </div>
                    <div class="mb-2" style="text-align: justify;">
                        Thời gian hẹn trả kết quả: <?= !empty($hoSo['ngay_hen_tra']) ? date('d/m/Y H:i', strtotime($hoSo['ngay_hen_tra'])) : '---' ?>.
                    </div>
                    <div class="mb-2" style="text-align: justify;">
                        Tuy nhiên, do nguyên nhân: <span id="previewLyDo" class="fst-italic text-primary"><?= e($hoSo['vbxl_ly_do'] ?? 'Hồ sơ phức tạp cần thêm thời gian xác minh.') ?></span>; cơ quan chúng tôi chưa thể hoàn thành giải quyết hồ sơ theo đúng thời gian đã hẹn.
                    </div>
                    <div class="mb-3" style="text-align: justify;">
                        Cơ quan xin chân thành cáo lỗi và xin hẹn lại ngày trả kết quả vào: <strong id="previewHenMoi" class="text-danger"><?= date('d/m/Y H:i', strtotime($ngayHenMoiGoiY)) ?></strong>.
                    </div>

                    <div class="row mt-4">
                        <div class="col-6"></div>
                        <div class="col-6 text-center">
                            <div class="fw-bold text-uppercase" style="font-size: 11px;">LÃNH ĐẠO CƠ QUAN</div>
                            <div class="fst-italic" style="font-size: 10px;">(Ký số điện tử)</div>
                            <div class="my-3 text-muted">
                                <span class="badge bg-light text-secondary border">[Ký số SmartCA]</span>
                            </div>
                            <div class="fw-bold"><?= e($user['ho_ten']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nhập PIN SmartCA để ký số Mẫu 04 -->
<div class="modal fade" id="smartCaModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-navy text-white">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="bi bi-shield-lock-fill me-2"></i>Xác thực Ký số Lãnh đạo Cơ quan (SmartCA)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3">
                    <div class="avatar-lg bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="bi bi-file-earmark-check fs-2"></i>
                    </div>
                </div>
                <h6 class="fw-bold text-navy mb-1">Ký số Phiếu xin lỗi Mẫu 04</h6>
                <p class="text-muted small mb-3">
                    Chứng thư số: <strong><?= e($user['ho_ten']) ?></strong> (Lãnh đạo cơ quan)<br>
                    Hồ sơ: <strong><?= e($hoSo['ma_ho_so']) ?></strong>
                </p>

                <div class="mb-3 text-start">
                    <label class="form-label small fw-semibold">Mã PIN SmartCA <span class="text-danger">*</span></label>
                    <input type="password" id="modal_pin" class="form-control text-center fs-4 letter-spacing-lg" 
                           placeholder="••••••" maxlength="6" autofocus autocomplete="off">
                    <div class="form-text small text-center text-muted">Nhập mã PIN demo: <code>123456</code></div>
                </div>

                <div id="modalAlert" class="alert alert-danger py-2 small d-none"></div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" id="btnSubmitSign" class="btn btn-danger px-4" onclick="executeSignMau04()">
                    <i class="bi bi-check-lg me-1"></i>Ký số ngay
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('ly_do_qua_han').addEventListener('input', function() {
    document.getElementById('previewLyDo').innerText = this.value || '...';
});
document.getElementById('ngay_hen_moi').addEventListener('change', function() {
    const val = this.value;
    if (val) {
        const d = new Date(val);
        document.getElementById('previewHenMoi').innerText = d.toLocaleString('vi-VN');
    }
});

let smartCaModalInstance = null;
function openSmartCaModal() {
    const pinInput = document.getElementById('modal_pin');
    pinInput.value = '';
    document.getElementById('modalAlert').classList.add('d-none');
    
    smartCaModalInstance = new bootstrap.Modal(document.getElementById('smartCaModal'));
    smartCaModalInstance.show();
    setTimeout(() => pinInput.focus(), 300);
}

function executeSignMau04() {
    const pin = document.getElementById('modal_pin').value.trim();
    const alertBox = document.getElementById('modalAlert');
    const submitBtn = document.getElementById('btnSubmitSign');

    if (!pin) {
        alertBox.innerText = 'Vui lòng nhập mã PIN SmartCA.';
        alertBox.classList.remove('d-none');
        return;
    }

    alertBox.classList.add('d-none');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang ký số...';

    const formData = new FormData();
    formData.append('csrf_token', '<?= e(csrf_token()) ?>');
    formData.append('pin', pin);
    formData.append('so_van_ban', document.getElementById('so_van_ban').value);
    formData.append('ly_do_qua_han', document.getElementById('ly_do_qua_han').value);
    formData.append('ngay_hen_moi', document.getElementById('ngay_hen_moi').value);

    fetch('/admin/sla/ky-so-mau-04/<?= $hoSo['id'] ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Ký số ngay';

        if (data.success) {
            smartCaModalInstance.hide();
            alert(data.message);
            window.location.href = data.redirect || '/admin/sla/danh-sach-qua-han';
        } else {
            alertBox.innerText = data.message || 'Lỗi khi ký số.';
            alertBox.classList.remove('d-none');
        }
    })
    .catch(err => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Ký số ngay';
        alertBox.innerText = 'Lỗi kết nối máy chủ.';
        alertBox.classList.remove('d-none');
    });
}
</script>
