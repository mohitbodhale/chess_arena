<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Migrations\BaseMigration;

/**
 * Adds the platform commission to the contests.
 *
 * The arena keeps a share of every collected entry fee. The share is stored on
 * the contest so the administrator can tune it per contest and the ledger can
 * show what the platform earned.
 */
class AddPlatformFeeToTournaments extends BaseMigration
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
            ->addColumn('platform_fee', 'decimal', [
                'default' => 20.0,
                'null' => false,
                'precision' => 5,
                'scale' => 2,
                'after' => 'prize_pool',
                'comment' => 'Percentage of the collected entry fees the platform keeps.',
            ])
            ->addColumn('platform_revenue', 'decimal', [
                'default' => 0.0,
                'null' => false,
                'precision' => 12,
                'scale' => 2,
                'after' => 'platform_fee',
                'comment' => 'Commission the platform earned when the contest was settled.',
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
            ->removeColumn('platform_fee')
            ->removeColumn('platform_revenue')
            ->update();

        $this->clearSchemaCache();
    }

    /**
     * Drop the cached table description.
     *
     * The ORM caches the column list of every table. A migration that adds a
     * column has to drop that cache, otherwise the application keeps working
     * with the old column list and silently ignores the new columns.
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
