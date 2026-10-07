<?php
/**
 * HP-eGov - Quản lý Trả kết quả TTHC (Bộ phận Một cửa)
 * Mẫu quy trình theo Nghị định 61/2018/NĐ-CP & Thông tư 01/2018/TT-VPCP
 */
$title = 'Quản lý Trả kết quả TTHC - Bộ phận Một cửa';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= route('admin.motcua.dashboard') ?>">Một cửa</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Trả kết quả giải quyết</li>
                </ol>
            </nav>
            <h1 class="h3 font-weight-bold text-gray-800 mb-0">Trả kết quả giải quyết TTHC</h1>
            <p class="text-muted small mb-0">Danh sách hồ sơ đã có kết quả giải quyết (HOAN_THANH) sẵn sàng bàn giao cho tổ chức, cá nhân</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= route('admin.motcua.soTheoDoi') ?>" class="btn btn-outline-primary">
                <i class="bi bi-journal-bookmark me-1"></i> Sổ theo dõi hồ sơ
            </a>
            <a href="<?= route('admin.motcua.dashboard') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Về bàn làm việc
            </a>
        </div>
    </div>

    <!-- Alert thông báo nếu có -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Bộ lọc & Thống kê -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control bg-light border-start-0" placeholder="Tìm theo mã hồ sơ, người nộp, CCCD...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="filterHinhThuc" class="form-select bg-light">
                        <option value="">-- Tất cả hình thức nhận kết quả --</option>
                        <option value="TRUC_TIEP">Nhận trực tiếp tại Một cửa</option>
                        <option value="BUU_CHINH">Nhận qua Bưu chính công ích</option>
                    </select>
                </div>
                <div class="col-md-5 text-md-end text-muted small mt-2 mt-md-0">
                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1 me-2">
                        <i class="bi bi-check2-circle me-1"></i><?= count($hoSoHoanThanh ?? []) ?> hồ sơ chờ trả
                    </span>
                    <span class="text-secondary"><i class="bi bi-info-circle me-1"></i>Hồ sơ trễ hạn phải xuất kèm văn bản xin lỗi Mẫu 04</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách hồ sơ HOAN_THANH -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableTraKetQua">
                <thead class="table-light text-uppercase small font-weight-bold">
                    <tr>
                        <th style="width: 50px;" class="text-center">STT</th>
                        <th style="width: 170px;">Mã hồ sơ</th>
                        <th>Thủ tục hành chính</th>
                        <th style="width: 190px;">Người nộp / CCCD</th>
                        <th style="width: 140px;">Hẹn trả</th>
                        <th style="width: 130px;" class="text-center">Hình thức trả</th>
                        <th style="width: 110px;" class="text-center">Hạn xử lý</th>
                        <th style="width: 160px;" class="text-end pe-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($hoSoHoanThanh)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="bi bi-inbox-fill text-muted opacity-50" style="font-size: 3rem;"></i>
                                    <p class="mt-3 mb-0">Hiện tại không có hồ sơ nào đang chờ trả kết quả.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $stt = 1; 
                        $now = new DateTime();
                        foreach ($hoSoHoanThanh as $hs): 
                            $ngayHen = !empty($hs['ngay_hen_tra']) ? new DateTime($hs['ngay_hen_tra']) : null;
                            $isTreHan = $ngayHen && ($ngayHen < $now);
                            $hinhThucNhan = $hs['hinh_thuc_nhan_kq'] ?? 'TRUC_TIEP';
                        ?>
                            <tr class="item-ho-so <?= $isTreHan ? 'table-warning-subtle' : '' ?>" 
                                data-ma="<?= htmlspecialchars($hs['ma_ho_so'] ?? '') ?>"
                                data-ten="<?= htmlspecialchars($hs['nguoi_nop_ho_ten'] ?? '') ?>"
                                data-hinhthuc="<?= htmlspecialchars($hinhThucNhan) ?>">
                                <td class="text-center font-weight-bold text-muted"><?= $stt++ ?></td>
                                <td>
                                    <div class="font-monospace font-weight-bold text-primary"><?= htmlspecialchars($hs['ma_ho_so'] ?? '') ?></div>
                                    <small class="text-muted"><?= !empty($hs['ngay_tiep_nhan']) ? date('d/m/Y H:i', strtotime($hs['ngay_tiep_nhan'])) : '' ?></small>
                                </td>
                                <td>
                                    <div class="font-weight-semibold text-dark text-truncate" style="max-width: 320px;" title="<?= htmlspecialchars($hs['ten_tthc'] ?? '') ?>">
                                        <?= htmlspecialchars($hs['ten_tthc'] ?? 'Thủ tục hành chính') ?>
                                    </div>
                                    <small class="text-muted font-monospace"><?= htmlspecialchars($hs['ma_tthc'] ?? '') ?></small>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark"><?= htmlspecialchars($hs['nguoi_nop_ho_ten'] ?? '') ?></div>
                                    <small class="text-muted"><i class="bi bi-person-vcard me-1"></i><?= htmlspecialchars($hs['nguoi_nop_cccd'] ?? '---') ?></small><br>
                                    <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($hs['nguoi_nop_dien_thoai'] ?? '---') ?></small>
                                </td>
                                <td>
                                    <?php if ($ngayHen): ?>
                                        <div class="font-weight-semibold <?= $isTreHan ? 'text-danger' : 'text-dark' ?>">
                                            <?= $ngayHen->format('d/m/Y') ?>
                                        </div>
                                        <small class="text-muted"><?= $ngayHen->format('H:i') ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Chưa xác định</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($hinhThucNhan === 'BUU_CHINH'): ?>
                                        <span class="badge bg-info-subtle text-info border border-info px-2 py-1">
                                            <i class="bi bi-mailbox me-1"></i>VNPost
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1">
                                            <i class="bi bi-building me-1"></i>Tại Một cửa
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isTreHan): ?>
                                        <span class="badge bg-danger text-white px-2 py-1" title="Quá hạn hẹn trả, bắt buộc kèm văn bản xin lỗi">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Trễ hạn
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                            <i class="bi bi-clock-check me-1"></i>Đúng hạn
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-primary btn-tra-kq" 
                                                data-id="<?= $hs['id'] ?>"
                                                data-ma="<?= htmlspecialchars($hs['ma_ho_so'] ?? '') ?>"
                                                data-ten="<?= htmlspecialchars($hs['nguoi_nop_ho_ten'] ?? '') ?>"
                                                data-cccd="<?= htmlspecialchars($hs['nguoi_nop_cccd'] ?? '') ?>"
                                                data-sdt="<?= htmlspecialchars($hs['nguoi_nop_dien_thoai'] ?? '') ?>"
                                                data-diachi="<?= htmlspecialchars($hs['nguoi_nop_dia_chi'] ?? '') ?>"
                                                data-hinhthuc="<?= htmlspecialchars($hinhThucNhan) ?>"
                                                data-trehan="<?= $isTreHan ? '1' : '0' ?>">
                                            <i class="bi bi-box-arrow-up-right me-1"></i> Trả KQ
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-huy-hs"
                                                data-id="<?= $hs['id'] ?>"
                                                data-ma="<?= htmlspecialchars($hs['ma_ho_so'] ?? '') ?>"
                                                title="Đề xuất hủy hồ sơ">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Trả kết quả -->
