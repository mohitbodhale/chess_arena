<?php
/**
 * The detail page of a single contest.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Tournament $tournament
 * @var array<\App\Model\Entity\TournamentRegistration> $registrations
 * @var array<int, \App\Model\Entity\TournamentRegistration> $standings
 * @var array<int, array<string, mixed>> $prizeRows
 * @var bool $isRegistered
 * @var bool $canJoin
 * @var bool $canManage
 */

$this->assign('title', (string)$tournament->get('title'));
$status = (string)$tournament->get('status');
$percent = (int)$tournament->get('slots_filled_percent');
?>
<div class="lobby-hero">
    <div class="shell">
        <div class="d-flex flex-wrap justify-content-between align-items-start">
            <div>
                <span class="eyebrow"><?= h((string)$tournament->get('category')) ?> · <?= h((string)$tournament->get('time_control')) ?></span>
                <h1><?= h((string)$tournament->get('title')) ?></h1>
                <p>
                    Hosted by <strong><?= h((string)($tournament->get('organizer')['username'] ?? 'Chess Arena')) ?></strong>
                    · starts <span data-countdown="<?= h((string)$tournament->get('start_time')) ?>">—</span>
                </p>
            </div>
            <div class="d-flex align-items-center mt-3 mt-md-0">
                <span class="pill <?= $status === 'Live' ? 'is-live' : ($status === 'Completed' ? 'is-done' : 'is-open') ?> mr-2">
                    <?php if ($status === 'Live') : ?>
                        <span class="dot-pulse"></span>Live
                    <?php else : ?>
                        <?= h($status) ?>
                    <?php endif; ?>
                </span>
                <a class="btn-arena is-muted" href="<?= $this->Url->build('/lobby') ?>">
                    <i class="fas fa-arrow-left"></i> Back to lobby
                </a>
            </div>
        </div>
    </div>
</div>

