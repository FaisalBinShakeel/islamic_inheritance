# Methodology

What this engine calculates, on what authority, and — just as important — what
it does not calculate.

> **Status: not reviewed.** Every rule below is a developer's reading of the
> classical rule tables. No qualified scholar has signed any of it off. Until
> the sign-off recorded in [REVIEW-CHECKLIST.md](REVIEW-CHECKLIST.md) is
> complete, output from this engine is a working draft, not a fatwa and not
> legal advice.

## Scope

Classical Sunni Faraid in the four schools: Hanafi, Shafi'i, Maliki and
Hanbali, plus one further option, **Ahl-e-Hadith / Ghair Muqallid**, for those
who do not follow a school taqlidan. The position is a **required** parameter
with no default — the engine refuses to guess, because guessing means handing
most of a global audience a confidently wrong answer.

Every result also reports what each of the other positions produces for the
same heirs, so the calculator states what each one holds rather than presenting
one figure as the answer.

Ja'fari (Shia) inheritance is out of scope. It is a different system, not a
toggle, and belongs in a later phase with its own review.

## Order of operations

1. **Deductions**, in this fixed order: funeral and burial expenses, then
   debts, then bequests (wasiyyah) capped at one third of what remains. The
   cap is enforced, not merely warned about, and the result says when it bit.
2. **Disqualification**: homicide and difference of religion remove an heir
   entirely. A disqualified heir also stops blocking anyone else — they are
   treated as not having survived.
3. **Exclusion (hajb hirman)**: a nearer heir removes a further one.
4. **Fixed shares (ashab al-furud)**: only the six Quranic fractions —
   1/2, 1/4, 1/8, 2/3, 1/3, 1/6.
5. **Residue (asaba)** to the nearest surviving class.
6. **Awl or radd** where the fixed shares over- or under-run the estate.
7. **Representation** under MFLO 1961, if that jurisdiction module is on.

## Arithmetic

All internal arithmetic is exact integer fractions (`Faraid\Fraction`). No
floating point is used anywhere in the calculation, because thirds and sixths
cannot be held exactly in binary floating point and the drift would show up as
a wrong share. Money amounts are the only place a float appears, and only at
the very end when a fraction is applied to an estate value.

Every result asserts that the shares plus any reported remainder sum to
exactly one, and throws rather than returning a distribution that does not
balance.

## Hajb: presence, not inheritance

Two rules that naive implementations get wrong, and that the test suite pins
down:

- Siblings reduce the mother from a third to a sixth **even when the father
  has excluded those siblings from inheriting**. The hajb nuqsan works by
  presence.
- A child or agnatic grandchild reduces the spouse's share by presence too,
  whether or not that grandchild ends up taking anything.

A **disqualified** heir is the exception: they are removed before any of this,
so a son disqualified for homicide neither inherits nor blocks his uncle.

## Awl and radd

**Awl** — the fixed shares claim more than the estate. The common denominator
is raised to the sum of the numerators and everyone is reduced in proportion.
The engine implements this by dividing each share by the total, which is the
same operation done exactly. Bases go 6 → 7, 8, 9, 10; 12 → 13, 15, 17;
24 → 27.

**Radd** — the fixed shares leave a surplus and no residuary survives. The
surplus returns to the fixed-share heirs in proportion to their shares, and
**not** to a spouse.

Where a spouse is the only heir, the engine does not invent a destination for
the remainder. It reports the spouse's fixed share, reports the remainder as
undistributed, and warns. Classically that surplus goes to the public treasury
(bayt al-mal); saying so is more honest than silently handing it to the
spouse. The behaviour is a configuration point
(`MadhhabRules::raddToSpouseWhenSoleHeir()`), not a doctrine.

## Named cases

| Case | Trigger | Treatment |
| --- | --- | --- |
| Umariyyatan (al-Gharrawayn) | Spouse, mother and father, nobody else | Mother takes one third of the remainder after the spouse, not of the whole estate |
| Mushtaraka (al-Himariyya) | Husband, a maternal sixth, two or more uterine siblings, full brothers | Per school — see below |
| Akdariyya | Husband, mother, true grandfather, one sister, nobody else | Awl to nine, then the grandfather and sister pool and redivide two to one, giving 27. Cannot arise in Hanafi |
| Asaba ma'a'l-ghayr | Full or consanguine sister with an inheriting daughter or son's daughter | The sister becomes a residuary and stands where a brother would |
| Asaba bi'l-ghayr | Daughters with sons, son's daughters with son's sons, sisters with brothers | Two to one |

