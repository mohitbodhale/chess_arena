<?php
/**
 * Quick verification harness for the chess engine (not part of the test suite).
 */
require __DIR__ . '/../vendor/autoload.php';

use App\Service\Chess\ChessEngine;

function perft(ChessEngine $engine, int $depth): int
{
    if ($depth === 0) {
        return 1;
    }
    $nodes = 0;
    foreach ($engine->legalMoves() as $move) {
        $child = clone $engine;
        $child->move($move['from'] . $move['to'] . ($move['promotion'] ?? ''));
        $nodes += perft($child, $depth - 1);
    }

    return $nodes;
}

$failures = 0;
function check(string $label, $expected, $actual): void
{
    global $failures;
    $ok = $expected === $actual;
    if (!$ok) {
        $failures++;
    }
    printf("%-58s %s (expected %s, got %s)\n", $label, $ok ? 'OK  ' : 'FAIL', var_export($expected, true), var_export($actual, true));
}

// --- perft from the initial position ---
$engine = new ChessEngine();
check('perft(1) initial', 20, perft(new ChessEngine(), 1));
check('perft(2) initial', 400, perft(new ChessEngine(), 2));
check('perft(3) initial', 8902, perft(new ChessEngine(), 3));

// --- perft from "Kiwipete" ---
$kiwipete = 'r3k2r/p1ppqpb1/bn2pnp1/3PN3/1p2P3/2N2Q1p/PPPBBPPP/R3K2R w KQkq - 0 1';
check('perft(1) kiwipete', 48, perft(new ChessEngine($kiwipete), 1));
check('perft(2) kiwipete', 2039, perft(new ChessEngine($kiwipete), 2));

// --- perft from a promotion-heavy position ---
$promo = 'n1n5/PPPk4/8/8/8/8/4Kppp/5N1N b - - 0 1';
check('perft(1) promotions', 24, perft(new ChessEngine($promo), 1));

// --- The Opera Game (Morphy vs Duke of Brunswick) ---
$opera = [
    'e4', 'e5', 'Nf3', 'd6', 'd4', 'Bg4', 'dxe5', 'Bxf3', 'Qxf3', 'dxe5', 'Bxc4', 'Nf6',
    'Qb3', 'Qe7', 'Nc3', 'c6', 'Bxf7+', 'Kxf7', 'O-O-O', 'Nd7', 'Nxd5', 'cxd5', 'Nbxd6',
    'Nxd6', 'Rxd6', 'Rhe8', 'Qxe4+', 'Qxe4', 'Rxe4+', 'Kf8', 'Bf5+', 'Ke7', 'Kd7+', 'Kd8',
    'Qh8+', 'Kd7', 'Qh7+', 'Kc8', 'Qh8+', 'Kd7', 'Qh7+', 'Kc8', 'Qh8+', 'Kd7', 'Qh7+', 'Kc8',
];
$engine = new ChessEngine();
$sans = [];
foreach ($opera as $uci) {
    $move = $engine->move($uci);
    if ($move === null) {
        check('opera move ' . $uci, 'not null', null);
        break;
    }
    $sans[] = $move['san'];
}
check(
    'opera game final FEN',
    'r1bqkb1r/pppp1Qpp/2n5/4n3/3pP3/8/PPPP1PPP/RNB1K1NR b KQkq - 0 4',
    $engine->toFen(),
);
check('opera checkmate', true, $engine->isCheckmate());
check('opera SAN length', 44, count($sans));

// --- SAN round trip ---
$engine = new ChessEngine();
$san = ['e4', 'e5', 'Nf3', 'Nc6', 'Bb5', 'a6', 'Ba4', 'Nf6', 'O-O', 'Be7', 'Re1', 'b5', 'Bb3', 'd6', 'c3', 'O-O', 'h3', 'Nb8', 'd4', 'Nbd7'];
foreach ($san as $token) {
    $move = $engine->moveSan($token);
    if ($move === null) {
        check('san move ' . $token, 'not null', null);
        break;
    }
}
check('san replay fen', 'r1bq1rk1/pppp1ppp/2n2n2/2b1p3/2B1P3/2NP1N2/PPP2PPP/R2Q1RK1 w - - 4 11', $engine->toFen());

// --- Castling rights loss on rook capture ---
$engine = new ChessEngine('r3k2r/8/8/8/8/8/8/R3K2R w KQkq - 0 1');
$engine->move('axh8');
check('castling right lost after rook capture', 'Kq', explode(' ', $engine->toFen())[2]);

// --- En passant ---
$engine = new ChessEngine('rnbqkbnr/ppp1p1pp/8/3pPp2/8/8/PPPP1PPP/RNBQKBNR w KQkq f6 0 3');
$moves = array_map(fn ($m) => $m['from'] . $m['to'], $engine->legalMoves('e5'));
check('en passant available', true, in_array('e5f6', $moves, true));

// --- Promotion ---
$engine = new ChessEngine('8/4P3/8/8/8/8/8/K6k w - - 0 1');
$moves = $engine->legalMoves('e7');
check('four promotion moves', 4, count($moves));

// --- FEN round trip ---
$engine = new ChessEngine();
check('fen round trip', ChessEngine::START_FEN, $engine->toFen());

printf("\n%s\n", $failures === 0 ? 'ALL CHECKS PASSED' : "$failures CHECK(S) FAILED");
exit($failures === 0 ? 0 : 1);
