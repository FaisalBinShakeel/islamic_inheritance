<?php

declare(strict_types=1);

/**
 * The installer.
 *
 * Upload the files, open this page, answer three screens. It checks the
 * environment, creates the tables, seeds the starting content, creates your
 * administrator account and writes config.php.
 *
 * Delete this file once you are done — the installer refuses to run again
 * anyway while config.php exists.
 */

require dirname(__DIR__) . '/src/autoload.php';

use App\Config;
use App\Installer;
use App\Locale;

Config::load();

$alreadyInstalled = Config::isInstalled();
$step = (string) ($_POST['step'] ?? ($alreadyInstalled ? 'done' : 'requirements'));
$errors = [];
$report = [];
$form = $_POST;

if (!$alreadyInstalled && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'test') {
        $test = Installer::testDatabase($form);
        if ($test['ok']) {
            $step = 'account';
        } else {
            $errors[] = 'Could not connect: ' . $test['message'];
            $step = 'database';
        }
    } elseif ($step === 'install') {
        $result = Installer::install($form);
        if ($result['ok']) {
            $step = 'finished';
            $report = $result['report'];
        } else {
            $errors[] = $result['message'];
            $step = 'account';
        }
    }
}

$sqliteAvailable = in_array('sqlite', PDO::getAvailableDrivers(), true);
$mysqlAvailable = in_array('mysql', PDO::getAvailableDrivers(), true);
$value = static fn (string $key, string $default = '') => e((string) ($form[$key] ?? $default));
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Install — Wirasat Calculator</title>
<link rel="stylesheet" href="/assets/css/site.css">
<style>
    body { display: flex; align-items: flex-start; justify-content: center; padding: 3rem 1rem; }
    .installer { width: 100%; max-width: 640px; }
    .steps { display: flex; gap: .5rem; font-size: .78rem; text-transform: uppercase; letter-spacing: .06em; color: var(--ink-faint); margin-bottom: 1.5rem; flex-wrap: wrap; }
    .steps span[aria-current] { color: var(--green); font-weight: 700; }
    .check { display: flex; gap: .6rem; padding: .5rem 0; border-bottom: 1px solid var(--line); align-items: baseline; }
    .check b { flex: none; width: 1.2rem; }
    .check .ok { color: var(--green); }
    .check .no { color: var(--danger); }
    .check small { display: block; color: var(--ink-faint); }
