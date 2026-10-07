<?php $this->layout('layouts/admin', ['title' => $title, 'breadcrumbs' => $breadcrumbs]); ?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="card-title m-0">Nhật ký Kết nối Tích hợp Hệ thống ngoài (LGSP Gateway)</h2>
            <p class="text-muted small m-0">Giám sát các cuộc gọi API liên thông tới CSDLQG Dân cư C06, Cổng DVCQG, VNPost, Viettel/VNPT SMS và SmartCA</p>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="<?= url('/admin/he-thong/nhat-ky-tich-hop') ?>" class="row g-3 mb-4 filter-form">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Hệ thống tích hợp</label>
                <select name="he_thong" class="form-select form-select-sm">
                    <option value="">-- Tất cả hệ thống --</option>
                    <option value="LGSP" <?= ($filters['he_thong'] === 'LGSP') ? 'selected' : '' ?>>LGSP Nền tảng tích hợp Hải Phòng</option>
                    <option value="CSDLQG_DAN_CU" <?= ($filters['he_thong'] === 'CSDLQG_DAN_CU') ? 'selected' : '' ?>>CSDLQG Dân cư (Bộ Công an C06)</option>
                    <option value="DVC_QUOC_GIA" <?= ($filters['he_thong'] === 'DVC_QUOC_GIA') ? 'selected' : '' ?>>Cổng Dịch vụ công Quốc gia</option>
                    <option value="SMS_ZNS" <?= ($filters['he_thong'] === 'SMS_ZNS') ? 'selected' : '' ?>>Tin nhắn SMS OTP & Zalo ZNS</option>
                    <option value="SMART_CA" <?= ($filters['he_thong'] === 'SMART_CA') ? 'selected' : '' ?>>Ký số từ xa SmartCA</option>
                    <option value="BUU_CHINH" <?= ($filters['he_thong'] === 'BUU_CHINH') ? 'selected' : '' ?>>Bưu chính công ích VNPost</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100">Lọc kết quả</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th style="width: 160px;">Thời gian</th>
                        <th style="width: 170px;">Hệ thống</th>
                        <th>Hành động / Endpoint</th>
                        <th style="width: 110px;" class="text-center">HTTP Status</th>
                        <th style="width: 120px;" class="text-center">Độ trễ</th>
                        <th style="width: 100px;" class="text-center">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Chưa có giao dịch tích hợp nào được ghi nhận.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= $log['id'] ?></td>
                                <td class="small"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <span class="badge bg-primary text-wrap"><?= e($log['dich_vu']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold small"><?= e($log['method'] ?? 'POST') ?></div>
                                    <div class="text-muted small font-monospace"><?= e($log['endpoint'] ?? '') ?></div>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $code = (int)($log['status_code'] ?? 200);
                                    $badge = $code >= 200 && $code < 300 ? 'bg-success' : ($code >= 400 && $code < 500 ? 'bg-warning text-dark' : 'bg-danger');
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= $code ?></span>
                                </td>
                                <td class="text-center small font-monospace">
                                    <?= number_format((float)($log['thoi_gian_xu_ly_ms'] ?? 0), 1) ?> ms
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-secondary" 
                                            onclick="alert(<?= htmlspecialchars(json_encode("Yêu cầu (Request Payload):\n" . ($log['request_payload'] ?? 'N/A') . "\n\nPhản hồi (Response Payload):\n" . ($log['response_payload'] ?? 'N/A')), ENT_QUOTES, 'UTF-8') ?>)">
                                        Payload
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="d-flex justify-content-between align-items-center mt-3">
                <span class="small text-muted">Trang <?= $page ?> / <?= $totalPages ?> (Tổng <?= number_format($total) ?> giao dịch)</span>
                <ul class="pagination pagination-sm m-0">
                    <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= url('/admin/he-thong/nhat-ky-tich-hop?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>