<div class="modal fade" id="modalTraKetQua" tabindex="-1" aria-labelledby="modalTraKetQuaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= route('admin.motcua.submitTraKetQua') ?>" method="POST" id="formSubmitTraKq">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="ho_so_id" id="modal_ho_so_id" value="">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTraKetQuaLabel">
                        <i class="bi bi-check2-square me-2"></i>Thực hiện Trả kết quả giải quyết TTHC
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <!-- Cảnh báo nếu trễ hạn -->
                    <div id="alertTreHan" class="alert alert-danger d-none mb-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-danger"></i>
                            <div>
                                <h6 class="font-weight-bold mb-1">CẢNH BÁO: HỒ SƠ TRỄ HẠN TRẢ KẾT QUẢ</h6>
                                <p class="small mb-0">Theo Điều 18 Thông tư 01/2018/TT-VPCP, cơ quan giải quyết phải lập và gửi kèm <strong>Mẫu số 04 - Phiếu xin lỗi và hẹn lại ngày trả kết quả</strong> cho người dân.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Thông tin hồ sơ tóm tắt -->
                    <div class="card bg-light border-0 rounded-3 mb-3 p-3">
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <small class="text-muted d-block">Mã hồ sơ:</small>
                                <span class="font-monospace font-weight-bold text-primary fs-6" id="modal_ma_ho_so">---</span>
                            </div>
                            <div class="col-sm-6">
                                <small class="text-muted d-block">Người nộp hồ sơ:</small>
                                <span class="font-weight-bold text-dark" id="modal_nguoi_nop">---</span>
                            </div>
                        </div>
                    </div>

                    <!-- Lựa chọn hình thức trả -->
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Hình thức trả kết quả <span class="text-danger">*</span></label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check card p-3 border h-100 cursor-pointer">
                                    <input class="form-check-input" type="radio" name="hinh_thuc" id="ht_truc_tiep" value="TRUC_TIEP" checked>
                                    <label class="form-check-label ms-2 font-weight-semibold" for="ht_truc_tiep">
                                        <i class="bi bi-building text-primary me-1"></i> Trực tiếp tại Bộ phận Một cửa
                                        <small class="text-muted d-block mt-1 font-weight-normal">Công dân/đại diện trực tiếp đến nhận và ký biên nhận</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check card p-3 border h-100 cursor-pointer">
                                    <input class="form-check-input" type="radio" name="hinh_thuc" id="ht_buu_chinh" value="BUU_CHINH">
                                    <label class="form-check-label ms-2 font-weight-semibold" for="ht_buu_chinh">
                                        <i class="bi bi-mailbox text-info me-1"></i> Chuyển phát Bưu chính VNPost
                                        <small class="text-muted d-block mt-1 font-weight-normal">Chuyển phát bưu chính công ích, tự sinh mã vận đơn</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Khối thông tin Trực tiếp -->
                    <div id="block_truc_tiep" class="border rounded-3 p-3 bg-white mb-3">
                        <h6 class="font-weight-bold text-primary mb-3">
                            <i class="bi bi-person-check me-1"></i> Thông tin người nhận kết quả
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small font-weight-semibold">Họ tên người nhận <span class="text-danger">*</span></label>
                                <input type="text" name="nguoi_nhan_ten" id="modal_nguoi_nhan_ten" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small font-weight-semibold">Số CCCD người nhận <span class="text-danger">*</span></label>
                                <input type="text" name="nguoi_nhan_cccd" id="modal_nguoi_nhan_cccd" class="form-control font-monospace" maxlength="12" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small font-weight-semibold">Số điện thoại liên hệ</label>
                                <input type="tel" name="nguoi_nhan_sdt" id="modal_nguoi_nhan_sdt" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small font-weight-semibold">Ghi chú xác nhận / Mối quan hệ</label>
                                <input type="text" name="ghi_chu_ky_nhan" class="form-control" placeholder="Chính chủ / Giấy ủy quyền số...">
                            </div>
                        </div>
                    </div>

                    <!-- Khối thông tin Bưu chính -->
                    <div id="block_buu_chinh" class="border rounded-3 p-3 bg-white mb-3 d-none">
                        <h6 class="font-weight-bold text-info mb-3">
                            <i class="bi bi-truck me-1"></i> Thông tin chuyển phát VNPost
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small font-weight-semibold">Họ tên người nhận <span class="text-danger">*</span></label>
                                <input type="text" name="buu_chinh_nguoi_nhan" id="modal_bc_nguoi_nhan" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small font-weight-semibold">Số điện thoại nhận <span class="text-danger">*</span></label>
                                <input type="tel" name="buu_chinh_sdt" id="modal_bc_sdt" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label small font-weight-semibold">Địa chỉ phát kết quả <span class="text-danger">*</span></label>
                                <textarea name="buu_chinh_dia_chi" id="modal_bc_diachi" rows="2" class="form-control" placeholder="Số nhà, đường, phường/xã, quận/huyện..."></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tao_van_don_mock" id="tao_van_don_mock" checked>
                                    <label class="form-check-label small" for="tao_van_don_mock">
                                        Tự động kết nối BuuChinhGateway sinh mã vận đơn VNPost chuẩn <code>HP...VN</code>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Đính kèm Văn bản xin lỗi nếu trễ hạn -->
                    <div id="block_dinh_kem_xin_loi" class="border border-warning rounded-3 p-3 bg-warning-subtle mb-3 d-none">
                        <h6 class="font-weight-bold text-warning-emphasis mb-2">
                            <i class="bi bi-file-earmark-text me-1"></i> Hồ sơ trễ hạn - Đính kèm Giấy xin lỗi (Mẫu số 04 TT 01/2018)
                        </h6>
                        <div class="row g-2 align-items-center">
                            <div class="col-sm-8">
                                <input type="text" name="so_van_ban_xin_loi" class="form-control form-control-sm" placeholder="Số văn bản xin lỗi (VD: 142/XL-UBND)">
                            </div>
                            <div class="col-sm-4">
                                <input type="date" name="ngay_van_ban_xin_loi" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary px-4 font-weight-semibold">
                        <i class="bi bi-check-circle me-1"></i> Hoàn tất Trả kết quả
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Đề xuất hủy hồ sơ -->
<div class="modal fade" id="modalHuyHoSo" tabindex="-1" aria-labelledby="modalHuyHoSoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= route('admin.motcua.deXuatHuy') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="ho_so_id" id="modal_huy_id" value="">
                
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalHuyHoSoLabel">
                        <i class="bi bi-exclamation-octagon me-2"></i>Đề xuất hủy hồ sơ
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-danger small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Đề xuất hủy hồ sơ sẽ chuyển trạng thái sang <strong>CHO_DUYET_HUY</strong> để Lãnh đạo cơ quan xem xét, phê duyệt.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Mã hồ sơ:</label>
                        <div class="font-monospace text-primary font-weight-bold" id="modal_huy_ma">---</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Căn cứ pháp lý đề xuất hủy <span class="text-danger">*</span></label>
                        <textarea name="can_cu_phap_ly" class="form-control" rows="2" placeholder="Ví dụ: Theo khoản 3 Điều 15 Nghị định... hoặc Công văn số..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Biên bản giải trình lý do hủy <span class="text-danger">*</span></label>
                        <textarea name="ly_do_giai_trinh" class="form-control" rows="3" placeholder="Trình bày cụ thể nguyên nhân đề xuất hủy hồ sơ..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-danger font-weight-semibold">
                        <i class="bi bi-send me-1"></i> Gửi đề xuất tới Lãnh đạo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterHinhThuc = document.getElementById('filterHinhThuc');
    const tableRows = document.querySelectorAll('#tableTraKetQua tbody tr.item-ho-so');

    function filterTable() {
        const query = searchInput.value.toLowerCase().trim();
        const ht = filterHinhThuc.value;

        tableRows.forEach(row => {
            const ma = row.dataset.ma.toLowerCase();
            const ten = row.dataset.ten.toLowerCase();
            const rowHt = row.dataset.hinhthuc;

            const matchQuery = ma.includes(query) || ten.includes(query);
            const matchHt = !ht || rowHt === ht;

            if (matchQuery && matchHt) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterTable);
    filterHinhThuc.addEventListener('change', filterTable);

    // Modal Trả kết quả
    const modalTraKq = new bootstrap.Modal(document.getElementById('modalTraKetQua'));
    const htRadios = document.querySelectorAll('input[name="hinh_thuc"]');
    const blockTrucTiep = document.getElementById('block_truc_tiep');
    const blockBuuChinh = document.getElementById('block_buu_chinh');
    const alertTreHan = document.getElementById('alertTreHan');
    const blockDinhKemXinLoi = document.getElementById('block_dinh_kem_xin_loi');

    htRadios.forEach(r => {
        r.addEventListener('change', function() {
            if (this.value === 'BUU_CHINH') {
                blockTrucTiep.classList.add('d-none');
                blockBuuChinh.classList.remove('d-none');
                document.getElementById('modal_nguoi_nhan_ten').removeAttribute('required');
                document.getElementById('modal_nguoi_nhan_cccd').removeAttribute('required');
                document.getElementById('modal_bc_nguoi_nhan').setAttribute('required', 'required');
                document.getElementById('modal_bc_sdt').setAttribute('required', 'required');
                document.getElementById('modal_bc_diachi').setAttribute('required', 'required');
            } else {
                blockTrucTiep.classList.remove('d-none');
                blockBuuChinh.classList.add('d-none');
                document.getElementById('modal_nguoi_nhan_ten').setAttribute('required', 'required');
                document.getElementById('modal_nguoi_nhan_cccd').setAttribute('required', 'required');
                document.getElementById('modal_bc_nguoi_nhan').removeAttribute('required');
                document.getElementById('modal_bc_sdt').removeAttribute('required');
                document.getElementById('modal_bc_diachi').removeAttribute('required');
            }
        });
    });

    document.querySelectorAll('.btn-tra-kq').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = this.dataset;
            document.getElementById('modal_ho_so_id').value = data.id;
            document.getElementById('modal_ma_ho_so').textContent = data.ma;
            document.getElementById('modal_nguoi_nop').textContent = data.ten;

            // Điền mặc định người nhận
            document.getElementById('modal_nguoi_nhan_ten').value = data.ten;
            document.getElementById('modal_nguoi_nhan_cccd').value = data.cccd;
            document.getElementById('modal_nguoi_nhan_sdt').value = data.sdt;

            document.getElementById('modal_bc_nguoi_nhan').value = data.ten;
            document.getElementById('modal_bc_sdt').value = data.sdt;
            document.getElementById('modal_bc_diachi').value = data.diachi;

            if (data.hinhthuc === 'BUU_CHINH') {
                document.getElementById('ht_buu_chinh').checked = true;
                document.getElementById('ht_buu_chinh').dispatchEvent(new Event('change'));
            } else {
                document.getElementById('ht_truc_tiep').checked = true;
                document.getElementById('ht_truc_tiep').dispatchEvent(new Event('change'));
            }

            // Xử lý trễ hạn
            if (data.trehan === '1') {
                alertTreHan.classList.remove('d-none');
                blockDinhKemXinLoi.classList.remove('d-none');
            } else {
                alertTreHan.classList.add('d-none');
                blockDinhKemXinLoi.classList.add('d-none');
            }

            modalTraKq.show();
        });
    });

    // Modal Hủy hồ sơ
    const modalHuy = new bootstrap.Modal(document.getElementById('modalHuyHoSo'));
    document.querySelectorAll('.btn-huy-hs').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modal_huy_id').value = this.dataset.id;
            document.getElementById('modal_huy_ma').textContent = this.dataset.ma;
            modalHuy.show();
        });
    });
});
</script>
