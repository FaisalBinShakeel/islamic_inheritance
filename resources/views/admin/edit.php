<?php
use App\Csrf;
use App\Locale;
use App\PostValidator;
/** @var array|null $post @var int|null $id @var list $errors @var list $warnings @var list $categories @var list $authors */
$f = static fn (string $key, string $default = '') => e((string) ($post[$key] ?? $default));
$action = $id !== null ? '/admin/posts/' . $id : '/admin/posts';
?>
<?php if (isset($_GET['saved'])): ?><p class="alert alert--ok" role="status">Saved.</p><?php endif; ?>
<?php foreach ($errors as $error): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endforeach; ?>
<?php foreach ($warnings as $warning): ?><p class="note note--normal"><?= e($warning) ?></p><?php endforeach; ?>

<form method="post" action="<?= e($action) ?>" class="card">
    <?= Csrf::field() ?>

    <!--
        Title and H1 sit side by side on purpose, with a warning when they
        diverge. That divergence is the bug this layout exists to prevent.
    -->
    <div class="grid" style="margin-bottom:.4rem">
        <div class="field">
            <label for="title">Title — the &lt;title&gt; tag</label>
            <input type="text" id="title" name="title" value="<?= $f('title') ?>" required maxlength="255">
            <p class="counter" id="title-counter"></p>
        </div>
        <div class="field">
            <label for="h1">H1 — the visible heading</label>
            <input type="text" id="h1" name="h1" value="<?= $f('h1') ?>" maxlength="255" placeholder="Leave blank to copy the title">
            <p class="counter" id="h1-warning"></p>
        </div>
    </div>

    <div class="grid" style="margin-bottom:1rem">
        <div class="field">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" value="<?= $f('slug') ?>" maxlength="190">
            <p class="hint">A slug never changes once published.</p>
        </div>
        <div class="field">
            <label for="locale">Locale</label>
            <select id="locale" name="locale">
                <?php foreach (Locale::enabled() as $code): ?>
                    <option value="<?= e($code) ?>" <?= ($post['locale'] ?? 'en') === $code ? 'selected' : '' ?>><?= e(Locale::englishName($code)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach (['draft' => 'Draft', 'scheduled' => 'Scheduled', 'published' => 'Published'] as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= ($post['status'] ?? 'draft') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="field" style="margin-bottom:1rem">
        <label for="meta_description">Meta description — required</label>
        <textarea id="meta_description" name="meta_description" style="min-height:70px" maxlength="<?= PostValidator::META_LIMIT ?>" required><?= $f('meta_description') ?></textarea>
        <p class="counter" id="meta-counter"></p>
    </div>

    <div class="grid" style="margin-bottom:1rem">
        <div class="field">
            <label for="target_keyword">Target keyword</label>
            <input type="text" id="target_keyword" name="target_keyword" value="<?= $f('target_keyword') ?>" maxlength="190">
            <p class="hint">Unique per locale — the keyword register is enforced by the database.</p>
        </div>
        <div class="field">
            <label for="translation_group">Translation group</label>
            <input type="text" id="translation_group" name="translation_group" value="<?= $f('translation_group') ?>" maxlength="120">
            <p class="hint">Same value across locales links them for hreflang.</p>
        </div>
        <div class="field">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">—</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= (int) ($post['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e((string) $category['name']) ?> (<?= e((string) $category['locale']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="author_id">Author</label>
            <select id="author_id" name="author_id">
                <option value="">—</option>
                <?php foreach ($authors as $author): ?>
                    <option value="<?= (int) $author['id'] ?>" <?= (int) ($post['author_id'] ?? 0) === (int) $author['id'] ? 'selected' : '' ?>><?= e((string) $author['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="field" style="margin-bottom:1rem">
        <label for="body">Body (HTML)</label>
        <textarea id="body" name="body" style="min-height:420px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.88rem"><?= $f('body') ?></textarea>
        <p class="hint">Use H2 and H3 only — the layout renders the page's single H1. Every image needs alt text. Internal links are checked before publishing.</p>
    </div>

    <div class="grid" style="margin-bottom:1rem">
        <div class="field">
            <label for="cover_image">Cover image path</label>
            <input type="text" id="cover_image" name="cover_image" value="<?= $f('cover_image') ?>">
        </div>
        <div class="field">
            <label for="image_alt">Cover image alt text</label>
            <input type="text" id="image_alt" name="image_alt" value="<?= $f('image_alt') ?>">
        </div>
        <div class="field">
            <label for="published_at">Publish at (UTC)</label>
            <input type="text" id="published_at" name="published_at" value="<?= $f('published_at') ?>" placeholder="YYYY-MM-DD HH:MM:SS">
        </div>
    </div>

    <div class="actions">
        <button class="btn" type="submit">Save</button>
        <?php if ($id !== null && ($post['status'] ?? '') === 'published'): ?>
            <a class="btn btn--ghost" target="_blank" rel="noopener"
               href="<?= e(Locale::path((($post['category_slug'] ?? '') === 'pages' ? '' : 'blog/') . (string) $post['slug'], (string) $post['locale'])) ?>">View</a>
        <?php endif; ?>
    </div>
</form>

<script>
(function () {
    var title = document.getElementById('title');
    var h1 = document.getElementById('h1');
    var meta = document.getElementById('meta_description');
    var titleCounter = document.getElementById('title-counter');
    var h1Warning = document.getElementById('h1-warning');
    var metaCounter = document.getElementById('meta-counter');
    var brandLength = <?= (int) (mb_strlen(App\Config::siteName()) + 3) ?>;

    function sync() {
        var withBrand = title.value.length + brandLength;
        titleCounter.textContent = title.value.length + ' characters (' + withBrand + ' with the brand suffix, limit 60)';
        titleCounter.className = withBrand > 60 ? 'counter counter--over' : 'counter';

        var effective = h1.value.trim() === '' ? title.value : h1.value;
        if (effective !== title.value) {
            h1Warning.textContent = 'The H1 differs from the title. Deliberate?';
            h1Warning.className = 'counter counter--over';
        } else {
            h1Warning.textContent = 'Matches the title.';
            h1Warning.className = 'counter';
        }

        metaCounter.textContent = meta.value.length + ' / 155 recommended, 160 maximum';
        metaCounter.className = meta.value.length > 155 ? 'counter counter--over' : 'counter';
    }

    [title, h1, meta].forEach(function (field) { field.addEventListener('input', sync); });
    sync();
})();
</script>
