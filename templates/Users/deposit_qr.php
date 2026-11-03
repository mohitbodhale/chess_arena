<?php
/**
 * Show the QR code for a deposit request.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\DepositRequest $request
 */

$this->assign('title', 'Deposit QR code');
?>
<div class="shell" style="padding-top:2rem;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <span class="eyebrow">Wallet · Deposit</span>
            <h1 style="font-size:1.9rem;margin:.4rem 0 .2rem;">Pay with QR</h1>
            <p class="muted" style="margin:0;">
                Request <strong>#<?= (int)$request->get('id') ?></strong> ·
                ₹<?= number_format((float)$request->get('amount'), 2) }
            </p>
        </div>
        <a class="btn-arena" href="<?= $this->Url->build('/users/dashboard') ?>">
            <i class="fa fa-arrow-left"></i> Back to wallet
        </a>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card surface surface-pad">
                <h3 style="margin:0 0 .5rem;font-size:1.05rem;">Scan to pay</h3>
                <div class="text-center mb-3">
                    <img src="<?= $this->Url->build('/users/deposit-qr-image/' . $request->get('id')) ?>"
                         alt="UPI QR code"
                         style="width:260px;height:260px;border:1px solid var(--line);border-radius:8px;background:#fff;">
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>UPI id</span>
                    <span class="mono tiny"><?= h((string)($request->get('upi_id') ?? 'arena@upi')) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Amount</span>
                    <span class="mono">₹<?= number_format((float)$request->get('amount'), 2) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Reference</span>
                    <span class="mono tiny"><?= h((string)$request->get('reference')) ?></span>
                </div>
                <p class="tiny muted mt-3">
                    After paying, an administrator will verify and credit your deposit balance.
                </p>
                <a class="btn-arena is-ghost mt-2" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'deposit']) ?>">
                    Back
                </a>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card surface surface-pad">
                <h3 style="margin:0 0 .5rem;font-size:1.05rem;">Payment details</h3>
                <table class="table mb-0" style="width:100%;">
                    <tbody>
                    <tr>
                        <th style="width:30%;">UPI id</th>
                        <td class="mono"><?= h((string)($request->get('upi_id') ?? 'arena@upi')) ?></td>
                    </tr>
                    <tr>
                        <th>Amount</th>
                        <td class="mono">₹<?= number_format((float)$request->get('amount'), 2) ?></td>
                    </tr>
                    <tr>
                        <th>Reference</th>
                        <td class="mono tiny"><?= h((string)$request->get('reference')) ?></td>
                    </tr>
                    <tr>
                        <th>Note</th>
                        <td class="tiny">Chess Arena deposit #<?= (int)$request->get('id') ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="pill is-open">Waiting for approval</span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
