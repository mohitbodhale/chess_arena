<?php
/**
 * The application shell.
 *
 * AdminLTE provides the admin chrome, the arena stylesheet paints it in the
 * dark Groww inspired palette.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User|null $currentUser
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
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= h($title) ?> · Chess Arena</title>
    <?= $this->Html->meta('csrf-token', $csrfToken) ?>
    <?= $this->Html->meta('viewport', 'width=device-width, initial-scale=1') ?>
    <?= $this->Html->css(['adminlte/adminlte.min.css', 'fonts/font-awesome.min.css', 'plugins/overlayscrollbars.min.css', 'fonts.css', 'arena.css']) ?>
    <?= $this->fetch('meta') ?>
</head>
<body class="hold-transition admin-body admin-shell">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Toggle navigation">
                    <i class="fa fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a class="nav-link" href="<?= $this->Url->build('/lobby') ?>">
                    <i class="fa fa-chess-board mr-2"></i>Lobby
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a class="nav-link" href="<?= $this->Url->build('/leaderboard') ?>">
                    <i class="fa fa-chart-line mr-2"></i>Leaderboard
                </a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto align-items-center">
            <?php if ($currentUser !== null) : ?>
                <li class="nav-item mr-3">
                    <span class="pill is-open" style="text-transform:none;letter-spacing:.02em;">
                        <?= h($currentUser->get('username')) ?>
                        <span class="ml-1" style="opacity:.7;">· <?= h((string)$currentRole) ?></span>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $this->Url->build('/users/dashboard') ?>">
                        <i class="fa fa-wallet mr-2"></i>Wallet
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $this->Url->build('/users/logout') ?>">
                        <i class="fa fa-sign-out-alt mr-2"></i>Sign out
                    </a>
                </li>
            <?php else : ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $this->Url->build('/users/login') ?>">Sign in</a>
                </li>
                <li class="nav-item">
                    <a class="btn-arena ml-2" href="<?= $this->Url->build('/users/register') ?>">Create account</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="<?= $this->Url->build('/') ?>" class="brand-link">
            <span class="brand-mark" style="width:28px;height:28px;border-radius:8px;">♞</span>
            <span class="brand-text">Chess Arena</span>
        </a>

        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-header">Play</li>
                    <li class="nav-item">
                        <a href="<?= $this->Url->build('/lobby') ?>" class="nav-link">
                            <i class="nav-icon fa fa-layer-group"></i>
                            <p>Tournament lobby</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= $this->Url->build('/tournaments/mine') ?>" class="nav-link">
                            <i class="nav-icon fa fa-gamepad"></i>
                            <p>My contests</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= $this->Url->build('/leaderboard') ?>" class="nav-link">
                            <i class="nav-icon fa fa-trophy"></i>
                            <p>Leaderboard</p>
                        </a>
                    </li>

                    <?php if ($currentRole === 'Admin') : ?>
                        <li class="nav-header">Admin</li>
                        <li class="nav-item">
                            <a href="<?= $this->Url->build('/admin') ?>" class="nav-link">
                                <i class="nav-icon fa fa-gauge-high"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= $this->Url->build('/admin/tournaments') ?>" class="nav-link">
                                <i class="nav-icon fa fa-sitemap"></i>
                                <p>Contests</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= $this->Url->build('/admin/wallet') ?>" class="nav-link">
                                <i class="nav-icon fa fa-file-invoice-dollar"></i>
                                <p>Wallet auditor</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= $this->Url->build('/admin/simulate') ?>" class="nav-link">
                                <i class="nav-icon fa fa-flask"></i>
                                <p>Match simulator</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= $this->Url->build('/admin/users') ?>" class="nav-link">
                                <i class="nav-icon fa fa-users"></i>
                                <p>Players</p>
                            </a>
                        </li>
                    <?php elseif ($currentRole === 'Organizer') : ?>
                        <li class="nav-header">Organizer</li>
                        <li class="nav-item">
                            <a href="<?= $this->Url->build('/organizer') ?>" class="nav-link">
                                <i class="nav-icon fa fa-gauge-high"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= $this->Url->build('/organizer/tournaments') ?>" class="nav-link">
                                <i class="nav-icon fa fa-sitemap"></i>
                                <p>My contests</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= $this->Url->build('/organizer/add-tournament') ?>" class="nav-link">
                                <i class="nav-icon fa fa-plus-circle"></i>
                                <p>Create contest</p>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </div>

    <footer class="main-footer">
        <div class="float-left d-none d-sm-inline-block">
            &copy; <?= date('Y') ?> Chess Arena — competitive chess with real prize pools.
        </div>
        <div class="float-right d-none d-sm-inline-block">
            <span class="mono" style="font-size:.78rem;">#121417 · #00D09C</span>
        </div>
    </footer>
</div>

<?= $this->Html->script(['plugins/jquery.min.js', 'plugins/bootstrap.bundle.min.js', 'plugins/jquery.overlayScrollbars.min.js', 'adminlte/adminlte.min.js']) ?>
<?= $this->fetch('script') ?>
</body>
</html>
