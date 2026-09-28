# Sources consulted

What was looked up, what it said, and what changed in the code because of it.

Every entry names a page that was actually fetched and read, with the passage
it turns on. Where a question was searched for and **not** settled, that is
recorded too — an unanswered question is more useful here than a confident
guess.

**This is not a scholar review.** Reading a fatwa site is not the same as a
qualified reviewer checking this calculator's output, and
[REVIEW-CHECKLIST.md](REVIEW-CHECKLIST.md) still stands. What these sources do
is move several questions from "a developer's reading" to "here is a named
source saying so, go and check it".

Consulted 28 September 2026.

---

## 1. Radd: Shafi'i and Maliki send the surplus to the treasury

**Source:** IslamQA, answer 160948, quoting Ibn Qudamah, *al-Mughni* 6/186.
<https://islamqa.info/en/answers/160948>

> "To sum up, if the deceased did not leave behind any heir except those who
> are entitled to an allotted share, such as daughters or sisters or
> grandmothers, and there is some wealth left over, then what is left over
> from the allotted shares should be given to them on the same basis as the
> allotted shares, **except in the case of a husband or wife**. This was
> narrated from 'Umar, 'Ali, Ibn Mas'ood and Ibn 'Abbaas… Ibn Suraaqah said:
> Things are done on this basis now in the regions.
>
> **Zayd ibn Thaabit was of the view that what is left over from the allotted
> shares should go to the bayt al-maal and no one should be given more than
> his allotted share. This was also the view of Maalik, al-Awzaa'i and
> al-Shaafa'i.**"

**Changed:** `Shafii::appliesRadd()` and `Maliki::appliesRadd()` returned
`true`. They now return `false`, and the result reports the remainder as
undistributed with the `madhhab_surplus_to_bayt_al_mal` note.

The old behaviour was defended in a code comment as "modern practice commonly
applies radd". That may well be true, but reporting a practice under a
school's name, when the school's own position is the opposite, is exactly what
this calculator exists not to do.

**Cases:** `shafii_surplus_goes_to_the_treasury_not_the_heirs`,
`maliki_surplus_goes_to_the_treasury_not_the_heirs`,
`hanbali_returns_the_surplus_to_the_heirs`.

---

## 2. Radd never reaches a spouse — including under the Ahl-e-Hadith option

**Source:** the same page, quoting Ibn Qudamah.

