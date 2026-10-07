<?php $this->layout('layouts/admin', ['title' => 'Trung tâm Báo cáo Thống kê']) ?>

<div class="admin-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold text-primary mb-1">
                📊 Trung tâm Báo cáo Thống kê Dịch vụ công (PH08)
            </h1>
            <p class="text-muted mb-0">
                Hệ thống báo cáo hành chính chuẩn hóa theo quy định của Chính phủ, Văn phòng Chính phủ và UBND TP. Hải Phòng
            </p>
        </div>
        <div>
            <a href="<?= e(url('/admin/ioc')) ?>" class="btn btn-warning text-dark fw-bold btn-sm">
                🎯 Trung tâm IOC Thời gian thực
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Báo cáo 1: Thông tư 01/2018/TT-VPCP -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 rounded-3 hover-shadow transition">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="fs-1 text-primary">📑</div>
                    <div>
                        <span class="badge bg-primary-subtle text-primary fw-bold">MẪU CHUẨN QUỐC GIA</span>
                        <h5 class="fw-bold text-dark mb-0 mt-1">Thông tư 01/2018/TT-VPCP</h5>
                    </div>
                </div>
                <p class="text-muted small flex-grow-1">
                    Báo cáo tình hình tiếp nhận và giải quyết TTHC (Biểu mẫu 01): Tồn kỳ trước, Tiếp nhận mới (Trực tiếp, Trực tuyến), Đã giải quyết (Trước hạn, Đúng hạn, Quá hạn), Đang giải quyết, Tỷ lệ đúng hạn.
                </p>
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <span class="small text-muted">Xuất CSV & In A4</span>
                    <a href="<?= e(url('/admin/bao-cao/thong-tu-01')) ?>" class="btn btn-sm btn-primary">
                        Xem báo cáo →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Báo cáo 2: Nghị định 107/2021/NĐ-CP -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 rounded-3 hover-shadow transition">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="fs-1 text-info">💾</div>
                    <div>
                        <span class="badge bg-info-subtle text-info fw-bold">SỐ HÓA HÀNH CHÍNH</span>
                        <h5 class="fw-bold text-dark mb-0 mt-1">Nghị định 107/2021/NĐ-CP</h5>
                    </div>
                </div>
                <p class="text-muted small flex-grow-1">
                    Thống kê tỷ lệ số hóa thành phần hồ sơ đầu vào (Scan, OCR, Ký số), số hóa kết quả giải quyết TTHC điện tử và tỷ lệ đồng bộ vào Kho dữ liệu điện tử của tổ chức/cá nhân.
                </p>
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <span class="small text-muted">Chỉ tiêu: 100%</span>
                    <a href="<?= e(url('/admin/bao-cao/so-hoa')) ?>" class="btn btn-sm btn-info text-white">
                        Xem báo cáo →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Báo cáo 3: Quá hạn & Mẫu 04 -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 rounded-3 hover-shadow transition">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="fs-1 text-danger">⚠️</div>
                    <div>
                        <span class="badge bg-danger-subtle text-danger fw-bold">KIỂM SOÁT SLA</span>
                        <h5 class="fw-bold text-dark mb-0 mt-1">Quá hạn & Mẫu số 04</h5>
                    </div>
                </div>
                <p class="text-muted small flex-grow-1">
                    Danh sách các hồ sơ bị trễ hạn xử lý, số giờ trễ hạn thực tế, tình trạng ban hành Văn bản xin lỗi công dân kèm ngày hẹn trả mới theo quy định tại Nghị định 61/2018/NĐ-CP.
                </p>
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <span class="small text-muted">Trừ điểm thi đua</span>
                    <a href="<?= e(url('/admin/bao-cao/qua-han')) ?>" class="btn btn-sm btn-danger">
                        Xem báo cáo →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Báo cáo 4: Hài lòng SIPAS -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 rounded-3 hover-shadow transition">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="fs-1 text-success">⭐</div>
                    <div>
                        <span class="badge bg-success-subtle text-success fw-bold">ĐÁNH GIÁ SIPAS</span>
                        <h5 class="fw-bold text-dark mb-0 mt-1">Mức độ Hài lòng Công dân</h5>
                    </div>
                </div>
                <p class="text-muted small flex-grow-1">
                    Đo lường mức độ hài lòng của công dân và tổ chức theo 5 nhóm tiêu chí chuẩn Quyết định 876/QĐ-BNV: Tiếp cận, Thủ tục, Công chức, Tiến độ kết quả và Tiếp nhận phản ánh.
                </p>
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <span class="small text-muted">Chỉ tiêu: ≥ 92%</span>
                    <a href="<?= e(url('/admin/bao-cao/hai-long')) ?>" class="btn btn-sm btn-success">
                        Xem báo cáo →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Báo cáo 5: Trực tuyến Toàn trình -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 rounded-3 hover-shadow transition">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="fs-1 text-primary">🌐</div>
                    <div>
                        <span class="badge bg-primary-subtle text-primary fw-bold">CHUYỂN ĐỔI SỐ</span>
                        <h5 class="fw-bold text-dark mb-0 mt-1">DVC Trực tuyến Toàn trình</h5>
                    </div>
                </div>
                <p class="text-muted small flex-grow-1">
                    Thống kê tỷ lệ hồ sơ phát sinh trực tuyến theo mức độ dịch vụ công: Toàn trình, Một phần, Dịch vụ công khác, đối chiếu chỉ tiêu vàng ≥ 80% của thành phố.
                </p>
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <span class="small text-muted">Chỉ tiêu: ≥ 80%</span>
                    <a href="<?= e(url('/admin/bao-cao/truc-tuyen')) ?>" class="btn btn-sm btn-outline-primary">
                        Xem báo cáo →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Báo cáo 6: Liên thông đa cơ quan -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 rounded-3 hover-shadow transition">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="fs-1 text-secondary">🔄</div>
                    <div>
                        <span class="badge bg-secondary-subtle text-secondary fw-bold">LIÊN THÔNG NGANG/DỌC</span>
                        <h5 class="fw-bold text-dark mb-0 mt-1">Phối hợp Liên thông Cơ quan</h5>
                    </div>
                </div>
                <p class="text-muted small flex-grow-1">
                    Thống kê các luồng luân chuyển hồ sơ liên thông ngang giữa các Sở (Xây dựng, TN&MT, KH&ĐT, GTVT) và liên thông dọc (UBND TP ↔ Quận/Huyện ↔ Xã/Phường), thời gian phối hợp.
                </p>
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <span class="small text-muted">BPMN-lite Luân chuyển</span>
                    <a href="<?= e(url('/admin/bao-cao/lien-thong')) ?>" class="btn btn-sm btn-outline-secondary">
                        Xem báo cáo →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
