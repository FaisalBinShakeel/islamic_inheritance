# Wirasat Calculator

An Islamic inheritance (faraid) calculator and guide library: a tested PHP
rule engine, a plain-PHP site around it, and a web installer that makes
setting it up the same three-screen job as any ordinary PHP application.

- **Install:** upload, open `/install.php`, answer three screens. See
  [INSTALL.md](INSTALL.md).
- **Requirements:** PHP 8.2+, and either SQLite (nothing to set up) or MySQL.
- **Dependencies:** none at runtime. No framework, no build step, no Node.

> ## Not ready for public promotion
>
> No qualified scholar has reviewed the inheritance rules yet. The expected
> answers in the test suite are a careful reading of the classical rule
> tables, cited case by case but unsigned. Every calculator result says so,
> and the test runner repeats it on every run.
>
> Faraid is not a percentage split, and a wrong answer here affects real
> family disputes and real money.
> [docs/REVIEW-CHECKLIST.md](docs/REVIEW-CHECKLIST.md) lists the nine open
> questions a reviewing scholar has to settle.

## What is here

### The engine

A pure PHP class: an array in, a result out. No database, no HTTP, no session,
no clock — which is what makes it testable, and testability is the only reason
anyone should trust its output.

- All four Sunni schools from **one** shared engine, plus an **Ahl-e-Hadith /
  Ghair Muqallid** option for those who do not follow a school taqlidan, with a
  strategy object overriding only the divergent rules. The position is
  required; the engine never guesses one.
- **Every result reports what each of the other positions produces** for the
  same heirs, so the calculator states what each one holds rather than
  presenting one figure as the answer. On ordinary estates it says plainly
  that all five agree — which is worth as much to a family as showing the
  differences in the few cases where they do not.
- **Exact integer fractions** throughout. No floating point, so no 0.333333
  drift on thirds and sixths.
- The doctrines simple calculators get wrong: awl, radd, Umariyyatan,
  Mushtaraka, Akdariyya, asaba ma'a'l-ghayr and bi'l-ghayr, and the
  grandfather competing with brothers under Zayd's doctrine.
- Estate deductions in order, with the bequest capped at one third.
- Pakistan's **MFLO 1961 s.4** representation as an explicit, off-by-default
  toggle, because it is statute rather than fiqh.
- A `reason_key` on every share and exclusion, so one calculation renders in
  any language without the engine knowing one exists.
- Shares must sum to exactly one, or the calculation throws.

### The site

- **Calculator** on one URL, no login, no page reload. Works with JavaScript
  off — the form posts and the server renders the same result.
- **Result as a document**: the heir table, a plain-language sentence for every
  share, who was excluded and by whom, what each other position says, a print
  stylesheet that produces a clean A4 page, copy-as-text and a WhatsApp share
  button.
- **A guide library of 38 pages** across English and Urdu — the basics, each
  heir's share, the doctrines, the schools, and the Islamic-will cluster for
  the UK, US, Canada and Australia — with categories, tag archives, authors,
  tables of contents, breadcrumbs and related posts.
- **English and Urdu** at launch, English at the root and every other language
  in a subfolder, with reciprocal hreflang and full RTL.
- **Admin**: posts with deletion, CSV import with a dry run, an error-report
  inbox, a keyword register, and an audit page that re-runs every publish rule
  across the whole site.
- **Report an error** form, because a calculator that quietly carries a wrong
  rule does real harm.

### SEO, enforced in code

Not a checklist — these are structural:

| Rule | How it is guaranteed |
| --- | --- |
| The H1 never silently diverges from the title | Both are columns, shown side by side with a live warning; the H1 defaults to the title |
| No hand-typed or `http://` canonical | Every canonical and `og:url` is built from one configured origin |
| No dead internal link ships | Publishing is blocked if any internal href fails to resolve |
| Meta description is never missing | Required field, live counter, blocks publishing |
| One page, one H1 | The layout renders it; a body containing an `<h1>` is rejected |
| No two posts chase one keyword | Unique index on (locale, target keyword) |

Plus JSON-LD per page type (WebApplication, FAQPage, Article, BreadcrumbList,
Blog, Organization), a generated `sitemap.xml` that includes the category and
tag archives with real `lastmod` values, and `robots.txt`. A test fetches every
URL the sitemap advertises and fails if any of them is not a 200.

## Running the tests

No dependencies needed:

