<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Tournament\WalletService;
use Cake\Event\EventInterface;

/**
 * Authentication and the player profile.
 */
class UsersController extends AppController
{
    /**
     * The landing page and the authentication screens are open to everybody.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Authentication->addUnauthenticatedActions(['welcome', 'login', 'register', 'logout']);
    }

    /**
     * The cinematic landing page.
     *
     * @return void
     */
    public function welcome(): void
    {
        $this->viewBuilder()->setLayout('landing');

        $tournaments = $this->getTableLocator()->get('Tournaments');
        $users = $this->getTableLocator()->get('Users');
        $registrations = $this->getTableLocator()->get('TournamentRegistrations');

        $liveCount = $tournaments->find('live')->count();
        $openCount = $tournaments->find('open')->count();
        $playerCount = $users->find()->count();

        $poolQuery = $tournaments->find()->where(['Tournaments.status' => 'Live']);
        $poolQuery->select(['total' => $poolQuery->func()->sum('prize_pool')]);
        $prizePool = round((float)($poolQuery->first()?->get('total') ?? 0), 2);

        $featured = $tournaments->find('open')
            ->contain(['Organizer'])
            ->orderBy(['Tournaments.prize_pool' => 'DESC', 'Tournaments.start_time' => 'ASC'])
            ->limit(6)
            ->all()
            ->toList();

        $topPlayers = [];
        $seen = [];
        $standings = $registrations->find()
            ->contain(['Users'])
            ->orderBy([
                'TournamentRegistrations.score' => 'DESC',
                'TournamentRegistrations.time_bonus' => 'DESC',
                'TournamentRegistrations.wins' => 'DESC',
            ])
            ->limit(80)
            ->all()
            ->toList();
        foreach ($standings as $entry) {
            $userId = (int)$entry->get('user_id');
            if (isset($seen[$userId])) {
                continue;
            }
            $seen[$userId] = true;
            $topPlayers[] = $entry;
            if (count($topPlayers) >= 6) {
                break;
            }
        }

        $this->set([
            'liveCount' => $liveCount,
            'openCount' => $openCount,
            'playerCount' => $playerCount,
            'prizePool' => $prizePool,
            'featured' => $featured,
            'topPlayers' => $topPlayers,
            'currentUser' => $this->currentUser(),
            'currentRole' => $this->currentUser()?->getRoleName(),
        ]);
    }

    /**
     * Sign in.
     *
     * @return \Cake\Http\Response|null
     */
    public function login()
    {
        $this->request->allowMethod(['get', 'post']);

        $result = $this->Authentication->getResult();
        if ($result !== null && $result->isValid()) {
            $user = $this->currentUser();

            if ($user !== null) {
                // Persist the identity so the session keeps the player signed in.
                $this->Authentication->setIdentity($user);

                $users = $this->getTableLocator()->get('Users');
                $user->set('last_login_at', date('Y-m-d H:i:s'));
                $users->save($user);
            }

            return $this->redirect($this->homeRouteFor($user));
        }

        if ($this->request->is('post') && $result !== null && !$result->isValid()) {
            $this->Flash->error(__('Invalid email or password.'));
        }

        $this->set('currentUser', $this->currentUser());
    }

    /**
     * Create a player account.
     *
     * @return \Cake\Http\Response|null
     */
    public function register()
    {
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['role_id'] = 3;
            $data['rating'] = 1200;
            $data['deposit_balance'] = '0.00';
            $data['winnings_balance'] = '0.00';
            $data['status'] = 'Active';

            $user = $users->patchEntity($user, $data);

            if ($users->save($user)) {
                $wallet = new WalletService();
                $wallet->credit(
                    $user,
                    1000.00,
                    'signup_bonus',
                    'Welcome bonus',
                    null,
                    null,
                    false,
                );
                $users->save($user);

                $this->Flash->success(__('Welcome to Chess Arena. Your bonus of ₹1,000 is ready to use.'));

                return $this->redirect(['action' => 'login']);
            }

            $this->Flash->error(__('The account could not be created. Please review the form.'));
        }

        $this->set(compact('user'));
    }

    /**
     * Sign out.
     *
     * @return \Cake\Http\Response|null
     */
    public function logout()
    {
        $this->Authentication->logout();

        $this->Flash->success(__('You have been signed out.'));

        return $this->redirect(['action' => 'welcome']);
    }

    /**
     * The profile of the signed in player.
     *
     * @return void
     */
    public function dashboard(): void
    {
        $user = $this->currentUser();
        $registrations = $this->getTableLocator()->get('TournamentRegistrations');
        $wallet = new WalletService();

        $myContests = $registrations->find()
            ->where(['TournamentRegistrations.user_id' => $this->currentUserId()])
            ->contain(['Tournaments'])
            ->orderBy(['TournamentRegistrations.id' => 'DESC'])
            ->all()
            ->toList();

        $this->set([
            'user' => $user,
            'myContests' => $myContests,
            'ledger' => $wallet->ledger((int)$this->currentUserId()),
            'totalBalance' => $user?->getTotalBalance() ?? 0.0,
        ]);
    }
}
