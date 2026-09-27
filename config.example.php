<?php

/**
 * Local configuration. Copy to config.php and fill in, or let the web
 * installer at /install.php write it for you.
 *
 * config.php is git-ignored — it holds database credentials and never belongs
 * in the repository.
 */

return [
    // The canonical origin, with scheme and no trailing slash.
    // Every canonical URL, og:url and sitemap entry is built from this one
    // value, so an http:// canonical on an https:// site is impossible.
    'url' => 'https://example.com',

    'site_name' => 'Wirasat Calculator',

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'wirasat',
        'user' => 'wirasat',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    // Locales that are live. 'en' must always be present; it is served at the
    // site root and every other locale sits in a subfolder.
    'locales' => ['en', 'ur'],

    'default_madhhab' => null,   // null = the user must choose

    'contact_email' => 'you@example.com',

    // Shown on the About page and in Article author schema.
    'organisation' => 'Wirasat Calculator',

    // Optional: verification tokens, left empty until you have them.
    'google_site_verification' => '',
    'bing_site_verification' => '',

    // Set false once the fiqh review in docs/REVIEW-CHECKLIST.md is complete.
    // While true, every calculator result carries the unreviewed-engine notice.
    'engine_unreviewed' => true,

    'debug' => false,
];
