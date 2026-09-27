# Installing

Upload the files, open the site, answer three screens. That is the whole
process — there is no build step, no Composer requirement, no Node, and no
command line unless you want one.

## What the server needs

- **PHP 8.2 or newer** with `mbstring`, `json` and `pdo`
- **A database**, either:
  - **SQLite** (`pdo_sqlite`) — nothing to set up, and enough for a site of
    this size; or
  - **MySQL or MariaDB** (`pdo_mysql`) — create an empty database first, the
    tables are created for you
- Apache with `mod_rewrite`, or nginx

That is all. No Redis, no queue, no cron job.

## Install in three steps

1. **Upload the project** to your server.
2. **Point the document root at `public/`.** If your host will not let you —
   most shared hosting will not — upload everything into `public_html` as it
   is; the `.htaccess` in the project root forwards requests into `public/`
   and blocks direct access to the code.
3. **Open `https://yourdomain.com/install.php`** and follow it:
   - it checks the environment and tells you exactly what is missing;
   - you choose SQLite or MySQL;
   - you set the site address, the site name and the languages;
   - you create your administrator account.

It then creates the tables, writes `config.php`, and seeds the calculator's
guides and trust pages so the site is not empty on day one.

**Delete `public/install.php` when it finishes.** It refuses to run again
while `config.php` exists, but there is no reason to leave it there.

## aaPanel, cPanel and similar

1. Create the site, and set the **document root** to `…/public` if the panel
   allows it.
2. Upload and extract the project.
3. For MySQL, create a database and user in the panel first, then give the
   installer those details.
4. Open `/install.php`.

If you are on nginx and the panel does not read `.htaccess`, copy the rules
from `deploy/nginx.conf` into the site's configuration. `deploy/apache.conf`
has the Apache equivalent.

## Installing by hand instead

```bash
cp config.example.php config.php
# edit config.php: url, site_name, db settings
php -r 'require "src/autoload.php";
    $pdo = App\Database::connect(App\Database::settings());
    App\Schema::migrate($pdo);
    App\Seeder::run($pdo);'
```

Then insert an administrator row, or run the installer once and let it do it.

## Permissions

- The project root must be writable while the installer runs, so it can write
  `config.php`. Make it read-only afterwards.
- For SQLite, `database/` must stay writable — that is where the database
  lives. It sits outside `public/`, so it is not reachable over HTTP, and the
  supplied server configs block it as well.
- Nothing else needs write access.

## After installing

1. Sign in at `/admin` and open **Audit** — it re-runs every publish rule
   across the whole site and lists anything to fix.
2. Submit `/sitemap.xml` to Google Search Console and Bing Webmaster Tools,
   then request indexing for each URL rather than waiting to be crawled.
3. Add your verification tokens to `config.php`
   (`google_site_verification`, `bing_site_verification`).
4. **Read `docs/REVIEW-CHECKLIST.md`.** The inheritance rules still need a
   qualified scholar's sign-off, and until they have it every result carries a
   notice saying so. Leave `engine_unreviewed` set to `true` in `config.php`
   until that review is done.

## Moving from SQLite to MySQL later

The schema is generated per driver, so the move is a data export rather than a
rewrite. Create the MySQL database, change the `db` block in `config.php`, run
the migrate snippet above against the new connection, and copy the rows across
with any SQLite-to-MySQL tool. Nothing in the application changes.

## Updating

Replace the files, keeping `config.php` and, if you use SQLite,
`database/site.sqlite`. Re-run the migrate snippet above; it only creates what
is missing, so it is safe to run again.
