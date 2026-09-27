<?php
/**
 * The calculator: one page, one column of questions, no login and no reload.
 *
 * Steps 1 to 4 cover the large majority of real estates and stay visible.
 * Everything rarer sits behind one expander, so the common path stays short.
 *
 * @var array $input   previously submitted values
 * @var string|null $resultHtml
 * @var string|null $error
 */

use App\Locale;

$v = static fn (string $key, $default = 0) => $input[$key] ?? $default;
$madhhab = (string) $v('madhhab', Locale::suggestedMadhhab() ?? '');
$gender = (string) $v('deceased_gender', 'male');
$jurisdiction = (string) $v('jurisdiction', 'classical');
?>
<p class="lede"><?= e(t('ui.tagline')) ?></p>

<div class="calc">
    <form class="calc__form" method="post" action="<?= e(lp()) ?>" id="calc-form"
          data-endpoint="<?= e(lp('api/calculate')) ?>" novalidate>
        <?php if (!empty($error)): ?>
            <p class="alert" role="alert"><?= e($error) ?></p>
        <?php endif; ?>
        <div id="calc-error" class="alert" role="alert" hidden></div>

        <fieldset>
            <legend><?= e(t('ui.step.deceased')) ?></legend>

            <div class="field" style="margin-bottom:1rem">
                <label for="f-madhhab"><?= e(t('ui.madhhab')) ?></label>
                <select id="f-madhhab" name="madhhab" required aria-describedby="f-madhhab-h">
                    <option value=""><?= e(t('ui.madhhab.choose')) ?></option>
                    <?php foreach (['hanafi', 'shafii', 'maliki', 'hanbali'] as $school): ?>
                        <option value="<?= e($school) ?>" <?= $madhhab === $school ? 'selected' : '' ?>><?= e(t('ui.madhhab.' . $school)) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="hint" id="f-madhhab-h"><?= e(t('ui.madhhab.help')) ?></p>
            </div>

            <label id="gender-label"><?= e(t('ui.deceased_gender')) ?></label>
            <div class="choice" role="radiogroup" aria-labelledby="gender-label">
                <input type="radio" id="g-male" name="deceased_gender" value="male" <?= $gender === 'male' ? 'checked' : '' ?>>
                <label for="g-male"><?= e(t('ui.male')) ?></label>
                <input type="radio" id="g-female" name="deceased_gender" value="female" <?= $gender === 'female' ? 'checked' : '' ?>>
                <label for="g-female"><?= e(t('ui.female')) ?></label>
            </div>

            <label id="jur-label" style="margin-top:1rem"><?= e(t('ui.jurisdiction')) ?></label>
            <div class="choice" role="radiogroup" aria-labelledby="jur-label">
                <input type="radio" id="j-classical" name="jurisdiction" value="classical" <?= $jurisdiction !== 'mflo' ? 'checked' : '' ?>>
                <label for="j-classical"><?= e(t('ui.jurisdiction.classical')) ?></label>
                <input type="radio" id="j-mflo" name="jurisdiction" value="mflo" <?= $jurisdiction === 'mflo' ? 'checked' : '' ?>>
                <label for="j-mflo"><?= e(t('ui.jurisdiction.mflo')) ?></label>
            </div>
            <p class="hint"><?= e(t('ui.jurisdiction.help')) ?></p>
        </fieldset>

        <fieldset>
            <legend><?= e(t('ui.step.spouse')) ?></legend>
            <div class="grid">
                <div class="field" data-spouse="female">
                    <?= App\View::partial('calculator/_check', ['name' => 'husband', 'label' => t('heir.husband'), 'value' => (bool) $v('husband', 0)]) ?>
                </div>
                <div data-spouse="male">
                    <?= App\View::partial('calculator/_number', ['name' => 'wives', 'label' => t('heir.wife.plural'), 'value' => (int) $v('wives')]) ?>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend><?= e(t('ui.step.children')) ?></legend>
            <div class="grid">
                <?= App\View::partial('calculator/_number', ['name' => 'sons', 'label' => t('heir.son.plural'), 'value' => (int) $v('sons')]) ?>
                <?= App\View::partial('calculator/_number', ['name' => 'daughters', 'label' => t('heir.daughter.plural'), 'value' => (int) $v('daughters')]) ?>
            </div>
        </fieldset>

        <fieldset>
            <legend><?= e(t('ui.step.parents')) ?></legend>
            <?= App\View::partial('calculator/_check', ['name' => 'father', 'label' => t('heir.father'), 'value' => (bool) $v('father', 0)]) ?>
            <?= App\View::partial('calculator/_check', ['name' => 'mother', 'label' => t('heir.mother'), 'value' => (bool) $v('mother', 0)]) ?>
        </fieldset>

        <details class="more" <?= !empty($input['_more_open']) ? 'open' : '' ?>>
            <summary><?= e(t('ui.step.others')) ?></summary>
            <div>
                <p class="hint"><?= e(t('ui.more_heirs.help')) ?></p>

                <?= App\View::partial('calculator/_check', ['name' => 'paternal_grandfather', 'label' => t('heir.paternal_grandfather'), 'value' => (bool) $v('paternal_grandfather', 0)]) ?>
                <?= App\View::partial('calculator/_check', ['name' => 'paternal_grandmother', 'label' => t('heir.paternal_grandmother'), 'value' => (bool) $v('paternal_grandmother', 0)]) ?>
                <?= App\View::partial('calculator/_check', ['name' => 'maternal_grandmother', 'label' => t('heir.maternal_grandmother'), 'value' => (bool) $v('maternal_grandmother', 0)]) ?>

                <div class="grid" style="margin-top:1rem">
                    <?= App\View::partial('calculator/_number', ['name' => 'sons_sons', 'label' => t('heir.sons_son.plural'), 'value' => (int) $v('sons_sons')]) ?>
                    <?= App\View::partial('calculator/_number', ['name' => 'sons_daughters', 'label' => t('heir.sons_daughter.plural'), 'value' => (int) $v('sons_daughters')]) ?>
                    <?= App\View::partial('calculator/_number', ['name' => 'full_brothers', 'label' => t('heir.full_brother.plural'), 'value' => (int) $v('full_brothers')]) ?>
                    <?= App\View::partial('calculator/_number', ['name' => 'full_sisters', 'label' => t('heir.full_sister.plural'), 'value' => (int) $v('full_sisters')]) ?>
                    <?= App\View::partial('calculator/_number', ['name' => 'consanguine_brothers', 'label' => t('heir.consanguine_brother.plural'), 'value' => (int) $v('consanguine_brothers')]) ?>
                    <?= App\View::partial('calculator/_number', ['name' => 'consanguine_sisters', 'label' => t('heir.consanguine_sister.plural'), 'value' => (int) $v('consanguine_sisters')]) ?>
                    <?= App\View::partial('calculator/_number', ['name' => 'uterine_siblings', 'label' => t('heir.uterine_sibling.plural'), 'value' => (int) $v('uterine_siblings')]) ?>
                    <?= App\View::partial('calculator/_number', ['name' => 'full_brother_sons', 'label' => t('heir.full_brother_son.plural'), 'value' => (int) $v('full_brother_sons')]) ?>
                    <?= App\View::partial('calculator/_number', ['name' => 'consanguine_brother_sons', 'label' => t('heir.consanguine_brother_son.plural'), 'value' => (int) $v('consanguine_brother_sons')]) ?>
                    <?= App\View::partial('calculator/_number', ['name' => 'paternal_uncles', 'label' => t('heir.full_paternal_uncle.plural'), 'value' => (int) $v('paternal_uncles')]) ?>
                    <?= App\View::partial('calculator/_number', ['name' => 'consanguine_paternal_uncles', 'label' => t('heir.consanguine_paternal_uncle.plural'), 'value' => (int) $v('consanguine_paternal_uncles')]) ?>
                    <?= App\View::partial('calculator/_number', ['name' => 'paternal_uncle_sons', 'label' => t('heir.full_paternal_uncle_son.plural'), 'value' => (int) $v('paternal_uncle_sons')]) ?>
                    <?= App\View::partial('calculator/_number', ['name' => 'consanguine_paternal_uncle_sons', 'label' => t('heir.consanguine_paternal_uncle_son.plural'), 'value' => (int) $v('consanguine_paternal_uncle_sons')]) ?>
                </div>
            </div>
        </details>

        <details class="more" id="mflo-block" <?= $jurisdiction === 'mflo' ? 'open' : '' ?>>
            <summary><?= e(t('ui.predeceased')) ?></summary>
            <div>
                <p class="hint"><?= e(t('ui.predeceased.help')) ?></p>
                <div id="predeceased-rows"></div>
                <button type="button" class="btn btn--ghost btn--small" id="add-predeceased"><?= e(t('ui.predeceased.add')) ?></button>
            </div>
        </details>

        <details class="more">
            <summary><?= e(t('ui.step.estate')) ?></summary>
            <div>
                <p class="hint"><?= e(t('ui.estate.help')) ?></p>
                <div class="grid">
                    <div class="field">
                        <label for="f-estate"><?= e(t('ui.estate.total')) ?></label>
                        <input type="number" inputmode="decimal" min="0" step="any" id="f-estate" name="estate_value" value="<?= e((string) $v('estate_value', '')) ?>">
                    </div>
                    <div class="field">
                        <label for="f-funeral"><?= e(t('ui.estate.funeral')) ?></label>
                        <input type="number" inputmode="decimal" min="0" step="any" id="f-funeral" name="funeral" value="<?= e((string) $v('funeral', '')) ?>">
                    </div>
                    <div class="field">
                        <label for="f-debts"><?= e(t('ui.estate.debts')) ?></label>
                        <input type="number" inputmode="decimal" min="0" step="any" id="f-debts" name="debts" value="<?= e((string) $v('debts', '')) ?>">
                    </div>
                    <div class="field">
                        <label for="f-wasiyyah"><?= e(t('ui.estate.wasiyyah')) ?></label>
                        <input type="number" inputmode="decimal" min="0" step="any" id="f-wasiyyah" name="wasiyyah" value="<?= e((string) $v('wasiyyah', '')) ?>">
                    </div>
                </div>
                <p class="hint"><?= e(t('ui.estate.deduction_note')) ?></p>
            </div>
        </details>

        <div class="actions">
            <button type="submit" class="btn"><?= e(t('ui.calculate')) ?></button>
            <a class="btn btn--ghost" href="<?= e(lp()) ?>"><?= e(t('ui.reset')) ?></a>
        </div>
    </form>

    <div class="calc__result">
        <div id="result" aria-live="polite" aria-atomic="true">
            <?php if (!empty($resultHtml)): ?>
                <?= $resultHtml ?>
            <?php else: ?>
                <div class="card">
                    <h2 style="margin-top:0"><?= e(t('ui.result')) ?></h2>
                    <p class="hint" style="margin:0"><?= e(t('ui.result.empty')) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<section class="wrap--narrow" style="margin-top:3rem;padding:0">
    <h2><?= e(t('ui.faq.title')) ?></h2>
    <?php foreach ($faq ?? [] as $item): ?>
        <h3><?= e($item['q']) ?></h3>
        <p><?= e($item['a']) ?></p>
    <?php endforeach; ?>
    <p><a href="<?= e(lp('methodology')) ?>"><?= e(t('ui.nav.methodology')) ?></a> · <a href="<?= e(lp('blog')) ?>"><?= e(t('ui.nav.guides')) ?></a></p>
</section>

<template id="predeceased-template">
    <div class="card" data-row>
        <div class="grid">
            <div class="field">
                <label><?= e(t('ui.predeceased.gender')) ?></label>
                <select name="predeceased_gender[]">
                    <option value="male"><?= e(t('heir.son')) ?></option>
                    <option value="female"><?= e(t('heir.daughter')) ?></option>
                </select>
            </div>
            <div class="field">
                <label><?= e(t('ui.predeceased.sons')) ?></label>
                <input type="number" min="0" max="50" step="1" name="predeceased_sons[]" value="0">
            </div>
            <div class="field">
                <label><?= e(t('ui.predeceased.daughters')) ?></label>
                <input type="number" min="0" max="50" step="1" name="predeceased_daughters[]" value="0">
            </div>
        </div>
        <button type="button" class="btn btn--ghost btn--small" data-remove><?= e(t('ui.predeceased.remove')) ?></button>
    </div>
</template>
