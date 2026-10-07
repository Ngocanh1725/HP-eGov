<?php
$this->layout('layouts/admin', ['title' => $title, 'breadcrumbs' => $breadcrumbs]);
$this->startSection('content');
?>

<div class="mb-4">
    <h2>Thư viện Thành phần Giao diện (HP-eGov UI Kit)</h2>
    <p class="text-muted">
        Hệ thống Design System hành chính chuẩn hóa phục vụ các phân hệ Một cửa, Thụ lý, Lãnh đạo và Cổng DVC Hải Phòng.
        Màu sắc: Navy <code>#0B2E59</code>, Đỏ cờ <code>#C8102E</code>, Vàng kim <code>#D4A017</code>, Font: Be Vietnam Pro.
    </p>
</div>

<!-- =========================================================================
     1. THẺ KPI (KPI CARDS)
     ========================================================================= -->
<section class="mb-4">
    <h3 class="mb-3">1. Thẻ Chỉ số Điều hành (KPI Cards)</h3>
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-info">
                <div class="kpi-label">Tổng tiếp nhận</div>
                <div class="kpi-value">1,248</div>
                <div class="kpi-subtext"><span class="text-success fw-bold">↑ 12%</span> so với tháng trước</div>
            </div>
            <div class="kpi-icon-box">📥</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-info">
                <div class="kpi-label">Đang thụ lý</div>
                <div class="kpi-value text-warning" style="color:var(--color-sla-yellow);">186</div>
                <div class="kpi-subtext">Trong hạn quy định</div>
            </div>
            <div class="kpi-icon-box" style="background:#FEF3C7;color:#D97706;">⏳</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-info">
                <div class="kpi-label">Tỷ lệ đúng hạn</div>
                <div class="kpi-value text-success">98.6%</div>
                <div class="kpi-subtext">Chỉ tiêu thành phố: ≥ 98%</div>
            </div>
            <div class="kpi-icon-box" style="background:#DCFCE7;color:#16A34A;">🎯</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-info">
                <div class="kpi-label">Quá hạn (Cảnh báo)</div>
                <div class="kpi-value text-danger">3</div>
                <div class="kpi-subtext">Yêu cầu lập Mẫu 04</div>
            </div>
            <div class="kpi-icon-box" style="background:#FEE2E2;color:#DC2626;">🚨</div>
        </div>
    </div>
</section>

<!-- =========================================================================
     2. HUY HIỆU TRẠNG THÁI & SLA BADGES
     ========================================================================= -->
<section class="mb-4">
    <div class="card" style="background:var(--bg-surface);padding:1.5rem;border-radius:var(--radius-lg);border:1px solid var(--border-color);">
        <h3 class="mb-3">2. Huy hiệu Trạng thái (9 Trạng thái TT 01/2018) & Kiểm soát SLA</h3>
        
        <div class="mb-3">
            <h4 style="font-size:0.95rem;color:var(--text-muted);margin-bottom:0.75rem;">A. 9 TRẠNG THÁI HỒ SƠ:</h4>
            <div class="d-flex flex-wrap gap-2">
                <?php
                $allStatuses = [
                    'CHO_TIEP_NHAN', 'MOI_TIEP_NHAN', 'DANG_XU_LY',
                    'CHO_BO_SUNG', 'CHO_PHE_DUYET', 'HOAN_THANH',
                    'DA_TRA_KET_QUA', 'TU_CHOI', 'DA_HUY'
                ];
                foreach ($allStatuses as $st):
                    $this->partial('partials/status_badge', ['status' => $st]);
                endforeach;
                ?>
            </div>
        </div>

        <div>
            <h4 style="font-size:0.95rem;color:var(--text-muted);margin-bottom:0.75rem;">B. 3 MÀU KIỂM SOÁT TIẾN ĐỘ SLA (§2.2):</h4>
            <div class="d-flex flex-wrap gap-3 align-center">
                <?php $this->partial('partials/sla_badge', ['mau' => 'XANH', 'gioConLai' => 36]); ?>
                <?php $this->partial('partials/sla_badge', ['mau' => 'VANG', 'gioConLai' => 12]); ?>
                <?php $this->partial('partials/sla_badge', ['mau' => 'DO', 'gioConLai' => 0]); ?>
                <span class="text-muted" style="font-size:0.85rem;">(Màu Đỏ nhấp nháy báo động vi phạm thời hạn)</span>
            </div>
        </div>
    </div>
