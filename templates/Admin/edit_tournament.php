<?php
/**
 * The configuration editor of a contest.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Tournament $tournament
 * @var array<string> $templates
 * @var array<\App\Model\Entity\User> $organizers
 */

$this->assign('title', 'Edit contest');
?>
<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.6rem;">
            <div>
                <span class="eyebrow">Administration</span>
                <h1 style="font-size:1.5rem;margin:.35rem 0 0;"><?= h((string)$tournament->get('title')) ?></h1>
            </div>
            <a class="btn-arena is-muted" href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'slots', $tournament->get('id')]) ?>">
                <i class="fa fa-users"></i> Slot manager
            </a>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <?= $this->element('admin/contest_form', [
                'tournament' => $tournament,
                'templates' => $templates,
                'organizers' => $organizers,
                'showPlatformFee' => true,
                'formAction' => ['controller' => 'Admin', 'action' => 'editTournament', $tournament->get('id')],
            ]) ?>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">State</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between" style="font-size:.9rem;">
                        <span class="muted">Status</span>
                        <span class="pill <?= $tournament->get('status') === 'Live' ? 'is-live' : 'is-done' ?>">
                            <?= h((string)$tournament->get('status')) ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mt-2" style="font-size:.9rem;">
                        <span class="muted">Seats filled</span>
                        <span class="mono">
                            <?= (int)$tournament->get('filled_slots') ?>/<?= (int)$tournament->get('max_slots') ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mt-2" style="font-size:.9rem;">
                        <span class="muted">Prize pool</span>
                        <span class="mono">₹<?= number_format((float)$tournament->get('prize_pool')) ?></span>
                    </div>
                    <hr>
                    <a class="btn-arena is-muted" href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'tournaments']) ?>">
                        <i class="fa fa-arrow-left"></i> Back to the contests
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
