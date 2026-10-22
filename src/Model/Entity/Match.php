<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Match Entity
 *
 * @property int $id
 * @property int $tournament_id
 * @property int $white_player_id
 * @property int $black_player_id
 * @property string|null $result
 * @property string|null $pgn
 * @property \Cake\I18n\DateTime $created_at
 *
 * @property \App\Model\Entity\Tournament $tournament
 */
class Match extends Entity
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
        'white_player_id' => true,
        'black_player_id' => true,
        'result' => true,
        'pgn' => true,
        'created_at' => true,
        'tournament' => true,
    ];
}
