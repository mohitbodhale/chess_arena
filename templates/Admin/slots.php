<?php
/**
 * The slot manager of a contest.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Tournament $tournament
 * @var array<\App\Model\Entity\TournamentRegistration> $registrations
 * @var array<\App\Model\Entity\ArenaMatch> $matches
 * @var array<int, array<string, mixed>> $prizeRows
 */

$this->assign('title', 'Slots');
?>
<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.6rem;">
            <div>
                <span class="eyebrow">Administration</span>
                <h1 style="font-size:1.5rem;margin:.35rem 0 0;"><?= h((string)$tournament->get('title')) ?></h1>
            </div>
            <a class="btn-arena is-muted" href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'editTournament', $tournament->get('id')]) ?>">
                <i class="fas fa-sliders-h"></i> Configuration
            </a>
        </div>
    </div>
</div>

<div class="container-fluid">
    <?= $this->element('admin/slots_panel', [
        'tournament' => $tournament,
        'registrations' => $registrations,
        'matches' => $matches,
        'prizeRows' => $prizeRows,
        'manageBase' => '/admin',
    ]) ?>
</div>
