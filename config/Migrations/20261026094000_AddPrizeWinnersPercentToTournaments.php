<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Migrations\BaseMigration;

/**
 * Lets the administrator pick what share of the players receives a prize.
 *
 * The administrator can configure how many percent of the filled seats are paid
 * out (for example "top 40% of players get a prize"). The arena then builds a
 * prize ladder that covers exactly those places and shares the distributable
 * pool among them. The platform commission stays untouched: it is computed from
 * the entry fee and the platform fee only, so the admin profit scenario is not
 * affected by how the prize pool is split among the winners.
 */
class AddPrizeWinnersPercentToTournaments extends BaseMigration
{
    /**
     * Up method.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('tournaments');

        $table
            ->addColumn('prize_winners_percent', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 5,
                'scale' => 2,
                'after' => 'platform_fee',
                'comment' => 'Share of the filled seats that receive a prize (null = use the payout ladder template).',
            ])
            ->update();

        $this->clearSchemaCache();
    }

    /**
     * Down method.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('tournaments');

        $table
            ->removeColumn('prize_winners_percent')
            ->update();

        $this->clearSchemaCache();
    }

    /**
     * Drop the cached table description.
     *
     * @return void
     */
    protected function clearSchemaCache(): void
    {
        if (Cache::getConfig('default')) {
            Cache::clear('default');
        }
    }
}
