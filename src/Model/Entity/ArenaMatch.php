<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Service\Chess\ChessEngine;
use Cake\ORM\Entity;

/**
 * Arena Match Entity
 *
 * @property int $id
 * @property int $tournament_id
 * @property int $white_player_id
 * @property int $black_player_id
 * @property string|null $result
 * @property string|null $termination
 * @property string|null $pgn
 * @property string $status
 * @property string $fen
 * @property array<int, string> $moves
 * @property int $white_time_ms
 * @property int $black_time_ms
 * @property int $increment_ms
 * @property int $round_number
 * @property \Cake\I18n\DateTime|null $started_at
 * @property \Cake\I18n\DateTime|null $ended_at
 * @property \Cake\I18n\DateTime $created_at
 *
 * @property \App\Model\Entity\Tournament $tournament
 * @property \App\Model\Entity\User $white_player
 * @property \App\Model\Entity\User $black_player
 */
class ArenaMatch extends Entity
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
        'termination' => true,
        'pgn' => true,
        'status' => true,
        'fen' => true,
        'moves' => true,
        'white_time_ms' => true,
        'black_time_ms' => true,
        'increment_ms' => true,
        'round_number' => true,
        'started_at' => true,
        'ended_at' => true,
        'created_at' => true,
        'tournament' => true,
        'white_player' => true,
        'black_player' => true,
    ];

    /**
     * Decode the JSON encoded move list.
     *
     * @param mixed $value Raw column value.
     * @return array<int, string>
     */
    protected function _getMoves(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }
        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    /**
     * Whether the match is still being played.
     *
     * @return bool
     */
    protected function _getIsLive(): bool
    {
        return $this->get('status') === 'Live';
    }

    /**
     * Number of half moves played so far.
     *
     * @return int
     */
    protected function _getMoveCount(): int
    {
        return count($this->get('moves'));
    }

    /**
     * The remaining time of a side in milliseconds.
     *
     * @param string $color `w` or `b`.
     * @return int
     */
    public function timeFor(string $color): int
    {
        return $color === 'w'
            ? (int)$this->get('white_time_ms')
            : (int)$this->get('black_time_ms');
    }

    /**
     * Set the remaining time of a side in milliseconds.
     *
     * @param string $color `w` or `b`.
     * @param int $milliseconds Remaining milliseconds.
     * @return void
     */
    public function setTimeFor(string $color, int $milliseconds): void
    {
        $milliseconds = max(0, $milliseconds);
        if ($color === 'w') {
            $this->set('white_time_ms', $milliseconds);
        } else {
            $this->set('black_time_ms', $milliseconds);
        }
    }

    /**
     * The remaining time of the side to move.
     *
     * @return int
     */
    public function timeForTurn(string $turn): int
    {
        return $this->timeFor($turn);
    }

    /**
     * The current board state as a FEN string.
     *
     * @return string
     */
    public function fen(): string
    {
        $fen = (string)$this->get('fen');

        return $fen !== '' ? $fen : ChessEngine::START_FEN;
    }

    /**
     * The identifier of the player whose turn it is.
     *
     * @return int|null
     */
    public function playerToMove(): ?int
    {
        $engine = new ChessEngine($this->fen());
        $turn = $engine->turn();

        return $turn === 'w' ? (int)$this->get('white_player_id') : (int)$this->get('black_player_id');
    }
}
