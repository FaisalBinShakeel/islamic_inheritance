# Scholar review checklist

The engine is not cleared for public use until this document is complete. The
test runner prints a warning on every run naming how many cases still carry no
sign-off.

Correctness here outranks every other requirement, including launch date. A
wrong answer in this domain affects real family disputes and real money.

## How a case is signed off

Each case in `tests/Cases/*.php` has a `source` field (required) and a
`verified_by` field (currently absent everywhere, which means null).

To record a sign-off, add to the case:

```php
'verified_by' => 'Mufti <full name>, <institution>, <date>',
```

Only add it for cases the named reviewer has confirmed **in writing**, against
the expected answer as written. The reviewer is confirming the numbers, not
the code.

A Hanafi Mufti cannot sign off Maliki output. Each school needs its own
reviewer.

## Sign-off log

| School | Reviewer | Credentials | Date | Cases covered |
| --- | --- | --- | --- | --- |
| Hanafi | _pending_ | | | |
| Shafi'i | _pending_ | | | |
| Maliki | _pending_ | | | |
| Hanbali | _pending_ | | | |

## What to put in front of the reviewer

1. [docs/METHODOLOGY.md](METHODOLOGY.md) — the rules as implemented.
2. The case files for their school, as a plain list of heirs and expected
   shares. `php bin/run-tests.php --verbose` prints every case title.
3. The open questions below, which are the points where the implementation had
   to choose and the choice is contestable.

## Open questions — these need an answer before launch

### 1. Mushtaraka in the Hanbali school

The engine implements the classical Hanbali position (Ali's view): the full
brothers take nothing, the same outcome as Hanafi.

The product specification instead groups Hanbali with Shafi'i as sharing the
third. Both cannot be right. The engine raises the warning
`madhhab_hanbali_mushtaraka_under_review` on every affected result rather than
picking silently.

**Needed:** a Hanbali reviewer's ruling, then either confirm
`Hanbali::mushtarakaSharesWithFullSiblings()` returns `false` or flip it.

### 2. Who shares the third in Mushtaraka

Where the sharing view applies (Shafi'i, Maliki), the engine divides the third
equally per head among the uterine siblings **and the full brothers and full
sisters**.

**Needed:** confirmation that full sisters are included in that count, and
that the division is per head with no two-to-one rule.

### 3. Radd in the Maliki and Shafi'i schools

Classically the surplus goes to the public treasury, not back to the heirs.
Modern practice in both schools commonly applies radd. The engine applies radd
and raises `madhhab_radd_modern_practice_not_classical`.

**Needed:** each school's reviewer to confirm which behaviour the calculator
should present as its default, and what the result page should say about it.

### 4. Radd where a spouse is the only heir

The engine gives the spouse their fixed share and reports the rest as
undistributed, with a warning. It does not hand the surplus to the spouse.

**Needed:** confirmation that this is the right thing to show a user, or a
ruling that the surplus should go to the spouse in the absence of a bayt
al-mal.

### 5. The mu'adda reckoning

Where the grandfather competes with **both** full and consanguine siblings
outside the Hanafi school, the engine reckons with the full siblings only and
raises `muadda_not_implemented`.

**Needed:** either a ruling that this approximation is acceptable for v1 with
the warning shown, or the full rule to implement.

### 6. Grandfather, siblings and an inheriting daughter

Outside the Hanafi school, this combination gives the residue to the
grandfather and raises
`grandfather_with_siblings_and_daughters_not_modelled`.

**Needed:** the correct treatment, or confirmation that the case should be
declined rather than answered.

### 7. Umariyyatan with a grandfather in place of the father

The engine applies the doctrine only with the father. Where a grandfather
stands in his place, the mother takes a third of the whole estate by the
ordinary rules.

**Needed:** confirmation per school.

### 8. Grandmothers whose side is not stated

The input accepts an explicit `maternal_grandmother` and
`paternal_grandmother`. A bare `grandmothers` count is treated as maternal —
the weaker exclusion — and warned about, because a paternal grandmother is
additionally excluded by the father.

**Needed:** confirmation that defaulting to maternal with a warning is
acceptable, or a requirement that the interface must always ask which side.

### 9. Abu Hanifa versus his two companions on the grandfather

The engine takes Abu Hanifa's own view as the Hanafi default: the grandfather
excludes the brothers. Abu Yusuf and Muhammad al-Shaybani hold that the
brothers share with him.

**Needed:** confirmation that Abu Hanifa's view is the right default for a
Hanafi audience, and whether the companions' view should be offered as an
option.

## Legal review, separate from the fiqh review

### MFLO 1961 section 4 (Pakistan)

The representation module needs confirmation from a **Pakistani lawyer**, not
a scholar: the provision has been litigated repeatedly and the engine should
not state the current legal position without that check.

Specifically: whether representation applies to the children of a predeceased
daughter on the same footing as a predeceased son, and how far down the line
it runs.

### Other jurisdictions

Not implemented. Before any of them ships, each needs local legal advice —
particularly France, where forced heirship (*réserve héréditaire*) can make
Islamic shares legally unenforceable, and publishing anything advisory without
that advice would mislead.

## Before the engine is declared ready

- [ ] Every case in `tests/Cases/` carries a `verified_by` value
- [ ] All nine open questions above are closed, and the code matches the answers
- [ ] MFLO 1961 confirmed by a Pakistani lawyer
- [ ] Reviewers' names and credentials published on the About page
- [ ] A working way for users to report a suspected calculation error, and
      someone responsible for acting on it quickly
