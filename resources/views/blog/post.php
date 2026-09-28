<?php
/** @var array $post @var list $toc @var string $body @var list $tags @var list $related */
?>
<article class="article">
    <p class="article__meta">
        <?php if (!empty($post['author_name'])): ?>
            <?= e(t('ui.blog.by')) ?> <strong><?= e((string) $post['author_name']) ?></strong><?php if (!empty($post['author_credentials'])): ?>, <?= e((string) $post['author_credentials']) ?><?php endif; ?> ·
        <?php endif; ?>
        <?php if (!empty($post['published_at'])): ?>
            <time datetime="<?= e(substr((string) $post['published_at'], 0, 10)) ?>"><?= e(format_date((string) $post['published_at'])) ?></time> ·
        <?php endif; ?>
        <?= e(t('ui.blog.reading_time', ['minutes' => (int) $post['reading_minutes']])) ?>
        <?php if (!empty($post['updated_at']) && substr((string) $post['updated_at'], 0, 10) !== substr((string) $post['published_at'], 0, 10)): ?>
            · <?= e(t('ui.blog.updated')) ?> <time datetime="<?= e(substr((string) $post['updated_at'], 0, 10)) ?>"><?= e(format_date((string) $post['updated_at'])) ?></time>
        <?php endif; ?>
    </p>

    <?php if (!empty($post['cover_image'])): ?>
        <img src="<?= e((string) $post['cover_image']) ?>" alt="<?= e((string) $post['image_alt']) ?>" width="1200" height="630" loading="eager" decoding="async">
    <?php endif; ?>

    <?php if ($toc !== []): ?>
    <nav class="toc" aria-label="<?= e(t('ui.blog.contents')) ?>">
        <h2><?= e(t('ui.blog.contents')) ?></h2>
        <ol>
            <?php foreach ($toc as $item): ?>
                <li style="<?= $item['level'] === 3 ? 'margin-inline-start:1rem' : '' ?>">
                    <a href="#<?= e($item['id']) ?>"><?= e($item['text']) ?></a>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php endif; ?>

    <?= $body ?>

    <?php if ($tags !== []): ?>
    <ul class="tags">
        <?php foreach ($tags as $tag): ?>
            <li><a href="<?= e(lp('blog/tag/' . $tag['slug'])) ?>"><?= e((string) $tag['name']) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <section class="cta">
        <h2><?= e(t('ui.blog.cta.title')) ?></h2>
        <p><?= e(t('ui.blog.cta.body')) ?></p>
        <a class="btn" href="<?= e(lp()) ?>"><?= e(t('ui.blog.cta.button')) ?></a>
    </section>

    <?php if ($related !== []): ?>
    <section>
        <h2><?= e(t('ui.blog.related')) ?></h2>
        <ul class="posts">
            <?php foreach ($related as $item): ?>
                <li class="post-card">
                    <h3 style="margin:0;font-size:1.05rem"><a href="<?= e(lp('blog/' . $item['slug'])) ?>"><?= e((string) $item['title']) ?></a></h3>
                    <p><?= e((string) ($item['excerpt'] ?: '')) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>
</article>
