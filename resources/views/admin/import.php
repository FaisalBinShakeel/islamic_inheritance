<?php use App\Csrf; /** @var array $result @var string|null $error */ ?>
<?php if (!empty($error)): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>

<form method="post" action="/admin/import" enctype="multipart/form-data" class="card">
    <?= Csrf::field() ?>
    <div class="field" style="margin-bottom:1rem">
        <label for="file">CSV file</label>
        <input type="file" id="file" name="file" accept=".csv,text/csv" required>
        <p class="hint">Columns: Slug, Locale, Title, H1, MetaDescription, Category, Tags, Body, ImageAlt, Author, PublishAt, TargetKeyword, TranslationGroup. Order does not matter; a header row is required.</p>
    </div>
    <div class="switchrow">
        <input type="checkbox" id="dry_run" name="dry_run" value="1" checked>
        <label for="dry_run">Dry run — check the file without writing anything</label>
    </div>
    <button class="btn" type="submit">Upload</button>
</form>

<?php if ($result !== []): ?>
    <h2>Result</h2>
    <p><?= (int) $result['imported'] ?> written, <?= (int) $result['skipped'] ?> skipped.</p>
    <table class="admin-table">
        <thead><tr><th>Row</th><th>Slug</th><th>Outcome</th><th>Notes</th></tr></thead>
        <tbody>
        <?php foreach ($result['rows'] as $row): ?>
            <tr>
                <td><?= (int) $row['row'] ?></td>
                <td><code><?= e($row['slug']) ?></code></td>
                <td><span class="pill <?= $row['status'] === 'skipped' || $row['status'] === 'error' ? 'pill--warn' : '' ?>"><?= e($row['status']) ?></span></td>
                <td><?= $row['messages'] === [] ? '—' : e(implode(' ', $row['messages'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
