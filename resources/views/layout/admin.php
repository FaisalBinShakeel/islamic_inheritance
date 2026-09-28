<?php
/** @var App\Seo $seo @var string $content */
use App\Auth;
use App\Config;
use App\Csrf;
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($seo->documentTitle()) ?></title>
    <link rel="stylesheet" href="<?= e(asset('/assets/css/site.css')) ?>">
</head>
<body>
<?php if (Auth::check()): ?>
<div class="admin-bar">
    <div class="wrap">
        <strong style="font-weight:600"><?= e(Config::siteName()) ?></strong>
        <a href="/admin">Dashboard</a>
        <a href="/admin/posts">Posts</a>
        <a href="/admin/posts/new">New</a>
        <a href="/admin/import">Import</a>
        <a href="/admin/audit">Audit</a>
        <a href="/admin/keywords">Keywords</a>
        <a href="/admin/reports">Reports</a>
        <a href="/" style="margin-inline-start:auto">View site</a>
        <form method="post" action="/admin/logout" style="display:inline">
            <?= Csrf::field() ?>
            <button type="submit" style="background:none;border:0;color:rgba(255,255,255,.85);cursor:pointer;font:inherit;padding:0">Sign out</button>
        </form>
    </div>
</div>
<?php endif; ?>

<main id="main">
    <div class="wrap">
        <h1><?= e($seo->heading()) ?></h1>
        <?= $content ?>
    </div>
</main>
</body>
</html>
