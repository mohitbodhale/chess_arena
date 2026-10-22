<?php
/**
 * The live arena: board, clocks, evaluation bar and move history.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ArenaMatch $match
 * @var array<string, mixed> $state
 * @var bool $isWhite
 * @var bool $canPlay
 */

use App\Service\Tournament\MatchService;

$this->assign('title', 'Live match');
$whiteName = (string)($match->get('white_player')['username'] ?? 'White');
$blackName = (string)($match->get('black_player')['username'] ?? 'Black');
$matchId = (int)$match->get('id');
$isOver = ($state['status'] ?? 'Live') !== 'Live';
?>
<div class="shell" style="padding-top:1.8rem;padding-bottom:2.5rem;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <span class="eyebrow">Live match #<?= $matchId ?></span>
            <h1 style="font-size:1.5rem;margin:.3rem 0 0;">
                <?= h((string)($match->get('tournament')['title'] ?? 'Tournament')) ?>
            </h1>
        </div>
        <a class="btn-arena is-muted" href="<?= $this->Url->build('/lobby') ?>">
            <i class="fas fa-arrow-left"></i> Lobby
        </a>
    </div>

    <div class="arena-grid">
        <div>
            <div class="surface surface-pad">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center player-card" data-card="b">
                        <span class="avatar mr-2" style="width:34px;height:34px;border-radius:9px;display:grid;place-items:center;background:var(--bg-elev-2);border:1px solid var(--line);font-size:.72rem;">
                            <?= h(strtoupper(substr($blackName, 0, 2))) ?>
                        </span>
                        <div>
                            <div style="font-weight:600;font-size:.95rem;" data-player="b"><?= h($blackName) ?></div>
                            <div class="tiny muted">Black · rating <?= (int)($match->get('black_player')['rating'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="clock" data-clock="b"><?= $this->Number->format((int)($state['black_time_ms'] ?? 0) / 1000, ['precision' => 0]) ?></div>
                </div>

                <div class="board-wrap">
                    <div class="eval-bar" title="Evaluation">
                        <span id="arena-eval-fill" style="height:50%"></span>
                        <i id="arena-eval-label">0.0</i>
                    </div>
                    <div class="board" id="arena-board"></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="d-flex align-items-center player-card" data-card="w">
                        <span class="avatar mr-2" style="width:34px;height:34px;border-radius:9px;display:grid;place-items:center;background:var(--bg-elev-2);border:1px solid var(--line);font-size:.72rem;">
                            <?= h(strtoupper(substr($whiteName, 0, 2))) ?>
                        </span>
                        <div>
                            <div style="font-weight:600;font-size:.95rem;" data-player="w"><?= h($whiteName) ?></div>
                            <div class="tiny muted">White · rating <?= (int)($match->get('white_player')['rating'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="clock" data-clock="w"><?= $this->Number->format((int)($state['white_time_ms'] ?? 0) / 1000, ['precision' => 0]) ?></div>
                </div>
            </div>

            <div class="game-banner mt-3" id="arena-banner"></div>
        </div>

        <div>
            <div class="surface surface-pad">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 style="margin:0;font-size:1.05rem;">Game state</h3>
                    <span class="pill <?= $isOver ? 'is-done' : 'is-live' ?>" id="arena-status-pill">
                        <?= $isOver ? 'Finished' : 'Live' ?>
                    </span>
                </div>

                <div class="move-list" id="arena-moves"></div>

                <p class="tiny muted" id="arena-status" style="margin:.6rem 0 0;"></p>

                <?php if ($canPlay) : ?>
                    <div class="d-flex mt-3" style="gap:.6rem;">
                        <button type="button" class="btn-arena is-muted" id="arena-draw" style="flex:1;">
                            <i class="fas fa-handshake"></i> Draw
                        </button>
                        <button type="button" class="btn-arena is-danger" id="arena-resign" style="flex:1;">
                            <i class="fas fa-flag"></i> Resign
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="surface surface-pad mt-3">
                <h3 style="margin:0 0 .8rem;font-size:1.05rem;">Scoring</h3>
                <table class="scoring-table" style="font-size:.85rem;">
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
                        <td>up to <strong>+2</strong></td>
                    </tr>
                </table>
                <p class="tiny muted" style="margin:.7rem 0 0;">
                    Every second left on the clock when the game ends is converted into bonus
                    points, no matter the result.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="promotion-overlay" id="promotion-overlay">
    <div class="promotion-card">
        <strong>Promote to</strong>
        <div class="promotion-options">
            <button type="button" data-promotion="q">♕</button>
            <button type="button" data-promotion="r">♖</button>
            <button type="button" data-promotion="b">♗</button>
            <button type="button" data-promotion="n">♘</button>
        </div>
    </div>
</div>

<?php $this->start('script'); ?>
<script>
    window.ARENA_BOARD = {
        state: <?= json_encode($state, JSON_THROW_ON_ERROR) ?>,
        flipped: <?= $isWhite ? 'false' : 'true' ?>,
        readOnly: <?= $canPlay ? 'false' : 'true' ?>,
        moveUrl: <?= json_encode($this->Url->build(['controller' => 'Matches', 'action' => 'move', $matchId]), JSON_THROW_ON_ERROR) ?>,
        stateUrl: <?= json_encode($this->Url->build(['controller' => 'Matches', 'action' => 'state', $matchId]), JSON_THROW_ON_ERROR) ?>,
        resignUrl: <?= json_encode($this->Url->build(['controller' => 'Matches', 'action' => 'resign', $matchId]), JSON_THROW_ON_ERROR) ?>,
        drawUrl: <?= json_encode($this->Url->build(['controller' => 'Matches', 'action' => 'draw', $matchId]), JSON_THROW_ON_ERROR) ?>,
        whiteWins: <?= $isWhite ? 'true' : 'false' ?>,
        blackWins: <?= $isWhite ? 'false' : 'true' ?>
    };
</script>
<?= $this->Html->script('chess.bundle.js') ?>
<?= $this->Html->script('arena.js') ?>
<?php $this->end(); ?>
