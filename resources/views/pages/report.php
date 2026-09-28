<?php
/** @var bool $sent @var string|null $message */
use App\Csrf;
?>
<div class="wrap--narrow" style="padding:0">
    <p class="lede"><?= e(t('ui.report.body')) ?></p>

    <?php if ($sent): ?>
        <p class="alert alert--ok" role="status"><?= e(t('ui.report.thanks')) ?></p>
    <?php else: ?>
        <?php if (!empty($message)): ?><p class="alert" role="alert"><?= e($message) ?></p><?php endif; ?>
        <form method="post" action="<?= e(lp('report')) ?>" class="card">
            <?= Csrf::field() ?>
            <div class="field" style="margin-bottom:1rem">
                <label for="r-heirs"><?= e(t('ui.result.heir')) ?></label>
                <input type="text" id="r-heirs" name="heirs" placeholder="wife 1, sons 2, daughters 3">
            </div>
            <div class="field" style="margin-bottom:1rem">
                <label for="r-madhhab"><?= e(t('ui.madhhab')) ?></label>
                <select id="r-madhhab" name="madhhab">
                    <option value=""><?= e(t('ui.madhhab.choose')) ?></option>
                    <?php foreach (Faraid\Madhhab\MadhhabRules::keys() as $school): ?>
                        <option value="<?= e($school) ?>"><?= e(t('ui.madhhab.' . $school)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="margin-bottom:1rem">
                <label for="r-message"><?= e(t('ui.report.message')) ?></label>
                <textarea id="r-message" name="message" required></textarea>
            </div>
            <div class="field" style="margin-bottom:1rem">
                <label for="r-expected"><?= e(t('ui.report.expected')) ?></label>
                <textarea id="r-expected" name="expected" style="min-height:80px"></textarea>
            </div>
            <div class="field" style="margin-bottom:1rem">
                <label for="r-email"><?= e(t('ui.report.email')) ?></label>
                <input type="email" id="r-email" name="email">
            </div>
            <button type="submit" class="btn"><?= e(t('ui.report.send')) ?></button>
        </form>
    <?php endif; ?>
</div>
