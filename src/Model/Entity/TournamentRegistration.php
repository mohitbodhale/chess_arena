<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TournamentRegistration Entity
 *
 * @property int $id
 * @property int $tournament_id
 * @property int $user_id
 * @property string|null $score
 * @property int|null $rank
 * @property string|null $prize_won
 * @property \Cake\I18n\DateTime $registered_at
 *
 * @property \App\Model\Entity\Tournament $tournament
 * @property \App\Model\Entity\User $user
 */
class TournamentRegistration extends Entity
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
        'tournament_id' => true,
        'user_id' => true,
        'score' => true,
        'rank' => true,
        'prize_won' => true,
        'registered_at' => true,
        'tournament' => true,
        'user' => true,
    ];
}
