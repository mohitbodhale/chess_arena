<?php
/**
 * The cinematic landing page.
 *
 * @var \App\View\AppView $this
 * @var int $liveCount
 * @var int $openCount
 * @var int $playerCount
 * @var float $prizePool
 * @var array<\App\Model\Entity\Tournament> $featured
 * @var array<\App\Model\Entity\TournamentRegistration> $topPlayers
 * @var \App\Model\Entity\User|null $currentUser
 * @var string|null $currentRole
 */

use App\Service\Tournament\TimeControl;

$this->assign('title', 'Competitive chess with real prize pools');
?>
<section class="hero">
    <div class="shell hero-grid">
        <div>
            <span class="eyebrow">Dream11 style chess arena</span>
            <h1>Buy a seat.<br><span class="accent">Win the pool.</span></h1>
            <p class="hero-lead">
                Chess Arena turns every match into a contest. Pick a format, pay the entry fee,
                and the prize pool is split by how you score — wins, draws and the time you
                leave on the clock.
            </p>
            <div class="hero-actions">
                <a class="btn-arena" href="<?= $this->Url->build('/lobby') ?>">
                    <i class="fa fa-bolt"></i> Enter a tournament
                </a>
                <a class="btn-arena is-ghost" href="<?= $this->Url->build('/leaderboard') ?>">
                    <i class="fa fa-chart-line"></i> Live leaderboard
                </a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <strong><?= number_format($liveCount) ?></strong>
                    <span>Live now</span>
                </div>
                <div class="hero-stat">
                    <strong><?= number_format($openCount) ?></strong>
                    <span>Open contests</span>
                </div>
                <div class="hero-stat">
                    <strong><?= number_format($playerCount) ?></strong>
                    <span>Players</span>
                </div>
                <div class="hero-stat">
                    <strong>₹<?= number_format($prizePool) ?></strong>
                    <span>Prize money</span>
                </div>
            </div>
        </div>

        <div class="hero-board">
            <div class="hero-board-inner">
                <?php
                $preview = [
                    'rnbqkbnr',
                    'pppppppp',
                    '........',
                    '..P.....',
                    '...p.p..',
                    '........',
                    'PPPP.PPP',
                    'RNBQKBNR',
                ];
                $previewFiles = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
                $previewRanks = [8, 7, 6, 5, 4, 3, 2, 1];
                $highlight = ['d6', 'f4'];
                foreach ($preview as $rowIndex => $row) {
                    foreach (str_split($row) as $fileIndex => $code) {
                        if ($code === '.') {
                            continue;
                        }
                        $isLight = ($rowIndex + $fileIndex) % 2 === 0;
                        $square = $previewFiles[$fileIndex] . $previewRanks[$rowIndex];
                        $isWhitePiece = ctype_upper($code);
                        $glyph = [
                            'p' => ['♙', '♟'], 'n' => ['♘', '♞'], 'b' => ['♗', '♝'],
                            'r' => ['♖', '♜'], 'q' => ['♕', '♛'], 'k' => ['♔', '♚'],
                        ][strtolower($code)][$isWhitePiece ? 0 : 1];
                        ?>
                        <span class="<?= $isLight ? 'light' : 'dark' ?> <?= $isWhitePiece ? 'piece-w' : 'piece-b' ?> <?= in_array($square, $highlight, true) ? 'last' : '' ?>"><?= $glyph ?></span>
                    <?php } ?>
                <?php } ?>
            </div>
            <div class="hero-board-tag">
                <span class="dot-pulse" style="color: var(--accent);"></span>
                <span><b>Blitz · 3+0</b> · 128 players in the arena</span>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="section-head">
            <span class="eyebrow">Tournament formats</span>
            <h2>Three formats, three different kinds of pressure.</h2>
            <p>
                Every contest is bound to a clock. The category decides the time control, the
                entry fee and the way the leaderboard shakes out.
            </p>
        </div>

        <div class="format-grid">
            <?php foreach (TimeControl::CATEGORIES as $key => $meta) : ?>
                <article class="format-card" style="--format-accent: <?= h($meta['accent']) ?>;">
                    <h3><?= h($meta['label']) ?></h3>
                    <div class="clock"><?= h($meta['display']) ?></div>
                    <p><?= h($meta['blurb']) ?></p>
                    <ul>
                        <li><?= $meta['base'] ?> seconds on the clock</li>
                        <li><?= $meta['increment'] > 0 ? $meta['increment'] . ' second increment per move' : 'No increment — every second counts' ?></li>
                        <li>Bonus points for the time you save</li>
                    </ul>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section is-alt">
    <div class="shell">
        <div class="section-head">
            <span class="eyebrow">How it works</span>
            <h2>Four mechanics that run every contest.</h2>
        </div>

        <div class="mechanic-grid">
            <article class="mechanic">
                <div class="icon"><i class="fa fa-indian-rupee-sign"></i></div>
                <h4>Entry fee → prize pool</h4>
                <p>
                    Every seat you buy adds to the pool. The bigger the lobby, the bigger the
                    payout — and the ladder that splits it is shown before you pay.
                </p>
            </article>
            <article class="mechanic">
                <div class="icon"><i class="fa fa-satellite-dish"></i></div>
                <h4>Live leaderboard</h4>
                <p>
                    Standings update the moment a game ends. Points, time bonus and rating
                    decide the order, so a fast win always beats a slow one.
                </p>
            </article>
            <article class="mechanic">
                <div class="icon"><i class="fa fa-chess-knight"></i></div>
                <h4>Legal move engine</h4>
                <p>
                    Every move is validated against a full rules engine, with countdown clocks,
                    a FEN state parser, PGN history and instant checkmate detection.
                </p>
            </article>
            <article class="mechanic">
                <div class="icon"><i class="fa fa-stopwatch"></i></div>
                <h4>Time bonus</h4>
                <p>
                    Leftover seconds are converted into bonus points. Slow chess still pays —
                    you just need to hold on to the clock.
                </p>
            </article>
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="grid" style="display:grid;grid-template-columns:1.1fr .9fr;gap:2rem;align-items:start;">
            <div class="surface surface-pad">
                <span class="eyebrow">Scoring</span>
                <h3 style="margin-top:.6rem;font-size:1.4rem;">Fantasy points, not just results.</h3>
                <table class="scoring-table" style="margin-top:1rem;">
                    <thead>
                    <tr>
                        <th>Outcome</th>
                        <th>Base points</th>
                        <th>Time bonus</th>
                        <th>Maximum</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Win</td>
                        <td><strong>+3</strong></td>
                        <td>up to +2</td>
                        <td><strong>+5</strong></td>
                    </tr>
                    <tr>
                        <td>Draw</td>
                        <td><strong>+1</strong></td>
                        <td>up to +2</td>
                        <td><strong>+3</strong></td>
                    </tr>
                    <tr>
                        <td>Loss</td>
                        <td><strong>0</strong></td>
                        <td>up to +2</td>
                        <td><strong>+2</strong></td>
                    </tr>
                    </tbody>
                </table>
                <p class="muted tiny" style="margin-top:1rem;">
                    Ties are broken by time bonus, then by wins, then by rating — exactly like a
                    fantasy cricket scorecard.
                </p>
            </div>

            <div class="surface surface-pad">
                <span class="eyebrow">Leaderboard</span>
                <h3 style="margin-top:.6rem;font-size:1.4rem;">Top of the arena</h3>
                <div style="margin-top:1rem;display:flex;flex-direction:column;gap:.6rem;">
                    <?php if ($topPlayers === []) : ?>
                        <p class="muted">No games have been scored yet. Be the first on the board.</p>
                    <?php endif; ?>
                    <?php foreach ($topPlayers as $index => $entry) : ?>
                        <div style="display:flex;align-items:center;gap:.8rem;">
                            <span class="rank-cell <?= $index < 3 ? 'is-' . ($index + 1) : '' ?>"><?= $index + 1 ?></span>
                            <span style="font-weight:600;flex:1;"><?= h((string)($entry->get('user')['username'] ?? 'Player')) ?></span>
                            <span class="points"><?= number_format((float)$entry->get('score'), 1) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a class="btn-arena is-ghost" style="margin-top:1.2rem;width:100%;" href="<?= $this->Url->build('/leaderboard') ?>">
                    Open the full leaderboard
                </a>
            </div>
        </div>
    </div>
