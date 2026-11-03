<?php
/**
 * Deposit requests awaiting admin approval.
 *
 * @var \App\View\AppView $this
 * @var array<\App\Model\Entity\DepositRequest> $requests
 * @var string $status
 */

$this->assign('title', 'Deposit requests');
?>
<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.6rem;">
            <div>
                <span class="eyebrow">Wallet audit</span>
                <h1 style="font-size:1.5rem;margin:.35rem 0 0;">Deposit requests</h1>
            </div>
            <a class="btn-arena is-muted" href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'wallet']) ?>">
                <i class="fa fa-arrow-left"></i> Back to wallet audit
            </a>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <?= ucfirst((string)$status) ?> deposit requests
                    </h3>
                </div>
                <div class="card-body p-0">
                    <?php if ($requests === []) : ?>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <tbody>
                                <tr>
                                    <td colspan="6" class="muted" style="padding:2rem;text-align:center;">
                                        No <?= (string)$status ?> deposit requests.
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                <tr>
                                    <th style="width:60px;">#</th>
                                    <th>Player</th>
                                    <th style="width:110px;">Amount</th>
                                    <th style="width:150px;">UPI id</th>
                                    <th style="width:150px;">Reference</th>
                                    <th style="width:130px;">Requested</th>
                                    <th style="width:140px;">Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($requests as $request) : ?>
                                    <tr>
                                        <td class="mono tiny"><?= (int)$request->get('id') ?></td>
                                        <td><?= h((string)($request->get('user')['username'] ?? 'Player')) ?></td>
                                        <td class="mono tiny">₹<?= number_format((float)$request->get('amount'), 2) ?></td>
                                        <td class="mono tiny"><?= h((string)($request->get('upi_id') ?? '—')) ?></td>
                                        <td class="mono tiny"><?= h((string)$request->get('reference')) ?></td>
                                        <td class="mono tiny"><?= $request->get('created_at') ? $request->get('created_at')->format('Y-m-d H:i') : '—' ?></td>
                                        <td class="text-right">
                                            <?php if ($request->isPending()) : ?>
                                                <form action="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'approveDeposit']) ?>" method="post" style="display:inline;">
                                                    <input type="hidden" name="request_id" value="<?= (int)$request->get('id') ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn-arena is-ghost" style="padding:.25rem .6rem;font-size:.75rem;" onclick="return confirm('Approve this deposit?')">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form action="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'approveDeposit']) ?>" method="post" style="display:inline;margin-left:.3rem;">
                                                    <input type="hidden" name="request_id" value="<?= (int)$request->get('id') ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn-arena is-ghost" style="padding:.25rem .6rem;font-size:.75rem;" onclick="return confirm('Reject this deposit?')">
                                                        Reject
                                                    </button>
                                                </form>
                                            <?php elseif ($request->isApproved()) : ?>
                                                <span class="pill is-live">Approved</span>
                                            <?php else : ?>
                                                <span class="pill is-done">Rejected</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card surface surface-pad">
                <h3 style="margin:0 0 .9rem;font-size:1.05rem;">Filter</h3>
                <div class="field mb-3">
                    <label style="font-size:.8rem;">Status</label>
                    <a class="btn-arena is-ghost<?= $status === 'pending' ? ' is-active' : '' ?>" href="<?= $this->Url->build(['action' => 'depositRequests', '?' => ['status' => 'pending']]) ?>">
                        Pending
                    </a>
                    <a class="btn-arena is-ghost<?= $status === 'approved' ? ' is-active' : '' ?>" href="<?= $this->Url->build(['action' => 'depositRequests', '?' => ['status' => 'approved']]) ?>">
                        Approved
                    </a>
                    <a class="btn-arena is-ghost<?= $status === 'rejected' ? ' is-active' : '' ?>" href="<?= $this->Url->build(['action' => 'depositRequests', '?' => ['status' => 'rejected']]) ?>">
                        Rejected
                    </a>
                    <a class="btn-arena is-ghost<?= $status === '' ? ' is-active' : '' ?>" href="<?= $this->Url->build(['action' => 'depositRequests', '?' => ['status' => '']]) ?>">
                        All
                    </a>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn-arena" href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'wallet']) ?>">
                        Wallet audit
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
