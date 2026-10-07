<?php
/** @var array<int, array{title: string, actor: string, time: string, note?: string, completed?: bool}> $items */
$items = $items ?? [];
?>
<div class="timeline">
    <?php if (empty($items)): ?>
        <p class="text-muted" style="font-size:0.875rem;">Chưa có dữ liệu lịch sử luân chuyển.</p>
    <?php else: ?>
        <?php foreach ($items as $item): ?>
            <div class="timeline-item <?= !empty($item['completed']) ? 'completed' : '' ?>">
                <div class="timeline-dot"></div>
                <div class="timeline-card">
                    <div class="timeline-header">
                        <span class="timeline-title"><?= e($item['title']) ?></span>
                        <span class="timeline-time"><?= e($item['time']) ?></span>
                    </div>
                    <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:0.25rem;">
                        <strong>Người thực hiện:</strong> <?= e($item['actor']) ?>
                    </div>
                    <?php if (!empty($item['note'])): ?>
                        <div style="font-size:0.85rem;color:var(--text-main);background:var(--bg-muted);padding:6px 10px;border-radius:6px;border-left:3px solid var(--color-primary);">
                            <?= e($item['note']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
