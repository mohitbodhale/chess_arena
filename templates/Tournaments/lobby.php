<?php
/**
 * The Dream11 style tournament lobby.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Tournament> $contests
 * @var string $category
 * @var string $status
 * @var string $search
 * @var array<string, string> $categories
 * @var array<int, int> $myContestIds
 * @var bool $canJoin
 */

use App\Service\Tournament\TimeControl;

$this->assign('title', 'Tournament lobby');
?>
<div class="lobby-hero">
    <div class="shell">
        <span class="eyebrow">Dream11 style lobby</span>
        <h1>Pick your battleground.</h1>
        <p>
            Every card is a contest with a live seat counter, an entry fee and the exact
            prize ladder. Fill a lobby, beat the field and take the pool.
        </p>
    </div>
</div>

<div class="shell">
    <div class="filter-bar">
        <div class="filter-group" role="group" aria-label="Category filter">
            <a href="#" class="<?= $category === 'All' ? 'is-active' : '' ?>" data-filter-category="All">All</a>
            <?php foreach (TimeControl::CATEGORIES as $key => $meta) : ?>
                <a href="#"
                   class="<?= $category === $key ? 'is-active' : '' ?>"
                   data-filter-category="<?= h($key) ?>"><?= h($meta['label']) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="filter-group" role="group" aria-label="Status filter">
            <a href="#" class="<?= $status === 'Open' ? 'is-active' : '' ?>" data-filter-status="Open">Open</a>
            <a href="#" class="<?= $status === 'Live' ? 'is-active' : '' ?>" data-filter-status="Live">Live</a>
            <a href="#" class="<?= $status === 'Completed' ? 'is-active' : '' ?>" data-filter-status="Completed">Finished</a>
        </div>

        <label class="filter-search">
            <i class="fas fa-search" style="color:var(--text-mute);"></i>
            <input type="search" id="lobby-search" placeholder="Search contests" value="<?= h($search) ?>">
        </label>
    </div>

    <div class="section" style="padding-top:1.8rem;">
        <?php if (count($contests) === 0) : ?>
            <div class="empty-state" id="lobby-empty">
                <h3>No contest matches this filter</h3>
                <p>Try another format or clear the search to see every open lobby.</p>
            </div>
        <?php else : ?>
            <div class="contest-grid" id="contest-grid"
                 data-category="<?= h($category) ?>"
                 data-status="<?= h($status) ?>">
                <?php foreach ($contests as $contest) : ?>
                    <?= $this->element('contest_card', [
                        'contest' => $contest,
                        'isRegistered' => in_array((int)$contest->get('id'), $myContestIds, true),
                        'canJoin' => $canJoin,
                    ]) ?>
                <?php endforeach; ?>
            </div>
            <div class="empty-state" id="lobby-empty" style="display:none;">
                <h3>No contest matches this filter</h3>
                <p>Try another format or clear the search to see every open lobby.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $this->start('script'); ?>
<?= $this->Html->script('lobby.js') ?>
<?php $this->end(); ?>
