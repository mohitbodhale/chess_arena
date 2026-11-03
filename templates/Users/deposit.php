<?php
/**
 * Request a wallet deposit.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var \App\Model\Entity\DepositRequest|null $pending
 */

$this->assign('title', 'Add money to wallet');
?>
<div class="shell" style="padding-top:2rem;">
    <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
        <div>
            <span class="eyebrow">Wallet · Deposit</span>
            <h1 style="font-size:1.9rem;margin:.4rem 0 .2rem;">Add money</h1>
            <p class="muted" style="margin:0;">
                Current deposit balance <strong>₹<?= number_format((float)$user->get('deposit_balance'), 2) ?></strong>
            </p>
        </div>
        <a class="btn-arena" href="<?= $this->Url->build('/users/dashboard') ?>">
            <i class="fa fa-arrow-left"></i> Back to wallet
        </a>
    </div>

    <?php if ($pending !== null && $pending->isPending()) : ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pending deposit request</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Amount</span>
                    <span class="mono">₹<?= number_format((float)$pending->get('amount'), 2) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Reference</span>
                    <span class="mono tiny"><?= h((string)$pending->get('reference')) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>Status</span>
                    <span class="pill is-open">Pending admin approval</span>
                </div>
                <p class="tiny muted mt-3">
                    Pay the amount using the UPI QR code below, then wait for an administrator to approve the
                    request. Your deposit balance will be credited once approved.
                </p>
                <a class="btn-arena mt-2" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'depositQr', $pending->get('id')]) ?>">
                    <i class="fa fa-qrcode"></i> Show QR code again
                </a>
            </div>
        </div>
    <?php else : ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Request deposit</h3>
            </div>
            <div class="card-body">
                <p class="tiny muted">
                    Enter the amount you want to add to your deposit balance. Pay using any UPI app by scanning
                    the QR code shown after you submit.
                </p>
                <?= $this->Form->create(null, ['url' => ['controller' => 'Users', 'action' => 'requestDeposit']]) ?>
                <div class="field mb-3">
                    <?= $this->Form->label('amount', 'Amount (₹)') ?>
                    <?= $this->Form->number('amount', [
                        'class' => 'form-control',
                        'placeholder' => '100',
                        'step' => '1',
                        'min' => '1',
                        'required' => true,
                    ]) ?>
                </div>
                <div class="field mb-3">
                    <?= $this->Form->label('upi_id', 'Your UPI id (optional)') ?>
                    <?= $this->Form->text('upi_id', [
                        'class' => 'form-control',
                        'placeholder' => 'you@upi',
                        'help' => 'Used to identify your payment.',
                    ]) ?>
                    <?php if ($this->Form->isFieldError('upi_id')) : ?>
                        <div class="error"><?= h((string)$this->Form->getError('upi_id')[0]) ?></div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn-arena">
                    <i class="fa fa-qrcode"></i> Request deposit & show QR
                </button>
                <?= $this->Form->end() ?>
            </div>
        </div>
    <?php endif; ?>
</div>
