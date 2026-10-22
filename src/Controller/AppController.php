<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\User;
use App\Rbac\IdentityHelper;
use Cake\Controller\Controller;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit from it.
 *
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');
        $this->loadComponent('Authorization.Authorization');
    }

    /**
     * The signed in user, if any.
     *
     * @return \App\Model\Entity\User|null
     */
    protected function currentUser(): ?User
    {
        return IdentityHelper::user($this->Authentication->getIdentity());
    }

    /**
     * The primary key of the signed in user.
     *
     * @return int|null
     */
    protected function currentUserId(): ?int
    {
        return IdentityHelper::userId($this->Authentication->getIdentity());
    }

    /**
     * The landing page a role should see after signing in.
     *
     * @param \App\Model\Entity\User|null $user The user.
     * @return string
     */
    protected function homeRouteFor(?User $user): string
    {
        return match ($user?->getRoleName()) {
            'Admin' => '/admin',
            'Organizer' => '/organizer',
            default => '/lobby',
        };
    }

    /**
     * Check that the current user has a permission.
     *
     * If the user lacks the permission, they are redirected to their home route
     * with a flash error message.
     *
     * @param string $permission The capability identifier from Permission.
     * @return \Psr\Http\Message\ResponseInterface|null Returns a redirect response if unauthorized.
     */
    protected function requirePermission(string $permission): ?\Psr\Http\Message\ResponseInterface
    {
        $user = $this->currentUser();
        if ($user === null || !$user->hasPermission($permission)) {
            $this->Flash->error('Your role does not allow access to this area.');
            $home = $this->homeRouteFor($user);
            return $this->redirect($home);
        }

        return null;
    }

    /**
     * The name of the signed in user, ready for the top bar.
     *
     * @return array<string, mixed>
     */
    protected function identityView(): array
    {
        $user = $this->currentUser();

        return [
            'currentUser' => $user,
            'currentRole' => $user?->getRoleName(),
        ];
    }
}