> "With regard to the spouses, what is left over (after giving the allotted
> shares) **should not be given to them, according to the consensus of the
> scholars**, but it was narrated from 'Uthmaan that he did give the left-over
> wealth to the husband, but perhaps he was a relative on the father's side
> ('asbah) or on the mother's side (dhu rahm), so he gave that to him, or he
> gave it from the bayt al-maal and not by way of inheritance."

**Changed:** `AhlAlHadith::raddToSpouseWhenSoleHeir()` returned `true`, resting
on precisely that report from Uthman. The same source explains the report away
and reports a consensus against it, so it now returns `false`.

**Consequence worth stating:** with this corrected, the Ahl-e-Hadith option now
produces **identical figures to Hanafi on every case in the suite**. A test,
`testAhlEHadithCurrentlyCoincidesWithHanafiThroughout`, asserts exactly that,
so if a reviewer changes any of its choices the claim fails loudly.

---

## 3. Awl: Ibn Abbas's rejection is obsolete, so applying awl is right everywhere

**Source:** IslamWeb fatwa 222526, quoting Ibn Qudamah, *al-Mughni*.
<https://www.islamweb.net/en/fatwa/222526/the-origion-of-awl-in-inheritance-law>

> "The opinion of applying 'Awl has been adopted by all Muslim scholars except
> Ibn 'Abbaas and a small group that held another opinion… **We do not know at
> the present time anyone who adopts the opinion of Ibn 'Abbaas.** We do not
> know of any disagreement among the jurists of the Islamic states regarding
> applying 'Awl."

The same page gives the origin: the first awl case arose under 'Umar — a woman
leaving a husband and two sisters — and al-'Abbas b. 'Abd al-Muttalib is
reported to have suggested the remedy, by analogy with creditors sharing an
insufficient estate proportionally.

**Changed:** nothing. This confirms the existing decision to apply awl under
every position, including Ahl-e-Hadith, and **closes** open question 10d.

---

## 4. Mushtaraka: Hanbali sides with Hanafi, not with Shafi'i

**Sources:** web search returning consistent statements across several
references, including *Inheritance according to the Five Schools of Islamic
Law* (Mughniyya) and academic summaries of the special cases:

> "Imams Malik and As-Shafi'i supported 'Umar's verdict, though Ahmad ibn
> Hanbal and Abu Hanifa opposed it."

**Changed:** nothing in the code — the engine already had Hanbali taking
Ali's view (full brothers take nothing). What changes is confidence: the
product specification this project was built from grouped Hanbali with
Shafi'i, and that appears to be **wrong**. Open question 1 is now answered
against the specification.

Not yet closed: this rests on secondary summaries rather than a primary Hanbali
text read directly, so the checklist keeps it open at a lower priority.

---

## 5. MFLO 1961 section 4: the courts do not read it the way it is written

**Source:** "Inheritance Rights of Orphaned Grandchildren: A Straightforward
Provision of Law?", LUMS Shaikh Ahmad Hassan School of Law.
<https://sahsol.lums.edu.pk/sites/default/files/2024-05/Inheritance%20Rights%20of%20Orphaned%20Grandchildren%20A%20Straightforward%20Provision%20of%20Law.pdf>

The section itself:

> "In the event of the death of any son or daughter of the propositus before
> the opening of succession, the children of such son or daughter, if any,
> living at the time the succession opens, shall per stirpes receive a share
> equivalent to the share which such son or daughter, as the case may be,
> would have received if alive."

The construction the courts actually apply, from *Kamal Khan v Mst. Zainab*,
PLD 1983 Lahore 546, taken up by the Supreme Court in *Mst. Zainab v Kamal
Khan*, PLD 1990 SC 1051, as Lucy Carroll renders it:

> "In the event of the death of any son or daughter of the propositus before
> the opening of succession, such predeceased child shall be allotted **a
> notional share** equivalent to what he or she would have received if alive.
> This notional share shall then be distributed **among the heirs of the
> predeceased child**, as if that child had died immediately after his or her
> parent."

And from the Lahore High Court judgment itself:

> "Mst. Zainab being the only surviving child [of the predeceased Rajoo] she
> cannot get more than one-half of the estate of Rajoo and the remaining half
> must revert to the collaterals."

**Changed — this was a real defect.** The engine paid the notional share to
the predeceased child's *children* only, two to one. That is the textual
reading, which the courts abandoned decades ago. It now runs the engine again
on that child's own heir set and distributes the notional share among all of
them, which is the settled construction; the textual reading remains available
as an option.

The practical difference: a predeceased son's **widow**, and his **mother**,
now take a part of his notional share. His mother is very often the deceased's
own widow, who therefore inherits twice — once from her husband, once from her
son. Case
`mflo_a_widow_can_inherit_twice_under_the_settled_construction` pins that down.

### Repugnancy, and why section 4 still stands

From the same paper:

> "…the Federal Shariat Court deemed section 4 to be contrary to the
> injunctions of Islam; **an appeal against this case remains pending before
> the Shariat Appellate Bench to this day.**"

The case is *Allah Rakha v Federation of Pakistan*, PLD 2000 SC 1. The pending
appeal suspends the declaration, so the section stands for now. The result's
note says this, names the case, and still tells the user to ask a lawyer where
the matter has reached — because "to this day" was true when that paper was
written, not necessarily today.

A third reading, the "very loose construction" of *Muhammad Fikree v Fikree
Development Corporation Ltd.* (orphaned grandchildren inherit only where they
would otherwise be excluded entirely), is noted in the paper as never having
been followed elsewhere. It is **not** implemented.

---

## Searched for and not settled

- **Which view contemporary Ahl-e-Hadith scholarship takes on the grandfather
  competing with brothers.** Several searches, including within islamqa.info,
  returned material on the schools' split but nothing stating a preference for
  this orientation. The engine keeps the Abu Bakr / Ibn Abbas position, still
  marked unverified (checklist 10a).
- **Mushtaraka under the Ahl-e-Hadith orientation specifically** (checklist
  10b). Same position as before, still unverified.
- **The mu'adda reckoning**, and **the grandfather with siblings alongside an
  inheriting daughter**. Not searched in depth; both remain unimplemented and
  warned about.

---

## How to redo this

The pages above were read directly. If you want to check any of them, open the
URL — do not take this file's word for it. If a quotation here does not match
what the page says, that is a bug in this file and worth
[reporting](../README.md).
