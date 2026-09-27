<?php
/** @var string $name @var string $label @var int $value @var string|null $hint */
$id = 'f-' . $name;
?>
<div class="field">
    <label for="<?= e($id) ?>"><?= e($label) ?></label>
    <input type="number" inputmode="numeric" min="0" max="50" step="1"
           id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= (int) ($value ?? 0) ?>"
           <?= !empty($hint) ? 'aria-describedby="' . e($id) . '-h"' : '' ?>>
    <?php if (!empty($hint)): ?><p class="hint" id="<?= e($id) ?>-h"><?= e($hint) ?></p><?php endif; ?>
</div>