</style>
</head>
<body>
<div class="installer">
    <h1 style="margin-bottom:.25rem">Wirasat Calculator</h1>
    <p class="hint">Islamic inheritance calculator and guide library. This installer sets up the database and your first administrator account.</p>

    <nav class="steps" aria-label="Installer steps">
        <?php foreach (['requirements' => 'Check', 'database' => 'Database', 'account' => 'Site &amp; account', 'finished' => 'Done'] as $key => $label): ?>
            <span <?= $step === $key || ($step === 'test' && $key === 'database') ? 'aria-current="step"' : '' ?>><?= $label ?></span>
        <?php endforeach; ?>
    </nav>

    <?php foreach ($errors as $error): ?>
        <p class="alert" role="alert"><?= e($error) ?></p>
    <?php endforeach; ?>

    <?php if ($alreadyInstalled && $step !== 'finished'): ?>
        <div class="card">
            <h2 style="margin-top:0">Already installed</h2>
            <p>config.php exists, so the installer will not run again. Delete <code>public/install.php</code> now — it is no longer needed.</p>
            <p><a class="btn" href="/">Open the site</a> <a class="btn btn--ghost" href="/admin">Admin</a></p>
        </div>

    <?php elseif ($step === 'requirements'): ?>
        <div class="card">
            <h2 style="margin-top:0">Environment</h2>
            <?php foreach (Installer::requirements() as $check): ?>
                <div class="check">
                    <b class="<?= $check['ok'] ? 'ok' : 'no' ?>"><?= $check['ok'] ? '✓' : '✕' ?></b>
                    <div>
                        <?= e($check['name']) ?>
                        <small><?= e($check['detail']) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (Installer::requirementsMet()): ?>
                <form method="post" style="margin-top:1.25rem">
                    <input type="hidden" name="step" value="database">
                    <button class="btn" type="submit">Continue</button>
                </form>
            <?php else: ?>
                <p class="alert" style="margin-top:1.25rem">Fix the items marked ✕ and reload this page.</p>
            <?php endif; ?>
        </div>

    <?php elseif ($step === 'database'): ?>
        <form method="post" class="card">
            <input type="hidden" name="step" value="test">
            <h2 style="margin-top:0">Database</h2>

            <label id="driver-label">Which database?</label>
            <div class="choice" role="radiogroup" aria-labelledby="driver-label">
                <?php if ($sqliteAvailable): ?>
                    <input type="radio" id="d-sqlite" name="driver" value="sqlite" <?= ($form['driver'] ?? 'sqlite') === 'sqlite' ? 'checked' : '' ?>>
                    <label for="d-sqlite">SQLite</label>
                <?php endif; ?>
                <?php if ($mysqlAvailable): ?>
                    <input type="radio" id="d-mysql" name="driver" value="mysql" <?= ($form['driver'] ?? '') === 'mysql' ? 'checked' : '' ?>>
                    <label for="d-mysql">MySQL / MariaDB</label>
                <?php endif; ?>
            </div>
            <p class="hint">SQLite needs nothing set up — it keeps everything in one file inside <code>database/</code>, which is enough for a site of this size. Choose MySQL if you already have a database, or expect heavy traffic.</p>

            <div id="sqlite-fields">
                <div class="field">
                    <label for="db_path">SQLite file</label>
                    <input type="text" id="db_path" name="db_path" value="<?= $value('db_path', Installer::defaultSqlitePath()) ?>">
                    <p class="hint">Keep it outside the public folder. The default is already safe.</p>
                </div>
            </div>

            <div id="mysql-fields" class="grid">
                <div class="field">
                    <label for="db_host">Host</label>
                    <input type="text" id="db_host" name="db_host" value="<?= $value('db_host', '127.0.0.1') ?>">
                </div>
                <div class="field">
                    <label for="db_port">Port</label>
                    <input type="number" id="db_port" name="db_port" value="<?= $value('db_port', '3306') ?>">
                </div>
                <div class="field">
                    <label for="db_name">Database name</label>
                    <input type="text" id="db_name" name="db_name" value="<?= $value('db_name') ?>">
                </div>
                <div class="field">
                    <label for="db_user">User</label>
                    <input type="text" id="db_user" name="db_user" value="<?= $value('db_user') ?>">
                </div>
                <div class="field">
                    <label for="db_pass">Password</label>
                    <input type="password" id="db_pass" name="db_pass" value="">
                </div>
            </div>

            <p class="hint">The database must already exist for MySQL; the tables are created for you.</p>
            <button class="btn" type="submit">Test the connection</button>
        </form>

    <?php elseif ($step === 'account'): ?>
        <form method="post" class="card">
            <input type="hidden" name="step" value="install">
            <?php foreach (['driver', 'db_path', 'db_host', 'db_port', 'db_name', 'db_user', 'db_pass'] as $carried): ?>
                <input type="hidden" name="<?= e($carried) ?>" value="<?= $value($carried) ?>">
            <?php endforeach; ?>

            <h2 style="margin-top:0">Your site</h2>
            <div class="field">
                <label for="url">Site address</label>
                <input type="text" id="url" name="url" value="<?= $value('url', Installer::guessUrl()) ?>" required>
                <p class="hint">Every canonical URL and sitemap entry is built from this, so use the address people will actually visit, with https.</p>
            </div>
            <div class="field">
                <label for="site_name">Site name</label>
                <input type="text" id="site_name" name="site_name" value="<?= $value('site_name', 'Wirasat Calculator') ?>" required>
            </div>
            <div class="field">
                <label for="currency">Currency symbol or code (optional)</label>
                <input type="text" id="currency" name="currency" value="<?= $value('currency', 'PKR') ?>">
            </div>

            <fieldset style="margin-top:1.25rem">
                <legend style="font-size:.95rem">Languages</legend>
                <p class="hint">English is served at the root; each other language sits in its own folder, such as /ur/.</p>
                <?php foreach (['en' => 'English', 'ur' => 'اردو (Urdu)'] as $code => $label): ?>
                    <div class="switchrow">
                        <input type="checkbox" id="loc-<?= e($code) ?>" name="locales[]" value="<?= e($code) ?>"
                               <?= $code === 'en' ? 'checked disabled' : (in_array($code, (array) ($form['locales'] ?? ['en', 'ur']), true) ? 'checked' : '') ?>>
                        <label for="loc-<?= e($code) ?>"><?= e($label) ?></label>
                    </div>
                <?php endforeach; ?>
                <input type="hidden" name="locales[]" value="en">
            </fieldset>

            <h2>Administrator</h2>
            <div class="field">
                <label for="admin_name">Your name</label>
                <input type="text" id="admin_name" name="admin_name" value="<?= $value('admin_name') ?>" required>
            </div>
            <div class="field">
                <label for="admin_email">Email</label>
                <input type="email" id="admin_email" name="admin_email" value="<?= $value('admin_email') ?>" required>
            </div>
            <div class="field">
                <label for="admin_password">Password</label>
                <input type="password" id="admin_password" name="admin_password" minlength="10" required>
                <p class="hint">At least 10 characters.</p>
            </div>

            <button class="btn" type="submit" style="margin-top:1rem">Install</button>
        </form>

    <?php elseif ($step === 'finished'): ?>
        <div class="card">
            <h2 style="margin-top:0">Installed</h2>
            <p>The database is ready and the starting content is in place.</p>
            <?php if ($report !== []): ?>
                <p class="hint">
                    <?= (int) ($report['categories'] ?? 0) ?> categories,
                    <?= (int) ($report['posts'] ?? 0) ?> pages and guides created<?= !empty($report['skipped']) ? ', ' . (int) $report['skipped'] . ' already present' : '' ?>.
                </p>
            <?php endif; ?>

            <p class="alert" role="alert"><strong>Delete <code>public/install.php</code> now.</strong> It is not needed again.</p>

            <h3>Next</h3>
            <ol>
                <li>Sign in to the admin and check the Audit page — it lists every SEO problem across the site.</li>
                <li>Submit <code>/sitemap.xml</code> to Google Search Console and Bing Webmaster Tools.</li>
                <li>Read <code>docs/REVIEW-CHECKLIST.md</code>: the inheritance rules still need a scholar's sign-off before you promote the site.</li>
            </ol>

            <p><a class="btn" href="/">Open the site</a> <a class="btn btn--ghost" href="/admin">Sign in to the admin</a></p>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var sqlite = document.getElementById('sqlite-fields');
    var mysql = document.getElementById('mysql-fields');
    if (!sqlite || !mysql) { return; }
    function sync() {
        var chosen = document.querySelector('input[name="driver"]:checked');
        var driver = chosen ? chosen.value : 'sqlite';
        sqlite.hidden = driver !== 'sqlite';
        mysql.hidden = driver !== 'mysql';
    }
    Array.prototype.forEach.call(document.querySelectorAll('input[name="driver"]'), function (input) {
        input.addEventListener('change', sync);
    });
    sync();
})();
</script>
</body>
</html>