<div class="shell">
    <div class="section" style="padding-top:1.8rem;">
        <div style="display:grid;grid-template-columns:1.4fr .9fr;gap:1.5rem;align-items:start;">

            <div>
                <div class="surface surface-pad">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <span class="section-label">Prize pool</span>
                            <div style="font-family:var(--display);font-size:2.4rem;line-height:1;color:var(--accent);">
                                ₹<?= number_format((float)$tournament->get('prize_pool')) ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="section-label">Entry fee</span>
                            <div style="font-family:var(--mono);font-size:1.4rem;color:var(--text);">
                                ₹<?= number_format((float)$tournament->get('entry_fee')) ?>
                            </div>
                        </div>
                    </div>

                    <div class="slot-head">
                        <span><b><?= (int)$tournament->get('filled_slots') ?></b> of <?= (int)$tournament->get('max_slots') ?> seats filled</span>
                        <span><?= (int)$tournament->get('slots_remaining') ?> left</span>
                    </div>
                    <div class="slot-bar <?= $tournament->get('is_full') ? 'is-full' : '' ?>">
                        <span style="width:<?= $percent ?>%"></span>
                    </div>

                    <div class="d-flex flex-wrap align-items-center mt-4" style="gap:.7rem;">
                        <?php if ($isRegistered) : ?>
                            <span class="pill is-open">You are registered</span>
                            <?php if ($myMatch !== null && $myMatch->get('status') === 'Live') : ?>
                                <a class="btn-arena"
                                   href="<?= $this->Url->build(['controller' => 'Matches', 'action' => 'play', $myMatch->get('id')]) ?>">
                                    <i class="fas fa-circle-play"></i> Play now
                                </a>
                            <?php elseif ($myMatch !== null) : ?>
                                <a class="btn-arena is-ghost"
                                   href="<?= $this->Url->build(['controller' => 'Matches', 'action' => 'play', $myMatch->get('id')]) ?>">
                                    Your board
                                </a>
                            <?php endif; ?>
                            <?php if ($status === 'Upcoming') : ?>
                                <?= $this->Form->postLink(
                                    'Leave contest',
                                    ['controller' => 'Tournaments', 'action' => 'leave', $tournament->get('id')],
                                    ['class' => 'btn-arena is-muted', 'confirm' => 'Leave this contest and get a refund?'],
                                ) ?>
                            <?php endif; ?>
                        <?php elseif ($canJoin) : ?>
                            <?= $this->Form->postLink(
                                'Join for ₹' . number_format((float)$tournament->get('entry_fee')),
                                ['controller' => 'Tournaments', 'action' => 'join', $tournament->get('id')],
                                ['class' => 'btn-arena'],
                            ) ?>
                        <?php else : ?>
                            <span class="pill is-done">
                                <?= $tournament->get('is_full') ? 'All seats sold' : 'Entries closed' ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($canManage) : ?>
                            <a class="btn-arena is-ghost" href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'slots', $tournament->get('id')]) ?>">
                                Manage slots
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="surface surface-pad mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 style="margin:0;font-size:1.15rem;">Standings</h3>
                        <a class="tiny" href="<?= $this->Url->build(['controller' => 'Leaderboard', 'action' => 'tournament', $tournament->get('id')]) ?>">
                            Full leaderboard
                        </a>
                    </div>

                    <?php if ($standings === []) : ?>
                        <p class="muted">No players have joined this contest yet.</p>
                    <?php else : ?>
                        <div style="overflow-x:auto;">
                            <table class="table" style="margin:0;color:var(--text);">
                                <thead>
                                <tr>
                                    <th style="width:56px;">Rank</th>
                                    <th>Player</th>
                                    <th style="width:90px;">W / D / L</th>
                                    <th style="width:90px;">Bonus</th>
                                    <th style="width:90px;">Points</th>
                                    <th style="width:110px;">Prize</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($standings as $rank => $registration) : ?>
                                    <tr>
                                        <td>
                                            <span class="rank-cell <?= $rank <= 3 ? 'is-' . $rank : '' ?>"><?= $rank ?></span>
                                        </td>
                                        <td><?= h((string)($registration->get('user')['username'] ?? 'Player')) ?></td>
                                        <td class="mono tiny">
                                            <?= (int)$registration->get('wins') ?> / <?= (int)$registration->get('draws') ?> / <?= (int)$registration->get('losses') ?>
                                        </td>
                                        <td class="mono tiny"><?= number_format((float)$registration->get('time_bonus'), 1) ?></td>
                                        <td class="points"><?= number_format((float)$registration->get('score'), 1) ?></td>
                                        <td class="mono tiny">
                                            <?= (float)$registration->get('prize_won') > 0 ? '₹' . number_format((float)$registration->get('prize_won')) : '—' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="surface surface-pad">
                    <h3 style="margin:0 0 .9rem;font-size:1.05rem;">Prize breakdown</h3>
                    <?php if ($prizeRows === []) : ?>
                        <p class="muted tiny">The organizer has not published a payout ladder yet.</p>
                    <?php else : ?>
                        <?php foreach ($prizeRows as $row) : ?>
                            <div class="prize-row mb-2">
                                <span class="rank"><i><?= (int)$row['rank'] ?></i> <?= h((string)$row['label']) ?></span>
                                <span class="amount">₹<?= number_format((float)$row['amount']) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <hr style="margin:.9rem 0;">
                        <div class="prize-row">
                            <span class="rank">Total distributed</span>
                            <span class="amount">
                                ₹<?= number_format(array_sum(array_column($prizeRows, 'amount'))) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="surface surface-pad mt-3">
                    <h3 style="margin:0 0 .9rem;font-size:1.05rem;">Rules</h3>
                    <p class="muted tiny" style="white-space:pre-line;">
                        <?= h((string)($tournament->get('rules') ?: 'Standard arena rules. Win = 3 points, draw = 1 point, loss = 0 points, plus up to 2 time bonus points for every game.')) ?>
                    </p>
                </div>

                <div class="surface surface-pad mt-3">
                    <h3 style="margin:0 0 .9rem;font-size:1.05rem;">Registered players</h3>
                    <?php if ($registrations === []) : ?>
                        <p class="muted tiny">Nobody has entered yet.</p>
                    <?php else : ?>
                        <?php foreach ($registrations as $registration) : ?>
                            <div class="d-flex align-items-center mb-2">
                                <span class="avatar" style="width:30px;height:30px;border-radius:8px;display:grid;place-items:center;background:var(--bg-elev-2);border:1px solid var(--line);font-size:.72rem;">
                                    <?= h(strtoupper(substr((string)($registration->get('user')['username'] ?? 'P'), 0, 2))) ?>
                                </span>
                                <span class="ml-2" style="font-size:.88rem;">
                                    <?= h((string)($registration->get('user')['username'] ?? 'Player')) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
