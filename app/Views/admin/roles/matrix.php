<?php $this->layout('layouts/admin', ['title' => 'Ma trận Phân quyền RBAC']); ?>

<?php $this->startSection('content'); ?>
<div class="content-header mb-4" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">MA TRẬN PHÂN QUYỀN VAI TRÒ & QUYỀN HẠN (RBAC)</h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Phân định quyền hạn cho 7 vai trò chuẩn hóa theo Báo cáo Kiến trúc §4.1
        </p>
    </div>
    <div>
        <button type="submit" form="form-role-matrix" class="btn btn-primary" style="padding: 0.6rem 1.25rem; font-weight: 700;">
            💾 LƯU THAY ĐỔI MA TRẬN QUYỀN
        </button>
    </div>
</div>

<div class="p-3 mb-4" style="background: #eff6ff; border-left: 4px solid var(--color-primary); border-radius: var(--radius-md); font-size: 0.85rem;">
    <strong style="color: var(--color-primary);">🛡️ Quy định an toàn thông tin Cấp độ 3 (§3.3 & §4.1):</strong>
    <p style="margin: 0.25rem 0 0 0; color: #1e3a8a;">
        Hệ thống <strong>chặn cứng bất biến</strong>: Vai trò <code>SYSADMIN</code> tuyệt đối không được cấp các quyền nghiệp vụ xử lý hồ sơ (<code>hoso.*</code>), ký số (<code>kyso.*</code>) và thẩm định thụ lý (<code>thu_ly.*</code>). Mọi nỗ lực gán quyền nghiệp vụ cho quản trị viên sẽ bị loại bỏ và ghi nhật ký kiểm toán.
    </p>
</div>

<form action="<?= e(url('/admin/he-thong/vai-tro/ma-tran')) ?>" method="POST" id="form-role-matrix">
    <?= csrf_field() ?>

    <div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow-x: auto;">
        <table class="table mb-0" style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
            <thead style="background: #0B2E59; color: #fff; position: sticky; top: 0; z-index: 10;">
                <tr>
                    <th style="padding: 1rem; text-align: left; width: 320px;">Nhóm / Tên quyền hạn</th>
                    <th style="padding: 1rem; text-align: left; width: 180px;">Mã quyền</th>
                    <?php foreach ($roles as $r): ?>
                        <th style="padding: 0.75rem; text-align: center; width: 110px;">
                            <span style="font-weight: 700; display: block;"><?= e($r['ma_vai_tro']) ?></span>
                            <span style="font-size: 0.7rem; font-weight: 400; opacity: 0.85; display: block; line-height: 1.2;">
                                <?= e($r['ten_vai_tro']) ?>
                            </span>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groupedPermissions as $groupName => $perms): ?>
                    <tr style="background: #f1f5f9; border-top: 2px solid #cbd5e1; border-bottom: 1px solid #cbd5e1;">
                        <td colspan="<?= 2 + count($roles) ?>" style="padding: 0.6rem 1rem; font-weight: 700; color: #0B2E59; font-size: 0.85rem;">
                            📁 NHÓM QUYỀN: <?= e($groupName) ?> (<?= count($perms) ?> quyền)
                        </td>
                    </tr>
                    <?php foreach ($perms as $p): ?>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 0.6rem 1rem;">
                                <strong style="color: #1e293b;"><?= e($p['ten_quyen']) ?></strong>
                                <?php if (!empty($p['mo_ta'])): ?>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?= e($p['mo_ta']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.6rem 1rem;">
                                <code><?= e($p['ma_quyen']) ?></code>
                            </td>
                            <?php foreach ($roles as $r): ?>
                                <?php 
                                    $isSysadmin = ($r['ma_vai_tro'] === 'SYSADMIN');
                                    $isBusinessPerm = str_starts_with($p['ma_quyen'], 'hoso.') || str_starts_with($p['ma_quyen'], 'kyso.') || str_starts_with($p['ma_quyen'], 'thu_ly.');
                                    $isBlockedForRole = ($isSysadmin && $isBusinessPerm);
                                    $isChecked = isset($matrix[$r['id']][$p['id']]);
                                ?>
                                <td style="padding: 0.6rem; text-align: center; vertical-align: middle;">
                                    <?php if ($isBlockedForRole): ?>
                                        <span title="Chặn cứng: Không cho phép gán quyền nghiệp vụ cho SYSADMIN" style="cursor: not-allowed; font-size: 1.1rem; color: #94a3b8;">
                                            🚫
                                        </span>
                                    <?php else: ?>
                                        <input type="checkbox" 
                                               name="matrix[<?= $r['id'] ?>][<?= $p['id'] ?>]" 
                                               value="1" 
                                               <?= $isChecked ? 'checked' : '' ?>
                                               style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--color-primary);">
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4 text-right" style="text-align: right;">
        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; font-weight: 700; font-size: 1rem;">
            💾 LƯU THAY ĐỔI MA TRẬN QUYỀN
        </button>
    </div>
</form>
<?php $this->endSection(); ?>
