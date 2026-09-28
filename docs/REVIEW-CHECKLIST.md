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

| Position | Reviewer | Credentials | Date | Cases covered |
| --- | --- | --- | --- | --- |
| Hanafi | _pending_ | | | |
| Shafi'i | _pending_ | | | |
| Maliki | _pending_ | | | |
| Hanbali | _pending_ | | | |
| Ahl-e-Hadith | _pending_ | | | |

## What to put in front of the reviewer

1. [docs/METHODOLOGY.md](METHODOLOGY.md) — the rules as implemented.
2. The case files for their school, as a plain list of heirs and expected
   shares. `php bin/run-tests.php --verbose` prints every case title.
3. The open questions below, which are the points where the implementation had
   to choose and the choice is contestable.

## What has been looked up since

[SOURCES-CONSULTED.md](SOURCES-CONSULTED.md) records a round of source-checking
against IslamQA, IslamWeb and Pakistani case law. It closed three of the
questions below and corrected three defects in the engine. Reading a fatwa site
is **not** a scholar review, so everything that remains open below still stands
— but several items now have a named source attached rather than a developer's
reading.

| Item | Status after checking |
| --- | --- |
| 1. Mushtaraka in the Hanbali school | **Answered against the product specification.** Ahmad b. Hanbal sided with Abu Hanifa; the full brothers take nothing. Kept open at low priority pending a primary Hanbali text. |
| 3. Radd in Maliki and Shafi'i | **Closed.** Ibn Qudamah names Malik and al-Shafi'i with Zayd b. Thabit: the surplus goes to the treasury. The engine was applying radd and has been corrected. |
| 4. Radd where a spouse is the only heir | **Closed.** Ibn Qudamah reports consensus that a spouse takes no part in radd, and explains away the report from Uthman that the old Ahl-e-Hadith behaviour rested on. Corrected. |
| 10c. Ahl-e-Hadith, radd to a sole spouse | **Closed** by the same passage. |
| 10d. Ahl-e-Hadith and awl | **Closed.** Ibn Qudamah: nobody in his time adopted Ibn Abbas's rejection of awl. Applying awl is right everywhere. |
| MFLO 1961 | **Defect found and fixed.** The engine used the textual reading; Pakistani courts apply the notional-share construction of *Kamal Khan*. See below. |

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

### 3. Radd in the Maliki and Shafi'i schools — CLOSED

Ibn Qudamah, *al-Mughni* 6/186, names Malik and al-Shafi'i alongside Zayd b.
Thabit: the surplus goes to the treasury and nobody receives more than their
allotted share. The engine previously applied radd in both schools; it now
reports the remainder as undistributed.

**Remaining, and smaller:** what a calculator should tell a user to do with
that remainder where no functioning treasury exists. It currently says to ask
a scholar.

### 4. Radd where a spouse is the only heir — CLOSED

Ibn Qudamah reports consensus that what is left over is not given to a spouse,
and explains the contrary report from Uthman as a payment made on some other
basis. The engine's behaviour for the four schools was already right; the
Ahl-e-Hadith option, which did hand the surplus to the spouse, has been
corrected to match.

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

### 10. The Ahl-e-Hadith option, as a whole

This option is offered in the interface and in the engine, and **not one of
its four distinguishing choices has been confirmed by anyone qualified in
it**. No fatwa is cited for any of them. Each result carries
`madhhab_ahl_e_hadith_not_verified` saying so, and the guide
`ahl-e-hadith-inheritance-rules` states it in the article body as well.

What a reviewer has to settle, one item at a time:

| # | Question | What the calculator currently does | Basis it rests on |
| --- | --- | --- | --- |
| 10a | Grandfather competing with brothers | He excludes them | Reported from Abu Bakr and Ibn Abbas, against Zayd b. Thabit |
| 10b | Mushtaraka | Full brothers take nothing | The view of Ali, against the report from Umar |
| 10c | Radd where a spouse is the only heir | **CLOSED** — the spouse takes no part in radd | Ibn Qudamah reports consensus; the Uthman report is explained away |
| 10d | Awl | **CLOSED** — applied, as everyone applies it | Ibn Qudamah: nobody in his time held Ibn Abbas's view |

The underlying disagreements between the Companions are documented — Ibn
Rushd's *Bidayat al-Mujtahid*, kitab al-fara'id, sets out all sides of each.
What is unverified is which side contemporary scholarship of this orientation
takes, and whether a calculator should present any single one as theirs.

With 10c and 10d closed, **this option now produces the same figures as Hanafi
in every case in the suite**, and a test asserts it. Whether that is the right
answer is 10a and 10b, which remain open. If a reviewer changes either, the
test fails and the guide's claim has to be rewritten with it.

## Legal review, separate from the fiqh review

### MFLO 1961 section 4 (Pakistan)

A defect was found here and fixed. The engine paid the predeceased child's
notional share to that child's **children** only — the words of the section.
Pakistani courts apply the construction of *Kamal Khan v Mst. Zainab*, PLD 1983
Lahore 546, endorsed in *Mst. Zainab v Kamal Khan*, PLD 1990 SC 1051: the child
takes a notional share which is then distributed among **all of that child's
heirs**. The engine now does that, with the textual reading kept as an option.

Still needs a **Pakistani lawyer**, not a scholar:

- Whether the *Kamal Khan* construction is still the operative one, and
  whether any later authority has moved it.
- Where the repugnancy appeal has reached. *Allah Rakha v Federation of
  Pakistan*, PLD 2000 SC 1, held section 4 repugnant to the injunctions of
  Islam; the appeal to the Shariat Appellate Bench was still pending as of the
  paper cited in [SOURCES-CONSULTED.md](SOURCES-CONSULTED.md), which suspends
  the declaration. If that appeal has since been decided, this whole module's
  premise changes.
- Whether the "very loose construction" of *Muhammad Fikree* — grandchildren
  inherit only where they would otherwise be excluded entirely — has been
  revived anywhere. It is not implemented.
- How far down the line representation runs.

### Other jurisdictions

Not implemented. Before any of them ships, each needs local legal advice —
particularly France, where forced heirship (*réserve héréditaire*) can make
Islamic shares legally unenforceable, and publishing anything advisory without
that advice would mislead.

## Before the engine is declared ready

- [ ] Every case in `tests/Cases/` carries a `verified_by` value
- [ ] All ten open questions above are closed, and the code matches the answers
- [ ] The Ahl-e-Hadith option is either confirmed by a qualified reviewer, or
      removed from the interface — leaving it there indefinitely with an
      unverified notice is not a resting state
- [ ] MFLO 1961 confirmed by a Pakistani lawyer
- [ ] Reviewers' names and credentials published on the About page
- [ ] A working way for users to report a suspected calculation error, and
      someone responsible for acting on it quickly
