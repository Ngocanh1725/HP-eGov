<?php if (!empty($breadcrumbs)): ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(url('/admin/dashboard')) ?>">Trang chủ</a></li>
        <?php foreach ($breadcrumbs as $index => $item): ?>
            <?php $itemTitle = $item['title'] ?? $item['label'] ?? ''; ?>
            <?php if ($index === count($breadcrumbs) - 1 || empty($item['url'])): ?>
                <li class="breadcrumb-item active" aria-current="page"><?= e($itemTitle) ?></li>
            <?php else: ?>
                <li class="breadcrumb-item"><a href="<?= e(url($item['url'])) ?>"><?= e($itemTitle) ?></a></li>
            <?php endif; ?>
        <?php endforeach; ?>

    </ol>
</nav>
<?php endif; ?>
