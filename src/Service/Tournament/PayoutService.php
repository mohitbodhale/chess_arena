<?php
declare(strict_types=1);

namespace App\Service\Tournament;

use App\Model\Entity\Tournament;
use App\Model\Entity\TournamentRegistration;

/**
 * Turns a prize pool and a distribution template into rupee amounts.
 *
 * The arena uses a Dream11 style payout ladder: a small number of ranks share
 * the whole pool, every rank has a label that is shown on the contest card and
 * the same ladder drives both the preview and the final settlement.
 */
class PayoutService
{
    /**
     * The share of the collected entry fees the platform keeps by default.
     */
    public const DEFAULT_PLATFORM_FEE = 20.0;

    /**
     * The distribution templates an organizer can pick from.
     *
     * @var array<string, array<int, array{rank: int, label: string, percent: float}>>
     */
    public const TEMPLATES = [
        'top1' => [
            ['rank' => 1, 'label' => 'Winner', 'percent' => 100.0],
        ],
        'top3' => [
            ['rank' => 1, 'label' => 'Winner', 'percent' => 50.0],
            ['rank' => 2, 'label' => 'Runner Up', 'percent' => 30.0],
            ['rank' => 3, 'label' => 'Third Place', 'percent' => 20.0],
        ],
        'top5' => [
            ['rank' => 1, 'label' => 'Winner', 'percent' => 40.0],
            ['rank' => 2, 'label' => 'Runner Up', 'percent' => 25.0],
            ['rank' => 3, 'label' => 'Third Place', 'percent' => 15.0],
            ['rank' => 4, 'label' => 'Fourth Place', 'percent' => 12.0],
            ['rank' => 5, 'label' => 'Fifth Place', 'percent' => 8.0],
        ],
        'top10' => [
            ['rank' => 1, 'label' => 'Winner', 'percent' => 30.0],
            ['rank' => 2, 'label' => 'Runner Up', 'percent' => 18.0],
            ['rank' => 3, 'label' => 'Third Place', 'percent' => 14.0],
            ['rank' => 4, 'label' => 'Fourth Place', 'percent' => 11.0],
            ['rank' => 5, 'label' => 'Fifth Place', 'percent' => 9.0],
            ['rank' => 6, 'label' => 'Sixth Place', 'percent' => 6.0],
            ['rank' => 7, 'label' => 'Seventh Place', 'percent' => 5.0],
            ['rank' => 8, 'label' => 'Eighth Place', 'percent' => 3.0],
            ['rank' => 9, 'label' => 'Ninth Place', 'percent' => 2.0],
            ['rank' => 10, 'label' => 'Tenth Place', 'percent' => 2.0],
        ],
    ];

    /**
     * The template names offered in the tournament creator.
     *
     * @return array<int, string>
     */
    public function templates(): array
    {
        return array_keys(self::TEMPLATES);
    }

    /**
     * The rows of a template.
     *
     * @param string $template Template name.
     * @return array<int, array{rank: int, label: string, percent: float}>
     */
    public function templateRows(string $template): array
    {
        return self::TEMPLATES[$template] ?? self::TEMPLATES['top3'];
    }

    /**
     * Pick a sensible template for a contest of the given size.
     *
     * @param int $maxSlots Number of seats.
     * @return string
     */
    public function defaultTemplate(int $maxSlots): string
    {
        if ($maxSlots <= 4) {
            return 'top1';
        }
        if ($maxSlots <= 8) {
            return 'top3';
        }
        if ($maxSlots <= 20) {
            return 'top5';
        }

        return 'top10';
    }

    /**
     * The distribution rows a contest should use by default.
     *
     * @param int $maxSlots Number of seats.
     * @return array<int, array{rank: int, label: string, percent: float}>
     */
    public function defaultBreakdown(int $maxSlots): array
    {
        return $this->templateRows($this->defaultTemplate($maxSlots));
    }

    /**
     * Resolve the payout rows of a contest into rupee amounts.
     *
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @return array<int, array{rank: int, label: string, percent: float, amount: float}>
     */
    public function rows(Tournament $tournament): array
    {
        $pool = (float)$tournament->get('prize_pool');
        $rows = [];
        foreach ((array)$tournament->get('prize_breakdown') as $row) {
            $percent = (float)($row['percent'] ?? 0);
            $rows[] = [
                'rank' => (int)($row['rank'] ?? 0),
                'label' => (string)($row['label'] ?? 'Rank ' . (int)($row['rank'] ?? 0)),
                'percent' => $percent,
                'amount' => round($pool * $percent / 100, 2),
            ];
        }

        return $rows;
    }

    /**
     * The entry fees that were collected for a contest.
     *
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @return float
     */
    public function collected(Tournament $tournament): float
    {
        return round((float)$tournament->get('entry_fee') * (int)$tournament->get('filled_slots'), 2);
    }

    /**
     * The commission the platform earns from a contest.
     *
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @return float
     */
    public function platformCut(Tournament $tournament): float
    {
        $fee = (float)($tournament->get('platform_fee') ?? self::DEFAULT_PLATFORM_FEE);

        return round($this->collected($tournament) * $fee / 100, 2);
    }

    /**
     * The prize pool that is left for the players after the platform cut.
     *
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @return float
     */
    public function distributablePool(Tournament $tournament): float
    {
        return round(max(0.0, $this->collected($tournament) - $this->platformCut($tournament)), 2);
    }

    /**
     * The prize pool a contest announces when it is created.
     *
     * @param float $entryFee The entry fee.
     * @param int $maxSlots The number of seats.
     * @param float $platformFee The platform fee in percent.
     * @return float
     */
    public function announcedPool(float $entryFee, int $maxSlots, float $platformFee): float
    {
        $share = max(0.0, min(100.0, $platformFee));

        return round($entryFee * $maxSlots * (100 - $share) / 100, 2);
    }

    /**
     * The sum of all percentages in a breakdown.
     *
     * @param array<int, array<string, mixed>> $breakdown Breakdown rows.
     * @return float
     */
    public function totalPercentage(array $breakdown): float
    {
        $total = 0.0;
        foreach ($breakdown as $row) {
            $total += (float)($row['percent'] ?? 0);
        }

        return round($total, 2);
    }

    /**
     * The payout owed to every ranked player.
     *
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @param array<int, \App\Model\Entity\TournamentRegistration> $standings Ranked registrations.
     * @return array<int, float> Registration id to amount.
     */
    public function distribute(Tournament $tournament, array $standings): array
    {
        $payouts = [];
        foreach ($this->rows($tournament) as $row) {
            $registration = $standings[$row['rank']] ?? null;
            if ($registration === null) {
                continue;
            }
            $payouts[$registration->get('id')] = $row['amount'];
        }

        return $payouts;
    }

    /**
     * The prize money still unclaimed, used by the payout audit.
     *
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @param array<int, \App\Model\Entity\TournamentRegistration> $standings Ranked registrations.
     * @return float
     */
    public function unclaimed(Tournament $tournament, array $standings): float
    {
        $paid = array_sum($this->distribute($tournament, $standings));

        return round(max(0.0, (float)$tournament->get('prize_pool') - $paid), 2);
    }
}
