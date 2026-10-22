<?php
/**
 * The arena rules.
 *
 * @var \App\View\AppView $this
 */

$this->assign('title', 'Arena rules');
?>
<div class="shell" style="padding:2.5rem 0 3.5rem;max-width:900px;">
    <span class="eyebrow">How the arena works</span>
    <h1 style="font-size:2.1rem;margin:.4rem 0 1.2rem;">Rules of the arena</h1>

    <div class="surface surface-pad mb-3">
        <h2 style="font-size:1.15rem;margin:0 0 .6rem;">Taking a seat</h2>
        <p class="muted" style="margin:0;">
            Every contest has a published entry fee and a fixed number of seats. The fee is
            taken from your deposit balance the moment you enter and it is refunded in full
            when you leave before the contest starts. A contest is locked for good once the
            first game is played.
        </p>
    </div>

    <div class="surface surface-pad mb-3">
        <h2 style="font-size:1.15rem;margin:0 0 .6rem;">Scoring</h2>
        <p class="muted" style="margin:0;">
            A win is worth 3 points, a draw 1 point and a loss 0 points. Every game can add up
            to 2 bonus points for the time you saved on the clock. The standings are sorted by
            points, then by bonus points, then by the number of wins.
        </p>
    </div>

    <div class="surface surface-pad mb-3">
        <h2 style="font-size:1.15rem;margin:0 0 .6rem;">The prize money</h2>
        <p class="muted" style="margin:0;">
            The arena keeps a commission of 10% of every rupee that was collected at the seats.
            The remaining 90% is the prize pool and it is shared by the players who took a
            place, following the payout ladder of the contest. When fewer players take a place
            than the ladder announces, the whole pool is shared by the players that are there.
        </p>
    </div>

    <div class="surface surface-pad mb-3">
        <h2 style="font-size:1.15rem;margin:0 0 .6rem;">Clocks and conduct</h2>
        <p class="muted" style="margin:0;">
            Every game runs on the time control of its format. A player who runs out of time
            loses on time, and a game can be ended by resigning or by agreeing a draw. Abuse of
            the clock or of other players costs you your seat in the arena.
        </p>
    </div>

    <a class="btn-arena" href="<?= $this->Url->build('/lobby') ?>">
        <i class="fas fa-bolt"></i> Find a contest
    </a>
</div>
