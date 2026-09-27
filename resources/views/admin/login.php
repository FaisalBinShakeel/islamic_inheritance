<?php use App\Csrf; /** @var string|null $error */ ?>
<div class="wrap--narrow" style="padding:0;max-width:420px">
    <?php if (!empty($error)): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" action="/admin/login" class="card">
        <?= Csrf::field() ?>
        <div class="field" style="margin-bottom:1rem">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autocomplete="username">
        </div>
        <div class="field" style="margin-bottom:1rem">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <button class="btn" type="submit">Sign in</button>
    </form>
</div>
