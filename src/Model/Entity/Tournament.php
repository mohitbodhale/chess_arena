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
 * @property string $platform_fee
 * @property string $platform_revenue
 * @property int $max_slots
 * @property int $filled_slots
 * @property string $status
 * @property int|null $organizer_id
 * @property array<int, array<string, mixed>> $prize_breakdown
 * @property int $base_seconds
 * @property int $increment_seconds
     * @property string|null $rules
     * @property float|null $prize_winners_percent
     * @property \Cake\I18n\DateTime $start_time
 * @property \Cake\I18n\DateTime $created_at
 *
 * @property \App\Model\Entity\User|null $organizer
 * @property \App\Model\Entity\ArenaMatch[] $matches
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
        'platform_fee' => true,
        'platform_revenue' => true,
        'max_slots' => true,
        'filled_slots' => true,
        'status' => true,
        'organizer_id' => true,
        'prize_breakdown' => true,
        'base_seconds' => true,
        'increment_seconds' => true,
        'rules' => true,
        'prize_winners_percent' => true,
        'start_time' => true,
        'created_at' => true,
        'organizer' => true,
        'matches' => true,
        'tournament_registrations' => true,
    ];

    /**
     * Decode the JSON encoded prize breakdown.
     *
     * @param mixed $value Raw column value.
     * @return array<int, array<string, mixed>>
     */
    protected function _getPrizeBreakdown(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Number of seats still available.
     *
     * @return int
     */
    protected function _getSlotsRemaining(): int
    {
        return max(0, (int)$this->get('max_slots') - (int)$this->get('filled_slots'));
    }

    /**
     * Percentage of the contest that is already sold, capped at 100.
     *
     * @return int
     */
    protected function _getSlotsFilledPercent(): int
    {
        $max = (int)$this->get('max_slots');
        if ($max <= 0) {
            return 0;
        }

        return (int)min(100, round(((int)$this->get('filled_slots') / $max) * 100));
    }

    /**
     * Whether the contest can still be joined.
     *
     * @return bool
     */
    protected function _getIsJoinable(): bool
    {
        return $this->get('status') === 'Upcoming' && (int)$this->get('slots_remaining') > 0;
    }

    /**
     * Whether the contest has no seats left.
     *
     * @return bool
     */
    protected function _getIsFull(): bool
    {
        return (int)$this->get('slots_remaining') === 0;
    }

    /**
     * The entry fees that were collected for this contest.
     *
     * @return float
     */
    protected function _getCollectedFees(): float
    {
        return round((float)$this->get('entry_fee') * (int)$this->get('filled_slots'), 2);
    }

    /**
     * The commission the platform earns from the collected fees.
     *
     * @return float
     */
    protected function _getPlatformCut(): float
    {
        return round($this->get('collected_fees') * (float)$this->get('platform_fee') / 100, 2);
    }

    /**
     * The prize rows resolved against the current prize pool.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function _getPrizeRows(): array
    {
        $pool = (float)$this->get('prize_pool');
        $rows = [];
        foreach ((array)$this->get('prize_breakdown') as $row) {
            $rows[] = [
                'rank' => (int)($row['rank'] ?? 0),
                'label' => (string)($row['label'] ?? ('Rank ' . (int)($row['rank'] ?? 0))),
                'percent' => (float)($row['percent'] ?? 0),
                'amount' => round($pool * ((float)($row['percent'] ?? 0)) / 100, 2),
            ];
        }

        return $rows;
    }
}
