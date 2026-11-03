<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Migrations\BaseMigration;

/**
 * Player-initiated deposit requests.
 *
 * A player can request to add money to their deposit balance. The request holds
 * the amount, the player's UPI id and a reference used to render a QR code for
 * payment. An administrator approves the request and credits the deposit
 * balance through the wallet ledger.
 */
class AddDepositRequests extends BaseMigration
{
    /**
     * Up method.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('deposit_requests');

        $table
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'comment' => 'The player who requested the deposit.',
            ])
            ->addColumn('amount', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => false,
                'comment' => 'Amount the player wants to add.',
            ])
            ->addColumn('upi_id', 'string', [
                'limit' => 100,
                'null' => true,
                'comment' => 'UPI id used to render the payment QR code.',
            ])
            ->addColumn('reference', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => 'Payment reference encoded in the QR code.',
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'default' => 'pending',
                'null' => false,
                'comment' => 'pending | approved | rejected.',
            ])
            ->addColumn('approved_by', 'integer', [
                'null' => true,
                'comment' => 'The administrator that approved the request.',
            ])
            ->addColumn('approved_at', 'datetime', [
                'null' => true,
                'comment' => 'When the request was approved.',
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addIndex('user_id')
            ->addIndex('status')
            ->create();

        $this->clearSchemaCache();
    }

    /**
     * Down method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->dropTable('deposit_requests');
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
