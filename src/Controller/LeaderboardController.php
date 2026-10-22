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

        $this->set([
            'tournament' => $tournament,
            'standings' => $ordered,
            'ranking' => $ranking,
            'currentUser' => $this->currentUser(),
            'currentRole' => $this->currentUser()?->getRoleName(),
        ]);
    }
}
