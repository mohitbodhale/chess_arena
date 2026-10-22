<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\TournamentRegistration;
use App\Rbac\IdentityHelper;
use App\Rbac\Permission;
use App\Rbac\RoleRegistry;
use Authorization\IdentityInterface;

/**
 * Decides who may read and edit the entry of a player in a contest.
 */
class TournamentRegistrationPolicy
{
    /**
     * The standings of a contest are public.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\TournamentRegistration $registration The entry.
     * @return bool
     */
    public function canView(?IdentityInterface $user, TournamentRegistration $registration): bool
    {
        return $user !== null;
    }

    /**
     * Admins may edit every entry, organizers the entries of their own
     * contests and players only their own entry.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\TournamentRegistration $registration The entry.
     * @return bool
     */
    public function canUpdate(?IdentityInterface $user, TournamentRegistration $registration): bool
    {
        if (IdentityHelper::can($user, Permission::TOURNAMENT_MANAGE)) {
            return true;
        }

        if (IdentityHelper::roleName($user) === RoleRegistry::ORGANIZER) {
            $organizerId = $registration->get('tournament')['organizer_id'] ?? null;
            $userId = IdentityHelper::userId($user);

            return $organizerId !== null && $userId !== null && (int)$organizerId === $userId;
        }

        $userId = IdentityHelper::userId($user);
        $ownerId = $registration->get('user_id');

        return $userId !== null && $ownerId !== null && (int)$ownerId === $userId;
    }
}
