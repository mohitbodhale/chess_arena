<?php
/**
 * The control room dashboard.
 *
 * @var \App\View\AppView $this
 * @var int $playerCount
 * @var int $contestCount
 * @var int $liveMatches
 * @var float $prizePool
 * @var float $collected
 * @var float $platformRevenue
 * @var int $registrationCount
 * @var array<string, array<int, mixed>> $categoryChart
 * @var array<string, array<int, mixed>> $statusChart
 * @var iterable<\App\Model\Entity\WalletTransaction> $ledger
 * @var array<\App\Model\Entity\Tournament> $recentContests
 * @var array<\App\Model\Entity\ArenaMatch> $liveGames
 * @var array<\App\Model\Entity\ArenaMatch> $recentResults
 */

$this->assign('title', 'Dashboard');
?>
<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.6rem;">
            <div>
                <span class="eyebrow">Control room</span>
                <h1 style="font-size:1.5rem;margin:.35rem 0 0;">Arena overview</h1>
            </div>
            <div>
                <a class="btn-arena" href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'addTournament']) ?>">
                    <i class="fas fa-plus"></i> New contest
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box">
                <div class="inner">
                    <h3><?= (int)$playerCount ?></h3>
                    <p>Players</p>
                    <span class="icon"><i class="fas fa-users"></i></span>
                </div>
                <div class="footer">
                    <a href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'users']) ?>">Manage accounts</a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box">
                <div class="inner">
                    <h3><?= (int)$contestCount ?></h3>
                    <p>Contests</p>
                    <span class="icon"><i class="fas fa-sitemap"></i></span>
                </div>
                <div class="footer">
                    <a href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'tournaments']) ?>">Open the list</a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box">
                <div class="inner">
                    <h3><?= (int)$liveMatches ?></h3>
                    <p>Live boards</p>
                    <span class="icon"><i class="fas fa-chess-board"></i></span>
                </div>
                <div class="footer">
                    <a href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'simulate']) ?>">Match simulator</a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box">
                <div class="inner">
                    <h3>₹<?= number_format((float)$platformRevenue, 0) ?></h3>
                    <p>Platform revenue</p>
                    <span class="icon"><i class="fas fa-chart-line"></i></span>
                </div>
                <div class="footer">
                    ₹<?= number_format((float)$collected, 0) ?> collected · ₹<?= number_format((float)$prizePool, 0) ?> promised
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Contests by format</h3>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Contests by status</h3>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Wallet ledger</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th>Player</th>
                                <th>Type</th>
                                <th style="width:110px;">Amount</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($ledger === []) : ?>
                                <tr>
                                    <td colspan="3" class="muted" style="padding:1.6rem;text-align:center;">
                                        No wallet movement yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($ledger as $entry) : ?>
                                <tr>
                                    <td class="tiny">
                                        <?= h((string)($entry->get('user')['username'] ?? '—')) ?>
                                        <span class="d-block muted" style="font-size:.7rem;">
                                            <?= h((string)($entry->get('created_at')?->format('d M, H:i') ?? '')) ?>
                                        </span>
                                    </td>
                                    <td><span class="pill is-done"><?= h((string)$entry->get('type')) ?></span></td>
                                    <td class="mono tiny" style="color:<?= (float)$entry->get('amount') >= 0 ? 'var(--accent)' : 'var(--danger)' ?>;">
                                        <?= (float)$entry->get('amount') >= 0 ? '+' : '−' ?>
                                        ₹<?= number_format(abs((float)$entry->get('amount')), 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer" style="background:transparent;border-top:1px solid var(--line);">
                    <a class="btn-arena is-ghost" href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'wallet']) ?>">
                        Open the auditor
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Live boards</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th>Contest</th>
                                <th>White</th>
                                <th>Black</th>
                                <th style="width:90px;">Moves</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($liveGames === []) : ?>
                                <tr>
                                    <td colspan="4" class="muted" style="padding:1.6rem;text-align:center;">
                                        No game is running right now.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($liveGames as $game) : ?>
                                <tr>
                                    <td class="tiny"><?= h((string)($game->get('tournament')['title'] ?? '—')) ?></td>
                                    <td class="tiny"><?= h((string)($game->get('white_player')['username'] ?? '—')) ?></td>
                                    <td class="tiny"><?= h((string)($game->get('black_player')['username'] ?? '—')) ?></td>
                                    <td class="mono tiny"><?= (int)$game->get('move_count') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Latest results</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th>Contest</th>
                                <th>Result</th>
                                <th style="width:120px;">Moves</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($recentResults === []) : ?>
                                <tr>
                                    <td colspan="3" class="muted" style="padding:1.6rem;text-align:center;">
                                        No game has been finished yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($recentResults as $result) : ?>
                                <tr>
                                    <td class="tiny"><?= h((string)($result->get('tournament')['title'] ?? '—')) ?></td>
                                    <td class="tiny mono"><?= h((string)($result->get('result') ?? '—')) ?></td>
                                    <td class="mono tiny"><?= (int)$result->get('move_count') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Newest contests</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Organizer</th>
                        <th style="width:120px;">Status</th>
                        <th style="width:120px;">Seats</th>
                        <th style="width:130px;">Prize pool</th>
                        <th style="width:120px;">Revenue</th>
                        <th style="width:170px;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($recentContests === []) : ?>
                        <tr>
                            <td colspan="7" class="muted" style="padding:1.6rem;text-align:center;">
                                No contest has been created yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($recentContests as $contest) : ?>
                        <tr>
                            <td><?= h((string)$contest->get('title')) ?></td>
                            <td class="tiny muted"><?= h((string)($contest->get('organizer')['username'] ?? '—')) ?></td>
                            <td>
                                <span class="pill <?= $contest->get('status') === 'Live' ? 'is-live' : 'is-done' ?>">
                                    <?= h((string)$contest->get('status')) ?>
                                </span>
                            </td>
                            <td class="mono tiny">
                                <?= (int)$contest->get('filled_slots') ?>/<?= (int)$contest->get('max_slots') ?>
                            </td>
                            <td class="mono tiny">₹<?= number_format((float)$contest->get('prize_pool'), 0) ?></td>
                            <td class="mono tiny" style="color:var(--accent);">
                                ₹<?= number_format((float)$contest->get('platform_revenue'), 0) ?>
                            </td>
                            <td class="text-right">
                                <a class="btn-arena is-ghost"
                                   href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'slots', $contest->get('id')]) ?>">
                                    Manage
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