```bash
php bin/run-tests.php            # 123 sourced engine cases
php bin/run-tests.php --verbose  # list each case
php bin/fuzz.php 20000           # random heir combinations
php bin/calculate.php '{"madhhab":"hanafi","deceased_gender":"male","heirs":{"wives":1,"sons":2,"daughters":3}}'
```

With Composer, the same cases run under PHPUnit alongside the site tests:

```bash
composer install
./vendor/bin/phpunit
```

336 tests. The site tests install a throwaway copy of the whole site into a
temporary SQLite file and fetch every published URL through the real kernel,
checking the invariants in the table above.

## Using the engine on its own

```php
require 'src/autoload.php';            // or vendor/autoload.php

$result = (new Faraid\Calculator())->calculate([
    'madhhab'         => 'hanafi',     // required — no default
    'deceased_gender' => 'male',
    'estate_value'    => 5000000,
    'deductions'      => ['funeral' => 50000, 'debts' => 200000, 'wasiyyah' => 0],
    'heirs'           => ['wives' => 1, 'mother' => true, 'sons' => 2, 'daughters' => 3],
]);

$result->toArray();
```

Heir keys: `husband`, `wives`, `father`, `mother`, `paternal_grandfather`,
`maternal_grandmother`, `paternal_grandmother`, `sons`, `daughters`,
`sons_sons`, `sons_daughters`, `full_brothers`, `full_sisters`,
`consanguine_brothers`, `consanguine_sisters`, `uterine_siblings`,
`full_brother_sons`, `consanguine_brother_sons`, `paternal_uncles`,
`consanguine_paternal_uncles`, `paternal_uncle_sons`,
`consanguine_paternal_uncle_sons`. An unrecognised key is an error, never a
silently dropped heir.

Disqualification (`homicide`, `different_religion`) and MFLO representation:

```php
'disqualified'         => [['heir' => 'sons', 'reason' => 'homicide', 'count' => 1]],
'apply_mflo_1961'      => true,
'predeceased_children' => [['gender' => 'male', 'sons' => 2, 'daughters' => 0]],
```

## Layout

```
src/Faraid/      the rule engine — Fraction, HeirType, Input, Calculator, Madhhab/
src/App/         the site — Config, Router, Seo, Locale, Translator, Controller/
resources/views  plain PHP templates, one layout
resources/lang   en.php and ur.php, every string in both
database/content one file per seeded page, editable without touching code
public/          document root: index.php, install.php, assets
deploy/          nginx and Apache configs
docs/            METHODOLOGY.md and REVIEW-CHECKLIST.md
tests/           sourced engine cases, plus SEO, i18n and route tests
bin/             run-tests.php, fuzz.php, calculate.php
```

## Adding a language

1. Copy `resources/lang/en.php` to `resources/lang/xx.php` and translate it —
   `TranslationTest` fails if a key is missing.
2. Add the code to `locales` in `config.php`.
3. Write original articles for that audience in `database/content/xx/`, or
   through the admin. Do not machine-translate: the legal position, the school
   and the search phrasing all differ.

The locale then has its own subfolder, hreflang entries, sitemap URLs and
language switcher automatically.

## Status

The engine, the site, the installer and the tests are done. The fiqh review is
not, and nothing should be promoted until it is. See
[docs/REVIEW-CHECKLIST.md](docs/REVIEW-CHECKLIST.md).

A round of source-checking against IslamQA, IslamWeb and Pakistani case law
closed several of those questions and **corrected three defects**, all recorded
with quotations and URLs in
[docs/SOURCES-CONSULTED.md](docs/SOURCES-CONSULTED.md):

- Radd was being applied in the **Maliki and Shafi'i** schools. Ibn Qudamah
  names both alongside Zayd b. Thabit: the surplus goes to the treasury. Fixed.
- The **Ahl-e-Hadith** option handed a sole surviving spouse the whole estate,
  on a report from Uthman that the same source explains away against a reported
  consensus. Fixed — and with it, that option now matches Hanafi throughout.
- **MFLO 1961 section 4** was implemented as the section is worded. Pakistani
  courts apply the notional-share construction of *Kamal Khan v Mst. Zainab*,
  under which the predeceased child's widow and mother also take a part. Fixed,
  with the textual reading kept as an option.

Reading a fatwa site is not a scholar review, so
[docs/REVIEW-CHECKLIST.md](docs/REVIEW-CHECKLIST.md) still stands. Two of the
Ahl-e-Hadith choices remain unverified and every result under that option says
so.
