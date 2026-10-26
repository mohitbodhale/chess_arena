<?php
/**
 * The match simulation tool.
 *
 * @var \App\View\AppView $this
 * @var array<\App\Model\Entity\ArenaMatch> $matches
 * @var \App\Model\Entity\ArenaMatch|null $match
 */

$this->assign('title', 'Match simulator');
?>
<div class="content-header">
    <div class="container-fluid">
        <span class="eyebrow">Administration</span>
        <h1 style="font-size:1.5rem;margin:.35rem 0 0;">Match simulator</h1>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <?php if ($matches !== []) : ?>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent matches</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>Players</th>
                                <th style="width:100px;">Status</th>
                                <th style="width:80px;"></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($matches as $item) : ?>
                                <tr>
                                    <td class="mono tiny"><?= (int)$item->get('id') ?></td>
                                    <td class="tiny">
                                        <?= h((string)($item->get('white_player')['username'] ?? '—')) ?>
                                        <span class="muted">vs</span>
                                        <?= h((string)($item->get('black_player')['username'] ?? '—')) ?>
                                        <span class="d-block muted" style="font-size:.7rem;">
                                            <?= h((string)($item->get('tournament')['title'] ?? '')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="pill <?= $item->get('status') === 'Live' ? 'is-live' : 'is-done' ?>">
                                            <?= h((string)$item->get('status')) ?>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <a class="btn-arena is-ghost"
                                           href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'simulate', $item->get('id')]) ?>">
                                            Open
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
        <?php endif; ?>

        <div class="col-lg-<?= $matches === [] ? 12 : 7 ?>">
            <?php if ($match === null) : ?>
                <div class="empty-state">
                    <h3><?= $matches === [] ? 'No matches available' : 'Pick a match' ?></h3>
                    <p class="tiny"><?= $matches === [] ? 'Start a contest to generate matches, then pick one to rehearse its outcome.' : 'Choose a match on the left to rehearse its outcome.' ?></p>
                </div>
            <?php else : ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Match #<?= (int)$match->get('id') ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <span class="section-label">White</span>
                                <div style="font-weight:600;"><?= h((string)($match->get('white_player')['username'] ?? '—')) ?></div>
                            </div>
                            <div class="col-6 text-right">
                                <span class="section-label">Black</span>
                                <div style="font-weight:600;"><?= h((string)($match->get('black_player')['username'] ?? '—')) ?></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between" style="font-size:.9rem;">
                            <span class="muted">Contest</span>
                            <span class="tiny"><?= h((string)($match->get('tournament')['title'] ?? '—')) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mt-2" style="font-size:.9rem;">
                            <span class="muted">Status</span>
                            <span class="pill <?= $match->get('status') === 'Live' ? 'is-live' : 'is-done' ?>">
                                <?= h((string)$match->get('status')) ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mt-2" style="font-size:.9rem;">
                            <span class="muted">Result</span>
                            <span class="mono tiny"><?= h((string)($match->get('result') ?? '—')) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mt-2" style="font-size:.9rem;">
                            <span class="muted">Moves</span>
                            <span class="mono tiny"><?= (int)$match->get('move_count') ?></span>
                        </div>

                        <hr>

                        <?php if ($match->get('status') === 'Completed') : ?>
                            <p class="tiny muted" style="margin-bottom:0;">
                                This match is already finished, so it cannot be simulated again.
                            </p>
                        <?php else : ?>
                            <?= $this->Form->create(null, [
                                'url' => ['controller' => 'Admin', 'action' => 'runSimulation', $match->get('id')],
                            ]) ?>
                            <div class="field">
                                <label for="simulate-result">Force the outcome</label>
                                <?= $this->Form->select('result', [
                                    '' => 'Play the game out',
                                    'White_Win' => 'White wins',
                                    'Black_Win' => 'Black wins',
                                    'Draw' => 'Draw',
                                ], ['class' => 'form-control']) ?>
                                <div class="help">
                                    Without a forced outcome the engine plays random legal moves until
                                    the game ends.
                                </div>
                            </div>
                            <?= $this->Form->end() ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
