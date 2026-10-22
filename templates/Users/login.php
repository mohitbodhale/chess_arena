<?php
/**
 * The sign in screen.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User|null $currentUser
 */

$this->assign('title', 'Sign in');
?>
<div class="auth-screen">
    <div class="auth-card">
        <a class="brand mb-4" href="<?= $this->Url->build('/') ?>">
            <span class="brand-mark">♞</span>
            Chess Arena
        </a>
        <h1>Welcome back</h1>
        <p class="lead">Sign in to enter contests, play your games and track the leaderboard.</p>

        <?= $this->Form->create(null, ['class' => 'needs-validation', 'novalidate' => true]) ?>
        <div class="field">
            <?= $this->Form->label('email', 'Email') ?>
            <?= $this->Form->text('email', [
                'class' => 'form-control',
                'placeholder' => 'you@example.com',
                'autocomplete' => 'username',
                'required' => true,
            ]) ?>
        </div>
        <div class="field">
            <?= $this->Form->label('password', 'Password') ?>
            <?= $this->Form->password('password', [
                'class' => 'form-control',
                'placeholder' => '••••••••',
                'autocomplete' => 'current-password',
                'required' => true,
            ]) ?>
        </div>
        <button type="submit" class="btn-arena" style="width:100%;">
            Sign in <i class="fas fa-arrow-right ml-2"></i>
        </button>
        <?= $this->Form->end() ?>

        <p class="auth-switch">
            New to the arena?
            <a href="<?= $this->Url->build('/users/register') ?>">Create a free account</a>
        </p>
    </div>
</div>
