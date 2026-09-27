<?php /** @var array $counts @var list $popular @var list $recent */ ?>
<div class="grid" style="margin-bottom:2rem">
    <?php foreach ([
        'Published' => $counts['published'],
        'Drafts' => $counts['drafts'],
        'Open error reports' => $counts['reports'],
        'Calculations run' => $counts['calculations'],
    ] as $label => $number): ?>
        <div class="card">
            <p class="hint" style="margin:0"><?= e($label) ?></p>
            <p style="font-family:var(--font-serif);font-size:1.8rem;margin:.2rem 0 0"><?= (int) $number ?></p>
        </div>
    <?php endforeach; ?>
</div>

<h2>Most common heir combinations</h2>
<p class="hint">What people actually enter. This is the best signal for which guide to write next.</p>
<?php if ($popular === []): ?>
    <p class="hint">Nothing recorded yet.</p>
<?php else: ?>
<table class="admin-table">
    <thead><tr><th>Heirs entered</th><th style="width:6rem">Times</th></tr></thead>
    <tbody>
    <?php foreach ($popular as $row): ?>
        <tr><td><code><?= e((string) $row['heir_signature']) ?></code></td><td><?= (int) $row['uses'] ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h2>Recently edited</h2>
<table class="admin-table">
    <thead><tr><th>Title</th><th>Locale</th><th>Status</th><th>Updated</th></tr></thead>
    <tbody>
    <?php foreach ($recent as $post): ?>
        <tr>
            <td><a href="/admin/posts/<?= (int) $post['id'] ?>"><?= e((string) $post['title']) ?></a></td>
            <td><?= e((string) $post['locale']) ?></td>
            <td><span class="pill <?= $post['status'] === 'published' ? '' : 'pill--draft' ?>"><?= e((string) $post['status']) ?></span></td>
            <td><?= e(format_date((string) $post['updated_at'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
