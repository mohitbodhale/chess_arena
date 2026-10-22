<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use App\Rbac\IdentityHelper;
use App\Rbac\Permission;
use Authorization\IdentityInterface;

/**
 * Decides who may manage accounts and who may audit or move money.
 */
class UserPolicy
{
    /**
     * Only admins may manage accounts.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\User|null $target The account.
     * @return bool
     */
    public function canManage(?IdentityInterface $user, ?User $target = null): bool
    {
        return IdentityHelper::can($user, Permission::USER_MANAGE);
    }

    /**
     * Who may read the wallet of another player.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\User|null $target The account.
     * @return bool
     */
    public function canAuditWallet(?IdentityInterface $user, ?User $target = null): bool
    {
        return IdentityHelper::can($user, Permission::WALLET_AUDIT);
    }

    /**
     * Who may credit or debit the wallet of another player.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\User|null $target The account.
     * @return bool
     */
    public function canAdjustWallet(?IdentityInterface $user, ?User $target = null): bool
    {
        return IdentityHelper::can($user, Permission::WALLET_ADJUST);
    }

    /**
     * Everyone may look at their own profile.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\User|null $target The account.
     * @return bool
     */
    public function canView(?IdentityInterface $user, ?User $target = null): bool
    {
        if ($user === null) {
            return false;
        }

        if (IdentityHelper::can($user, Permission::USER_MANAGE)) {
            return true;
        }

        $userId = IdentityHelper::userId($user);
        $targetId = $target?->get('id');

        return $userId !== null && $targetId !== null && (int)$targetId === $userId;
    }
}
