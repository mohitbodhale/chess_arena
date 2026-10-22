<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Tournament Entity
 *
 * @property int $id
 * @property string $title
 * @property string $category
 * @property string $time_control
 * @property string $entry_fee
 * @property string $prize_pool
 * @property int $max_slots
 * @property int $filled_slots
 * @property string $status
 * @property \Cake\I18n\DateTime $start_time
 * @property \Cake\I18n\DateTime $created_at
 *
 * @property \App\Model\Entity\Match[] $matches
 * @property \App\Model\Entity\TournamentRegistration[] $tournament_registrations
 */
class Tournament extends Entity
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
        'title' => true,
        'category' => true,
        'time_control' => true,
        'entry_fee' => true,
        'prize_pool' => true,
        'max_slots' => true,
        'filled_slots' => true,
        'status' => true,
        'start_time' => true,
        'created_at' => true,
        'matches' => true,
        'tournament_registrations' => true,
    ];
}