</section>

<section class="section is-alt">
    <div class="shell">
        <div class="section-head">
            <span class="eyebrow">Open contests</span>
            <h2>Seats are filling up right now.</h2>
            <p>Every card shows the entry fee, the prize ladder and how many seats are left.</p>
        </div>

        <?php if ($featured === []) : ?>
            <div class="empty-state">
                <h3>No contests are open yet</h3>
                <p>Organizers are preparing the next round. Check back in a moment.</p>
            </div>
        <?php else : ?>
            <div class="contest-grid">
                <?php foreach ($featured as $contest) : ?>
                    <article class="contest-card">
                        <div class="contest-card__top">
                            <div>
                                <h3 class="contest-card__title"><?= h((string)$contest->get('title')) ?></h3>
                                <span class="pill is-<?= strtolower((string)$contest->get('category')) ?>">
                                    <?= h((string)$contest->get('category')) ?>
                                </span>
                            </div>
                            <span class="pill <?= $contest->get('status') === 'Live' ? 'is-live' : 'is-open' ?>">
                                <?= $contest->get('status') === 'Live' ? '<span class="dot-pulse"></span>Live' : 'Open' ?>
                            </span>
                        </div>
                        <div class="contest-card__meta">
                            <span><b><?= h((string)$contest->get('time_control')) ?></b> control</span>
                            <span><b><?= number_format((int)$contest->get('filled_slots')) ?></b> joined</span>
                        </div>
                        <div class="contest-card__pool">
                            <span>Prize pool</span>
                            <strong>₹<?= number_format((float)$contest->get('prize_pool')) ?></strong>
                        </div>
                        <div class="slot-block">
                            <div class="slot-head">
                                <span><b><?= (int)$contest->get('filled_slots') ?></b> of <?= (int)$contest->get('max_slots') ?> seats</span>
                                <span><?= (int)$contest->get('slots_remaining') ?> left</span>
                            </div>
                            <div class="slot-bar <?= $contest->get('is_full') ? 'is-full' : '' ?>">
                                <span style="width:<?= (int)$contest->get('slots_filled_percent') ?>%"></span>
                            </div>
                        </div>
                        <div class="contest-card__foot">
                            <div class="entry-fee">
                                ₹<?= number_format((float)$contest->get('entry_fee')) ?>
                                <span>Entry fee</span>
                            </div>
                            <a class="btn-arena" href="<?= $this->Url->build(['controller' => 'Tournaments', 'action' => 'view', $contest->get('id')]) ?>">
                                View contest
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="landing-cta">
            <span class="eyebrow" style="justify-content:center;">Ready when you are</span>
            <h2 style="margin-top:.8rem;">Your first seat is one click away.</h2>
            <p>
                Create a free account, get a ₹1,000 bonus in your wallet and enter the next
                Blitz contest before the lobby fills up.
            </p>
            <div style="display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap;">
                <a class="btn-arena" href="<?= $this->Url->build('/users/register') ?>">Create free account</a>
                <a class="btn-arena is-ghost" href="<?= $this->Url->build('/lobby') ?>">Browse contests</a>
            </div>
        </div>
    </div>
</section>
