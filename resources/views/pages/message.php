<?php /** @var string $message @var array|null $link */ ?>
<p class="lede"><?= e($message) ?></p>
<?php if (!empty($link)): ?>
    <p><a class="btn" href="<?= e($link['href']) ?>"><?= e($link['label']) ?></a></p>
<?php endif; ?>
