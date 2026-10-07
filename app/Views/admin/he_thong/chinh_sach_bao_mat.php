<?php $this->layout('layouts/admin', ['title' => $title, 'breadcrumbs' => $breadcrumbs]); ?>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title m-0">Cấu hình Chính sách An toàn thông tin & Tham số Bảo mật</h2>
        <p class="text-muted small m-0">Thiết lập các tiêu chuẩn an ninh hệ thống cấp độ 3 theo Nghị định 85/2016/NĐ-CP và TCVN 11930:2017</p>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/he-thong/chinh-sach-bao-mat') ?>">
            <?= csrf_field() ?>

            <div class="row">
                <!-- Nhóm 1: Chính sách Mật khẩu & Đăng nhập -->
                <div class="col-md-6 mb-4">
                    <div class="border rounded p-3 h-100 bg-light">
                        <h4 class="fw-bold text-primary mb-3">🔑 Chính sách Mật khẩu & Đăng nhập</h4>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Độ dài mật khẩu tối thiểu:</label>
                            <div class="input-group">
                                <input type="number" name="mat_khau_do_dai_toi_thieu" class="form-control" min="6" max="32" value="<?= e($policies['mat_khau_do_dai_toi_thieu'] ?? 8) ?>" required>
                                <span class="input-group-text">ký tự</span>
                            </div>
                            <small class="text-muted">Bắt buộc bao gồm chữ hoa, chữ thường, chữ số và ký tự đặc biệt.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Số lần đăng nhập sai tối đa trước khi khóa:</label>
                            <div class="input-group">
                                <input type="number" name="so_lan_dang_nhap_sai_khoa" class="form-control" min="3" max="10" value="<?= e($policies['so_lan_dang_nhap_sai_khoa'] ?? 5) ?>" required>
                                <span class="input-group-text">lần</span>
                            </div>
                            <small class="text-muted">Chống tấn công vét cạn mật khẩu (Brute-force attack).</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Thời gian tạm khóa tài khoản:</label>
                            <div class="input-group">
                                <input type="number" name="thoi_gian_khoa_tai_khoan_phut" class="form-control" min="5" max="1440" value="<?= e($policies['thoi_gian_khoa_tai_khoan_phut'] ?? 15) ?>" required>
                                <span class="input-group-text">phút</span>
                            </div>
                            <small class="text-muted">Sau khoảng thời gian này, tài khoản tự động mở khóa.</small>
                        </div>
                    </div>
                </div>

                <!-- Nhóm 2: Phiên làm việc & Tần suất truy cập -->
                <div class="col-md-6 mb-4">
                    <div class="border rounded p-3 h-100 bg-light">
                        <h4 class="fw-bold text-primary mb-3">⏱️ Phiên làm việc & Tần suất (Rate Limit)</h4>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Thời gian hết hạn phiên làm việc không hoạt động (Session Timeout):</label>
                            <div class="input-group">
                                <input type="number" name="session_lifetime_phut" class="form-control" min="5" max="120" value="<?= e($policies['session_lifetime_phut'] ?? 30) ?>" required>
                                <span class="input-group-text">phút</span>
                            </div>
                            <small class="text-muted">Hệ thống tự động đăng xuất khi cán bộ không thao tác để tránh rủi ro bỏ máy.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Giới hạn tần suất truy cập Cổng DVC (Rate Limiting Layer 7):</label>
                            <div class="input-group">
                                <input type="number" name="rate_limit_public" class="form-control" min="10" max="300" value="<?= e($policies['rate_limit_public'] ?? 60) ?>" required>
                                <span class="input-group-text">req / phút / IP</span>
                            </div>
                            <small class="text-muted">Ngăn chặn spam và bot quét dữ liệu quá mức cho phép.</small>
                        </div>
                    </div>
                </div>

                <!-- Nhóm 3: Chế độ bảo trì hệ thống (Static Maintenance Site) -->
                <div class="col-12 mb-4">
                    <div class="border rounded p-3 border-warning bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="fw-bold text-warning-emphasis m-0">🚧 Chế độ Bảo trì Hệ thống (Static Maintenance Site)</h4>
                            <div class="form-check form-switch fs-5 m-0">
                                <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" id="switchMaintenance" <?= ($policies['maintenance_mode'] == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label fs-6 fw-bold" for="switchMaintenance">
                                    <?= ($policies['maintenance_mode'] == 1) ? '<span class="text-danger">ĐANG BẬT BẢO TRÌ</span>' : '<span class="text-success">Đang hoạt động bình thường</span>' ?>
                                </label>
                            </div>
                        </div>
                        <p class="small text-muted mb-3">
                            Khi bật chế độ bảo trì, hệ thống sẽ trả về trang thông báo tĩnh <code>public/maintenance.html</code> cho toàn bộ công dân và doanh nghiệp.
                            Chỉ có tài khoản Quản trị viên SYSADMIN mới có thể đăng nhập để thao tác kỹ thuật và khắc phục sự cố.
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Thông điệp bảo trì hiển thị:</label>
                            <textarea name="maintenance_message" class="form-control" rows="2"><?= e($policies['maintenance_message'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    💾 Lưu Cấu hình Chính sách
                </button>
            </div>
        </form>
    </div>
</div>
