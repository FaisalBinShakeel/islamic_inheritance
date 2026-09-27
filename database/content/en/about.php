<?php

return [
    'slug' => 'about',
    'category' => 'pages',
    'translation_group' => 'about',
    'title' => 'About',
    'h1' => 'About this calculator',
    'meta_description' => 'Who built this Islamic inheritance calculator, what it does, what it deliberately does not do, and the review it is still waiting for.',
    'body' => <<<'HTML'
<p>This is a free calculator for Islamic inheritance — <em>faraid</em>, <em>meeras</em>, <em>wirasat</em> — with a library of guides built around it. You enter the surviving heirs, and it works out each share, shows the reasoning behind it, and names anyone who is excluded and by whom.</p>

<h2>What it is</h2>
<p>The whole product rests on one calculation engine. That engine is a single piece of code with no database access, no network access and no hidden state, which is what makes it possible to test it properly. It works entirely in exact fractions — never decimals — because a third and a sixth cannot be held exactly in a computer's floating-point numbers, and the drift that causes would show up as a wrong share.</p>
<p>Every rule it applies is written out plainly on the <a href="/methodology">Methodology page</a>, along with the parts of the subject it does not cover.</p>

<h2>What it is not</h2>
<p>It is not a fatwa. It is not legal advice. It does not produce a court-ready document or an official heir certificate. It does not generate rulings with artificial intelligence — every rule in it is written by hand and open to inspection.</p>

<h2>The review this is waiting for</h2>
<p>Faraid is not a percentage split, and a wrong answer here affects real family disputes and real money. The rules in this calculator are a careful reading of the classical rule tables, and they are tested against a suite of worked cases, each one citing the verse, hadith, textbook or statute it comes from.</p>
<p>What has <strong>not</strong> happened yet is a sign-off from a qualified scholar in each of the four schools. Until that is done and published here, every result carries a notice saying so. We would rather say that plainly than let a row of confident numbers imply an authority the project has not yet earned. The <a href="/sources">Sources page</a> lists what the rules were drawn from.</p>

<h2>If you think a result is wrong</h2>
<p>Tell us. There is a <a href="/report">form for reporting a suspected error</a>, and reports are read. A calculator that quietly carries a wrong rule for months does real harm, and a single credible complaint would undo the trust the whole thing depends on.</p>

<h2>Common questions</h2>
<h3>Is the calculator free?</h3>
<p>Yes, entirely, with no account and nothing saved. What you enter is not stored against your name — the only thing recorded is the anonymous shape of the family, which tells us which guide to write next.</p>
<h3>Which school of law does it follow?</h3>
<p>Whichever you choose. It covers the four Sunni schools and refuses to guess, because guessing would hand most people a confidently wrong answer. Ja'fari rules are a different system and are not covered.</p>
<h3>Can I use the result to divide an estate?</h3>
<p>Use it to understand the shares, then confirm them with a qualified Mufti. For the division to have legal effect, you will also need a lawyer in your country — see the guides on <a href="/blog/islamic-will-uk">Islamic wills</a> for why that matters outside Muslim-majority jurisdictions.</p>

<p><a href="/">Open the calculator</a>.</p>
HTML,
];
