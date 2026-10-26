<?php
/**
 * The workspace of an organizer.
 *
 * @var \App\View\AppView $this
 * @var array<\App\Model\Entity\Tournament> $contests
 * @var int $liveCount
 * @var int $seats
 * @var float $pool
 * @var array<\App\Model\Entity\ArenaMatch> $matches
 */

$this->assign('title', 'Organizer dashboard');
?>
<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.6rem;">
            <div>
                <span class="eyebrow">Organizer</span>
                <h1 style="font-size:1.5rem;margin:.35rem 0 0;">Your workspace</h1>
            </div>
            <a class="btn-arena" href="<?= $this->Url->build(['controller' => 'Organizer', 'action' => 'addTournament']) ?>">
                <i class="fa fa-plus"></i> New contest
            </a>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box">
                <div class="inner">
                    <h3><?= count($contests) ?></h3>
                    <p>Your contests</p>
                    <span class="icon"><i class="fa fa-sitemap"></i></span>
                </div>
                <div class="footer">
                    <a href="<?= $this->Url->build(['controller' => 'Organizer', 'action' => 'tournaments']) ?>">Open the list</a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box">
                <div class="inner">
                    <h3><?= (int)$liveCount ?></h3>
                    <p>Live right now</p>
                    <span class="icon"><i class="fa fa-bolt"></i></span>
                </div>
                <div class="footer">
                    Seats sold <?= (int)$seats ?>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box">
                <div class="inner">
                    <h3><?= count($matches) ?></h3>
                    <p>Recent matches</p>
                    <span class="icon"><i class="fa fa-chess-board"></i></span>
                </div>
                <div class="footer">
                    Across every contest
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box">
                <div class="inner">
                    <h3>₹<?= number_format((float)$pool, 0) ?></h3>
                    <p>Prize money</p>
                    <span class="icon"><i class="fa fa-trophy"></i></span>
                </div>
                <div class="footer">
                    Your payout responsibility
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Your contests</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th>Title</th>
                                <th style="width:110px;">Status</th>
                                <th style="width:110px;">Seats</th>
                                <th style="width:130px;">Prize pool</th>
                                <th style="width:110px;"></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($contests === []) : ?>
                                <tr>
                                    <td colspan="5" class="muted" style="padding:2rem;text-align:center;">
                                        You have not created a contest yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($contests as $contest) : ?>
                                <tr>
                                    <td><?= h((string)$contest->get('title')) ?></td>
                                    <td>
                                        <span class="pill <?= $contest->get('status') === 'Live' ? 'is-live' : 'is-done' ?>">
                                            <?= h((string)$contest->get('status')) ?>
                                        </span>
                                    </td>
                                    <td class="mono tiny">
                                        <?= (int)$contest->get('filled_slots') ?>/<?= (int)$contest->get('max_slots') ?>
                                    </td>
                                    <td class="mono tiny">₹<?= number_format((float)$contest->get('prize_pool'), 0) ?></td>
                                    <td class="text-right">
                                        <a class="btn-arena is-ghost"
                                           href="<?= $this->Url->build(['controller' => 'Organizer', 'action' => 'slots', $contest->get('id')]) ?>">
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

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Latest matches</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th>Contest</th>
                                <th>Players</th>
                                <th style="width:120px;">Result</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($matches === []) : ?>
                                <tr>
                                    <td colspan="3" class="muted" style="padding:2rem;text-align:center;">
                                        No match yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($matches as $match) : ?>
                                <tr>
                                    <td class="tiny muted"><?= h((string)($match->get('tournament')['title'] ?? '—')) ?></td>
                                    <td class="tiny">
                                        <?= h((string)($match->get('white_player')['username'] ?? '—')) ?>
                                        <span class="muted">vs</span>
                                        <?= h((string)($match->get('black_player')['username'] ?? '—')) ?>
                                    </td>
                                    <td class="mono tiny"><?= h((string)($match->get('result') ?? '—')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
