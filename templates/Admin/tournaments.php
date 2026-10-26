<?php
/**
 * Every contest of the arena.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedInterface $contests
 * @var string $category
 * @var string $status
 * @var array<int, string> $categories
 */

use App\Service\Tournament\TimeControl;

$this->assign('title', 'Contests');

$statusOptions = ['' => 'Every status', 'Upcoming' => 'Upcoming', 'Live' => 'Live', 'Completed' => 'Completed'];
?>
<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.6rem;">
            <div>
                <span class="eyebrow">Administration</span>
                <h1 style="font-size:1.5rem;margin:.35rem 0 0;">All contests</h1>
            </div>
            <a class="btn-arena" href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'addTournament']) ?>">
                <i class="fa fa-plus"></i> New contest
            </a>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row align-items-end" style="gap:.6rem;">
                <div class="col-md-4 col-6">
                    <div class="field" style="margin-bottom:0;">
                        <label for="filter-category">Format</label>
                        <select class="form-control" id="filter-category" name="category">
                            <option value="">Every format</option>
                            <?php foreach (TimeControl::CATEGORIES as $key => $meta) : ?>
                                <option value="<?= h($key) ?>" <?= $category === $key ? 'selected' : '' ?>>
                                    <?= h($meta['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="field" style="margin-bottom:0;">
                        <label for="filter-status">Status</label>
                        <select class="form-control" id="filter-status" name="status">
                            <?php foreach ($statusOptions as $value => $label) : ?>
                                <option value="<?= h($value) ?>" <?= $status === $value ? 'selected' : '' ?>>
                                    <?= h($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn-arena" style="width:100%;">
                        <i class="fa fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Organizer</th>
                        <th style="width:110px;">Format</th>
                        <th style="width:110px;">Status</th>
                        <th style="width:120px;">Seats</th>
                        <th style="width:130px;">Prize pool</th>
                        <th style="width:110px;">Starts</th>
                        <th style="width:190px;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($contests) === 0) : ?>
                        <tr>
                            <td colspan="8" class="muted" style="padding:2rem;text-align:center;">
                                No contest matches this filter.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($contests as $contest) : ?>
                        <tr>
                            <td>
                                <?= h((string)$contest->get('title')) ?>
                                <span class="d-block tiny muted">
                                    ₹<?= number_format((float)$contest->get('entry_fee')) ?> per seat
                                </span>
                            </td>
                            <td class="tiny muted"><?= h((string)($contest->get('organizer')['username'] ?? '—')) ?></td>
                            <td><span class="pill is-<?= h(strtolower((string)$contest->get('category'))) ?>"><?= h((string)$contest->get('category')) ?></span></td>
                            <td>
                                <span class="pill <?= $contest->get('status') === 'Live' ? 'is-live' : 'is-done' ?>">
                                    <?= h((string)$contest->get('status')) ?>
                                </span>
                            </td>
                            <td class="mono tiny"><?= (int)$contest->get('filled_slots') ?>/<?= (int)$contest->get('max_slots') ?></td>
                            <td class="mono tiny">₹<?= number_format((float)$contest->get('prize_pool'), 0) ?></td>
                            <td class="tiny muted">
                                <?= h((string)($contest->get('start_time')?->format('d M, H:i') ?? '—')) ?>
                            </td>
                            <td class="text-right">
                                <a class="btn-arena is-ghost"
                                   href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'slots', $contest->get('id')]) ?>">
                                    Slots
                                </a>
                                <a class="btn-arena is-ghost ml-1"
                                   href="<?= $this->Url->build(['controller' => 'Admin', 'action' => 'editTournament', $contest->get('id')]) ?>">
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
