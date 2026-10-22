<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\MatchesTable;
use App\Model\Table\TournamentsTable;
use App\Service\Tournament\MatchService;
use App\Service\Tournament\PayoutService;
use App\Service\Tournament\TournamentService;

/**
 * The workspace of an organizer.
 *
 * An organizer runs their own contests. Every action is scoped to the
 * contests they own and the policies reject anything else.
 */
class OrganizerController extends AppController
{
    /**
     * @var \App\Model\Table\TournamentsTable
     */
    protected $Tournaments;

    /**
     * @var \App\Model\Table\MatchesTable
     */
    protected $Matches;

    /**
     * @var \App\Service\Tournament\TournamentService
     */
    protected $tournamentService;

    /**
     * @var \App\Service\Tournament\PayoutService
     */
    protected $payoutService;

    /**
     * @var \App\Service\Tournament\MatchService
     */
    protected $matchService;

    /**
     * Initialize method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->Tournaments = $this->getTableLocator()->get('Tournaments');
        $this->Matches = $this->getTableLocator()->get('Matches');
        $this->tournamentService = new TournamentService();
        $this->payoutService = new PayoutService();
        $this->matchService = new MatchService();
    }

    /**
     * The overview of the contests the organizer runs.
     *
     * @return void
     */
    public function dashboard(): void
    {
        $userId = $this->currentUserId();

        $contests = $this->Tournaments->find()
            ->where(['Tournaments.organizer_id' => $userId])
            ->orderBy(['Tournaments.id' => 'DESC'])
            ->all()
            ->toList();

        $liveCount = 0;
        $seats = 0;
        $pool = 0.0;
        foreach ($contests as $contest) {
            if ($contest->get('status') === 'Live') {
                $liveCount++;
            }
            $seats += (int)$contest->get('filled_slots');
            $pool += (float)$contest->get('prize_pool');
        }

        $matches = $this->Matches->find()
            ->where(['Matches.tournament_id IN' => array_map(
                fn ($contest) => $contest->get('id'),
                $contests,
            ) ?: [0]])
            ->contain(['Tournaments', 'WhitePlayers', 'BlackPlayers'])
            ->orderBy(['Matches.id' => 'DESC'])
            ->limit(10)
            ->all()
            ->toList();

        $this->set([
            'contests' => $contests,
            'liveCount' => $liveCount,
            'seats' => $seats,
            'pool' => round($pool, 2),
            'matches' => $matches,
            'currentUser' => $this->currentUser(),
            'currentRole' => $this->currentUser()?->getRoleName(),
        ]);
    }

    /**
     * The contests of the organizer.
     *
     * @return void
     */
    public function tournaments(): void
    {
        $query = $this->Tournaments->find()
            ->where(['Tournaments.organizer_id' => $this->currentUserId()])
            ->orderBy(['Tournaments.id' => 'DESC']);

        $contests = $this->paginate($query, ['limit' => 15]);

        $this->set([
            'contests' => $contests,
            'currentUser' => $this->currentUser(),
            'currentRole' => $this->currentUser()?->getRoleName(),
        ]);
    }

    /**
     * The tournament creator.
     *
     * @return \Cake\Http\Response|null
     */
    public function addTournament()
    {
        $tournament = $this->Tournaments->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            // The platform fee belongs to the administration.
            unset($data['platform_fee']);
            $data['prize_breakdown'] = $this->payoutService->templateRows(
                (string)($data['prize_template'] ?? 'top3'),
            );

            try {
                $tournament = $this->tournamentService->create($data, $this->currentUserId());
                $this->Flash->success(sprintf('The contest "%s" is live for entries.', $tournament->get('title')));

                return $this->redirect(['action' => 'slots', $tournament->get('id')]);
            } catch (\RuntimeException $exception) {
                $this->Flash->error($exception->getMessage());
            }
        }

