<?php /** @var list $findings @var int $checked */ ?>
<p class="hint">
    The rules that block a publish, re-run across everything already on the site.
    A renamed slug that leaves a dead link behind shows up here rather than sitting unnoticed.
</p>
<p><?= (int) $checked ?> posts checked, <?= count($findings) ?> with something to fix.</p>

<?php if ($findings === []): ?>
    <p class="alert alert--ok">Nothing to fix.</p>
<?php else: ?>
    <?php foreach ($findings as $finding): ?>
        <div class="card">
            <h2 style="margin:0 0 .4rem;font-size:1.05rem">
                <a href="/admin/posts/<?= (int) $finding['post']['id'] ?>"><?= e((string) $finding['post']['title']) ?></a>
                <span class="pill <?= $finding['post']['status'] === 'published' ? '' : 'pill--draft' ?>"><?= e((string) $finding['post']['status']) ?></span>
            </h2>
            <?php foreach ($finding['errors'] as $error): ?>
                <p class="note note--high" style="margin:.35rem 0"><?= e($error) ?></p>
            <?php endforeach; ?>
            <?php foreach ($finding['warnings'] as $warning): ?>
                <p class="note note--normal" style="margin:.35rem 0"><?= e($warning) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
