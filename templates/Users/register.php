<?php
/**
 * The registration screen.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */

$this->assign('title', 'Create account');
?>
<div class="auth-screen">
    <div class="auth-card">
        <a class="brand mb-4" href="<?= $this->Url->build('/') ?>">
            <span class="brand-mark">♞</span>
            Chess Arena
        </a>
        <h1>Create your seat</h1>
        <p class="lead">New accounts start with a ₹1,000 bonus in the wallet.</p>

        <?= $this->Form->create($user, ['class' => 'needs-validation', 'novalidate' => true]) ?>
        <div class="field">
            <?= $this->Form->label('username', 'Handle') ?>
            <?= $this->Form->text('username', [
                'class' => 'form-control',
                'placeholder' => 'knight_rider',
                'autocomplete' => 'nickname',
                'required' => true,
            ]) ?>
            <?php $usernameError = $user->getError('username'); ?>
            <?php if ($usernameError) : ?>
                <div class="error"><?= h((string)$usernameError[0]) ?></div>
            <?php endif; ?>
        </div>
        <div class="field">
            <?= $this->Form->label('email', 'Email') ?>
            <?= $this->Form->email('email', [
                'class' => 'form-control',
                'placeholder' => 'you@example.com',
                'autocomplete' => 'email',
                'required' => true,
            ]) ?>
            <?php $emailError = $user->getError('email'); ?>
            <?php if ($emailError) : ?>
                <div class="error"><?= h((string)$emailError[0]) ?></div>
            <?php endif; ?>
        </div>
        <div class="field">
            <?= $this->Form->label('password', 'Password') ?>
            <?= $this->Form->password('password', [
                'class' => 'form-control',
                'placeholder' => 'At least 6 characters',
                'autocomplete' => 'new-password',
                'required' => true,
            ]) ?>
            <?php $passwordError = $user->getError('password'); ?>
            <?php if ($passwordError) : ?>
                <div class="error"><?= h((string)$passwordError[0]) ?></div>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn-arena" style="width:100%;">
            Create account <i class="fas fa-bolt ml-2"></i>
        </button>
        <?= $this->Form->end() ?>

        <p class="auth-switch">
            Already registered?
            <a href="<?= $this->Url->build('/users/login') ?>">Sign in</a>
        </p>
    </div>
</div>
