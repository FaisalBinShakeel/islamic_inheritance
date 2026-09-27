<?php
/** @var string $name @var string $label @var bool $value */
$id = 'f-' . $name;
?>
<div class="switchrow">
    <input type="checkbox" id="<?= e($id) ?>" name="<?= e($name) ?>" value="1" <?= !empty($value) ? 'checked' : '' ?>>
    <label for="<?= e($id) ?>"><?= e($label) ?></label>
</div>
