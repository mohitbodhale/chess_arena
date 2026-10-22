<?php
/**
 * The Dream11 style contest card.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Tournament $contest
 * @var bool $isRegistered
 * @var bool $canJoin
 */

$category = strtolower((string)$contest->get('category'));
$status = (string)$contest->get('status');
$percent = (int)$contest->get('slots_filled_percent');
$rows = $contest->get('prize_rows');
?>
<article class="contest-card <?= $isRegistered ? 'is-joined' : '' ?>"
         data-contest
         data-category="<?= h((string)$contest->get('category')) ?>"
         data-status="<?= h($status) ?>"
         data-title="<?= h(strtolower((string)$contest->get('title'))) ?>">
    <div class="contest-card__top">
        <div>
            <h3 class="contest-card__title"><?= h((string)$contest->get('title')) ?></h3>
            <span class="pill is-<?= h($category) ?>"><?= h((string)$contest->get('category')) ?></span>
            <span class="pill is-done ml-1"><?= h((string)$contest->get('time_control')) ?></span>
        </div>
        <span class="pill <?= $status === 'Live' ? 'is-live' : ($status === 'Completed' ? 'is-done' : 'is-open') ?>">
            <?php if ($status === 'Live') : ?>
                <span class="dot-pulse"></span>Live
            <?php else : ?>
                <?= h($status) ?>
            <?php endif; ?>
        </span>
    </div>

    <div class="contest-card__meta">
        <span><b><?= number_format((int)$contest->get('filled_slots')) ?></b> players</span>
        <span><b><?= h((string)($contest->get('organizer')['username'] ?? 'Arena')) ?></b> host</span>
        <span><b data-countdown="<?= h((string)$contest->get('start_time')) ?>">—</b> start</span>
    </div>

    <div class="contest-card__pool">
        <span>Prize pool</span>
        <strong>₹<?= number_format((float)$contest->get('prize_pool')) ?></strong>
    </div>

    <div class="prize-rows">
        <?php foreach (array_slice($rows, 0, 3) as $row) : ?>
            <div class="prize-row">
                <span class="rank"><i><?= (int)$row['rank'] ?></i> <?= h((string)$row['label']) ?></span>
                <span class="amount">₹<?= number_format((float)$row['amount']) ?></span>
            </div>
        <?php endforeach; ?>
        <?php if ($rows === []) : ?>
            <div class="prize-row"><span class="rank">Winner takes all</span><span class="amount">—</span></div>
        <?php endif; ?>
    </div>

    <div class="slot-block">
        <div class="slot-head">
            <span><b><?= (int)$contest->get('filled_slots') ?></b> of <?= (int)$contest->get('max_slots') ?> seats filled</span>
            <span><?= (int)$contest->get('slots_remaining') ?> left</span>
        </div>
        <div class="slot-bar <?= $contest->get('is_full') ? 'is-full' : '' ?>" data-slot-bar="<?= $percent ?>">
            <span style="width:0%"></span>
        </div>
    </div>

    <div class="contest-card__foot">
        <div class="entry-fee">
            ₹<?= number_format((float)$contest->get('entry_fee')) ?>
            <span>Entry fee</span>
        </div>
        <div class="d-flex align-items-center">
            <?php if ($isRegistered) : ?>
                <span class="pill is-open mr-2">Joined</span>
            <?php endif; ?>
            <a class="btn-arena"
               href="<?= $this->Url->build(['controller' => 'Tournaments', 'action' => 'view', $contest->get('id')]) ?>">
                <?= $status === 'Live' ? 'Watch' : ($status === 'Completed' ? 'Results' : 'Enter') ?>
            </a>
        </div>
    </div>
</article>
