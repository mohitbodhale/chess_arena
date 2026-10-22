<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\MatchesTable;
use App\Model\Table\TournamentsTable;
use App\Model\Table\UsersTable;
use App\Model\Table\WalletTransactionsTable;
use App\Rbac\RoleRegistry;
use App\Service\Tournament\MatchService;
use App\Service\Tournament\PayoutService;
use App\Service\Tournament\SimulationService;
use App\Service\Tournament\TimeControl;
use App\Service\Tournament\TournamentService;
use App\Service\Tournament\WalletService;


/**
 * The control room of the arena.
 *
 * Every action is guarded by the role based access control layer, so only an
 * admin reaches this controller.
 */
class AdminController extends AppController
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
     * @var \App\Model\Table\UsersTable
     */
    protected $Users;

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
        $this->Users = $this->getTableLocator()->get('Users');
        $this->tournamentService = new TournamentService();
        $this->payoutService = new PayoutService();
        $this->matchService = new MatchService();
    }

    /**
     * The dashboard with the key figures of the arena.
     *
     * @return void
     */
    public function dashboard(): void
    {
        $registrations = $this->getTableLocator()->get('TournamentRegistrations');
        $wallet = new WalletService();

        $playerCount = $this->Users->find()->count();
        $contestCount = $this->Tournaments->find()->count();
        $liveMatches = $this->Matches->find('live')->count();

        $poolQuery = $this->Tournaments->find();
        $poolQuery->select(['total' => $poolQuery->func()->sum('prize_pool')]);
        $prizePool = round((float)($poolQuery->first()?->get('total') ?? 0), 2);

        $feeQuery = $this->getTableLocator()->get('WalletTransactions')->find();
        $feeQuery->select(['total' => $feeQuery->func()->sum('amount')]);
        $feeQuery->where(['WalletTransactions.type' => 'entry_fee']);
        $collected = round((float)($feeQuery->first()?->get('total') ?? 0), 2);

        $revenueQuery = $this->getTableLocator()->get('WalletTransactions')->find();
        $revenueQuery->select(['total' => $revenueQuery->func()->sum('amount')]);
        $revenueQuery->where(['WalletTransactions.type' => 'platform_commission']);
        $platformRevenue = round((float)($revenueQuery->first()?->get('total') ?? 0), 2);


        $ledger = $wallet->auditLedger(12);

        $categoryChart = ['labels' => [], 'data' => []];
        foreach (TimeControl::CATEGORIES as $key => $meta) {
            $categoryChart['labels'][] = $meta['label'];
            $categoryChart['data'][] = $this->Tournaments->find()
                ->where(['Tournaments.category' => $key])
                ->count();
        }

        $statusChart = ['labels' => [], 'data' => []];
        foreach (['Upcoming', 'Live', 'Completed'] as $status) {
            $statusChart['labels'][] = $status;
            $statusChart['data'][] = $this->Tournaments->find()
                ->where(['Tournaments.status' => $status])
                ->count();
        }

        $recentContests = $this->Tournaments->find()
            ->contain(['Organizer'])
            ->orderBy(['Tournaments.id' => 'DESC'])
            ->limit(6)
            ->all()
            ->toList();

        $liveGames = $this->Matches->find('live')
            ->contain(['Tournaments', 'WhitePlayers', 'BlackPlayers'])
            ->orderBy(['Matches.id' => 'DESC'])
            ->limit(8)
            ->all()
            ->toList();

        $recentResults = $this->Matches->find()
            ->where(['Matches.status' => 'Completed'])
            ->contain(['Tournaments', 'WhitePlayers', 'BlackPlayers'])
            ->orderBy(['Matches.id' => 'DESC'])
            ->limit(8)
            ->all()
            ->toList();

        $this->set([
            'playerCount' => $playerCount,
            'contestCount' => $contestCount,
            'liveMatches' => $liveMatches,
            'prizePool' => $prizePool,
            'collected' => $collected,
            'platformRevenue' => $platformRevenue,
            'registrationCount' => $registrations->find()->count(),
            'categoryChart' => $categoryChart,
            'statusChart' => $statusChart,
            'ledger' => $ledger,
            'recentContests' => $recentContests,
            'liveGames' => $liveGames,
            'recentResults' => $recentResults,
            'currentUser' => $this->currentUser(),
            'currentRole' => $this->currentUser()?->getRoleName(),
        ]);
    }

    /**
     * Every contest of the arena.
     *
     * @return void
     */
    public function tournaments(): void
    {
        $query = $this->Tournaments->find()->contain(['Organizer']);

        $category = (string)($this->request->getQuery('category') ?? '');
        $status = (string)($this->request->getQuery('status') ?? '');
        if ($category !== '') {
            $query->where(['Tournaments.category' => $category]);
        }
        if ($status !== '') {
            $query->where(['Tournaments.status' => $status]);
        }

        $query->orderBy(['Tournaments.id' => 'DESC']);

        $contests = $this->paginate($query, ['limit' => 15]);

        $this->set([
            'contests' => $contests,
            'category' => $category,
            'status' => $status,
            'categories' => TimeControl::categories(),
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
            $organizerId = (int)($data['organizer_id'] ?? 0);
            if ($organizerId <= 0) {
                $organizerId = (int)$this->currentUserId();
            }
            $data['organizer_id'] = $organizerId;
            $data['prize_breakdown'] = $this->payoutService->templateRows(
                (string)($data['prize_template'] ?? 'top3'),
            );

            try {
                $tournament = $this->tournamentService->create($data, (int)$data['organizer_id']);
                $this->Flash->success(sprintf('The contest "%s" is live for entries.', $tournament->get('title')));

                return $this->redirect(['action' => 'slots', $tournament->get('id')]);
            } catch (\RuntimeException $exception) {
                $this->Flash->error($exception->getMessage());
            }
        }

        $this->set([
            'tournament' => $tournament,
            'templates' => $this->payoutService->templates(),
            'organizers' => $this->Users->find()->where(['Users.role_id IN' => [1, 2]])->all(),
            'currentUser' => $this->currentUser(),
            'currentRole' => $this->currentUser()?->getRoleName(),
        ]);
    }

    /**
     * Edit the configuration of a contest.
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
            'organizers' => $this->Users->find()->where(['Users.role_id IN' => [1, 2]])->all(),
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
        $tournament = $this->Tournaments->get($id, ['contain' => ['Organizer']]);
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
     * Settle a contest and pay the prize money.
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
            'The contest was settled. ₹%s was paid out, ₹%s is still unclaimed and ₹%s is platform revenue.',
            number_format(array_sum($result['payouts']), 2),
            number_format($result['unclaimed'], 2),
            number_format($result['platform_revenue'], 2),
        ));

        return $this->redirect(['action' => 'slots', $tournament->get('id')]);
    }

    /**
     * The wallet auditor.
     *
     * @return void
     */
    public function wallet(): void
    {
        $wallet = new WalletService();
        $query = $this->getTableLocator()->get('WalletTransactions')->find('ledger')
            ->contain(['Users', 'Tournaments']);

        $userId = $this->request->getQuery('user_id');
        if ($userId !== null && $userId !== '') {
            $query->where(['WalletTransactions.user_id' => (int)$userId]);
        }
        $type = (string)($this->request->getQuery('type') ?? '');
        if ($type !== '') {
            $query->where(['WalletTransactions.type' => $type]);
        }

        $entries = $this->paginate($query, ['limit' => 25]);

        $this->set([
            'entries' => $entries,
            'players' => $this->Users->find()->orderBy(['Users.username' => 'ASC'])->all(),
            'types' => WalletTransactionsTable::TYPES,
            'currentUser' => $this->currentUser(),
            'currentRole' => $this->currentUser()?->getRoleName(),
        ]);
    }

    /**
     * Credit or debit the wallet of a player.
     *
     * @return \Cake\Http\Response|null
     */
    public function adjustWallet()
    {
        $this->request->allowMethod(['post']);

        $users = $this->getTableLocator()->get('Users');
        $user = $users->get($this->request->getData('user_id'));
        $this->Authorization->authorize($user, 'adjustWallet');

        $amount = round((float)$this->request->getData('amount'), 2);
        $description = (string)($this->request->getData('description') ?? 'Manual adjustment');
        $toWinnings = (bool)$this->request->getData('to_winnings');

        if ($amount === 0.0) {
            $this->Flash->error('The amount must not be zero.');

            return $this->redirect(['action' => 'wallet']);
        }

        $wallet = new WalletService();
        if ($wallet->adjust($user, $amount, $description, $this->currentUserId(), $toWinnings)) {
            $users->save($user);
            $this->Flash->success(sprintf(
                '%s was applied to the wallet of %s.',
                $amount > 0 ? '₹' . number_format($amount, 2) : '-₹' . number_format(abs($amount), 2),
                $user->get('username'),
            ));
        } else {
            $this->Flash->error('The wallet holds not enough balance for this debit.');
        }

        return $this->redirect(['action' => 'wallet']);
    }

    /**
     * The match simulation tool.
     *
     * @param string|null $id The match.
     * @return void
     */
    public function simulate(?string $id = null): void
    {
        $query = $this->Matches->find()
            ->contain(['Tournaments', 'WhitePlayers', 'BlackPlayers'])
            ->orderBy(['Matches.id' => 'DESC']);

        $match = null;
        if ($id !== null) {
            $match = $this->Matches->get($id, [
                'contain' => ['Tournaments', 'WhitePlayers', 'BlackPlayers'],
            ]);
            $this->Authorization->authorize($match, 'simulate');
        }

        $this->set([
            'matches' => $query->limit(25)->all(),
            'match' => $match,
            'currentUser' => $this->currentUser(),
            'currentRole' => $this->currentUser()?->getRoleName(),
        ]);
    }

    /**
     * Run a simulation on a match.
     *
     * @param string|null $id The match.
     * @return \Cake\Http\Response|null
     */
    public function runSimulation(?string $id = null)
    {
        $this->request->allowMethod(['post']);

        $match = $this->Matches->get($id, ['contain' => ['Tournaments']]);
        $this->Authorization->authorize($match, 'simulate');

        $simulation = new SimulationService();
        $forced = $this->request->getData('result');
        $result = $simulation->simulate(
            $match,
            is_string($forced) && $forced !== '' ? $forced : null,
        );

        if ($result['ok']) {
            $this->Flash->success($result['message']);
        } else {
            $this->Flash->error($result['message']);
        }

        return $this->redirect(['action' => 'simulate', $match->get('id')]);
    }

    /**
     * The user directory.
     *
     * @return void
     */
    public function users(): void
    {
        $query = $this->Users->find()->contain(['Roles']);

        $role = (string)($this->request->getQuery('role') ?? '');
        if ($role !== '') {
            $query->where(['Roles.name' => $role]);
        }

        $query->orderBy(['Users.id' => 'DESC']);

        $users = $this->paginate($query, ['limit' => 20]);

        $this->set([
            'users' => $users,
            'role' => $role,
            'currentUser' => $this->currentUser(),
            'currentRole' => $this->currentUser()?->getRoleName(),
        ]);
    }

    /**
     * Change the role of a user.
     *
     * @return \Cake\Http\Response|null
     */
    public function setRole()
    {
        $this->request->allowMethod(['post']);

        $users = $this->getTableLocator()->get('Users');
        $user = $users->get($this->request->getData('user_id'));
        $this->Authorization->authorize($user, 'manage');

        $roleId = (int)$this->request->getData('role_id');
        if (!in_array($roleId, RoleRegistry::ID_MAP, true)) {
            $this->Flash->error('Unknown role.');

            return $this->redirect(['action' => 'users']);
        }

        $user->set('role_id', $roleId);
        if ($users->save($user)) {
            $this->Flash->success(sprintf('The role of %s was changed.', $user->get('username')));
        } else {
            $this->Flash->error('The role could not be changed.');
        }

        return $this->redirect(['action' => 'users']);
    }
}
