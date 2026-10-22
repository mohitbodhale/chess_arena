<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property int $role_id
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string|null $deposit_balance
 * @property string|null $winnings_balance
 * @property int|null $rating
 * @property \Cake\I18n\DateTime $created_at
 *
 * @property \App\Model\Entity\Role $role
 * @property \App\Model\Entity\TournamentRegistration[] $tournament_registrations
 */
class User extends Entity
{
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
        'deposit_balance' => true,
        'winnings_balance' => true,
        'rating' => true,
        'created_at' => true,
        'role' => true,
        'tournament_registrations' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<string>
     */
    protected array $_hidden = [
        'password',
    ];
}