<?php $this->start('script'); ?>
<?= $this->Html->script('plugins/chart.min.js') ?>
<script>
(function () {
    var grid = 'rgba(255,255,255,0.06)';
    var ticks = {color: '#9AA4AE', font: {family: 'Inter, sans-serif', size: 11}};
    var common = {
        responsive: true,
        maintainAspectRatio: false,
        legend: {labels: {color: '#9AA4AE', boxWidth: 12, font: {size: 11}}},
        scales: {
            xAxes: [{gridLines: {color: grid, zeroLineColor: grid}, ticks: ticks}],
            yAxes: [{
                gridLines: {color: grid, zeroLineColor: grid},
                ticks: {color: '#9AA4AE', precision: 0, beginAtZero: true},
            }],
        },
    };

    var categoryCanvas = document.getElementById('categoryChart');
    if (categoryCanvas && window.Chart) {
        new window.Chart(categoryCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($categoryChart['labels']) ?>,
                datasets: [{
                    data: <?= json_encode($categoryChart['data']) ?>,
                    backgroundColor: ['#00D09C', '#5B8DEF', '#FFB020', '#A78BFA'],
                    borderColor: '#171A1E',
                    borderWidth: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {position: 'bottom', labels: {color: '#9AA4AE', boxWidth: 12, font: {size: 11}}},
            },
        });
    }

    var statusCanvas = document.getElementById('statusChart');
    if (statusCanvas && window.Chart) {
        new window.Chart(statusCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($statusChart['labels']) ?>,
                datasets: [{
                    label: 'Contests',
                    data: <?= json_encode($statusChart['data']) ?>,
                    backgroundColor: ['rgba(255,176,32,0.55)', 'rgba(255,92,122,0.6)', 'rgba(0,208,156,0.55)'],
                    borderRadius: 6,
                    barThickness: 38,
                }],
            },
            options: common,
        });
    }
})();
</script>
<?php $this->end(); ?>
