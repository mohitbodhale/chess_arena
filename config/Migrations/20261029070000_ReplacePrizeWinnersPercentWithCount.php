<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Migrations\BaseMigration;

/**
 * Replace prize_winners_percent with prize_winners_count.
 *
 * The administrator now picks how many players receive a prize (for example
 * "top 6 of 10 players get paid") instead of a percentage. The winner always
 * receives 50% of the distributable prize pool and the remaining winners share
 * the rest in decreasing order by rank.
 */
class ReplacePrizeWinnersPercentWithCount extends BaseMigration
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
            ->removeColumn('prize_winners_percent')
            ->addColumn('prize_winners_count', 'integer', [
                'default' => null,
                'null' => true,
                'after' => 'platform_fee',
                'comment' => 'Number of players that receive a prize (null = use the payout ladder template).',
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
            ->removeColumn('prize_winners_count')
            ->addColumn('prize_winners_percent', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 5,
                'scale' => 2,
                'after' => 'platform_fee',
            ])
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
