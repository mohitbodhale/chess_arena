<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Tournament;
use App\Rbac\Permission;
use App\Service\Tournament\PayoutService;
use App\Service\Tournament\TournamentService;

/**
 * The Dream11 style tournament lobby and the contest detail pages.
 */
class TournamentsController extends AppController
{
    /**
     * The contests and the services the lobby needs.
     *
     * @var \App\Model\Table\TournamentsTable
     */
    protected $Tournaments;

    /**
     * @var \App\Service\Tournament\TournamentService
     */
    protected $tournamentService;

    /**
     * @var \App\Service\Tournament\PayoutService
     */
    protected $payoutService;

    /**
     * Initialize method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->Tournaments = $this->getTableLocator()->get('Tournaments');
        $this->tournamentService = new TournamentService();
        $this->payoutService = new PayoutService();
    }

    /**
     * The lobby with the live category filters.
     *
     * @return void
     */
    public function lobby(): void
    {
        $category = (string)($this->request->getQuery('category') ?? 'All');
        $status = (string)($this->request->getQuery('status') ?? 'All');
        $search = trim((string)($this->request->getQuery('q') ?? ''));

        $query = $this->Tournaments->find()->contain(['Organizer']);

        if ($category !== 'All' && $category !== '') {
            $query->where(['Tournaments.category' => $category]);
        }
        if ($status === 'Open') {
            $query->where(['Tournaments.status IN' => ['Upcoming', 'Live']]);
        } elseif ($status === 'Live') {
            $query->where(['Tournaments.status' => 'Live']);
        } elseif ($status === 'Completed') {
            $query->where(['Tournaments.status' => 'Completed']);
        }
        if ($search !== '') {
            $query->where([
                'OR' => [
                    'Tournaments.title LIKE' => '%' . $search . '%',
                    'Tournaments.category LIKE' => '%' . $search . '%',
                ],
            ]);
        }

        $query->orderBy([
            'Tournaments.status' => 'ASC',
            'Tournaments.prize_pool' => 'DESC',
            'Tournaments.start_time' => 'ASC',
        ]);

        $contests = $this->paginate($query, ['limit' => 12]);

        $myContestIds = [];
        $userId = $this->currentUserId();
        if ($userId !== null) {
            $registrations = $this->getTableLocator()->get('TournamentRegistrations');
            $myContestIds = $registrations->find()
                ->select(['tournament_id' => $registrations->aliasField('tournament_id')])
                ->where(['TournamentRegistrations.user_id' => $userId])
                ->all()
                ->extract('tournament_id')
                ->toList();
        }

        $categories = ['' => 'All'];
        foreach (\App\Service\Tournament\TimeControl::CATEGORIES as $key => $meta) {
            $categories[$key] = $meta['label'];
        }
        $categories['All'] = 'All formats';

        $this->set([
            'contests' => $contests,
            'category' => $category,
            'status' => $status,
            'search' => $search,
            'categories' => $categories,
            'myContestIds' => $myContestIds,
            'myMatches' => $this->playerMatches(),
            'canJoin' => $this->currentUser()?->hasPermission(Permission::TOURNAMENT_JOIN) ?? false,
        ]);
    }

    /**
     * The matches of the signed in player, keyed by the contest they belong to.
     *
     * A running game is preferred over a finished one, so a player with both
     * always gets the board that still needs a move.
     *
     * @param array<int, int> $tournamentIds Limit the lookup to these contests.
     * @return array<int, \App\Model\Entity\ArenaMatch>
     */
    protected function playerMatches(array $tournamentIds = []): array
    {
        $userId = $this->currentUserId();
        if ($userId === null) {
            return [];
        }

        $matches = $this->getTableLocator()->get('Matches')
            ->find('forPlayer', ['userId' => $userId]);

        if ($tournamentIds !== []) {
            $matches->where(['Matches.tournament_id IN' => $tournamentIds]);
        }

        $matches->orderBy([
            new \Cake\Database\Expression\QueryExpression('CASE Matches.status WHEN "Live" THEN 0 WHEN "Pending" THEN 1 ELSE 2 END'),
            'Matches.id',
        ]);

        $byContest = [];
        foreach ($matches->all() as $match) {
            $contestId = (int)$match->get('tournament_id');
            if (!isset($byContest[$contestId]) || $match->get('status') === 'Live') {
                $byContest[$contestId] = $match;
            }
        }

        return $byContest;
    }

