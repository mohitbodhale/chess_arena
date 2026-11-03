<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * DepositRequest Entity
 *
 * @property int $id
 * @property int $user_id
 * @property float $amount
 * @property string|null $upi_id
 * @property string $reference
 * @property string $status
 * @property int|null $approved_by
 * @property \Cake\I18n\DateTime|null $approved_at
 * @property \Cake\I18n\DateTime $created_at
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\User|null $approver
 */
class DepositRequest extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'amount' => true,
        'upi_id' => true,
        'reference' => true,
        'status' => true,
        'approved_by' => true,
        'approved_at' => true,
        'created_at' => true,
        'user' => true,
        'approver' => true,
    ];

    /**
     * Whether the request is still pending approval.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->get('status') === 'pending';
    }

    /**
     * Whether the request was approved.
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->get('status') === 'approved';
    }

    /**
     * Whether the request was rejected.
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->get('status') === 'rejected';
    }
}
