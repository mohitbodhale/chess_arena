<?php 
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\EventInterface;

class UsersController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['login', 'register', 'welcome']);
    }

    public function welcome()
    {
        $this->viewBuilder()->setLayout('landing');
        // Render Cinematic Welcome Page
    }

    public function login()
    {
        $this->request->allowMethod(['get', 'post']);
        $result = $this->Authentication->getResult();
        if ($result && $result->isValid()) {
            $user = $this->Authentication->getIdentity();
            if ($user->role_id === 1) {
                return $this->redirect(['controller' => 'Admin', 'action' => 'dashboard']);
            }
            return $this->redirect(['controller' => 'Tournaments', 'action' => 'lobby']);
        }
        if ($this->request->is('post') && !$result->isValid()) {
            $this->Flash->error(__('Invalid username or password'));
        }
    }

    public function register()
    {
        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            $user->role_id = 3; // Player
            $user->deposit_balance = 1000.00; // Registration Bonus
            if ($this->Users->save($user)) {
                $this->Flash->success(__('Registration successful! Welcome bonus of ₹1,000 credited.'));
                return $this->redirect(['action' => 'login']);
            }
            $this->Flash->error(__('Registration failed. Please try again.'));
        }
        $this->set(compact('user'));
    }

    public function logout()
    {
        $result = $this->Authentication->getResult();
        if ($result && $result->isValid()) {
            $this->Authentication->logout();
            return $this->redirect(['action' => 'welcome']);
        }
    }
}
?>