    /**
     * The detail page of a single contest.
     *
     * @param string|null $id The contest.
     * @return void
     */
    public function view(?string $id = null): void
    {
        $tournament = $this->Tournaments->get($id, ['contain' => ['Organizer']]);

        $this->Authorization->authorize($tournament, 'view');

        $registrations = $this->tournamentService->registrations((int)$tournament->get('id'));
        $standings = $this->tournamentService->standings((int)$tournament->get('id'));

        $userId = $this->currentUserId();
        $isRegistered = false;
        foreach ($registrations as $registration) {
            if ((int)$registration->get('user_id') === $userId) {
                $isRegistered = true;

                break;
            }
        }

        $playerMatches = $this->playerMatches([(int)$tournament->get('id')]);

        // Match duration in seconds keyed by registration id.
        $durations = $this->tournamentService->matchDurations((int)$tournament->get('id'));

        // Match duration per player, keyed by user id (for display).
        $matchDurations = [];
        $registrationsById = [];
        foreach ($registrations as $registration) {
            $registrationsById[(int)$registration->get('id')] = $registration;
        }
        foreach ($durations as $regId => $seconds) {
            $registration = $registrationsById[$regId] ?? null;
            if ($registration !== null) {
                $matchDurations[(int)$registration->get('user_id')] = $seconds;
            }
        }

        // Explain why each player is ranked above the next one when points tie.
        $tieBreakBasis = [];
        $previous = null;
        foreach ($standings as $rank => $registration) {
            if ($previous !== null) {
                $tieBreakBasis[$rank] = $this->tournamentService->rankingTieBreakBasis($previous, $registration, (int)$tournament->get('id'));
            }
            $previous = $registration;
        }

        $this->set([
            'tournament' => $tournament,
            'registrations' => $registrations,
            'standings' => $standings,
            'tieBreakBasis' => $tieBreakBasis,
            'matchDurations' => $matchDurations,
            'prizeRows' => $this->payoutService->rows($tournament),
            'isRegistered' => $isRegistered,
            'myMatch' => $playerMatches[(int)$tournament->get('id')] ?? null,
            'canJoin' => $this->Authorization->can($tournament, 'join'),
            'canManage' => $this->Authorization->can($tournament, 'manage'),
        ]);
    }

    /**
     * Buy a seat.
     *
     * @param string|null $id The contest.
     * @return \Cake\Http\Response|null
     */
    public function join(?string $id = null)
    {
        $this->request->allowMethod(['post']);

        $tournament = $this->Tournaments->get($id);
        $this->Authorization->authorize($tournament, 'join');

        $user = $this->currentUser();
        if ($user === null) {
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $result = $this->tournamentService->join($tournament, $user, $this->currentUserId());

        if ($result['ok']) {
            $this->Flash->success($result['message']);
        } else {
            $this->Flash->error($result['message']);
        }

        return $this->redirect(['action' => 'view', $tournament->get('id')]);
    }

    /**
     * Give a seat back.
     *
     * @param string|null $id The contest.
     * @return \Cake\Http\Response|null
     */
    public function leave(?string $id = null)
    {
        $this->request->allowMethod(['post']);

        $tournament = $this->Tournaments->get($id);
        $this->Authorization->authorize($tournament, 'view');

        $user = $this->currentUser();
        if ($user === null) {
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $result = $this->tournamentService->leave($tournament, $user, $this->currentUserId());

        if ($result['ok']) {
            $this->Flash->success($result['message']);
        } else {
            $this->Flash->error($result['message']);
        }

        return $this->redirect(['action' => 'view', $tournament->get('id')]);
    }

    /**
     * The contests the signed in player is registered for.
     *
     * @return void
     */
    public function mine(): void
    {
        $registrations = $this->getTableLocator()->get('TournamentRegistrations');

        $contests = $registrations->find()
            ->where(['TournamentRegistrations.user_id' => $this->currentUserId()])
            ->contain(['Tournaments'])
            ->orderBy(['TournamentRegistrations.id' => 'DESC'])
            ->all()
            ->toList();

        $contestIds = [];
        foreach ($contests as $registration) {
            $contest = $registration->get('tournament');
            if ($contest !== null) {
                $contestIds[] = (int)$contest->get('id');
            }
        }

        $this->set([
            'contests' => $contests,
            'myMatches' => $this->playerMatches($contestIds),
        ]);
    }

    /**
     * The contests that are waiting for a player.
     *
     * @param string|null $id The contest.
     * @return void
     */
    public function seats(?string $id = null): void
    {
        $tournament = $this->Tournaments->get($id, ['contain' => ['Organizer']]);
        $this->Authorization->authorize($tournament, 'view');

        $registrations = $this->tournamentService->registrations((int)$tournament->get('id'));

        $this->set([
            'tournament' => $tournament,
            'registrations' => $registrations,
            'prizeRows' => $this->payoutService->rows($tournament),
        ]);
    }
}