</section>

<!-- =========================================================================
     3. STEPPER 5 BƯỚC & WORKFLOW TIMELINE
     ========================================================================= -->
<section class="mb-4">
    <div class="card" style="background:var(--bg-surface);padding:1.5rem;border-radius:var(--radius-lg);border:1px solid var(--border-color);">
        <h3 class="mb-2">3. Stepper 5 Bước Quy trình Tiếp nhận & Thụ lý (§2.1)</h3>
        <?php $this->partial('partials/stepper', ['currentStep' => 3]); ?>

        <h3 class="mt-4 mb-2">Nhật ký Chuỗi Luân chuyển Hồ sơ (Timeline)</h3>
        <?php
        $demoTimeline = [
            ['title' => 'B1. Tiếp nhận hồ sơ giấy tại Bộ phận Một cửa', 'actor' => 'Nguyễn Văn Minh (Cán bộ Một cửa - Q. Hồng Bàng)', 'time' => '15/01/2026 08:30', 'note' => 'Hồ sơ đầy đủ thành phần hợp lệ. Đã quét số hóa bản giấy và cấp Giấy hẹn Mẫu 01.', 'completed' => true],
            ['title' => 'B2. Lãnh đạo Phòng phân công thụ lý', 'actor' => 'Trần Thị Thu (Phó Trưởng phòng Quản lý đô thị)', 'time' => '15/01/2026 10:15', 'note' => 'Giao chuyên viên Lê Quang Huy thẩm định hiện trường.', 'completed' => true],
            ['title' => 'B3. Thẩm định điều kiện & Dự thảo kết quả', 'actor' => 'Lê Quang Huy (Chuyên viên thẩm định)', 'time' => '16/01/2026 14:00', 'note' => 'Đã thẩm định thực địa đạt tiêu chuẩn. Trình lãnh đạo phê duyệt.', 'completed' => false],
        ];
        $this->partial('partials/timeline', ['items' => $demoTimeline]);
        ?>
    </div>
</section>

<!-- =========================================================================
     4. NÚT BẤM (BUTTONS)
     ========================================================================= -->
<section class="mb-4">
    <div class="card" style="background:var(--bg-surface);padding:1.5rem;border-radius:var(--radius-lg);border:1px solid var(--border-color);">
        <h3 class="mb-3">4. Các Loại Nút Bấm & Kích Thước (Buttons)</h3>
        <div class="d-flex flex-wrap gap-2 align-center mb-3">
            <button type="button" class="btn btn-primary">Navy Primary</button>
            <button type="button" class="btn btn-accent">Đỏ cờ Accent</button>
            <button type="button" class="btn btn-gold">Vàng kim Gold</button>
            <button type="button" class="btn btn-secondary">Secondary</button>
            <button type="button" class="btn btn-outline">Outline</button>
            <button type="button" class="btn btn-outline-primary">Outline Primary</button>
            <button type="button" class="btn btn-primary" disabled>Disabled</button>
        </div>

        <div class="d-flex flex-wrap gap-2 align-center">
            <button type="button" class="btn btn-sm btn-primary">Nút Nhỏ (sm)</button>
            <button type="button" class="btn btn-primary">Nút Vừa (md)</button>
            <button type="button" class="btn btn-lg btn-primary">Nút Lớn (lg)</button>
            <button type="button" class="btn btn-icon btn-secondary" title="In văn bản">🖨</button>
            <button type="button" class="btn btn-icon btn-secondary" title="Tải xuống">💾</button>
        </div>
    </div>
</section>

<!-- =========================================================================
     5. BIỂU MẪU NHẬP LIỆU (FORMS & FLOATING LABELS)
     ========================================================================= -->
