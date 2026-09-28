<?php
/**
 * The result, treated as a document rather than a page fragment: a clear
 * title, the heir table as the centrepiece, and a layout that stays
 * intentional when printed. The print stylesheet and the screen version come
 * from this one template, so print is never an afterthought.
 *
 * @var array $r presented result
 * @var bool $unreviewed
 * @var string $shareText
 * @var array|null $comparison what the other three schools say about this case
 */

use App\Config;
use App\Support\Str;

$currency = $r['currency'] ?? '';
?>
<article class="result" id="result-doc">
    <header class="result__head">
        <h2 class="result__title"><?= e(t('ui.result')) ?></h2>
        <p class="result__meta">
            <?= e(t(
                $r['is_school_of_law'] ? 'ui.result.attributed' : 'ui.result.attributed_position',
                ['school' => t('ui.madhhab.' . $r['madhhab'])]
            )) ?>
            <?php if ($r['mflo']): ?> · <?= e(t('ui.jurisdiction.mflo')) ?><?php endif; ?>
            <?php if ($r['special_case'] !== null): ?> · <?= e(ucfirst(str_replace('_', ' ', (string) $r['special_case']))) ?><?php endif; ?>
        </p>
        <?php if ($r['net_estate'] !== null): ?>
            <p style="margin:.6rem 0 0">
                <?= e(t('ui.result.net_estate')) ?>:
                <span class="result__net num ltr"><?= e(Str::money((float) $r['net_estate'], $currency)) ?></span>
            </p>
        <?php endif; ?>
    </header>

    <table class="shares">
        <?php if ($r['denominator'] > 1): ?>
        <caption class="hint" style="caption-side:bottom;text-align:start;margin-top:.5rem">
            <?= e(t('ui.result.denominator', ['denominator' => $r['denominator']])) ?>
        </caption>
        <?php endif; ?>
        <thead>
            <tr>
                <th scope="col"><?= e(t('ui.result.heir')) ?></th>
                <th scope="col" class="num"><?= e(t('ui.result.number')) ?></th>
                <th scope="col" class="num"><?= e(t('ui.result.share')) ?></th>
                <th scope="col" class="num"><?= e(t('ui.result.percent')) ?></th>
                <?php if ($r['net_estate'] !== null): ?>
                    <th scope="col" class="num"><?= e(t('ui.result.amount')) ?></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($r['shares'] as $row): ?>
            <tr>
                <th scope="row" style="font-weight:500"><?= e($row['label']) ?></th>
                <td class="num"><?= (int) $row['count'] ?></td>
                <td class="num fraction"><?= e($row['over_denominator']) ?>
                    <?php if ($row['count'] > 1): ?><span class="each"><?= e($row['per_head']) ?> <?= e(t('ui.result.each')) ?></span><?php endif; ?>
                </td>
                <td class="num"><?= e($row['percent']) ?>%</td>
                <?php if ($r['net_estate'] !== null): ?>
                    <td class="num"><?= e(Str::money((float) $row['amount'], $currency)) ?>
                        <?php if ($row['amount_each'] !== null): ?><span class="each"><?= e(Str::money((float) $row['amount_each'], $currency)) ?> <?= e(t('ui.result.each')) ?></span><?php endif; ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><?= e(t('ui.result.total')) ?></td>
                <td class="num fraction"><?= e($r['distributed']) ?></td>
                <td class="num">&nbsp;</td>
                <?php if ($r['net_estate'] !== null): ?><td class="num">&nbsp;</td><?php endif; ?>
            </tr>
            <?php if ($r['has_remainder']): ?>
            <tr>
                <td colspan="2"><?= e(t('ui.result.undistributed')) ?></td>
                <td class="num fraction"><?= e($r['undistributed']) ?></td>
                <td class="num">&nbsp;</td>
                <?php if ($r['net_estate'] !== null): ?><td class="num">&nbsp;</td><?php endif; ?>
            </tr>
            <?php endif; ?>
        </tfoot>
    </table>

    <h3><?= e(t('ui.result.why')) ?></h3>
    <ul class="why">
        <?php foreach ($r['shares'] as $row): ?>
            <li>
                <b><?= e($row['label']) ?> <span class="nowrap">— <span class="fraction ltr"><?= e($row['fraction']) ?></span></span></b>
                <span><?= e($row['reason']) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($r['excluded'] !== []): ?>
    <section class="excluded">
        <h3><?= e(t('ui.result.excluded')) ?></h3>
        <ul>
            <?php foreach ($r['excluded'] as $row): ?>
                <li><strong><?= e($row['label']) ?></strong> — <?= e($row['reason']) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <?php if ($r['notes'] !== []): ?>
    <section class="notes">
        <h3><?= e(t('ui.result.notes')) ?></h3>
        <?php foreach ($r['notes'] as $note): ?>
            <p class="note note--<?= e($note['severity']) ?>"><?= e($note['text']) ?></p>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <?php if (!empty($comparison)): ?>
        <?= App\View::partial('calculator/comparison', ['c' => $comparison, 'selected' => $r['madhhab']]) ?>
    <?php endif; ?>

    <div class="disclaimer">
        <strong><?= e(t('ui.disclaimer.title')) ?></strong>
        <?= e(t('ui.disclaimer.body')) ?>
        <?php if ($unreviewed): ?>
            <br><br><?= e(t('ui.disclaimer.unreviewed')) ?>
        <?php endif; ?>
    </div>

    <div class="actions no-print" style="margin-top:1.25rem">
        <button type="button" class="btn btn--ghost btn--small" data-print><?= e(t('ui.result.print')) ?></button>
        <button type="button" class="btn btn--ghost btn--small" data-copy><?= e(t('ui.result.copy')) ?></button>
        <a class="btn btn--ghost btn--small" data-whatsapp rel="noopener"
           href="https://wa.me/?text=<?= e(rawurlencode($shareText)) ?>"
           target="_blank"><?= e(t('ui.result.whatsapp')) ?></a>
        <a class="btn btn--ghost btn--small" href="<?= e(lp('report')) ?>"><?= e(t('ui.result.report')) ?></a>
    </div>
    <textarea id="share-text" hidden aria-hidden="true"><?= e($shareText) ?></textarea>
</article>
