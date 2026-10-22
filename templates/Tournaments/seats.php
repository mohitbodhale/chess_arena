<?php
/**
 * The contest that is waiting for a player.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Tournament $tournament
 * @var array<\App\Model\Entity\TournamentRegistration> $registrations
 * @var array<int, array<string, mixed>> $prizeRows
 */

$this->assign('title', 'Seats');
?>
<div class="lobby-hero">
    <div class="shell">
        <span class="eyebrow">
            <?= h((string)$tournament->get('category')) ?> · <?= h((string)$tournament->get('time_control')) ?>
        </span>
        <h1><?= h((string)$tournament->get('title')) ?></h1>
        <p>
            <strong><?= (int)$tournament->get('filled_slots') ?></strong> of
            <?= (int)$tournament->get('max_slots') ?> seats taken ·
            prize pool <strong>₹<?= number_format((float)$tournament->get('prize_pool')) ?></strong>
        </p>
    </div>
</div>

<div class="shell">
    <div class="section" style="padding-top:1.8rem;">
        <div class="row">
            <div class="col-lg-8">
                <div class="surface">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>Player</th>
                                <th style="width:120px;">Rating</th>
                                <th style="width:110px;">W / D / L</th>
                                <th style="width:100px;">Points</th>
                                <th style="width:120px;">Prize</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($registrations === []) : ?>
                                <tr>
                                    <td colspan="6" class="muted" style="padding:2rem;text-align:center;">
                                        The first player has not shown up yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($registrations as $index => $registration) : ?>
                                <tr>
                                    <td class="mono tiny"><?= $index + 1 ?></td>
                                    <td><?= h((string)($registration->get('user')['username'] ?? 'Player')) ?></td>
                                    <td class="mono tiny"><?= (int)($registration->get('user')['rating'] ?? 0) ?></td>
                                    <td class="mono tiny">
                                        <?= (int)$registration->get('wins') ?> / <?= (int)$registration->get('draws') ?> / <?= (int)$registration->get('losses') ?>
                                    </td>
                                    <td class="points"><?= number_format((float)$registration->get('score'), 1) ?></td>
                                    <td class="mono tiny">
                                        <?= (float)$registration->get('prize_won') > 0 ? '₹' . number_format((float)$registration->get('prize_won')) : '—' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="surface surface-pad mb-3">
                    <span class="section-label">Payout ladder</span>
                    <div class="prize-rows" style="padding:0;margin-top:.7rem;">
                        <?php foreach ($prizeRows as $row) : ?>
                            <div class="prize-row">
                                <span class="rank"><i><?= (int)$row['rank'] ?></i> <?= h((string)$row['label']) ?></span>
                                <span class="amount">₹<?= number_format((float)$row['amount']) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($prizeRows === []) : ?>
                            <p class="muted tiny" style="margin-bottom:0;">No ladder configured yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="surface surface-pad">
                    <?php if (!empty($tournament->get('rules'))) : ?>
                        <span class="section-label">Rules</span>
                        <p class="tiny muted" style="margin:.6rem 0 0;white-space:pre-line;">
                            <?= h((string)$tournament->get('rules')) ?>
                        </p>
                    <?php endif; ?>
                    <a class="btn-arena is-muted mt-3" style="width:100%;"
                       href="<?= $this->Url->build(['controller' => 'Tournaments', 'action' => 'view', $tournament->get('id')]) ?>">
                        <i class="fas fa-arrow-left"></i> Back to the contest
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