<section class="mb-4">
    <div class="card" style="background:var(--bg-surface);padding:1.5rem;border-radius:var(--radius-lg);border:1px solid var(--border-color);">
        <h3 class="mb-3">5. Biểu Mẫu Nhập Liệu (Floating Labels & Validation)</h3>
        <form class="d-grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
            <div class="form-floating">
                <input type="text" class="form-control" id="demo_ho_ten" placeholder="Họ và tên" value="Nguyễn Văn An">
                <label for="demo_ho_ten">Họ và tên người nộp (*)</label>
            </div>

            <div class="form-floating">
                <input type="text" class="form-control" id="demo_cccd" placeholder="Số CCCD" value="031090001234">
                <label for="demo_cccd">Số CCCD (12 chữ số Đề án 06)</label>
            </div>

            <div class="form-floating">
                <input type="text" class="form-control is-invalid" id="demo_sdt" placeholder="Số điện thoại" value="0123456">
                <label for="demo_sdt">Số điện thoại liên hệ</label>
                <div class="invalid-feedback">Số điện thoại không hợp lệ (phải gồm 10 số đầu mạng VN).</div>
            </div>

            <div class="form-floating">
                <select class="form-select" id="demo_quan_huyen">
                    <option value="303" selected>Quận Hồng Bàng</option>
                    <option value="304">Quận Ngô Quyền</option>
                    <option value="305">Quận Lê Chân</option>
                    <option value="311">Thành phố Thủy Nguyên</option>
                </select>
                <label for="demo_quan_huyen">Đơn vị hành chính tiếp nhận</label>
            </div>
        </form>
    </div>
</section>

<!-- =========================================================================
     6. BẢNG DỮ LIỆU TƯƠNG TÁC (DATA TABLE WITH SORT & SEARCH)
     ========================================================================= -->
<section class="mb-4">
    <div class="card" style="background:var(--bg-surface);padding:1.5rem;border-radius:var(--radius-lg);border:1px solid var(--border-color);">
        <div class="d-flex justify-between align-center flex-wrap gap-2 mb-3">
            <h3>6. Bảng Dữ liệu Hồ sơ (Sắp xếp cột & Tìm kiếm nhanh)</h3>
            <div style="max-width:320px;width:100%;">
                <input type="text" class="form-control" placeholder="🔍 Tìm kiếm nhanh trong bảng..." data-table-search="#tableDossiers">
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table table-hover" id="tableDossiers">
                <thead>
                    <tr>
                        <th class="sortable">Mã hồ sơ (QCVN 102)</th>
                        <th class="sortable">Chủ hồ sơ</th>
                        <th class="sortable">Thủ tục hành chính</th>
                        <th class="sortable">Trạng thái</th>
                        <th class="sortable">Tiến độ SLA</th>
                        <th class="text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>000.04.15.H21-260115-0042-000101</strong></td>
                        <td>Công ty CP Cảng Hải Phòng</td>
                        <td>Quyết định chủ trương đầu tư logistics</td>
                        <td><?php $this->partial('partials/status_badge', ['status' => 'DANG_XU_LY']); ?></td>
                        <td><?php $this->partial('partials/sla_badge', ['mau' => 'XANH', 'gioConLai' => 48]); ?></td>
                        <td class="text-right">
                            <button type="button" class="btn btn-sm btn-outline-primary">Chi tiết</button>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>000.04.15.H21-260115-0021-000102</strong></td>
                        <td>Nguyễn Văn Bình</td>
                        <td>Cấp GPXD nhà ở riêng lẻ đô thị</td>
                        <td><?php $this->partial('partials/status_badge', ['status' => 'CHO_BO_SUNG']); ?></td>
                        <td><?php $this->partial('partials/sla_badge', ['mau' => 'VANG', 'gioConLai' => 18]); ?></td>
                        <td class="text-right">
                            <button type="button" class="btn btn-sm btn-outline-primary">Chi tiết</button>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>000.04.15.H21-260115-0015-000103</strong></td>
                        <td>Trần Thị Cẩm</td>
                        <td>Đăng ký biến động quyền sử dụng đất</td>
                        <td><?php $this->partial('partials/status_badge', ['status' => 'CHO_PHE_DUYET']); ?></td>
                        <td><?php $this->partial('partials/sla_badge', ['mau' => 'DO', 'gioConLai' => 0]); ?></td>
                        <td class="text-right">
                            <button type="button" class="btn btn-sm btn-outline-primary">Chi tiết</button>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>000.04.15.H21-260115-0001-000104</strong></td>
                        <td>Vũ Đình Dũng</td>
                        <td>Chứng thực bản sao điện tử từ bản chính</td>
                        <td><?php $this->partial('partials/status_badge', ['status' => 'DA_TRA_KET_QUA']); ?></td>
                        <td><?php $this->partial('partials/sla_badge', ['mau' => 'XANH', 'gioConLai' => 2]); ?></td>
                        <td class="text-right">
                            <button type="button" class="btn btn-sm btn-outline-primary">Chi tiết</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- =========================================================================
     7. TẢI TỆP TIN & TIẾN TRÌNH (DROPZONE UPLOAD)
     ========================================================================= -->