        $this->set([
            'tournament' => $tournament,
            'templates' => $this->payoutService->templates(),
            'currentUser' => $this->currentUser(),
            'currentRole' => $this->currentUser()?->getRoleName(),
        ]);
    }

    /**
     * Edit a contest of the organizer.
     *
     * @param string|null $id The contest.
     * @return \Cake\Http\Response|null
     */
    public function editTournament(?string $id = null)
    {
        $tournament = $this->Tournaments->get($id);
        $this->Authorization->authorize($tournament, 'update');

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            // The platform fee belongs to the administration.
            unset($data['platform_fee']);
            if (!empty($data['prize_template'])) {
                $data['prize_breakdown'] = $this->payoutService->templateRows((string)$data['prize_template']);
            }
            $tournament = $this->Tournaments->patchEntity($tournament, $data);
            if ($this->Tournaments->save($tournament)) {
                $this->Flash->success('The contest was updated.');

                return $this->redirect(['action' => 'tournaments']);
            }
            $this->Flash->error('The contest could not be updated.');
        }

        $this->set([
            'tournament' => $tournament,
            'templates' => $this->payoutService->templates(),
            'currentUser' => $this->currentUser(),
            'currentRole' => $this->currentUser()?->getRoleName(),
        ]);
    }

    /**
     * The slot manager of a contest.
     *
     * @param string|null $id The contest.
     * @return void
     */
    public function slots(?string $id = null): void
    {
        $tournament = $this->Tournaments->get($id);
        $this->Authorization->authorize($tournament, 'manage');

        $registrations = $this->tournamentService->registrations((int)$tournament->get('id'));
        $matches = $this->Matches->find()
            ->where(['Matches.tournament_id' => $tournament->get('id')])
            ->contain(['WhitePlayers', 'BlackPlayers'])
            ->all()
            ->toList();

        $this->set([
            'tournament' => $tournament,
            'registrations' => $registrations,
            'matches' => $matches,
            'prizeRows' => $this->payoutService->rows($tournament),
            'currentUser' => $this->currentUser(),
            'currentRole' => $this->currentUser()?->getRoleName(),
        ]);
    }

    /**
     * Start a contest and pair the players.
     *
     * @param string|null $id The contest.
     * @return \Cake\Http\Response|null
     */
    public function start(?string $id = null)
    {
        $this->request->allowMethod(['post']);

        $tournament = $this->Tournaments->get($id);
        $this->Authorization->authorize($tournament, 'manage');

        $created = $this->matchService->start($tournament);
        $this->Flash->success(sprintf('%d matches were created for %s.', $created, $tournament->get('title')));

        return $this->redirect(['action' => 'slots', $tournament->get('id')]);
    }

    /**
     * Settle a contest.
     *
     * @param string|null $id The contest.
     * @return \Cake\Http\Response|null
     */
    public function settle(?string $id = null)
    {
        $this->request->allowMethod(['post']);

        $tournament = $this->Tournaments->get($id);
        $this->Authorization->authorize($tournament, 'manage');

        $result = $this->tournamentService->settle($tournament, $this->currentUserId());
        $this->Flash->success(sprintf(
            'The contest was settled. ₹%s was paid out and ₹%s went to the platform.',
            number_format(array_sum($result['payouts']), 2),
            number_format($result['platform_revenue'], 2),
        ));

        return $this->redirect(['action' => 'slots', $tournament->get('id')]);
    }

    /**
     * Remove a player from a contest.
     *
     * @param string|null $id The contest.
     * @param string|null $userId The player.
     * @return \Cake\Http\Response|null
     */
    public function removePlayer(?string $id = null, ?string $userId = null)
    {
        $this->request->allowMethod(['post']);

        $tournament = $this->Tournaments->get($id);
        $this->Authorization->authorize($tournament, 'manage');

        $registrations = $this->getTableLocator()->get('TournamentRegistrations');
        $registration = $registrations->find()
            ->where([
                'tournament_id' => $tournament->get('id'),
                'user_id' => $userId,
            ])
            ->first();

        if ($registration === null) {
            $this->Flash->error('That player is not registered for this contest.');

            return $this->redirect(['action' => 'slots', $tournament->get('id')]);
        }

        $registrations->delete($registration);
        $tournament->set('filled_slots', max(0, (int)$tournament->get('filled_slots') - 1));
        $this->Tournaments->save($tournament);

        $this->Flash->success('The player was removed from the contest.');

        return $this->redirect(['action' => 'slots', $tournament->get('id')]);
    }
}
