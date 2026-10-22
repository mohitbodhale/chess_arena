<?php
/**
 * The contests the signed in player is registered for.
 *
 * @var \App\View\AppView $this
 * @var array<\App\Model\Entity\TournamentRegistration> $contests
 */

$this->assign('title', 'My contests');
?>
<div class="lobby-hero">
    <div class="shell">
        <span class="eyebrow">Your arena</span>
        <h1>My contests.</h1>
        <p>Every seat you took, the points you collected and the money you won.</p>
    </div>
</div>

<div class="shell">
    <div class="section" style="padding-top:1.8rem;">
        <?php if ($contests === []) : ?>
            <div class="empty-state">
                <h3>No contest yet</h3>
                <p class="tiny">Take a seat in the lobby and your contests show up here.</p>
                <a class="btn-arena mt-3" href="<?= $this->Url->build(['controller' => 'Tournaments', 'action' => 'lobby']) ?>">
                    <i class="fas fa-layer-group"></i> Open the lobby
                </a>
            </div>
        <?php else : ?>
            <div class="contest-grid">
                <?php foreach ($contests as $registration) : ?>
                    <?php $contest = $registration->get('tournament'); ?>
                    <?php if ($contest === null) : ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <article class="contest-card is-joined">
                        <div class="contest-card__top">
                            <div>
                                <h3 class="contest-card__title"><?= h((string)$contest->get('title')) ?></h3>
                                <span class="pill is-<?= h(strtolower((string)$contest->get('category'))) ?>">
                                    <?= h((string)$contest->get('category')) ?>
                                </span>
                                <span class="pill is-done ml-1"><?= h((string)$contest->get('time_control')) ?></span>
                            </div>
                            <span class="pill <?= $contest->get('status') === 'Live' ? 'is-live' : 'is-done' ?>">
                                <?= h((string)$contest->get('status')) ?>
                            </span>
                        </div>

                        <div class="contest-card__meta">
                            <span><b><?= number_format((float)$registration->get('score'), 1) ?></b> points</span>
                            <span><b><?= (int)$registration->get('wins') ?></b> wins</span>
                            <span><b><?= (int)$registration->get('draws') ?></b> draws</span>
                            <span><b><?= (int)$registration->get('losses') ?></b> losses</span>
                        </div>

                        <div class="contest-card__pool">
                            <span>Your prize</span>
                            <strong>
                                ₹<?= number_format((float)$registration->get('prize_won')) ?>
                            </strong>
                        </div>

                        <div class="slot-block">
                            <div class="slot-head">
                                <span><b><?= (int)$contest->get('filled_slots') ?></b> of <?= (int)$contest->get('max_slots') ?> seats filled</span>
                                <span><?= (int)$contest->get('slots_remaining') ?> left</span>
                            </div>
                            <div class="slot-bar <?= $contest->get('is_full') ? 'is-full' : '' ?>">
                                <span style="width:<?= (int)$contest->get('slots_filled_percent') ?>%"></span>
                            </div>
                        </div>

                        <div class="contest-card__foot">
                            <div class="entry-fee">
                                Rank #<?= (int)$registration->get('rank') ?: '—' ?>
                                <span>Your standing</span>
                            </div>
                            <div class="d-flex" style="gap:.5rem;">
                                <?php $myMatch = $myMatches[(int)$contest->get('id')] ?? null; ?>
                                <?php if ($myMatch !== null && $myMatch->get('status') === 'Live') : ?>
                                    <a class="btn-arena"
                                       href="<?= $this->Url->build(['controller' => 'Matches', 'action' => 'play', $myMatch->get('id')]) ?>">
                                        <i class="fas fa-circle-play"></i> Play now
                                    </a>
                                <?php elseif ($myMatch !== null) : ?>
                                    <a class="btn-arena is-ghost"
                                       href="<?= $this->Url->build(['controller' => 'Matches', 'action' => 'play', $myMatch->get('id')]) ?>">
                                        Your board
                                    </a>
                                <?php endif; ?>
                                <a class="btn-arena is-muted"
                                   href="<?= $this->Url->build(['controller' => 'Tournaments', 'action' => 'seats', $contest->get('id')]) ?>">
                                    Seats
                                </a>
                                <a class="btn-arena"
                                   href="<?= $this->Url->build(['controller' => 'Tournaments', 'action' => 'view', $contest->get('id')]) ?>">
                                    Open
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
