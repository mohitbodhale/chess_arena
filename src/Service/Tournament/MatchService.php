<?php
declare(strict_types=1);

namespace App\Service\Tournament;

use App\Model\Entity\ArenaMatch;
use App\Model\Entity\Tournament;
use App\Model\Entity\TournamentRegistration;
use App\Model\Entity\User;
use App\Model\Table\MatchesTable;
use App\Model\Table\TournamentRegistrationsTable;
use App\Service\Chess\ChessEngine;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * The authoritative game engine behind the arena.
 *
 * The browser runs chess.js for instant feedback, but every move that reaches
 * the server is replayed against the PHP engine. A tampered client can never
 * produce a move that the engine would not accept.
 */
class MatchService
{
    use LocatorAwareTrait;

    /**
     * @var \App\Service\Tournament\RankingService
     */
    protected RankingService $ranking;

    /**
     * Constructor.
     *
     * @param \App\Service\Tournament\RankingService|null $ranking Ranking service.
     */
    public function __construct(?RankingService $ranking = null)
    {
        $this->ranking = $ranking ?? new RankingService();
    }

    /**
     * Start a contest and pair up the players.
     *
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @return int The number of matches that were created.
     */
    public function start(Tournament $tournament): int
    {
        /** @var \App\Model\Table\TournamentRegistrationsTable $registrations */
        $registrations = $this->getTableLocator()->get('TournamentRegistrations');
        /** @var \App\Model\Table\MatchesTable $matches */
        $matches = $this->getTableLocator()->get('Matches');
        /** @var \App\Model\Table\TournamentsTable $tournaments */
        $tournaments = $this->getTableLocator()->get('Tournaments');

        $players = $registrations->find()
            ->where(['TournamentRegistrations.tournament_id' => $tournament->get('id')])
            ->contain(['Users'])
            ->all()
            ->toList();

        if (count($players) < 2) {
            return 0;
        }

        // Sort by rating so that the strongest players meet first, then shuffle
        // the halves so the first round is not always the same.
        usort($players, fn ($a, $b) => (int)($b['user']['rating'] ?? 0) <=> (int)($a['user']['rating'] ?? 0));
        $half = (int)floor(count($players) / 2);
        $top = array_slice($players, 0, $half);
        $bottom = array_slice($players, $half);
        shuffle($top);
        shuffle($bottom);

        $created = 0;
        foreach ($top as $index => $registration) {
            $opponent = $bottom[$index] ?? null;
            if ($opponent === null) {
                continue;
            }

            $white = mt_rand(0, 1) === 1 ? $registration : $opponent;
            $black = $white === $registration ? $opponent : $registration;

            $baseMillis = (int)$tournament->get('base_seconds') * 1000;
            $incrementMillis = (int)$tournament->get('increment_seconds') * 1000;

            $match = $matches->newEntity([
                'tournament_id' => $tournament->get('id'),
                'white_player_id' => $white->get('user_id'),
                'black_player_id' => $black->get('user_id'),
                'result' => 'Pending',
                'termination' => null,
                'status' => 'Live',
                'fen' => ChessEngine::START_FEN,
                'moves' => [],
                'white_time_ms' => $baseMillis,
                'black_time_ms' => $baseMillis,
                'increment_ms' => $incrementMillis,
                'round_number' => 1,
                'started_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($matches->save($match)) {
                $created++;
            }
        }

        $tournament->set('status', 'Live');
        $tournaments->save($tournament);

        return $created;
    }

    /**
     * The engine instance for the current position of a match.
     *
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @return \App\Service\Chess\ChessEngine
     */
    public function engine(ArenaMatch $match): ChessEngine
    {
        // The engine is always rebuilt from the opening position and the stored
        // moves, so the position never depends on a stale FEN.
        $engine = new ChessEngine(ChessEngine::START_FEN);
        foreach ($match->get('moves') as $move) {
            $engine->move((string)$move);
        }

        return $engine;
    }

    /**
     * Play a move.
     *
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @param \App\Model\Entity\User $user The player that moves.
     * @param string $from Origin square.
     * @param string $to Destination square.
     * @param string|null $promotion Promotion piece.
     * @param int $elapsedMillis Milliseconds the player spent on the move.
     * @return array{ok: bool, message: string, state: array<string, mixed>}
     */
    public function play(
        ArenaMatch $match,
        User $user,
        string $from,
        string $to,
        ?string $promotion = null,
        int $elapsedMillis = 0,
    ): array {
        if ($match->get('status') !== 'Live') {
            return $this->failed('This game is already finished.', $match);
        }

        $isWhite = (int)$match->get('white_player_id') === (int)$user->get('id');
        $isBlack = (int)$match->get('black_player_id') === (int)$user->get('id');
        if (!$isWhite && !$isBlack) {
            return $this->failed('You are not a player of this game.', $match);
        }

        $engine = $this->engine($match);
        $turn = $engine->turn();
        if (($turn === 'w' && !$isWhite) || ($turn === 'b' && !$isBlack)) {
            return $this->failed('It is not your turn.', $match);
        }

        $uci = strtolower($from . $to . ($promotion !== null ? strtolower($promotion) : ''));
        $applied = $engine->move($uci);
        if ($applied === null) {
            return $this->failed('That move is not legal in this position.', $match);
        }

        $remaining = $match->timeFor($turn) - max(0, $elapsedMillis);
        if ($remaining <= 0) {
            // The clock of the side to move expired before the move was sent.
            $match->setTimeFor($turn, 0);
            $this->finish($match, $engine, 'timeout', 0, $turn);
            $this->saveMatch($match);

            return [
                'ok' => true,
                'message' => 'Your clock ran out and the game is over.',
                'state' => $this->state($match),
            ];
        }

        $remaining += (int)$match->get('increment_ms');

        $match->setTimeFor($turn, $remaining);
        $match->set('moves', array_merge($match->get('moves'), [$uci]));
        $match->set('fen', $engine->toFen());

        $termination = $engine->terminationReason();
        if ($termination !== null) {
            $this->finish($match, $engine, $termination, $remaining, $turn);
        }

        $this->saveMatch($match);

        return [
            'ok' => true,
            'message' => 'Move played.',
            'state' => $this->state($match),
        ];
    }

    /**
     * Concede the game.
     *
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @param \App\Model\Entity\User $user The player that resigns.
     * @return array{ok: bool, message: string, state: array<string, mixed>}
     */
    public function resign(ArenaMatch $match, User $user): array
    {
        if ($match->get('status') !== 'Live') {
            return $this->failed('This game is already finished.', $match);
        }

        $isWhite = (int)$match->get('white_player_id') === (int)$user->get('id');
        $isBlack = (int)$match->get('black_player_id') === (int)$user->get('id');
        if (!$isWhite && !$isBlack) {
            return $this->failed('You are not a player of this game.', $match);
        }

        $engine = $this->engine($match);
        $result = $isWhite ? 'Black_Win' : 'White_Win';
        $turn = $engine->turn();
        $this->finish($match, $engine, 'resign', $match->timeFor($turn), $turn, $result);
        $this->saveMatch($match);

        return [
            'ok' => true,
            'message' => 'You resigned the game.',
            'state' => $this->state($match),
        ];
    }

    /**
     * Agree a draw with the opponent.
     *
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @param \App\Model\Entity\User $user The player that offers the draw.
     * @return array{ok: bool, message: string, state: array<string, mixed>}
     */
    public function draw(ArenaMatch $match, User $user): array
    {
        if ($match->get('status') !== 'Live') {
            return $this->failed('This game is already finished.', $match);
        }

        $engine = $this->engine($match);
        $turn = $engine->turn();
        $this->finish($match, $engine, 'draw_agreed', $match->timeFor($turn), $turn);
        $this->saveMatch($match);

        return [
            'ok' => true,
            'message' => 'The game ended in a draw.',
            'state' => $this->state($match),
        ];
    }

    /**
     * Force a result, used by the admin match simulation tools.
     *
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @param string $result `White_Win`, `Black_Win` or `Draw`.
     * @param string $termination Termination reason.
     * @return array{ok: bool, message: string, state: array<string, mixed>}
     */
    public function forceResult(ArenaMatch $match, string $result, string $termination = 'admin_decision'): array
    {
        $engine = $this->engine($match);
        $turn = $engine->turn();
        $this->finish($match, $engine, $termination, $match->timeFor($turn), $turn, $result);
        $this->saveMatch($match);

        return [
            'ok' => true,
            'message' => 'The result was recorded.',
            'state' => $this->state($match),
        ];
    }

    /**
     * A JSON friendly snapshot of a match.
     *
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @return array<string, mixed>
     */
    public function state(ArenaMatch $match): array
    {
        $engine = $this->engine($match);
        $moves = [];
        foreach ($engine->history() as $move) {
            $moves[] = [
                'san' => $move['san'] ?? '',
                'from' => $move['from'],
                'to' => $move['to'],
                'piece' => $move['type'],
                'capture' => (bool)($move['capture'] ?? false),
                'castle' => $move['castle'] ?? null,
                'promotion' => $move['promotion'] ?? null,
            ];
        }

        return [
            'id' => (int)$match->get('id'),
            'fen' => $engine->toFen(),
            'turn' => $engine->turn(),
            'status' => $match->get('status'),
            'result' => $match->get('result'),
            'termination' => $match->get('termination'),
            'white_time_ms' => (int)$match->get('white_time_ms'),
            'black_time_ms' => (int)$match->get('black_time_ms'),
            'increment_ms' => (int)$match->get('increment_ms'),
            'evaluation' => round($engine->evaluate(), 2),
            'in_check' => $engine->isCheck(),
            'game_over' => $engine->isGameOver(),
            'moves' => $moves,
        ];
    }

    /**
     * The legal destinations of a square for the side to move.
     *
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @param string $square Origin square.
     * @return array<int, string>
     */
    public function legalTargets(ArenaMatch $match, string $square): array
    {
        return $this->engine($match)->legalTargets(strtolower($square));
    }

    /**
     * Finish a game, store the PGN and award the points.
     *
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @param \App\Service\Chess\ChessEngine $engine The engine.
     * @param string $termination Termination reason.
     * @param int $moverTimeMillis The remaining time of the side that moved.
     * @param string $moverTurn The side that moved.
     * @param string|null $forcedResult Force a specific result.
     * @return void
     */
    protected function finish(
        ArenaMatch $match,
        ChessEngine $engine,
        string $termination,
        int $moverTimeMillis,
        string $moverTurn,
        ?string $forcedResult = null,
    ): void {
        $result = $forcedResult ?? $this->resultFor($termination, $moverTurn, $moverTimeMillis);
        if ($result === null) {
            return;
        }

        $match->set('result', $result);
        $match->set('termination', $termination);
        $match->set('status', 'Completed');
        $match->set('ended_at', date('Y-m-d H:i:s'));
        $match->set('pgn', $engine->pgn($this->pgnHeaders($match)));

        $this->awardPoints($match, $result);
    }

    /**
     * The result of a finished game.
     *
     * @param string $termination Termination reason.
     * @param string $moverTurn The side that moved last.
     * @param int $moverTimeMillis The remaining time of the side that moved last.
     * @return string|null
     */
    protected function resultFor(string $termination, string $moverTurn, int $moverTimeMillis): ?string
    {
        return match ($termination) {
            'checkmate' => $moverTurn === 'w' ? 'Black_Win' : 'White_Win',
            'timeout' => $moverTimeMillis <= 0
                ? ($moverTurn === 'w' ? 'Black_Win' : 'White_Win')
                : ($moverTurn === 'w' ? 'White_Win' : 'Black_Win'),
            'resign' => $moverTurn === 'w' ? 'Black_Win' : 'White_Win',
            'stalemate', 'threefold_repetition', 'insufficient_material', 'fifty_move_rule', 'draw_agreed' => 'Draw',
            default => null,
        };
    }

    /**
     * Award the fantasy points of a finished game to both registrations.
     *
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @param string $result The result.
     * @return void
     */
    protected function awardPoints(ArenaMatch $match, string $result): void
    {
        /** @var \App\Model\Table\TournamentRegistrationsTable $registrations */
        $registrations = $this->getTableLocator()->get('TournamentRegistrations');

        $white = $registrations->find()
            ->where([
                'tournament_id' => $match->get('tournament_id'),
                'user_id' => $match->get('white_player_id'),
            ])
            ->first();
        $black = $registrations->find()
            ->where([
                'tournament_id' => $match->get('tournament_id'),
                'user_id' => $match->get('black_player_id'),
            ])
            ->first();

        if ($white === null || $black === null) {
            return;
        }

        $baseMillis = $this->baseClock($match);
        $this->ranking->award(
            $white,
            $black,
            $result,
            (int)$match->get('white_time_ms'),
            (int)$match->get('black_time_ms'),
            $baseMillis,
        );

        $registrations->save($white);
        $registrations->save($black);
    }

    /**
     * The starting clock of a match in milliseconds.
     *
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @return int
     */
    protected function baseClock(ArenaMatch $match): int
    {
        /** @var \App\Model\Table\TournamentsTable $tournaments */
        $tournaments = $this->getTableLocator()->get('Tournaments');
        $tournament = $tournaments->find()->where(['Tournaments.id' => $match->get('tournament_id')])->first();

        if ($tournament === null) {
            return 180000;
        }

        return (int)$tournament->get('base_seconds') * 1000;
    }

    /**
     * The PGN headers of a match.
     *
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @return array<string, string>
     */
    protected function pgnHeaders(ArenaMatch $match): array
    {
        $white = $this->playerName((int)$match->get('white_player_id'));
        $black = $this->playerName((int)$match->get('black_player_id'));

        return [
            'Event' => 'Chess Arena',
            'Site' => 'Chess Arena',
            'Date' => date('Y.m.d'),
            'Round' => (string)$match->get('round_number'),
            'White' => $white,
            'Black' => $black,
            'Result' => $this->resultNotation((string)$match->get('result')),
        ];
    }

    /**
     * The name of a player.
     *
     * @param int $userId The player.
     * @return string
     */
    protected function playerName(int $userId): string
    {
        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->getTableLocator()->get('Users');
        $user = $users->find()->where(['Users.id' => $userId])->first();

        return $user === null ? 'Unknown' : (string)$user->get('username');
    }

    /**
     * The PGN notation of a result.
     *
     * @param string $result The result.
     * @return string
     */
    protected function resultNotation(string $result): string
    {
        return match ($result) {
            'White_Win' => '1-0',
            'Black_Win' => '0-1',
            'Draw' => '1/2-1/2',
            default => '*',
        };
    }

    /**
     * Persist the match.
     *
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @return void
     */
    protected function saveMatch(ArenaMatch $match): void
    {
        /** @var \App\Model\Table\MatchesTable $matches */
        $matches = $this->getTableLocator()->get('Matches');
        $matches->save($match);
    }

    /**
     * Build a failed result with the current state of the match.
     *
     * @param string $message The reason.
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @return array{ok: bool, message: string, state: array<string, mixed>}
     */
    protected function failed(string $message, ArenaMatch $match): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'state' => $this->state($match),
        ];
    }
}
