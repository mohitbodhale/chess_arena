<?php
/**
 * The player wallet and contest overview.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var array<\App\Model\Entity\TournamentRegistration> $myContests
 * @var array<\App\Model\Entity\WalletTransaction> $ledger
 * @var float $totalBalance
 */

$this->assign('title', 'My wallet');
?>
<div class="shell" style="padding-top:2rem;">
    <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
        <div>
            <span class="eyebrow">Player wallet</span>
            <h1 style="font-size:1.9rem;margin:.4rem 0 .2rem;">Hello, <?= h((string)$user->get('username')) ?></h1>
            <p class="muted" style="margin:0;">
                Role <strong><?= h((string)$user->getRoleName()) ?></strong> ·
                rating <strong><?= (int)$user->get('rating') ?></strong>
            </p>
        </div>
        <a class="btn-arena" href="<?= $this->Url->build('/lobby') ?>">
            <i class="fa fa-bolt"></i> Find a contest
        </a>
    </div>

    <div class="row">
        <div class="col-lg-3 col-sm-6">
            <div class="small-box">
                <div class="inner">
                    <h3>₹<?= number_format($totalBalance, 2) ?></h3>
                    <p>Total balance</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="small-box">
                <div class="inner">
                    <h3>₹<?= number_format((float)$user->get('deposit_balance'), 2) ?></h3>
                    <p>Deposit</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="small-box">
                <div class="inner">
                    <h3>₹<?= number_format((float)$user->get('winnings_balance'), 2) ?></h3>
                    <p>Winnings</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="small-box">
                <div class="inner">
                    <h3><?= count($myContests) ?></h3>
                    <p>Contests joined</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">My contests</h3>
                </div>
                <div class="card-body p-0">
                    <?php if ($myContests === []) : ?>
                        <p class="muted" style="padding:1.2rem 1.3rem;">
                            You have not entered a contest yet.
                            <a href="<?= $this->Url->build('/lobby') ?>">Browse the lobby</a>.
                        </p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                <tr>
                                    <th>Contest</th>
                                    <th>Status</th>
                                    <th>W / D / L</th>
                                    <th>Points</th>
                                    <th>Prize</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($myContests as $entry) : ?>
                                    <?php $contest = $entry->get('tournament'); ?>
                                    <tr>
                                        <td><?= h((string)($contest['title'] ?? 'Contest')) ?></td>
                                        <td>
                                            <span class="pill <?= ($contest['status'] ?? '') === 'Live' ? 'is-live' : 'is-done' ?>">
                                                <?= h((string)($contest['status'] ?? '')) ?>
                                            </span>
                                        </td>
                                        <td class="mono tiny">
                                            <?= (int)$entry->get('wins') ?> / <?= (int)$entry->get('draws') ?> / <?= (int)$entry->get('losses') ?>
                                        </td>
                                        <td class="points"><?= number_format((float)$entry->get('score'), 1) ?></td>
                                        <td class="mono tiny">
                                            <?= (float)$entry->get('prize_won') > 0 ? '₹' . number_format((float)$entry->get('prize_won')) : '—' ?>
                                        </td>
                                        <td class="text-right">
                                            <a class="btn-arena is-ghost"
                                               href="<?= $this->Url->build(['controller' => 'Tournaments', 'action' => 'view', $contest['id'] ?? 0]) ?>">
                                                Open
                                            </a>
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

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Wallet ledger</h3>
                </div>
                <div class="card-body p-0">
                    <?php if ($ledger === []) : ?>
                        <p class="muted" style="padding:1.2rem 1.3rem;">No wallet movements yet.</p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <tbody>
                                <?php foreach ($ledger as $entry) : ?>
                                    <tr>
                                        <td>
                                            <div style="font-size:.88rem;"><?= h((string)$entry->get('description')) ?></div>
                                            <div class="tiny muted mono"><?= h((string)$entry->get('type')) ?></div>
                                        </td>
                                        <td class="text-right mono" style="white-space:nowrap;">
                                            <span style="color:<?= $entry->get('direction') === 'credit' ? 'var(--accent)' : 'var(--danger)' ?>;">
                                                <?= $entry->get('direction') === 'credit' ? '+' : '−' ?>₹<?= number_format((float)$entry->get('amount'), 2) ?>
                                            </span>
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
    </div>
</div>
