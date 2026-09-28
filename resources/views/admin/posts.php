<?php use App\Locale; /** @var list $posts @var string|null $locale */ ?>
<p class="actions" style="margin-bottom:1.25rem">
    <a class="btn btn--small" href="/admin/posts/new">New post</a>
    <a class="btn btn--ghost btn--small" href="/admin/posts">All</a>
    <?php foreach (Locale::enabled() as $code): ?>
        <a class="btn btn--ghost btn--small" href="/admin/posts?locale=<?= e($code) ?>"><?= e(Locale::englishName($code)) ?></a>
    <?php endforeach; ?>
</p>

<?php if (isset($_GET['deleted'])): ?>
    <p class="alert alert--ok" role="status">
        Deleted <code><?= e((string) $_GET['deleted']) ?></code>. Check the
        <a href="/admin/audit">audit page</a> for links that now point nowhere.
    </p>
<?php endif; ?>

<table class="admin-table">
    <thead><tr><th>Title</th><th>Slug</th><th>Locale</th><th>Category</th><th>Keyword</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($posts as $post): ?>
        <tr>
            <td>
                <a href="/admin/posts/<?= (int) $post['id'] ?>"><?= e((string) $post['title']) ?></a>
                <?php if ($post['title'] !== $post['h1']): ?>
                    <span class="pill pill--warn" title="Title and H1 differ">H1 ≠ title</span>
                <?php endif; ?>
            </td>
            <td><code><?= e((string) $post['slug']) ?></code></td>
            <td><?= e((string) $post['locale']) ?></td>
            <td><?= e((string) ($post['category_name'] ?? '—')) ?></td>
            <td><?= e((string) ($post['target_keyword'] ?? '—')) ?></td>
            <td><span class="pill <?= $post['status'] === 'published' ? '' : 'pill--draft' ?>"><?= e((string) $post['status']) ?></span></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
