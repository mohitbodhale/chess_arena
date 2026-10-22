<?php 
namespace App\Controller;

use App\Controller\AppController;

class TournamentsController extends AppController
{
    public function lobby()
    {
        $tournaments = $this->Tournaments->find('all')
        ->where(['status IN' => ['Upcoming', 'Live']])
        ->order(['start_time' => 'ASC'])
        ->all();
        
        $this->set(compact('tournaments'));
    }
    
    public function join($id = null)
    {
        $this->request->allowMethod(['post']);
        $user = $this->Authentication->getIdentity();
        $tournament = $this->Tournaments->get($id);
        
        if ($tournament->filled_slots >= $tournament->max_slots) {
            $this->Flash->error(__('Tournament slots are full!'));
            return $this->redirect(['action' => 'lobby']);
        }
        
        $userEntity = $this->getTableLocator()->get('Users')->get($user->id);
        if ($userEntity->deposit_balance < $tournament->entry_fee) {
            $this->Flash->error(__('Insufficient wallet balance. Please add funds.'));
            return $this->redirect(['action' => 'lobby']);
        }
        
        // Deduct Entry Fee and Register
        $userEntity->deposit_balance -= $tournament->entry_fee;
        $tournament->filled_slots += 1;
        
        $registrationTable = $this->getTableLocator()->get('TournamentRegistrations');
        $reg = $registrationTable->newEntity([
            'tournament_id' => $tournament->id,
            'user_id' => $user->id
        ]);
        
        if ($this->getTableLocator()->get('Users')->save($userEntity) &&
            $this->Tournaments->save($tournament) &&
            $registrationTable->save($reg)) {
                $this->Flash->success(__('Successfully registered for ' . $tournament->title));
            } else {
                $this->Flash->error(__('Registration failed. Please try again.'));
            }
            
            return $this->redirect(['action' => 'lobby']);
    }
    
    public function arena($id = null)
    {
        $tournament = $this->Tournaments->get($id, [
            'contain' => ['TournamentRegistrations.Users']
        ]);
        $this->set(compact('tournament'));
    }
}


?>