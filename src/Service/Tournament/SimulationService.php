<?php
declare(strict_types=1);

namespace App\Service\Tournament;

use App\Model\Entity\ArenaMatch;
use App\Service\Chess\ChessEngine;

/**
 * The match simulation tool of the admin dashboard.
 *
 * A simulation plays a whole game with random legal moves on a copy of the
 * engine and then writes the outcome back onto the match, so an organizer can
 * rehearse a round or settle a stuck contest without a real game.
 */
class SimulationService
{
    /**
     * @var \App\Service\Tournament\MatchService
     */
    protected MatchService $matches;

    /**
     * Constructor.
     *
     * @param \App\Service\Tournament\MatchService|null $matches Match service.
     */
    public function __construct(?MatchService $matches = null)
    {
        $this->matches = $matches ?? new MatchService();
    }

    /**
     * Simulate a game and store the result.
     *
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @param string|null $forcedResult Force `White_Win`, `Black_Win` or `Draw`.
     * @param int $maxPlies Maximum number of half moves that are played.
     * @return array{ok: bool, message: string, pgn: string, moves: int, result: string}
     */
    public function simulate(ArenaMatch $match, ?string $forcedResult = null, int $maxPlies = 160): array
    {
        if ($match->get('status') === 'Completed') {
            return [
                'ok' => false,
                'message' => 'This match is already finished.',
                'pgn' => (string)$match->get('pgn'),
                'moves' => count($match->get('moves')),
                'result' => (string)$match->get('result'),
            ];
        }

        $engine = new ChessEngine(ChessEngine::START_FEN);
        foreach ($match->get('moves') as $move) {
            $engine->move((string)$move);
        }

        $plies = 0;
        while ($plies < $maxPlies && !$engine->isGameOver()) {
            $moves = $engine->legalMoves();
            if ($moves === []) {
                break;
            }
            $move = $moves[array_rand($moves)];
            $engine->move($move['from'] . $move['to'] . ($move['promotion'] ?? ''));
            $plies++;
        }

        $moves = array_map(
            fn (array $move): string => $move['from'] . $move['to'] . ($move['promotion'] ?? ''),
            $engine->history(),
        );

        $match->set('moves', $moves);
        $match->set('fen', $engine->toFen());

        if ($forcedResult !== null && in_array($forcedResult, ['White_Win', 'Black_Win', 'Draw'], true)) {
            $result = $forcedResult;
            $termination = 'admin_simulation';
        } elseif ($engine->isCheckmate()) {
            $result = $engine->turn() === 'w' ? 'Black_Win' : 'White_Win';
            $termination = 'checkmate';
        } elseif ($engine->isStalemate() || $engine->isInsufficientMaterial() || $engine->halfmoveClock() >= 100) {
            $result = 'Draw';
            $termination = $engine->terminationReason() ?? 'draw_agreed';
        } else {
            $result = 'Draw';
            $termination = 'admin_simulation';
        }

        $this->matches->forceResult($match, $result, $termination);

        return [
            'ok' => true,
            'message' => sprintf('Simulated %d plies, the game ended as %s.', $plies, $result),
            'pgn' => (string)$match->get('pgn'),
            'moves' => count($moves),
            'result' => $result,
        ];
    }

    /**
     * The PGN notation of a result.
     *
     * @param string $result The result.
     * @return string
     */
    protected function notation(string $result): string
    {
        return match ($result) {
            'White_Win' => '1-0',
            'Black_Win' => '0-1',
            'Draw' => '1/2-1/2',
            default => '*',
        };
    }
}
