<?php
use App\Enums\TrangThaiHoSo;

/** @var string|TrangThaiHoSo $status */
$statusCode = ($status instanceof TrangThaiHoSo) ? $status->value : (string)($status ?? 'MOI_TIEP_NHAN');

$enum = TrangThaiHoSo::tryFrom($statusCode) ?? TrangThaiHoSo::MOI_TIEP_NHAN;

$classMap = [
    'CHO_TIEP_NHAN' => 'badge-status-cho-tiep-nhan',
    'MOI_TIEP_NHAN' => 'badge-status-moi-tiep-nhan',
    'DANG_XU_LY'    => 'badge-status-dang-xu-ly',
    'CHO_BO_SUNG'   => 'badge-status-cho-bo-sung',
    'CHO_PHE_DUYET' => 'badge-status-cho-phe-duyet',
    'HOAN_THANH'    => 'badge-status-hoan-thanh',
    'DA_TRA_KET_QUA'=> 'badge-status-da-tra-kq',
    'TU_CHOI'       => 'badge-status-tu-choi',
    'DA_HUY'        => 'badge-status-da-huy',
];

$badgeClass = $classMap[$enum->value] ?? 'badge-status-moi-tiep-nhan';
?>
<span class="badge-status <?= $badgeClass ?>">
    <?= e($enum->label()) ?>
</span>
