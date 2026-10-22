<?php
/**
 * The global leaderboard.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\TournamentRegistration> $entries
 */

$this->assign('title', 'Leaderboard');
?>
<div class="lobby-hero">
    <div class="shell">
        <span class="eyebrow">Live leaderboard</span>
        <h1>The arena standings.</h1>
        <p>Points first, then time bonus, then wins. Exactly how the prize money is split.</p>
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
                        <th>Contest</th>
                        <th style="width:110px;">W / D / L</th>
                        <th style="width:100px;">Bonus</th>
                        <th style="width:100px;">Points</th>
                        <th style="width:120px;">Prize</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($entries) === 0) : ?>
                        <tr>
                            <td colspan="7" class="muted" style="padding:2rem;text-align:center;">
                                No games have been scored yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php $position = 0; ?>
                    <?php foreach ($entries as $entry) : ?>
                        <?php $position++; ?>
                        <tr>
                            <td><span class="rank-cell <?= $position <= 3 ? 'is-' . $position : '' ?>"><?= $position ?></span></td>
                            <td><?= h((string)($entry->get('user')['username'] ?? 'Player')) ?></td>
                            <td class="tiny muted"><?= h((string)($entry->get('tournament')['title'] ?? '')) ?></td>
                            <td class="mono tiny">
                                <?= (int)$entry->get('wins') ?> / <?= (int)$entry->get('draws') ?> / <?= (int)$entry->get('losses') ?>
                            </td>
                            <td class="mono tiny"><?= number_format((float)$entry->get('time_bonus'), 1) ?></td>
                            <td class="points"><?= number_format((float)$entry->get('score'), 1) ?></td>
                            <td class="mono tiny">
                                <?= (float)$entry->get('prize_won') > 0 ? '₹' . number_format((float)$entry->get('prize_won')) : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?= $this->element('pagination') ?>
    </div>
</div>
