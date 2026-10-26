<?php
/**
 * The contests of an organizer.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedInterface $contests
 */

$this->assign('title', 'My contests');
?>
<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.6rem;">
            <div>
                <span class="eyebrow">Organizer</span>
                <h1 style="font-size:1.5rem;margin:.35rem 0 0;">My contests</h1>
            </div>
            <a class="btn-arena" href="<?= $this->Url->build(['controller' => 'Organizer', 'action' => 'addTournament']) ?>">
                <i class="fa fa-plus"></i> New contest
            </a>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th style="width:110px;">Format</th>
                        <th style="width:110px;">Status</th>
                        <th style="width:120px;">Seats</th>
                        <th style="width:130px;">Prize pool</th>
                        <th style="width:130px;">Starts</th>
                        <th style="width:190px;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($contests) === 0) : ?>
                        <tr>
                            <td colspan="7" class="muted" style="padding:2rem;text-align:center;">
                                You have not created a contest yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($contests as $contest) : ?>
                        <tr>
                            <td><?= h((string)$contest->get('title')) ?></td>
                            <td><span class="pill is-<?= h(strtolower((string)$contest->get('category'))) ?>"><?= h((string)$contest->get('category')) ?></span></td>
                            <td>
                                <span class="pill <?= $contest->get('status') === 'Live' ? 'is-live' : 'is-done' ?>">
                                    <?= h((string)$contest->get('status')) ?>
                                </span>
                            </td>
                            <td class="mono tiny"><?= (int)$contest->get('filled_slots') ?>/<?= (int)$contest->get('max_slots') ?></td>
                            <td class="mono tiny">₹<?= number_format((float)$contest->get('prize_pool'), 0) ?></td>
                            <td class="tiny muted"><?= h((string)($contest->get('start_time')?->format('d M, H:i') ?? '—')) ?></td>
                            <td class="text-right">
                                <a class="btn-arena is-ghost"
                                   href="<?= $this->Url->build(['controller' => 'Organizer', 'action' => 'slots', $contest->get('id')]) ?>">
                                    Slots
                                </a>
                                <a class="btn-arena is-ghost ml-1"
                                   href="<?= $this->Url->build(['controller' => 'Organizer', 'action' => 'editTournament', $contest->get('id')]) ?>">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (count($contests) > 0) : ?>
            <div class="card-footer" style="background:transparent;border-top:1px solid var(--line);">
                <?= $this->element('pagination') ?>
            </div>
        <?php endif; ?>
    </div>
</div>
