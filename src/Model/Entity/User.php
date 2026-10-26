<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Rbac\RoleRegistry;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property int $role_id
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string|null $plain_password
 * @property string|null $deposit_balance
 * @property string|null $winnings_balance
 * @property int|null $rating
 * @property string $status
 * @property \Cake\I18n\DateTime|null $last_login_at
 * @property \Cake\I18n\DateTime $created_at
 *
 * @property \App\Model\Entity\Role $role
 * @property \App\Model\Entity\TournamentRegistration[] $tournament_registrations
 * @property \App\Model\Entity\WalletTransaction[] $wallet_transactions
 */
class User extends Entity
{
    /**
     * Hash a plain password before it is stored.
     *
     * @param string|null $password The plain password.
     * @return string|null The hash.
     */
    protected function _setPassword(?string $password): ?string
    {
        if ($password === null || $password === '') {
            return null;
        }

        $this->set('plain_password', $password, ['guard' => false]);

        return (new DefaultPasswordHasher())->hash($password);
    }

    /**
     * Whether a plain password matches the stored hash.
     *
     * @param string $password The plain password.
     * @return bool
     */
    public function checkPassword(string $password): bool
    {
        $hash = (string)$this->get('password');
        if ($hash === '') {
            return false;
        }

        return (new DefaultPasswordHasher())->check($password, $hash);
    }

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'role_id' => true,
        'username' => true,
        'email' => true,
        'password' => true,
        'plain_password' => true,
        'deposit_balance' => true,
        'winnings_balance' => true,
        'rating' => true,
        'status' => true,
        'last_login_at' => true,
        'created_at' => true,
        'role' => true,
        'tournament_registrations' => true,
        'wallet_transactions' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<string>
     */
    protected array $_hidden = [
    ];

    /**
     * The name of the role this user belongs to.
     *
     * @return string|null
     */
    public function getRoleName(): ?string
    {
        return RoleRegistry::nameForUser($this);
    }

    /**
     * The capabilities granted to this user.
     *
     * @return array<int, string>
     */
    public function getPermissions(): array
    {
        return RoleRegistry::permissionsFor($this->getRoleName());
    }

    /**
     * Whether this user holds a capability.
     *
     * @param string $permission Capability identifier.
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return RoleRegistry::can($this->getRoleName(), $permission);
    }

    /**
     * The total money held by this user.
     *
     * @return float
     */
    public function getTotalBalance(): float
    {
        return (float)$this->get('deposit_balance') + (float)$this->get('winnings_balance');
    }

    /**
     * Initials used by the avatar bubble in the top bar.
     *
     * @return string
     */
    public function getInitials(): string
    {
        $username = (string)$this->get('username');
        $parts = preg_split('/[\s_\-.]+/', trim($username)) ?: [];
        $initials = '';
        foreach ($parts as $part) {
            if ($part !== '') {
                $initials .= strtoupper($part[0]);
            }
            if (strlen($initials) === 2) {
                break;
            }
        }

        return $initials !== '' ? $initials : strtoupper(substr($username, 0, 2));
    }
}
