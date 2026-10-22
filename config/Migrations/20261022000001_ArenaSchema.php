<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Migrations\BaseMigration;

/**
 * Extends the arena schema with everything the tournament platform needs:
 * contest configuration, live match state, fantasy scoring columns and the
 * wallet ledger.
 *
 * The migration is written so that it can run on a database that already holds
 * the base tables. Every statement checks the catalogue first, so running it
 * twice is harmless.
 */
class ArenaSchema extends BaseMigration
{
    /**
     * The starting position used by every new match.
     */
    private const START_FEN = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

    /**
     * Apply the schema changes.
     *
     * @return void
     */
    public function up(): void
    {
        $this->addColumn('users', 'status', "VARCHAR(20) NOT NULL DEFAULT 'Active'");
        $this->addColumn('users', 'last_login_at', 'DATETIME NULL');

        $this->addColumn('tournaments', 'organizer_id', 'INT NULL');
        $this->addColumn('tournaments', 'prize_breakdown', 'TEXT NULL');
        $this->addColumn('tournaments', 'base_seconds', 'INT NOT NULL DEFAULT 180');
        $this->addColumn('tournaments', 'increment_seconds', 'INT NOT NULL DEFAULT 0');
        $this->addColumn('tournaments', 'rules', 'TEXT NULL');

        $this->addColumn('matches', 'termination', 'VARCHAR(30) NULL');
        $this->addColumn('matches', 'status', "VARCHAR(20) NOT NULL DEFAULT 'Pending'");
        $this->addColumn('matches', 'fen', "VARCHAR(120) NOT NULL DEFAULT '" . self::START_FEN . "'");
        $this->addColumn('matches', 'moves', 'TEXT NULL');
        $this->addColumn('matches', 'white_time_ms', 'BIGINT NOT NULL DEFAULT 180000');
        $this->addColumn('matches', 'black_time_ms', 'BIGINT NOT NULL DEFAULT 180000');
        $this->addColumn('matches', 'increment_ms', 'INT NOT NULL DEFAULT 0');
        $this->addColumn('matches', 'round_number', 'INT NOT NULL DEFAULT 1');
        $this->addColumn('matches', 'started_at', 'DATETIME NULL');
        $this->addColumn('matches', 'ended_at', 'DATETIME NULL');

        $this->addColumn('tournament_registrations', 'time_bonus', 'DECIMAL(5,1) NOT NULL DEFAULT 0.0');
        $this->addColumn('tournament_registrations', 'wins', 'INT NOT NULL DEFAULT 0');
        $this->addColumn('tournament_registrations', 'draws', 'INT NOT NULL DEFAULT 0');
        $this->addColumn('tournament_registrations', 'losses', 'INT NOT NULL DEFAULT 0');
        $this->addColumn('tournament_registrations', 'status', "VARCHAR(20) NOT NULL DEFAULT 'Registered'");

        $this->createWalletTransactions();
        $this->createIndexes();
        $this->clearSchemaCache();
    }

    /**
     * Roll the schema changes back.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS `wallet_transactions`');

        foreach ([
            'users' => ['status', 'last_login_at'],
            'tournaments' => ['organizer_id', 'prize_breakdown', 'base_seconds', 'increment_seconds', 'rules'],
            'matches' => [
                'termination',
                'status',
                'fen',
                'moves',
                'white_time_ms',
                'black_time_ms',
                'increment_ms',
                'round_number',
                'started_at',
                'ended_at',
            ],
            'tournament_registrations' => ['time_bonus', 'wins', 'draws', 'losses', 'status'],
        ] as $table => $columns) {
            foreach ($columns as $column) {
                $this->execute(sprintf('ALTER TABLE `%s` DROP COLUMN `%s`', $table, $column));
            }
        }

        $this->clearSchemaCache();
    }

    /**
     * Drop the cached table description.
     *
     * The ORM caches the column list of every table, so a migration has to
     * clear that cache or the application keeps using the old columns.
     *
     * @return void
     */
    protected function clearSchemaCache(): void
    {
        if (Cache::getConfig('default')) {
            Cache::clear('default');
        }
    }