<section class="mb-4">
    <div class="card" style="background:var(--bg-surface);padding:1.5rem;border-radius:var(--radius-lg);border:1px solid var(--border-color);">
        <h3 class="mb-3">7. Kéo thả Tải lên Tệp tin (Drag & Drop File Upload)</h3>
        <div class="dropzone" id="demoDropzone">
            <input type="file" style="display:none;" id="fileUploadInput" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg">
            <div class="dropzone-icon">☁</div>
            <h4 style="font-size:1.05rem;color:var(--color-primary);margin-bottom:0.25rem;">Kéo thả tệp tin vào đây hoặc bấm để chọn tệp</h4>
            <p class="text-muted" style="font-size:0.85rem;margin-bottom:0;">
                Hỗ trợ định dạng PDF, DOCX, XLSX, JPG (Tối đa 10MB theo NĐ 107/2021/NĐ-CP)
            </p>
            <div class="dropzone-file-name text-primary fw-bold mt-2" style="font-size:0.85rem;"></div>
            <div class="progress-bar-container" style="display:none;">
                <div class="progress-bar-fill"></div>
            </div>
        </div>
    </div>
</section>

<!-- =========================================================================
     8. HỘP THOẠI MODAL & THÔNG BÁO TOAST
     ========================================================================= -->
<section class="mb-4">
    <div class="card" style="background:var(--bg-surface);padding:1.5rem;border-radius:var(--radius-lg);border:1px solid var(--border-color);">
        <h3 class="mb-3">8. Hộp thoại Tương tác (Modal, Confirm Dialog & Toast Notifications)</h3>
        
        <div class="d-flex flex-wrap gap-2 align-center mb-3">
            <button type="button" class="btn btn-primary" id="btnTestModal">Mở Hộp thoại Modal mẫu</button>
            <button type="button" class="btn btn-accent" id="btnTestConfirm">Mở Xác nhận Xóa/Hủy logic</button>
        </div>

        <div class="d-flex flex-wrap gap-2 align-center">
            <button type="button" class="btn btn-sm btn-secondary" id="btnToastSuccess">Toast Thành công</button>
            <button type="button" class="btn btn-sm btn-secondary" id="btnToastError">Toast Lỗi</button>
            <button type="button" class="btn btn-sm btn-secondary" id="btnToastWarning">Toast Cảnh báo</button>
            <button type="button" class="btn btn-sm btn-secondary" id="btnToastInfo">Toast Thông tin</button>
        </div>
    </div>
</section>

