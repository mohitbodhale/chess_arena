<?php
declare(strict_types=1);

namespace App\Service\Tournament;

use App\Model\Entity\TournamentRegistration;

/**
 * The scoring engine behind the live leaderboards.
 *
 * Fantasy style scoring is used so that a player is rewarded for how fast he
 * wins, not only for winning:
 *
 * - Win  = +3 points plus the time bonus
 * - Draw = +1 point plus the time bonus
 * - Loss = +0 points plus the time bonus
 *
 * The time bonus is a share of the clock that was left when the game ended,
 * capped at two points, so it scales with the format of the contest.
 */
class RankingService
{
    public const POINTS_WIN = 3.0;
    public const POINTS_DRAW = 1.0;
    public const POINTS_LOSS = 0.0;

    /**
     * The largest time bonus a single game can award.
     */
    public const MAX_TIME_BONUS = 2.0;

    /**
     * The points awarded for a game outcome.
     *
     * @param string $result `White_Win`, `Black_Win` or `Draw`.
     * @return float
     */
    public function basePoints(string $result): float
    {
        return match ($result) {
            'White_Win', 'Black_Win' => self::POINTS_WIN,
            'Draw' => self::POINTS_DRAW,
            default => self::POINTS_LOSS,
        };
    }

    /**
     * The time bonus of a single game.
     *
     * @param int $remainingMillis Milliseconds left on the clock.
     * @param int $baseMillis The starting clock of the game.
     * @return float
     */
    public function timeBonus(int $remainingMillis, int $baseMillis): float
    {
        if ($baseMillis <= 0) {
            return 0.0;
        }

        $ratio = min(1.0, max(0.0, $remainingMillis / $baseMillis));

        return round(self::MAX_TIME_BONUS * $ratio, 1);
    }

    /**
     * The points a single player earns in a single game.
     *
     * @param string $result The game result.
     * @param int $remainingMillis Milliseconds the player had left.
     * @param int $baseMillis The starting clock of the game.
     * @param bool $isWinner Whether the player won the game.
     * @return array{base: float, bonus: float, total: float}
     */
    public function pointsFor(string $result, int $remainingMillis, int $baseMillis, bool $isWinner): array
    {
        $base = $isWinner ? self::POINTS_WIN : ($result === 'Draw' ? self::POINTS_DRAW : self::POINTS_LOSS);
        $bonus = $this->timeBonus($remainingMillis, $baseMillis);

        return [
            'base' => round($base, 1),
            'bonus' => $bonus,
            'total' => round($base + $bonus, 1),
        ];
    }

    /**
     * The points of both players in a finished game.
     *
     * @param string $result `White_Win`, `Black_Win` or `Draw`.
     * @param int $whiteMillis Milliseconds white had left.
     * @param int $blackMillis Milliseconds black had left.
     * @param int $baseMillis The starting clock of the game.
     * @return array{white: array{base: float, bonus: float, total: float}, black: array{base: float, bonus: float, total: float}}
     */
    public function pointsForGame(string $result, int $whiteMillis, int $blackMillis, int $baseMillis): array
    {
        $whiteWon = $result === 'White_Win';
        $blackWon = $result === 'Black_Win';

        return [
            'white' => $this->pointsFor($result, $whiteMillis, $baseMillis, $whiteWon),
            'black' => $this->pointsFor($result, $blackMillis, $baseMillis, $blackWon),
        ];
    }

    /**
     * Add the points of a finished game to the two registrations.
     *
     * @param \App\Model\Entity\TournamentRegistration $white The white player.
     * @param \App\Model\Entity\TournamentRegistration $black The black player.
     * @param string $result `White_Win`, `Black_Win` or `Draw`.
     * @param int $whiteMillis Milliseconds white had left.
     * @param int $blackMillis Milliseconds black had left.
     * @param int $baseMillis The starting clock of the game.
     * @return void
     */
    public function award(
        TournamentRegistration $white,
        TournamentRegistration $black,
        string $result,
        int $whiteMillis,
        int $blackMillis,
        int $baseMillis,
    ): void {
        $points = $this->pointsForGame($result, $whiteMillis, $blackMillis, $baseMillis);

        $this->applyTo($white, $points['white'], $result === 'White_Win' ? 'win' : ($result === 'Draw' ? 'draw' : 'loss'));
        $this->applyTo($black, $points['black'], $result === 'Black_Win' ? 'win' : ($result === 'Draw' ? 'draw' : 'loss'));
    }

    /**
     * Sort the registrations of a contest and stamp the rank on each of them.
     *
     * The order is points, then time bonus, then wins, then rating, then the
     * registration id so that the order is always stable.
     *
     * @param array<int, \App\Model\Entity\TournamentRegistration> $registrations The registrations.
     * @return array<int, \App\Model\Entity\TournamentRegistration> Ranked registrations keyed by rank.
     */
    public function rank(array $registrations): array
    {
        usort($registrations, function (TournamentRegistration $a, TournamentRegistration $b): int {
            $byPoints = $b->points() <=> $a->points();
            if ($byPoints !== 0) {
                return $byPoints;
            }

            $byBonus = $b->bonus() <=> $a->bonus();
            if ($byBonus !== 0) {
                return $byBonus;
            }

            $byWins = (int)$b->get('wins') <=> (int)$a->get('wins');
            if ($byWins !== 0) {
                return $byWins;
            }

            $ratingA = (int)($a->get('user')['rating'] ?? 0);
            $ratingB = (int)($b->get('user')['rating'] ?? 0);
            $byRating = $ratingB <=> $ratingA;
            if ($byRating !== 0) {
                return $byRating;
            }

            return (int)$a->get('id') <=> (int)$b->get('id');
        });

        $ranked = [];
        $rank = 0;
        foreach ($registrations as $registration) {
            $rank++;
            $registration->set('rank', $rank);
            $ranked[$rank] = $registration;
        }

        return $ranked;
    }

    /**
     * Apply a single game result to a registration.
     *
     * @param \App\Model\Entity\TournamentRegistration $registration The registration.
     * @param array{base: float, bonus: float, total: float} $points The points earned.
     * @param string $outcome `win`, `draw` or `loss`.
     * @return void
     */
    protected function applyTo(TournamentRegistration $registration, array $points, string $outcome): void
    {
        $field = match ($outcome) {
            'win' => 'wins',
            'draw' => 'draws',
            default => 'losses',
        };

        $registration->set($field, (int)$registration->get($field) + 1);
        $registration->set('score', round($registration->points() + $points['total'], 1));
        $registration->set('time_bonus', round($registration->bonus() + $points['bonus'], 1));
    }
}
