<?php
/**
 * The cinematic landing layout.
 *
 * @var \App\View\AppView $this
 */

use App\Rbac\IdentityHelper;

$identity = $this->getRequest()->getAttribute('identity');
$currentUser = IdentityHelper::user($identity);
$currentRole = $currentUser?->getRoleName();
$csrfToken = (string)$this->getRequest()->getAttribute('csrfToken');
$title = $this->fetch('title') ?: 'Chess Arena';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> · Chess Arena</title>
    <?= $this->Html->meta('csrf-token', $csrfToken) ?>
    <?= $this->Html->css(['fonts/font-awesome.min.css', 'fonts.css', 'arena.css']) ?>
    <?= $this->fetch('meta') ?>
</head>
<body>

<nav class="landing-nav">
    <div class="shell">
        <a class="brand" href="<?= $this->Url->build('/') ?>">
            <span class="brand-mark">♞</span>
            Chess Arena
        </a>
        <div class="landing-links">
            <a href="<?= $this->Url->build('/lobby') ?>">Tournaments</a>
            <a href="<?= $this->Url->build('/leaderboard') ?>">Leaderboard</a>
            <?php if ($currentRole === 'Admin') : ?>
                <a href="<?= $this->Url->build('/admin') ?>">Admin</a>
            <?php elseif ($currentRole === 'Organizer') : ?>
                <a href="<?= $this->Url->build('/organizer') ?>">Organizer</a>
            <?php endif; ?>
            <?php if ($currentUser !== null) : ?>
                <a href="<?= $this->Url->build('/users/dashboard') ?>">Wallet</a>
                <a class="btn-arena" href="<?= $this->Url->build('/users/logout') ?>">Sign out</a>
            <?php else : ?>
                <a href="<?= $this->Url->build('/users/login') ?>">Sign in</a>
                <a class="btn-arena" href="<?= $this->Url->build('/users/register') ?>">Join free</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<?= $this->fetch('content') ?>

<footer class="landing-footer">
    <div class="shell">
        <span>© <?= date('Y') ?> Chess Arena. Play fair, win fair.</span>
        <span class="mono">#121417 · #00D09C</span>
    </div>
</footer>

<?= $this->Html->script(['plugins/jquery.min.js']) ?>
<?= $this->fetch('script') ?>
</body>
</html>
