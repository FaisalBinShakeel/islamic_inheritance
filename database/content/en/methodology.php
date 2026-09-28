<?php

return [
    'slug' => 'methodology',
    'category' => 'pages',
    'translation_group' => 'methodology',
    'title' => 'Methodology',
    'h1' => 'How this calculator works',
    'meta_description' => 'The rules this Islamic inheritance calculator applies, in order: deductions, exclusion, fixed shares, residue, awl and radd — and what it leaves out.',
    'body' => <<<'HTML'
<p>This page states exactly what the calculator does, so that a scholar, a lawyer or a curious user can check it rather than take it on trust.</p>

<h2>The order of operations</h2>
<p>Shares are worked out on the <strong>net estate</strong>, after three deductions taken in this order:</p>
<ol>
    <li>Funeral and burial expenses</li>
    <li>Outstanding debts</li>
    <li>A bequest (<em>wasiyyah</em>), capped at one third of whatever remains after the first two</li>
</ol>
<p>The cap is enforced, not merely mentioned: enter a larger bequest and the calculator reduces it to the third and tells you it has done so.</p>
<p>After that, the calculation runs in this sequence: disqualification, then exclusion, then the fixed shares, then the residue, then <em>awl</em> or <em>radd</em> if the totals do not land exactly on the whole estate.</p>

<h2>Exclusion works by presence, not by inheritance</h2>
<p>Two rules that simpler calculators get wrong, and that this one is tested against:</p>
<ul>
    <li>Siblings reduce the mother from a third to a sixth <strong>even when the father has excluded those siblings from inheriting anything</strong>. Their presence is enough.</li>
    <li>A child or agnatic grandchild reduces a spouse's share by existing, whether or not that grandchild ends up taking anything.</li>
</ul>
<p>A <em>disqualified</em> heir is the exception. Someone excluded for unlawfully killing the deceased, or for difference of religion, is removed before any of this — so they neither inherit nor block anyone else.</p>

<h2>Awl and radd</h2>
<p><strong>Awl</strong> applies when the fixed shares claim more than the whole estate. The common denominator rises to the sum of the numerators and every heir is reduced in the same proportion. Every position applies it; Ibn Abbas's rejection of awl is a minority view that Ibn Qudamah records as having no adherents even in his own time, and it is not built in here.</p>
<p><strong>Radd</strong> applies when the shares leave a surplus and no residuary heir survives: the surplus returns to the fixed-share heirs in proportion — but never to a spouse, on which Ibn Qudamah reports a consensus. The Hanafi and Hanbali schools apply radd. <strong>The Maliki and Shafi'i schools do not</strong>: there the surplus goes to the public treasury and nobody receives more than their allotted share.</p>
<p>Where a spouse is the only heir, the calculator does not invent a destination for the remainder. It shows the spouse's fixed share, reports the rest as undistributed, and says why. Classically that surplus goes to the public treasury. Saying so is more honest than quietly handing it over. There is a full worked explanation in <a href="/blog/awl-and-radd-explained">awl and radd explained</a>.</p>

<h2>Where the four schools differ</h2>
<p>The schools agree on the great majority of cases. The calculator holds only the divergences, and applies them according to the school you choose:</p>
<table>
    <tr><th>Question</th><th>Hanafi</th><th>Shafi'i, Maliki, Hanbali</th></tr>
    <tr><td>Grandfather competing with brothers</td><td>He excludes them</td><td>They share with him under Zayd's doctrine</td></tr>
    <tr><td>The Mushtaraka case</td><td>Full brothers take nothing</td><td>Shafi'i and Maliki: they share the third. Hanbali sides with Hanafi</td></tr>
    <tr><td>Radd</td><td>Applied, never to a spouse</td><td>Hanbali the same; Maliki and Shafi'i send the surplus to the treasury</td></tr>
    <tr><td>Akdariyya</td><td>Cannot arise</td><td>Arises, resolved over a denominator of 27</td></tr>
</table>
<p>Two of these are still open questions in this calculator, and results affected by them carry a warning: the treatment of Mushtaraka in the Hanbali school, and whether radd or the public treasury should be presented as the default in the Maliki and Shafi'i schools.</p>

<h2>Pakistan: MFLO 1961</h2>
<p>Section 4 of the Muslim Family Laws Ordinance 1961 is <strong>statute, not fiqh</strong>. It gives the children of a son or daughter who died before the deceased the share their parent would have taken — where the classical rules would exclude them entirely if a living son survives. It is offered as an explicit toggle, off by default.</p>
<p>There is a wrinkle that matters. The words of the section give the share to the predeceased child's <em>children</em>. Pakistani courts read it differently: that child is given a <strong>notional share</strong>, which then passes to <em>all</em> of their heirs — their widow, their mother and their children. That is the construction of <em>Kamal Khan v Mst. Zainab</em>, endorsed by the Supreme Court in <em>Mst. Zainab v Kamal Khan</em> (PLD 1990 SC 1051), and it is what this calculator applies by default. The words of the section remain available as an option so the difference is visible.</p>
<p>A consequence worth knowing: a predeceased son's mother is often the deceased's own widow, so she can inherit twice — once from her husband, and again from her son's notional share.</p>
<p>Section 4 was also held repugnant to the injunctions of Islam by the Federal Shariat Court in <em>Allah Rakha v Federation of Pakistan</em> (PLD 2000 SC 1). The appeal to the Shariat Appellate Bench suspends that declaration, so the section stands — but where that appeal has reached is a question for a Pakistani lawyer, and every result using this option says so.</p>

<h2>What is deliberately not covered</h2>
<p>Each of these needs its own scholarly handling, and the calculator does not approximate any of them:</p>
<ul>
    <li>An unborn child (<em>haml</em>)</li>
    <li>A missing person presumed dead (<em>mafqud</em>)</li>
    <li>Indeterminate gender (<em>khuntha</em>)</li>
    <li>Distant kindred where no sharer or residuary survives (<em>dhawu al-arham</em>)</li>
    <li>A second death before distribution (<em>munasakha</em>)</li>
    <li>The <em>mu'adda</em> reckoning, where a grandfather competes with both full and paternal half-siblings</li>
</ul>
<p>Where the calculator meets one of these it says so, rather than producing a number it cannot defend.</p>

<h2>Arithmetic</h2>
<p>Everything internal is exact integer fractions. Every result asserts that the shares plus any reported remainder come to exactly one, and the calculation fails loudly rather than returning a distribution that does not balance.</p>

<p><a href="/">Try it on your own case</a>, or read <a href="/blog/islamic-inheritance-law-explained">the full guide to Islamic inheritance law</a>.</p>
HTML,
];