    /**
     * Create the ledger of every movement of money.
     *
     * @return void
     */
    protected function createWalletTransactions(): void
    {
        if ($this->tableExists('wallet_transactions')) {
            return;
        }

        $this->execute(
            'CREATE TABLE `wallet_transactions` ('
            . '`id` INT AUTO_INCREMENT PRIMARY KEY,'
            . '`user_id` INT NOT NULL,'
            . '`tournament_id` INT NULL,'
            . '`type` VARCHAR(30) NOT NULL,'
            . '`direction` VARCHAR(10) NOT NULL,'
            . '`amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,'
            . '`balance_after` DECIMAL(10,2) NOT NULL DEFAULT 0.00,'
            . '`description` VARCHAR(255) NULL,'
            . '`created_by` INT NULL,'
            . '`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . 'INDEX `wallet_user_idx` (`user_id`),'
            . 'INDEX `wallet_tournament_idx` (`tournament_id`)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );
    }

    /**
     * Add the indexes the lobby and the leaderboards rely on.
     *
     * @return void
     */
    protected function createIndexes(): void
    {
        $indexes = [
            ['tournaments', 'tournaments_organizer_idx', ['organizer_id']],
            ['tournaments', 'tournaments_status_idx', ['status']],
            ['matches', 'matches_tournament_idx', ['tournament_id']],
            ['matches', 'matches_status_idx', ['status']],
            ['tournament_registrations', 'registrations_tournament_idx', ['tournament_id']],
            ['tournament_registrations', 'registrations_user_idx', ['user_id']],
        ];

        foreach ($indexes as [$table, $name, $columns]) {
            if ($this->indexExists($table, $name)) {
                continue;
            }
            $this->execute(sprintf(
                'CREATE INDEX `%s` ON `%s` (`%s`)',
                $name,
                $table,
                implode('`, `', $columns),
            ));
        }

        if (!$this->indexExists('tournament_registrations', 'registrations_unique_idx')) {
            $this->execute(
                'CREATE UNIQUE INDEX `registrations_unique_idx` '
                . 'ON `tournament_registrations` (`tournament_id`, `user_id`)',
            );
        }
    }

    /**
     * Add a column when it is missing.
     *
     * @param string $table The table.
     * @param string $column The column.
     * @param string $definition The column definition.
     * @return void
     */
    protected function addColumn(string $table, string $column, string $definition): void
    {
        if (!$this->tableExists($table)) {
            return;
        }
        if ($this->columnExists($table, $column)) {
            return;
        }

        $this->execute(sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s', $table, $column, $definition));
    }

    /**
     * Whether a table exists.
     *
     * @param string $table The table.
     * @return bool
     */
    protected function tableExists(string $table): bool
    {
        $row = $this->countRows(
            'SELECT COUNT(*) AS `c` FROM information_schema.TABLES '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'' . $table . '\'',
        );

        return (int)($row['c'] ?? 0) > 0;
    }

    /**
     * Whether a column exists.
     *
     * @param string $table The table.
     * @param string $column The column.
     * @return bool
     */
    protected function columnExists(string $table, string $column): bool
    {
        $row = $this->countRows(
            'SELECT COUNT(*) AS `c` FROM information_schema.COLUMNS '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'' . $table . '\' '
            . 'AND COLUMN_NAME = \'' . $column . '\'',
        );

        return (int)($row['c'] ?? 0) > 0;
    }

    /**
     * Whether an index exists.
     *
     * @param string $table The table.
     * @param string $index The index.
     * @return bool
     */
    protected function indexExists(string $table, string $index): bool
    {
        $row = $this->countRows(
            'SELECT COUNT(*) AS `c` FROM information_schema.STATISTICS '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'' . $table . '\' '
            . 'AND INDEX_NAME = \'' . $index . '\'',
        );

        return (int)($row['c'] ?? 0) > 0;
    }

    /**
     * Run a query and return the first row.
     *
     * @param string $sql The query.
     * @return array<string, mixed>
     */
    protected function countRows(string $sql): array
    {
        $row = $this->getAdapter()->fetchRow($sql);

        return is_array($row) ? $row : [];
    }
}
