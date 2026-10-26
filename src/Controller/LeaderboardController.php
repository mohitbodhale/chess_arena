<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Tournament\RankingService;
use App\Service\Tournament\TournamentService;

/**
 * The global leaderboard and the standings of a single contest.
 */
class LeaderboardController extends AppController
{
    /**
     * The global leaderboard across every contest.
     *
     * @return void
     */
    public function index(): void
    {
        $registrations = $this->getTableLocator()->get('TournamentRegistrations');

        $query = $registrations->find()
            ->contain(['Users', 'Tournaments'])
            ->orderBy([
                'TournamentRegistrations.score' => 'DESC',
                'TournamentRegistrations.time_bonus' => 'DESC',
                'TournamentRegistrations.wins' => 'DESC',
                'Users.rating' => 'DESC',
            ]);

        $entries = $this->paginate($query, ['limit' => 25]);

        $this->set([
            'entries' => $entries,
            'currentUser' => $this->currentUser(),
            'currentRole' => $this->currentUser()?->getRoleName(),
        ]);
    }

    /**
     * The standings of a single contest.
     *
     * @param string|null $id The contest.
     * @return void
     */
    public function tournament(?string $id = null): void
    {
        $tournament = $this->getTableLocator()->get('Tournaments')->get($id);
        $this->Authorization->authorize($tournament, 'view');

        $service = new TournamentService();
        $ranking = new RankingService();

        $standings = $service->standings((int)$tournament->get('id'));
        $ordered = array_values($standings);

        // Match duration in seconds keyed by registration id.
        $durations = $service->matchDurations((int)$tournament->get('id'));

        // Match duration per player, keyed by user id (for display).
        $matchDurations = [];
        $registrationsById = [];
        foreach ($service->registrations((int)$tournament->get('id')) as $registration) {
            $registrationsById[(int)$registration->get('id')] = $registration;
        }
        foreach ($durations as $regId => $seconds) {
            $registration = $registrationsById[$regId] ?? null;
            if ($registration !== null) {
                $matchDurations[(int)$registration->get('user_id')] = $seconds;
            }
        }

        // Opponent name per player for the single match of this contest.
        $opponents = [];
        $matches = $this->getTableLocator()->get('Matches')
            ->find()
            ->where(['Matches.tournament_id' => $tournament->get('id')])
            ->contain(['WhitePlayers', 'BlackPlayers'])
            ->all()
            ->toList();
        foreach ($matches as $match) {
            $whiteId = (int)$match->get('white_player_id');
            $blackId = (int)$match->get('black_player_id');
            $whiteName = (string)($match->get('white_player')['username'] ?? '—');
            $blackName = (string)($match->get('black_player')['username'] ?? '—');
            $opponents[$whiteId] = $blackName;
            $opponents[$blackId] = $whiteName;
        }

        // Explain why each player is ranked above the next one when points tie.
        $tieBreakBasis = [];
        $previous = null;
        foreach ($ordered as $rank => $registration) {
            if ($previous !== null) {
                $tieBreakBasis[$rank] = $ranking->tieBreakBasis($previous, $registration, $durations);
            }
            $previous = $registration;
        }

        $this->set([
            'tournament' => $tournament,
            'standings' => $ordered,
            'tieBreakBasis' => $tieBreakBasis,
            'matchDurations' => $matchDurations,
            'opponents' => $opponents,
            'ranking' => $ranking,
            'currentUser' => $this->currentUser(),
            'currentRole' => $this->currentUser()?->getRoleName(),
        ]);
    }
}
