<?php
/**
 * What each school says about this particular estate.
 *
 * The point of this panel is attribution. Every figure below is reported as
 * the position of a named school, not as the answer, which is the only honest
 * way to present a question the schools themselves have argued over for
 * centuries.
 *
 * @var array $c comparison
 * @var string $selected the school the main result follows
 */
?>
<section class="compare">
    <h3><?= e(t('ui.compare.title')) ?></h3>

    <?php if ($c['unanimous']): ?>
        <p class="compare__verdict compare__verdict--agree"><?= e(t('ui.compare.unanimous')) ?></p>
    <?php else: ?>
        <p class="compare__verdict"><?= e(t('ui.compare.differ')) ?></p>

        <?php foreach ($c['groups'] as $group): ?>
            <?php if (count($group['schools']) > 1): ?>
                <p class="hint" style="margin:.2rem 0"><?= e(t('ui.compare.same_as')) ?> <strong><?= e(implode(t('ui.list_separator'), $group['labels'])) ?></strong></p>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="compare__scroll">
            <table class="shares compare__table">
                <thead>
                    <tr>
                        <th scope="col"><?= e(t('ui.result.heir')) ?></th>
                        <?php foreach ($c['schools'] as $school): ?>
                            <th scope="col" class="num<?= $school === $selected ? ' is-selected' : '' ?>">
                                <?= e(t('ui.madhhab.' . $school)) ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($c['rows'] as $row): ?>
                    <tr<?= $row['differs'] ? ' class="differs"' : '' ?>>
                        <th scope="row" style="font-weight:500"><?= e($row['label']) ?></th>
                        <?php foreach ($c['schools'] as $school): ?>
                            <td class="num fraction<?= $school === $selected ? ' is-selected' : '' ?>">
                                <?= $row['by_school'][$school] === null
                                    ? '<span class="nothing">' . e(t('ui.compare.nothing')) . '</span>'
                                    : e((string) $row['by_school'][$school]) ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="hint"><?= e(t('ui.compare.your_school', ['school' => t('ui.madhhab.' . $selected)])) ?></p>
        <p class="note note--normal"><?= e(t('ui.compare.ask')) ?></p>
    <?php endif; ?>

    <?php if (!empty($c['notes'])): ?>
        <details class="more" style="margin-top:1rem">
            <summary><?= e(t('ui.compare.notes')) ?></summary>
            <div>
                <?php foreach ($c['notes'] as $school => $lines): ?>
                    <p style="margin:.4rem 0"><strong><?= e(t('ui.madhhab.' . $school)) ?>:</strong>
                        <?php foreach ($lines as $line): ?><br><?= e($line) ?><?php endforeach; ?>
                    </p>
                <?php endforeach; ?>
            </div>
        </details>
    <?php endif; ?>
</section>
