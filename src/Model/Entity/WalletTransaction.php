<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Wallet Transaction Entity
 *
 * Every movement of money in the arena is recorded here so the wallet auditor
 * can reconstruct every balance.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $tournament_id
 * @property string $type
 * @property string $direction
 * @property string $amount
 * @property string $balance_after
 * @property string $description
 * @property int|null $created_by
 * @property \Cake\I18n\DateTime $created_at
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\Tournament|null $tournament
 */
class WalletTransaction extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'tournament_id' => true,
        'type' => true,
        'direction' => true,
        'amount' => true,
        'balance_after' => true,
        'description' => true,
        'created_by' => true,
        'created_at' => true,
        'user' => true,
        'tournament' => true,
    ];

    /**
     * The signed amount of this transaction.
     *
     * @return float
     */
    public function getSignedAmount(): float
    {
        $amount = (float)$this->get('amount');

        return $this->get('direction') === 'debit' ? -$amount : $amount;
    }
}
