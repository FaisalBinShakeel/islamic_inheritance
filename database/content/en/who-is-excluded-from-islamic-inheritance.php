<?php

return [
    'slug' => 'who-is-excluded-from-islamic-inheritance',
    'category' => 'doctrines',
    'translation_group' => 'exclusion',
    'target_keyword' => 'who is excluded from islamic inheritance',
    'title' => 'Who is excluded from Islamic inheritance',
    'h1' => 'Who is excluded from Islamic inheritance, and by whom',
    'meta_description' => 'The rules of hajb: which relatives are removed from an estate entirely, which only have their share reduced, and the two outright disqualifications.',
    'body' => <<<'HTML'
<p>Relatives who expected a share and received nothing always want to know why. The answer is almost never arbitrary: Faraid has a precise set of exclusion rules called <em>hajb</em>, and they follow one principle — a nearer relative removes a further one. This guide lists them, and the <a href="/">calculator</a> names on every result exactly who was excluded and by whom.</p>

<h2>Two kinds of exclusion</h2>
<p><strong>Hajb hirman</strong> removes an heir completely. A son excludes the deceased's brothers; they take nothing at all.</p>
<p><strong>Hajb nuqsan</strong> only reduces a share. A husband's half becomes a quarter when there are children; he is not excluded, just reduced.</p>

<h2>Who removes whom</h2>
<table>
    <tr><th>This heir</th><th>Excludes</th></tr>
    <tr><td>A son (or son's son)</td><td>All brothers, sisters, nephews, uncles and cousins</td></tr>
    <tr><td>A son</td><td>The son's son, and the son's daughter</td></tr>
    <tr><td>The father</td><td>The grandfather, all siblings of every kind, and his own mother</td></tr>
    <tr><td>The mother</td><td>Every grandmother, on both sides</td></tr>
    <tr><td>Any child or grandchild</td><td>Maternal half-siblings</td></tr>
    <tr><td>The father or grandfather</td><td>Maternal half-siblings</td></tr>
    <tr><td>A full brother</td><td>Paternal half-brothers and half-sisters</td></tr>
    <tr><td>Two daughters</td><td>A son's daughter — unless a son's son of her level rescues her</td></tr>
    <tr><td>Two full sisters</td><td>Paternal half-sisters — unless a paternal half-brother rescues them</td></tr>
</table>
<p>Among the residuary heirs the rule is simply order of nearness. Sons come before the father's line, which comes before brothers, which comes before uncles. The first class present takes everything left; every class below it takes nothing.</p>

<h2>Five heirs who can never be excluded</h2>
<p>However the family is composed, these five always inherit something: the <strong>husband or wife</strong>, the <strong>father</strong>, the <strong>mother</strong>, the <strong>son</strong> and the <strong>daughter</strong>. Their shares can be reduced, but they cannot be removed.</p>

<h2>Two disqualifications</h2>
<p>These are different from exclusion. They remove a person from the reckoning entirely, before anything else is worked out:</p>
<ul>
    <li><strong>Homicide.</strong> An heir who unlawfully killed the deceased does not inherit — "the killer does not inherit".</li>
    <li><strong>Difference of religion.</strong> Under the classical rule a non-Muslim does not inherit from a Muslim, nor the reverse.</li>
</ul>
<p>There is an important consequence. A disqualified heir does not block anyone else. If a man's only son is disqualified, the son takes nothing <em>and</em> stops excluding the deceased's brothers, who now inherit. An heir who is merely excluded still blocks; a disqualified one does not.</p>

<h2>The rule simple calculators get wrong</h2>
<p>An excluded sibling still reduces the mother's share.</p>
<p>A man dies leaving his mother, his father and three sisters. The father excludes all three sisters, who take nothing. But the mother does not take her usual third: because two or more siblings survive, she is held to a sixth — and the father takes the remaining five sixths. The sisters' presence changed the answer even though they inherited nothing.</p>
<table>
    <tr><th>Heir</th><th>Share</th></tr>
    <tr><td>Mother</td><td>1/6</td></tr>
    <tr><td>Father</td><td>5/6</td></tr>
    <tr><td>Three sisters</td><td>Nothing — excluded by the father</td></tr>
</table>
<p>Change it to a single sister and the mother goes back to a third, because the Qur'anic text requires two or more.</p>

<h2>The one case where the schools split</h2>
<p>When a grandfather survives alongside the deceased's brothers, Abu Hanifa's view is that he stands in the father's place and excludes them entirely. The Maliki, Shafi'i and Hanbali schools follow Zayd b. Thabit: the grandfather shares with the brothers, taking whichever is best for him of an equal brother's portion, a third of the residue, or a sixth of the whole estate.</p>
<p>The answers differ substantially, which is why the calculator asks which school you follow rather than assuming one.</p>

<h2>Common questions</h2>
<h3>Can a grandson inherit if his father died first?</h3>
<p>Under the classical rules, only if no son of the deceased survives — a living son excludes him. Pakistan changed this by statute: section 4 of the Muslim Family Laws Ordinance 1961 gives him the share his father would have taken. The calculator offers that as a separate option.</p>
<h3>Are adopted children excluded?</h3>
<p>An adopted child does not inherit as a child, because the tie that creates inheritance is lineage. Provision can still be made through the one-third bequest, which is precisely what it exists for.</p>
<h3>Why did my uncle get nothing?</h3>
<p>Almost certainly because the deceased left a son, or a father. Both sit ahead of an uncle in the order of nearness, and both exclude him completely.</p>

<p><a href="/">See who is excluded in your own case</a>, or read <a href="/blog/awl-and-radd-explained">awl and radd explained</a>.</p>
HTML,
];
