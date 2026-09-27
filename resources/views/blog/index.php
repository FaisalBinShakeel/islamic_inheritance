<?php
/** @var list<array> $posts @var list<array> $categories @var int $page @var int $pages @var string $intro */
use App\Support\Str;
?>
<?php if (!empty($intro)): ?><p class="lede"><?= e($intro) ?></p><?php endif; ?>

<?php if ($categories !== []): ?>
<ul class="tags" style="margin-top:1rem">
    <?php foreach ($categories as $category): ?>
        <li><a href="<?= e(lp('blog/category/' . $category['slug'])) ?>"><?= e((string) $category['name']) ?></a></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if ($posts === []): ?>
    <p><?= e(t('ui.blog.empty')) ?></p>
<?php else: ?>
<ul class="posts">
    <?php foreach ($posts as $post): ?>
        <li class="post-card">
            <h2><a href="<?= e(lp('blog/' . $post['slug'])) ?>"><?= e((string) $post['title']) ?></a></h2>
            <p><?= e((string) ($post['excerpt'] ?: Str::excerpt((string) $post['body']))) ?></p>
            <p class="meta">
                <?php if (!empty($post['category_name'])): ?><?= e((string) $post['category_name']) ?> · <?php endif; ?>
                <?= e(t('ui.blog.reading_time', ['minutes' => (int) $post['reading_minutes']])) ?>
                <?php if (!empty($post['published_at'])): ?> · <time datetime="<?= e(substr((string) $post['published_at'], 0, 10)) ?>"><?= e(format_date((string) $post['published_at'])) ?></time><?php endif; ?>
            </p>
        </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if ($pages > 1): ?>
<nav class="actions" style="margin-top:2rem" aria-label="Pagination">
    <?php if ($page > 1): ?><a class="btn btn--ghost btn--small" href="<?= e(lp('blog')) ?><?= $page - 1 > 1 ? '?page=' . ($page - 1) : '' ?>">←</a><?php endif; ?>
    <span class="hint" style="margin:0"><?= (int) $page ?> / <?= (int) $pages ?></span>
    <?php if ($page < $pages): ?><a class="btn btn--ghost btn--small" href="<?= e(lp('blog')) ?>?page=<?= $page + 1 ?>">→</a><?php endif; ?>
</nav>
<?php endif; ?>

<section class="cta">
    <h2><?= e(t('ui.blog.cta.title')) ?></h2>
    <p><?= e(t('ui.blog.cta.body')) ?></p>
    <a class="btn" href="<?= e(lp()) ?>"><?= e(t('ui.blog.cta.button')) ?></a>
</section>
