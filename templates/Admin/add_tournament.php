<?php
/**
 * The tournament creator.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Tournament $tournament
 * @var array<string> $templates
 * @var array<\App\Model\Entity\User> $organizers
 */

$this->assign('title', 'New contest');
?>
<div class="content-header">
    <div class="container-fluid">
        <span class="eyebrow">Administration</span>
        <h1 style="font-size:1.5rem;margin:.35rem 0 0;">Create a contest</h1>
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
                'formAction' => ['controller' => 'Admin', 'action' => 'addTournament'],
            ]) ?>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">How the arena scores</h3>
                </div>
                <div class="card-body">
                    <table class="scoring-table">
                        <tbody>
                        <tr>
                            <td>Win</td>
                            <td><strong>+3</strong></td>
                        </tr>
                        <tr>
                            <td>Draw</td>
                            <td><strong>+1</strong></td>
                        </tr>
                        <tr>
                            <td>Loss</td>
                            <td><strong>0</strong></td>
                        </tr>
                        <tr>
                            <td>Time bonus</td>
                            <td><strong>up to +2</strong></td>
                        </tr>
                        </tbody>
                    </table>
                    <hr>
                    <p class="tiny muted" style="margin-bottom:0;">
                        The prize pool is 80% of the collected entry fees unless you type an
                        explicit amount. The ladder decides how the pool is split between the
                        top players.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
