<?php
/** @var int $currentStep (1..5) */
$currentStep = (int)($currentStep ?? 1);

$steps = [
    1 => ['title' => 'B1. Tiếp nhận', 'sub' => 'Một cửa / Online'],
    2 => ['title' => 'B2. Phân công', 'sub' => 'Lãnh đạo Phòng giao việc'],
    3 => ['title' => 'B3. Thụ lý', 'sub' => 'Thẩm định & Liên thông'],
    4 => ['title' => 'B4. Phê duyệt', 'sub' => 'Ký số lãnh đạo'],
    5 => ['title' => 'B5. Trả kết quả', 'sub' => 'Đóng dấu số & Trả KQ'],
];
?>
<div class="stepper">
    <?php foreach ($steps as $stepNum => $info): ?>
        <?php
        $itemClass = '';
        if ($stepNum < $currentStep) {
            $itemClass = 'completed';
        } elseif ($stepNum === $currentStep) {
            $itemClass = 'active';
        }
        ?>
        <div class="step-item <?= $itemClass ?>">
            <div class="step-circle">
                <?php if ($stepNum < $currentStep): ?>
                    ✓
                <?php else: ?>
                    <?= $stepNum ?>
                <?php endif; ?>
            </div>
            <div class="step-title">
                <?= e($info['title']) ?>
                <div style="font-size:0.75rem;font-weight:400;color:var(--text-light);"><?= e($info['sub']) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
