<?php
/**
 * The slot manager, shared by the admin and the organizer.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Tournament $tournament
 * @var array<\App\Model\Entity\TournamentRegistration> $registrations
 * @var array<\App\Model\Entity\ArenaMatch> $matches
 * @var array<int, array<string, mixed>> $prizeRows
 * @var string $manageBase
 */

$status = (string)$tournament->get('status');
?>
<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Contest</h3>
            </div>
            <div class="card-body">
                <h4 style="font-size:1.1rem;margin-bottom:.3rem;"><?= h((string)$tournament->get('title')) ?></h4>
                <p class="tiny muted" style="margin-bottom:1rem;">
                    <?= h((string)$tournament->get('category')) ?> ·
                    <?= h((string)$tournament->get('time_control')) ?> ·
                    <span class="pill <?= $status === 'Live' ? 'is-live' : 'is-done' ?>"><?= h($status) ?></span>
                </p>

                <div class="slot-head">
                    <span><b><?= (int)$tournament->get('filled_slots') ?></b> of <?= (int)$tournament->get('max_slots') ?> seats</span>
                    <span><?= (int)$tournament->get('slots_remaining') ?> left</span>
                </div>
                <div class="slot-bar <?= $tournament->get('is_full') ? 'is-full' : '' ?>">
                    <span style="width:<?= (int)$tournament->get('slots_filled_percent') ?>%"></span>
                </div>

                <div class="d-flex justify-content-between mt-3" style="font-size:.9rem;">
                    <span class="muted">Entry fee</span>
                    <span class="mono">₹<?= number_format((float)$tournament->get('entry_fee')) ?></span>
                </div>
                <div class="d-flex justify-content-between mt-2" style="font-size:.9rem;">
                    <span class="muted">Collected</span>
                    <span class="mono">₹<?= number_format((float)$tournament->get('collected_fees'), 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mt-2" style="font-size:.9rem;">
                    <span class="muted">
                        Platform fee
                        <span class="tiny">(<?= h(number_format((float)$tournament->get('platform_fee'), 2)) ?>%)</span>
                    </span>
                    <span class="mono" style="color:var(--accent);">
                        ₹<?= number_format((float)$tournament->get('platform_cut'), 2) ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between mt-2" style="font-size:.9rem;">
                    <span class="muted">Prize pool</span>
                    <span class="mono">₹<?= number_format((float)$tournament->get('prize_pool'), 2) ?></span>
                </div>
                <?php if ($status === 'Completed') : ?>
                    <div class="d-flex justify-content-between mt-2" style="font-size:.9rem;">
                        <span class="muted">Revenue booked</span>
                        <span class="mono" style="color:var(--accent);">
                            ₹<?= number_format((float)$tournament->get('platform_revenue'), 2) ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="d-flex mt-3" style="gap:.5rem;">
                    <?php if ($status === 'Upcoming') : ?>
                        <?= $this->Form->postLink(
                            'Start & pair players',
                            $manageBase . '/start/' . $tournament->get('id'),
                            ['class' => 'btn-arena', 'confirm' => 'Start this contest and pair the players?'],
                        ) ?>
                    <?php endif; ?>
                    <?php if ($status === 'Live') : ?>
                        <?= $this->Form->postLink(
                            'Settle payouts',
                            $manageBase . '/settle/' . $tournament->get('id'),
                            ['class' => 'btn-arena', 'confirm' => 'Settle this contest and pay the prize money?'],
                        ) ?>
                    <?php endif; ?>
                    <a class="btn-arena is-muted"
                       href="<?= $this->Url->build(['controller' => 'Tournaments', 'action' => 'view', $tournament->get('id')]) ?>">
                        Public page
                    </a>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Payout ladder</h3>
            </div>
            <div class="card-body">
                <?php foreach ($prizeRows as $row) : ?>
                    <div class="prize-row mb-2">
                        <span class="rank"><i><?= (int)$row['rank'] ?></i> <?= h((string)$row['label']) ?></span>
                        <span class="amount">₹<?= number_format((float)$row['amount']) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($prizeRows === []) : ?>
                    <p class="muted tiny">No payout ladder configured.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Slots</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th>Player</th>
                            <th style="width:110px;">W / D / L</th>
                            <th style="width:100px;">Points</th>
                            <th style="width:110px;">Prize</th>
                            <th style="width:110px;"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($registrations === []) : ?>
                            <tr>
                                <td colspan="6" class="muted" style="padding:2rem;text-align:center;">
                                    No player has taken a seat yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($registrations as $index => $registration) : ?>
                            <tr>
                                <td class="mono tiny"><?= $index + 1 ?></td>
                                <td>
                                    <?= h((string)($registration->get('user')['username'] ?? 'Player')) ?>
                                    <span class="tiny muted d-block">
                                        rating <?= (int)($registration->get('user')['rating'] ?? 0) ?>
                                    </span>
                                </td>
                                <td class="mono tiny">
                                    <?= (int)$registration->get('wins') ?> / <?= (int)$registration->get('draws') ?> / <?= (int)$registration->get('losses') ?>
                                </td>
                                <td class="points"><?= number_format((float)$registration->get('score'), 1) ?></td>
                                <td class="mono tiny">
                                    <?= (float)$registration->get('prize_won') > 0 ? '₹' . number_format((float)$registration->get('prize_won')) : '—' ?>
                                </td>
                                <td class="text-right">
                                    <?php if ($status === 'Upcoming') : ?>
                                        <?= $this->Form->postLink(
                                            '<i class="fas fa-user-minus"></i>',
                                            $manageBase . '/remove-player/'
                                                . $tournament->get('id') . '/' . $registration->get('user_id'),
                                            [
                                                'class' => 'btn-arena is-muted',
                                                'confirm' => 'Remove this player from the contest?',
                                            ],
                                        ) ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Matches</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                        <tr>
                            <th style="width:70px;">Match</th>
                            <th>White</th>
                            <th>Black</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:130px;">Result</th>
                            <th style="width:110px;"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($matches === []) : ?>
                            <tr>
                                <td colspan="6" class="muted" style="padding:2rem;text-align:center;">
                                    No matches yet. Start the contest to pair the players.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($matches as $match) : ?>
                            <tr>
                                <td class="mono tiny">#<?= (int)$match->get('id') ?></td>
                                <td><?= h((string)($match->get('white_player')['username'] ?? '—')) ?></td>
                                <td><?= h((string)($match->get('black_player')['username'] ?? '—')) ?></td>
                                <td>
                                    <span class="pill <?= $match->get('status') === 'Live' ? 'is-live' : 'is-done' ?>">
                                        <?= h((string)$match->get('status')) ?>
                                    </span>
                                </td>
                                <td class="mono tiny"><?= h((string)($match->get('result') ?? '—')) ?></td>
                                <td class="text-right">
                                    <a class="btn-arena is-ghost"
                                       href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'simulate', $match->get('id')]) ?>">
                                        Simulate
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
