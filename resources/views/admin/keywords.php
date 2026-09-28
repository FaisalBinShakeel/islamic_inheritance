<?php use App\Locale; /** @var list $rows */ ?>
<p class="hint">
    One post, one primary phrase, per locale — the database enforces it.
    Check here before writing the next piece.
</p>

<?php
$byLocale = [];
foreach ($rows as $row) {
    $byLocale[(string) $row['locale']][] = $row;
}
?>

<?php foreach ($byLocale as $locale => $posts): ?>
    <h2><?= e(Locale::englishName($locale)) ?> <span class="hint" style="font-weight:400">(<?= count($posts) ?>)</span></h2>
    <table class="admin-table" style="margin-bottom:2rem">
        <thead><tr><th>Target keyword</th><th>Post</th><th>Category</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($posts as $row): ?>
            <tr>
                <td>
                    <?php if (!empty($row['target_keyword'])): ?>
                        <code><?= e((string) $row['target_keyword']) ?></code>
                    <?php else: ?>
                        <span class="pill pill--warn">not recorded</span>
                    <?php endif; ?>
                </td>
                <td><a href="/admin/posts/<?= (int) $row['id'] ?>"><?= e((string) $row['title']) ?></a></td>
                <td><?= e((string) ($row['category_name'] ?? '—')) ?></td>
                <td><span class="pill <?= $row['status'] === 'published' ? '' : 'pill--draft' ?>"><?= e((string) $row['status']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; ?>
