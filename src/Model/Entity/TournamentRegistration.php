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
 * @property string $score
 * @property string $time_bonus
 * @property int $wins
 * @property int $draws
 * @property int $losses
 * @property int $rank
 * @property string $prize_won
 * @property string $status
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
        'time_bonus' => true,
        'wins' => true,
        'draws' => true,
        'losses' => true,
        'rank' => true,
        'prize_won' => true,
        'status' => true,
        'registered_at' => true,
        'tournament' => true,
        'user' => true,
    ];

    /**
     * Total points collected by the player.
     *
     * @return float
     */
    public function points(): float
    {
        return (float)$this->get('score');
    }

    /**
     * Time bonus points collected by the player.
     *
     * @return float
     */
    public function bonus(): float
    {
        return (float)$this->get('time_bonus');
    }

    /**
     * Number of matches the player has taken part in.
     *
     * @return int
     */
    public function played(): int
    {
        return (int)$this->get('wins') + (int)$this->get('draws') + (int)$this->get('losses');
    }
}
