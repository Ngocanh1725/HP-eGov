<?php if (!empty($breadcrumbs)): ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(url('/admin/dashboard')) ?>">Trang chủ</a></li>
        <?php foreach ($breadcrumbs as $index => $item): ?>
            <?php if ($index === count($breadcrumbs) - 1 || empty($item['url'])): ?>
                <li class="breadcrumb-item active" aria-current="page"><?= e($item['title']) ?></li>
            <?php else: ?>
                <li class="breadcrumb-item"><a href="<?= e(url($item['url'])) ?>"><?= e($item['title']) ?></a></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>
<?php endif; ?>
