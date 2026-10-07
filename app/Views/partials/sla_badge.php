<?php
/** @var string $mau 'XANH' | 'VANG' | 'DO' */
$mau = strtoupper($mau ?? 'XANH');
$text = match ($mau) {
    'XANH' => 'Đúng hạn',
    'VANG' => 'Sắp đến hạn',
    'DO'   => 'Quá hạn',
    default => 'Bình thường',
};

$cssClass = match ($mau) {
    'XANH' => 'badge-sla-xanh',
    'VANG' => 'badge-sla-vang',
    'DO'   => 'badge-sla-do',
    default => 'badge-sla-xanh',
};
?>
<span class="badge-sla <?= $cssClass ?>" title="Tiến độ kiểm soát SLA Hải Phòng">
    <span class="sla-dot"></span>
    <span><?= e($text) ?></span>
    <?php if (!empty($gioConLai)): ?>
        <small style="opacity:0.85;">(<?= e((string)$gioConLai) ?>h)</small>
    <?php endif; ?>
</span>
