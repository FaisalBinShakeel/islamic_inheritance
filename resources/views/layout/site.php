<?php
/**
 * The one site layout.
 *
 * The <h1> is rendered here, from the Seo object, so a page physically cannot
 * ship with two of them or with none. Templates render everything below it.
 *
 * @var App\Seo $seo
 * @var string $content
 */

use App\Config;
use App\Locale;

$locale = Locale::active();
$dir = Locale::direction();
$crumbs = $seo->crumbs();
?>
<!doctype html>
<html lang="<?= e(Locale::hreflang($locale)) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f4c3a">
    <?= $seo->renderHead() ?>

    <link rel="icon" href="<?= e(asset('/assets/img/icon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('/assets/css/site.css')) ?>">
    <?php if ($token = Config::get('google_site_verification')): ?>
    <meta name="google-site-verification" content="<?= e((string) $token) ?>">
    <?php endif; ?>
    <?php if ($token = Config::get('bing_site_verification')): ?>
    <meta name="msvalidate.01" content="<?= e((string) $token) ?>">
    <?php endif; ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="skip" href="#main"><?= e(t('ui.nav.skip')) ?></a>

<header class="masthead">
    <div class="wrap masthead__inner">
        <a class="brand" href="<?= e(lp()) ?>">
            <svg class="brand__mark" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <circle cx="12" cy="12" r="11" fill="none" stroke="#0f4c3a" stroke-width="1.5"/>
                <path d="M12 3v18M4 8h16M4 16h16" stroke="#0f4c3a" stroke-width="1.5" fill="none"/>
            </svg>
            <?= e(Config::siteName()) ?>
        </a>

        <nav class="nav" aria-label="Main">
            <a href="<?= e(lp()) ?>"><?= e(t('ui.nav.calculator')) ?></a>
            <a href="<?= e(lp('blog')) ?>"><?= e(t('ui.nav.guides')) ?></a>
            <a href="<?= e(lp('methodology')) ?>"><?= e(t('ui.nav.methodology')) ?></a>
            <a href="<?= e(lp('about')) ?>"><?= e(t('ui.nav.about')) ?></a>
        </nav>

        <?php if (count(Locale::enabled()) > 1): ?>
        <div class="langs" role="group" aria-label="<?= e(t('ui.nav.language')) ?>">
            <?php foreach (Locale::enabled() as $code): ?>
                <a href="<?= e(Locale::path('', $code)) ?>"
                   lang="<?= e(Locale::hreflang($code)) ?>"
                   hreflang="<?= e(Locale::hreflang($code)) ?>"
                   <?= $code === $locale ? 'aria-current="true"' : '' ?>><?= e(Locale::nativeName($code)) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</header>

<main id="main">
    <div class="wrap">
        <?php if ($crumbs !== []): ?>
        <nav class="crumbs" aria-label="Breadcrumb">
            <ol>
                <?php foreach ($crumbs as $index => $crumb): ?>
                    <li><?php if ($index === count($crumbs) - 1): ?>
                        <span aria-current="page"><?= e($crumb['name']) ?></span>
                    <?php else: ?>
                        <a href="<?= e($crumb['path']) ?>"><?= e($crumb['name']) ?></a>
                    <?php endif; ?></li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php endif; ?>

        <h1><?= e($seo->heading()) ?></h1>
        <?= $content ?>
    </div>
</main>

<footer class="foot">
    <div class="wrap">
        <div class="foot__cols">
            <div>
                <h2><?= e(t('ui.nav.calculator')) ?></h2>
                <ul>
                    <li><a href="<?= e(lp()) ?>"><?= e(t('ui.calculator')) ?></a></li>
                    <li><a href="<?= e(lp('blog')) ?>"><?= e(t('ui.nav.guides')) ?></a></li>
                </ul>
            </div>
            <div>
                <h2><?= e(t('ui.nav.about')) ?></h2>
                <ul>
                    <li><a href="<?= e(lp('about')) ?>"><?= e(t('ui.nav.about')) ?></a></li>
                    <li><a href="<?= e(lp('methodology')) ?>"><?= e(t('ui.nav.methodology')) ?></a></li>
                    <li><a href="<?= e(lp('sources')) ?>">Sources</a></li>
                    <li><a href="<?= e(lp('disclaimer')) ?>">Disclaimer</a></li>
                    <li><a href="<?= e(lp('contact')) ?>"><?= e(t('ui.nav.contact')) ?></a></li>
                </ul>
            </div>
            <div>
                <h2><?= e(t('ui.nav.language')) ?></h2>
                <ul>
                    <?php foreach (Locale::enabled() as $code): ?>
                        <li><a href="<?= e(Locale::path('', $code)) ?>" hreflang="<?= e(Locale::hreflang($code)) ?>"><?= e(Locale::nativeName($code)) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <p><?= e(Config::siteName()) ?> — <?= e(t('ui.footer.rights')) ?></p>
    </div>
</footer>
<?php if (!empty($pageScript)): ?>
<script src="<?= e(asset($pageScript)) ?>" defer></script>
<?php endif; ?>
<?php if (!empty($inlineScript)): ?>
<script><?= $inlineScript ?></script>
<?php endif; ?>
</body>
</html>
