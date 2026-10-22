<?php
declare(strict_types=1);

namespace App\Service\Tournament;

use App\Model\Entity\User;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Moves money between the deposit and the winnings balance of a player and
 * records every movement in the ledger.
 *
 * The ledger is the audit trail the admin wallet auditor reads, so a balance
 * can always be reconstructed.
 */
class WalletService
{
    use LocatorAwareTrait;

    /**
     * Debit a player's deposit balance.
     *
     * @param \App\Model\Entity\User $user The player.
     * @param float $amount Amount to debit.
     * @param string $type Ledger type.
     * @param string $description Human readable description.
     * @param int|null $tournamentId The contest the movement belongs to.
     * @param int|null $createdBy The administrator that triggered the movement.
     * @return bool Whether the movement succeeded.
     */
    public function debit(
        User $user,
        float $amount,
        string $type,
        string $description,
        ?int $tournamentId = null,
        ?int $createdBy = null,
    ): bool {
        $amount = round(abs($amount), 2);
        if ($amount <= 0) {
            return false;
        }

        $balance = round((float)$user->get('deposit_balance'), 2);
        if ($balance < $amount) {
            return false;
        }

        $user->set('deposit_balance', round($balance - $amount, 2));

        return $this->writeLedger($user, $type, 'debit', $amount, $description, $tournamentId, $createdBy);
    }

    /**
     * Credit a player's balance.
     *
     * @param \App\Model\Entity\User $user The player.
     * @param float $amount Amount to credit.
     * @param string $type Ledger type.
     * @param string $description Human readable description.
     * @param int|null $tournamentId The contest the movement belongs to.
     * @param int|null $createdBy The administrator that triggered the movement.
     * @param bool $toWinnings Whether the money lands in the winnings balance.
     * @return bool Whether the movement succeeded.
     */
    public function credit(
        User $user,
        float $amount,
        string $type,
        string $description,
        ?int $tournamentId = null,
        ?int $createdBy = null,
        bool $toWinnings = true,
    ): bool {
        $amount = round(abs($amount), 2);
        if ($amount <= 0) {
            return false;
        }

        $field = $toWinnings ? 'winnings_balance' : 'deposit_balance';
        $user->set($field, round((float)$user->get($field) + $amount, 2));

        return $this->writeLedger($user, $type, 'credit', $amount, $description, $tournamentId, $createdBy);
    }

    /**
     * Move money in either direction, used by the admin wallet auditor.
     *
     * @param \App\Model\Entity\User $user The player.
     * @param float $amount Amount, negative to debit.
     * @param string $description Human readable description.
     * @param int|null $createdBy The administrator that triggered the movement.
     * @param bool $toWinnings Whether a credit lands in the winnings balance.
     * @return bool Whether the movement succeeded.
     */
    public function adjust(
        User $user,
        float $amount,
        string $description,
        ?int $createdBy = null,
        bool $toWinnings = false,
    ): bool {
        if ($amount < 0) {
            return $this->debit($user, $amount, 'adjustment', $description, null, $createdBy);
        }

        return $this->credit($user, $amount, 'adjustment', $description, null, $createdBy, $toWinnings);
    }

    /**
     * The ledger of a single player.
     *
     * @param int $userId The player.
     * @param int $limit Maximum number of rows.
     * @return array<int, \App\Model\Entity\WalletTransaction>
     */
    public function ledger(int $userId, int $limit = 50): array
    {
        /** @var \App\Model\Table\WalletTransactionsTable $table */
        $table = $this->getTableLocator()->get('WalletTransactions');

        return $table->find('ledger')
            ->where(['WalletTransactions.user_id' => $userId])
            ->limit($limit)
            ->all()
            ->toList();
    }

    /**
     * The full ledger, used by the admin wallet auditor.
     *
     * @param int $limit Maximum number of rows.
     * @return array<int, \App\Model\Entity\WalletTransaction>
     */
    public function auditLedger(int $limit = 200): array
    {
        /** @var \App\Model\Table\WalletTransactionsTable $table */
        $table = $this->getTableLocator()->get('WalletTransactions');

        return $table->find('ledger')
            ->contain(['Users', 'Tournaments'])
            ->limit($limit)
            ->all()
            ->toList();
    }

    /**
     * Write a ledger entry.
     *
     * @param \App\Model\Entity\User $user The player.
     * @param string $type Ledger type.
     * @param string $direction `credit` or `debit`.
     * @param float $amount The amount.
     * @param string $description Human readable description.
     * @param int|null $tournamentId The contest the movement belongs to.
     * @param int|null $createdBy The administrator that triggered the movement.
     * @return bool
     */
    protected function writeLedger(
        User $user,
        string $type,
        string $direction,
        float $amount,
        string $description,
        ?int $tournamentId,
        ?int $createdBy,
    ): bool {
        /** @var \App\Model\Table\WalletTransactionsTable $transactions */
        $transactions = $this->getTableLocator()->get('WalletTransactions');

        $transaction = $transactions->newEntity([
            'user_id' => $user->get('id'),
            'tournament_id' => $tournamentId,
            'type' => $type,
            'direction' => $direction,
            'amount' => $amount,
            'balance_after' => round((float)$user->get('deposit_balance'), 2),
            'description' => $description,
            'created_by' => $createdBy,
        ]);

        return $transactions->save($transaction) !== false;
    }

    /**
     * Reload a player from the database.
     *
     * @param int $userId The player.
     * @return \App\Model\Entity\User
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When the player does not exist.
     */
    public function reload(int $userId): User
    {
        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->getTableLocator()->get('Users');

        return $users->get($userId);
    }
}