<!-- Modal Mẫu -->
<div class="modal-backdrop" id="demoSampleModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title">Thông tin Hồ sơ Hành chính</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body">
            <p><strong>Mã hồ sơ:</strong> 000.04.15.H21-260115-0042-000101</p>
            <p><strong>Cơ quan giải quyết:</strong> UBND Quận Hồng Bàng</p>
            <p><strong>Người đại diện:</strong> Trần Quốc Toản (031090005678)</p>
            <p class="text-muted mb-0">Hồ sơ đã được lưu trữ an toàn trong cơ sở dữ liệu MariaDB InnoDB với chữ ký số bảo đảm tính toàn vẹn.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Đóng</button>
            <button type="button" class="btn btn-primary" data-modal-close>Xác nhận duyệt</button>
        </div>
    </div>
</div>

<!-- =========================================================================
     9. BIỂU ĐỒ CHART.JS & QR CODE CỤC BỘ
     ========================================================================= -->
<section class="mb-4">
    <div class="card" style="background:var(--bg-surface);padding:1.5rem;border-radius:var(--radius-lg);border:1px solid var(--border-color);">
        <h3 class="mb-3">9. Thư viện Trực quan hóa Cục bộ (Chart.js & QR Code Generator)</h3>
        
        <div class="d-grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
            <div>
                <h4 style="font-size:0.95rem;color:var(--text-muted);margin-bottom:1rem;">TỶ LỆ GIẢI QUYẾT THEO TIẾN ĐỘ SLA (CHART.JS)</h4>
                <div style="max-height:260px;position:relative;">
                    <canvas id="slaChartCanvas"></canvas>
                </div>
            </div>

            <div>
                <h4 style="font-size:0.95rem;color:var(--text-muted);margin-bottom:1rem;">MÃ QR TRA CỨU HỒ SƠ (QRCODE-GENERATOR)</h4>
                <div class="d-flex align-center gap-3">
                    <div id="qrCodeContainer" style="padding:10px;background:#fff;border:1px solid var(--border-color);border-radius:8px;"></div>
                    <div style="font-size:0.875rem;">
                        <p class="mb-1"><strong>Mã tra cứu:</strong> <code>000.04.15.H21-260115-0042</code></p>
                        <p class="text-muted mb-2">Quét mã bằng camera điện thoại hoặc ứng dụng VNeID để tra cứu tiến độ xử lý hồ sơ.</p>
                        <span class="badge-status badge-status-hoan-thanh">Hợp lệ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =========================================================================
     10. TRẠNG THÁI CHỜ (SKELETON) & EMPTY STATE
     ========================================================================= -->
<section class="mb-4">
    <div class="card" style="background:var(--bg-surface);padding:1.5rem;border-radius:var(--radius-lg);border:1px solid var(--border-color);">
        <h3 class="mb-3">10. Trạng thái Trống (Empty State) & Chờ tải dữ liệu (Skeleton)</h3>
        
        <div class="d-grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
            <div>
                <h4 style="font-size:0.9rem;color:var(--text-muted);margin-bottom:0.75rem;">SKELETON LOADING SHIMMER:</h4>
                <div class="d-grid gap-2">
                    <div class="skeleton" style="width:70%;height:20px;"></div>
                    <div class="skeleton" style="width:100%;height:14px;"></div>
                    <div class="skeleton" style="width:90%;height:14px;"></div>
                    <div class="skeleton" style="width:40%;height:32px;border-radius:8px;margin-top:6px;"></div>
                </div>
            </div>

            <div>
                <h4 style="font-size:0.9rem;color:var(--text-muted);margin-bottom:0.75rem;">EMPTY STATE:</h4>
                <div class="empty-state" style="padding:1.5rem;">
                    <div class="empty-state-icon" style="font-size:2rem;margin-bottom:0.5rem;">📁</div>
                    <div class="empty-state-title" style="font-size:1rem;">Không có hồ sơ nào</div>
                    <div class="empty-state-desc" style="font-size:0.825rem;margin-bottom:1rem;">
                        Hiện tại không có hồ sơ nào trong hàng chờ thẩm định của bạn.
                    </div>
                    <button type="button" class="btn btn-sm btn-primary">Làm mới danh sách</button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Nạp script khởi tạo UI Kit (Tuân thủ CSP, không inline script) -->
<script src="<?= e(asset('js/ui-kit.js')) ?>"></script>

<?php $this->endSection(); ?>
