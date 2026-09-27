<?php

return [
    'slug' => 'awl-and-radd-explained',
    'category' => 'doctrines',
    'translation_group' => 'awl-radd',
    'target_keyword' => 'awl and radd explained',
    'title' => 'Awl and radd explained',
    'h1' => 'Awl and radd, explained with worked examples',
    'meta_description' => 'What happens when Islamic inheritance shares add up to more or less than the estate: awl raises the denominator, radd returns the surplus. With examples.',
    'body' => <<<'HTML'
<p>Add up the fixed shares in a real estate and they often do not come to exactly one. Sometimes they claim more than the whole estate; sometimes they leave a surplus with nobody entitled to it. Faraid has a named remedy for each — <em>awl</em> and <em>radd</em> — and they are the two places a naive calculator goes wrong. The <a href="/">calculator here</a> tells you on the result when either has been applied.</p>

<h2>Awl: when the shares claim too much</h2>
<p>Take the classic case. A man dies leaving a wife, two daughters, his father and his mother.</p>
<ul>
    <li>Wife: 1/8, because there are children</li>
    <li>Two daughters: 2/3 shared</li>
    <li>Father: 1/6</li>
    <li>Mother: 1/6</li>
</ul>
<p>Over a common denominator of 24 that is 3 + 16 + 4 + 4 = <strong>27/24</strong>. Three twenty-fourths more than exists.</p>
<p>The remedy is not to cut one heir. It is to raise the denominator to the total, so everyone is reduced in exactly the same proportion:</p>
<table>
    <tr><th>Heir</th><th>Before</th><th>After awl</th></tr>
    <tr><td>Wife</td><td>3/24</td><td>3/27</td></tr>
    <tr><td>Two daughters</td><td>16/24</td><td>16/27</td></tr>
    <tr><td>Father</td><td>4/24</td><td>4/27</td></tr>
    <tr><td>Mother</td><td>4/24</td><td>4/27</td></tr>
</table>
<p>This case is called <strong>al-Minbariyya</strong>. It was put to Ali b. Abi Talib while he was on the pulpit — the <em>minbar</em> — at Kufa, and he is reported to have answered without pausing that the wife's eighth had become a ninth. 3/27 is exactly 1/9.</p>
<p>The denominators awl can produce are fixed and few. A base of 6 can rise to 7, 8, 9 or 10; a base of 12 to 13, 15 or 17; a base of 24 only to 27.</p>

<h3>A smaller awl case</h3>
<p>A woman leaves her husband and two full sisters. The husband takes 1/2, the sisters 2/3 — that is 3/6 + 4/6 = 7/6. The denominator becomes 7: the husband takes 3/7 and the sisters 4/7.</p>

<h2>Radd: when the shares leave a surplus</h2>
<p>The opposite case. A man dies leaving his mother and one daughter, nothing else.</p>
<ul>
    <li>Mother: 1/6, because there is a child</li>
    <li>Daughter: 1/2, as the only daughter with no brother</li>
</ul>
<p>That is 2/3. A third of the estate is unclaimed, and there is no son, brother, uncle or other residuary to take it.</p>
<p>The surplus is <em>returned</em> to the fixed-share heirs in proportion to what they already hold. The mother's share and the daughter's stand at 1:3, so the surplus divides the same way:</p>
<table>
    <tr><th>Heir</th><th>Fixed share</th><th>After radd</th></tr>
    <tr><td>Mother</td><td>1/6</td><td>1/4</td></tr>
    <tr><td>Daughter</td><td>1/2</td><td>3/4</td></tr>
</table>

<h3>The one heir radd never reaches</h3>
<p>A husband or wife takes no part in the return. Add a widow to the case above — a wife, the mother and one daughter — and the wife keeps her eighth exactly, while the whole of the remaining seven eighths is shared between mother and daughter in the same 1:3 ratio.</p>
<table>
    <tr><th>Heir</th><th>Fixed share</th><th>After radd</th></tr>
    <tr><td>Wife</td><td>1/8</td><td>1/8</td></tr>
    <tr><td>Mother</td><td>1/6</td><td>7/32</td></tr>
    <tr><td>Daughter</td><td>1/2</td><td>21/32</td></tr>
</table>

<h3>Where a spouse is the only heir</h3>
<p>If a woman dies leaving only her husband, he takes his half — and classically the remaining half goes to the public treasury rather than to him. This calculator reports that remainder as undistributed instead of quietly assigning it, and says so on the result.</p>
<p>The Hanafi school applies radd as described here. The Maliki and Shafi'i schools classically sent the surplus to the treasury, though modern practice in both commonly returns it. Which to present as the default is one of the open questions on the <a href="/methodology">methodology page</a>.</p>

<h2>Why the arithmetic has to be exact</h2>
<p>Both remedies produce denominators like 27 and 32, and both involve thirds and sixths. A calculator working in decimals turns 1/3 into 0.333333 and loses a fraction of the estate somewhere in the rounding. This one works entirely in exact fractions and refuses to return a result whose shares do not sum to precisely one.</p>

<h2>Common questions</h2>
<h3>Does awl mean someone has been cheated?</h3>
<p>No. Every heir is reduced by the same proportion, which is the point. Nobody is pushed out to keep someone else whole.</p>
<h3>Why does a widow not share in radd?</h3>
<p>Because the marital tie ends at death, whereas the blood relationships that attract the return do not. That is the reasoning the classical manuals give.</p>
<h3>How do I know which one applies to me?</h3>
<p>Add the fixed shares. More than the estate is awl; less with no residuary heir is radd; exactly the estate and neither applies. Or enter the heirs in the <a href="/">calculator</a>, which says on the result which was used.</p>

<p>Next: <a href="/blog/who-is-excluded-from-islamic-inheritance">who is excluded from Islamic inheritance</a>.</p>
HTML,
];
