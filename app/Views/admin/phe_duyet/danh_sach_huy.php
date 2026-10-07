<?php $this->layout('layouts/admin', ['title' => $title ?? 'Phê duyệt đề xuất hủy hồ sơ TTHC']) ?>

<?php $this->section('content') ?>
<div class="page-header" style="margin-bottom: 24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                <a href="<?= e(url('/admin/phe-duyet/co-quan')) ?>" style="color:#64748B; text-decoration:none;">← Phê duyệt cơ quan</a>
            </div>
            <h1 style="font-size: 22px; font-weight: 700; color: #DC2626; margin: 0;">
                ⚠️ Phê duyệt Đề xuất Hủy Hồ sơ TTHC (Rule mục 7: Bất biến dữ liệu)
            </h1>
            <p style="color: #64748B; margin: 0; font-size: 14px;">
                Chỉ thực hiện Hủy logic (chuyển DA_HUY, is_deleted=1). Nghiêm cấm xóa vật lý hồ sơ khỏi CSDL.
            </p>
        </div>
    </div>
</div>

<div class="card" style="padding:0; overflow:hidden;">
    <div style="padding:14px 20px; background:#FEF2F2; border-bottom:1px solid #FEE2E2; color:#991B1B; font-weight:600;">
        Danh sách hồ sơ Bộ phận Một cửa đã lập biên bản giải trình và đề xuất hủy
    </div>

    <div class="table-responsive">
        <table class="table" style="margin-bottom:0;">
            <thead>
                <tr>
                    <th style="width: 50px; text-align:center;">STT</th>
                    <th style="width: 170px;">Mã hồ sơ</th>
                    <th>Thủ tục hành chính</th>
                    <th style="width: 150px;">Người nộp</th>
                    <th>Căn cứ & Lý do hủy đề xuất</th>
                    <th style="width: 140px; text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($hoSoList)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px 20px; color:#64748B;">
                            Hiện không có hồ sơ nào có yêu cầu chờ phê duyệt hủy.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($hoSoList as $idx => $hs): ?>
                        <tr>
                            <td style="text-align:center; color:#64748B;"><?= $idx + 1 ?></td>
                            <td style="font-weight:700; color:#0B2E59;">
                                <?= e($hs['ma_ho_so']) ?>
                            </td>
                            <td><?= e($hs['ten_tthc']) ?></td>
                            <td><?= e($hs['ho_ten_nguoi_nop']) ?></td>
                            <td style="font-size:13px; color:#475569;">
                                <em><?= e($hs['ghi_chu'] ?? 'Bộ phận Một cửa đề xuất hủy do người nộp rút hồ sơ') ?></em>
                            </td>
                            <td style="text-align:right;">
                                <form method="POST" action="<?= e(url("/admin/phe-duyet/cho-huy/{$hs['id']}/duyet")) ?>" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn phê duyệt HỦY LOGIC hồ sơ này? Hành động này sẽ được ghi vào nhật ký kiểm toán bất biến.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="y_kien_duyet_huy" value="Lãnh đạo cơ quan đồng ý hủy hồ sơ theo biên bản">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        ✓ Phê duyệt Hủy
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection() ?>
