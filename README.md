# Wirasat / Faraid calculation engine

A pure PHP engine that takes a list of surviving heirs and returns their
Islamic inheritance shares as exact fractions, with the reason for every
share and the reason for every exclusion.

This repository is **the engine only** — no web interface, no database, no
blog. That is deliberate: the engine is built and reviewed before any
interface exists, because a UI wrapped around unverified rules is worse than
no tool at all.

> ## Not ready for public use
>
> No qualified scholar has reviewed these rules yet. The expected answers in
> the test suite are a developer's reading of the classical rule tables, cited
> case by case but unsigned. The test runner says so on every run.
>
> Faraid is not a percentage split, and a wrong answer here affects real family
> disputes and real money. See
> [docs/REVIEW-CHECKLIST.md](docs/REVIEW-CHECKLIST.md) for what has to happen
> before this ships.

## What it does

- All four Sunni schools — Hanafi, Shafi'i, Maliki, Hanbali — from one shared
  rule engine with a madhhab strategy object overriding only the divergent
  rules. The school is required; the engine never guesses one.
- Exact integer fraction arithmetic throughout. No floating point, so no
  0.333333 drift on thirds and sixths.
- The doctrines that naive calculators get wrong: awl, radd, Umariyyatan,
  Mushtaraka, Akdariyya, asaba ma'a'l-ghayr, and the grandfather competing
  with brothers under Zayd's doctrine.
- Estate deductions in order — funeral expenses, debts, then bequests capped
  at one third.
- Pakistan's MFLO 1961 section 4 (grandchildren of a predeceased child
  inheriting by representation) as an explicit, off-by-default toggle.
- A `reason_key` on every share and every exclusion, so the same engine output
  renders in English, Urdu, Arabic, French or Indonesian without the engine
  knowing anything about language.
- Loud failure: the shares must sum to exactly one, and the engine throws
  rather than returning a distribution that does not balance.

It does **not** attempt haml (unborn child), mafqud (missing person), khuntha
(indeterminate gender), dhawu al-arham (distant kindred) or munasakha. Where
it meets a case it cannot defend, it says so in `warnings` instead of
inventing a number.

## Running it

No dependencies are needed to use the engine or run the case suite:

```bash
php bin/run-tests.php              # every sourced case
php bin/run-tests.php --group=awl  # one group
php bin/run-tests.php --verbose    # list each case as it passes
php bin/fuzz.php 20000             # random heir combinations, checks nothing crashes or loses the estate
```

With Composer installed, the same cases run under PHPUnit:

```bash
composer install
./vendor/bin/phpunit
```

Calculate a case by hand:

```bash
php bin/calculate.php '{"madhhab":"hanafi","deceased_gender":"male",
  "estate_value":5000000,"deductions":{"funeral":50000,"debts":200000},
  "heirs":{"wives":1,"mother":true,"sons":2,"daughters":3}}'
```

## Using it from code

```php
require 'src/autoload.php';            // or vendor/autoload.php

$result = (new Faraid\Calculator())->calculate([
    'madhhab'         => 'hanafi',     // required — no default
    'deceased_gender' => 'male',
    'estate_value'    => 5000000,
    'deductions'      => ['funeral' => 50000, 'debts' => 200000, 'wasiyyah' => 0],
    'heirs'           => [
        'wives' => 1, 'mother' => true, 'sons' => 2, 'daughters' => 3,
    ],
]);

$result->toArray();
```

Output, trimmed:

```json
{
  "madhhab": "hanafi",
  "net_estate": 4750000,
  "denominator": 168,
  "awl_applied": false,
  "radd_applied": false,
  "shares": [
    {
      "heir": "wife", "count": 1,
      "numerator": 21, "denominator": 168,
      "fraction": "1/8", "percent": 12.5, "amount": 593750,
      "basis": "quranic", "reason_key": "wife_with_descendant"
    }
  ],
  "excluded": [],
  "warnings": []
}
```

The engine is a pure class: it takes an array, returns a `Result`, and touches
no database, no HTTP, no session and no clock. That is what makes it testable,
and testability is the only reason anyone should trust its output.

### Heir keys

`husband`, `wives`, `father`, `mother`, `paternal_grandfather`,
`maternal_grandmother`, `paternal_grandmother`, `sons`, `daughters`,
`sons_sons`, `sons_daughters`, `full_brothers`, `full_sisters`,
`consanguine_brothers`, `consanguine_sisters`, `uterine_siblings`,
`full_brother_sons`, `consanguine_brother_sons`, `paternal_uncles`,
`consanguine_paternal_uncles`, `paternal_uncle_sons`,
`consanguine_paternal_uncle_sons`.

An unrecognised key is an error, never a silently dropped heir.

### Disqualification

```php
'disqualified' => [
    ['heir' => 'sons', 'reason' => 'homicide', 'count' => 1],
],
```

Reasons are `homicide` and `different_religion`. A disqualified heir neither
inherits nor blocks anyone else.

### MFLO 1961 representation

```php
'apply_mflo_1961'      => true,
'predeceased_children' => [
    ['gender' => 'male', 'sons' => 2, 'daughters' => 0],
],
```

## Layout

```
src/Faraid/
  Fraction.php          exact integer fraction arithmetic
  HeirType.php          every heir class the engine knows
  Input.php             normalisation and validation
  Calculator.php        the rule engine
  CalculationState.php  per-run scratch space (the Calculator stays stateless)
  Share.php  Exclusion.php  Result.php
  Madhhab/              the four schools — only the divergent rules
tests/
  Cases/                sourced cases, one file per area
  Support/              loader and comparison harness
bin/
  run-tests.php  fuzz.php  calculate.php
docs/
  METHODOLOGY.md        what is implemented, on what authority
  REVIEW-CHECKLIST.md   what the reviewing scholars must settle
```

## The test suite is the build gate

109 cases across single heirs, spouses, descendants, siblings, awl, radd, the
named doctrines, exclusion chains, the madhhab divergences, MFLO 1961, estate
deductions and input validation.

Every case carries a `source` field naming the Qur'anic verse, hadith,
textbook or statute it comes from. **A case with no source is rejected by the
loader.** A `verified_by` field records the scholar who confirmed the expected
answer in writing; it is empty everywhere today, and the runner reports that
count on every run.

## Next

The engine comes first and everything else waits on the review. After that,
in order: the calculator interface with the madhhab selector and per-country
legal notes, then the blog system with its validation baked into the publish
path, then the trust pages, then one locale at a time with articles written
for that audience rather than machine-translated.
