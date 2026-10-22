<?php
/**
 * The standings of a single contest.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Tournament $tournament
 * @var array<\App\Model\Entity\TournamentRegistration> $standings
 */

$this->assign('title', 'Standings');
?>
<div class="lobby-hero">
    <div class="shell">
        <span class="eyebrow"><?= h((string)$tournament->get('category')) ?> · <?= h((string)$tournament->get('time_control')) ?></span>
        <h1><?= h((string)$tournament->get('title')) ?></h1>
        <p>
            Prize pool <strong>₹<?= number_format((float)$tournament->get('prize_pool')) ?></strong>
            · <?= (int)$tournament->get('filled_slots') ?> players
        </p>
    </div>
</div>

<div class="shell">
    <div class="section" style="padding-top:1.8rem;">
        <div class="surface">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                    <tr>
                        <th style="width:70px;">Rank</th>
                        <th>Player</th>
                        <th style="width:110px;">W / D / L</th>
                        <th style="width:100px;">Bonus</th>
                        <th style="width:100px;">Points</th>
                        <th style="width:120px;">Prize</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($standings === []) : ?>
                        <tr>
                            <td colspan="6" class="muted" style="padding:2rem;text-align:center;">
                                No players have joined this contest yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php $position = 0; ?>
                    <?php foreach ($standings as $registration) : ?>
                        <?php $position++; ?>
                        <tr>
                            <td><span class="rank-cell <?= $position <= 3 ? 'is-' . $position : '' ?>"><?= $position ?></span></td>
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
        </div>

        <a class="btn-arena is-muted mt-3" href="<?= $this->Url->build(['controller' => 'Tournaments', 'action' => 'view', $tournament->get('id')]) ?>">
            <i class="fas fa-arrow-left"></i> Back to the contest
        </a>
    </div>
</div>