Note the one class where sex does not change the share: **uterine siblings
divide their third equally**, male and female alike.

## Where the schools differ

| Divergence | Hanafi | Shafi'i | Maliki | Hanbali | Ahl-e-Hadith |
| --- | --- | --- | --- | --- | --- |
| Grandfather with full or consanguine brothers | Excludes them (Abu Hanifa) | Zayd's doctrine: they share | Zayd's doctrine | Zayd's doctrine | Excludes them |
| Mushtaraka | Full brothers take nothing | They share the third | They share the third | **Take nothing — open question** | Take nothing |
| Radd | Applied, spouse excluded | Applied (modern practice; classically bayt al-mal) | Applied (modern practice; classically bayt al-mal) | Applied | Applied, and reaches a sole surviving spouse |
| Akdariyya | Does not arise | Arises | Arises | Arises | Does not arise |

### Ahl-e-Hadith / Ghair Muqallid

Not a fifth school, and its adherents would not call it one; `isSchoolOfLaw()`
returns false for it. It sits in the same strategy layer because that is where
"which position is taken where the evidence is read differently" belongs.

Inheritance is mostly explicit text, so this option produces exactly what all
four schools produce on ordinary estates. It differs at four points:

| Point | This option | Basis |
| --- | --- | --- |
| Grandfather with brothers | He excludes them | Reported from Abu Bakr and Ibn Abbas |
| Mushtaraka | Full brothers take nothing | The view of Ali |
| Radd where a spouse is the only heir | The surplus goes to the spouse | Commonly cited from Uthman |
| Awl | Applied as the majority applies it | Ibn Abbas's rejection of awl is **not** implemented |

**None of these attributions has been verified**, and no fatwa is cited for
any of them. They are the positions most commonly attributed. Every result
under this option says so on the page, and the four points are open questions
in [REVIEW-CHECKLIST.md](REVIEW-CHECKLIST.md). See also the guide
`ahl-e-hadith-inheritance-rules`, which sets out all sides.

Under **Zayd's doctrine** the grandfather takes whichever is best for him of:
sharing with the brothers as though he were one of them (muqasama), one third
of the residue, or one sixth of the whole estate. Sisters beside the
grandfather become residuaries in the muqasama rather than taking their
Quranic half or two thirds — except in Akdariyya.

The architecture is one shared engine with a madhhab strategy object
overriding only these points. There are not four engines, because four
codebases would mean four places for a bug to hide.

## Jurisdiction: Pakistan, MFLO 1961 s.4

Section 4 of the Muslim Family Laws Ordinance 1961 is **statute, not fiqh**.
It gives the children of a predeceased son or daughter the share their parent
would have taken. Classical Hanafi rules would exclude them where a living son
survives.

It is off by default and exposed as an explicit toggle. When on, the engine
treats each predeceased child as if alive, runs the ordinary calculation, then
pays that child's individual portion down to their own children at two to one.
Every such result carries a warning to confirm the current legal position with
a Pakistani lawyer, because the provision has been litigated repeatedly.

Fiqh does not change by country; the law does. Other jurisdiction modules
(UK, US, France, Canada, Australia) belong at this same layer and are not
implemented yet.

## Not implemented, deliberately

Each of these needs its own scholarly handling and its own test cases. The
engine does not approximate them:

- **Haml** — an unborn child
- **Mafqud** — a missing person presumed dead
- **Khuntha** — indeterminate gender
- **Dhawu al-arham** — distant kindred where no sharer or residuary survives
- **Munasakha** — a second death before distribution
- **Mu'adda** — the reckoning where consanguine siblings are counted against
  the grandfather and then surrender to the full siblings. Detected and
  warned about, not calculated.
- **Grandfather with siblings and an inheriting daughter**, outside the Hanafi
  school. The engine gives the residue to the grandfather and warns loudly
  rather than guessing.

Where the engine hits one of these it says so in `warnings`. It never silently
produces a number it cannot defend.

## Sources cited in the test suite

- The Qur'an, 4:11, 4:12 and 4:176
- Hadith collections as cited per case (al-Bukhari, Muslim, Abu Dawud,
  al-Tirmidhi, Ibn Majah)
- al-Sajawandi, *al-Fara'id al-Sirajiyya* — the standard Hanafi manual
- Ibn Rushd, *Bidayat al-Mujtahid*, kitab al-fara'id — for the divergences
  between the schools
- Muslim Family Laws Ordinance 1961 (Pakistan), section 4

Every case in `tests/Cases/` carries its own `source` field. A case with no
source is rejected by the loader.
