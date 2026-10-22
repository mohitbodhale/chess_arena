<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Tournament;
use App\Rbac\IdentityHelper;
use App\Rbac\Permission;
use App\Rbac\RoleRegistry;
use Authorization\IdentityInterface;

/**
 * Decides who may do what with a contest.
 *
 * An admin may touch every contest, an organizer only the contests they own
 * and a player may only look at them.
 */
class TournamentPolicy
{
    /**
     * Any signed in user may browse a contest.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @return bool
     */
    public function canView(?IdentityInterface $user, Tournament $tournament): bool
    {
        return $user !== null;
    }

    /**
     * Only admins and organizers may create a contest.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\Tournament|null $tournament The contest.
     * @return bool
     */
    public function canCreate(?IdentityInterface $user, ?Tournament $tournament = null): bool
    {
        return IdentityHelper::can($user, Permission::TOURNAMENT_CREATE);
    }

    /**
     * Admins may edit every contest, organizers only their own.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @return bool
     */
    public function canUpdate(?IdentityInterface $user, Tournament $tournament): bool
    {
        if (IdentityHelper::can($user, Permission::TOURNAMENT_UPDATE)) {
            return true;
        }

        $role = IdentityHelper::roleName($user);
        if ($role !== RoleRegistry::ORGANIZER) {
            return false;
        }

        $owner = $tournament->get('organizer_id');
        $userId = IdentityHelper::userId($user);

        return $owner !== null && $userId !== null && (int)$owner === $userId;
    }

    /**
     * Admins may delete every contest, organizers only their own.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @return bool
     */
    public function canDelete(?IdentityInterface $user, Tournament $tournament): bool
    {
        return $this->canUpdate($user, $tournament);
    }

    /**
     * Who may start rounds, manage slots and settle a contest.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @return bool
     */
    public function canManage(?IdentityInterface $user, Tournament $tournament): bool
    {
        if (IdentityHelper::can($user, Permission::TOURNAMENT_MANAGE)) {
            return true;
        }

        $role = IdentityHelper::roleName($user);
        if ($role !== RoleRegistry::ORGANIZER) {
            return false;
        }

        $owner = $tournament->get('organizer_id');
        $userId = IdentityHelper::userId($user);

        return $owner !== null && $userId !== null && (int)$owner === $userId;
    }

    /**
     * Who may buy a seat.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @return bool
     */
    public function canJoin(?IdentityInterface $user, Tournament $tournament): bool
    {
        if (!IdentityHelper::can($user, Permission::TOURNAMENT_JOIN)) {
            return false;
        }

        return $tournament->get('status') === 'Upcoming'
            && (int)$tournament->get('filled_slots') < (int)$tournament->get('max_slots');
    }
}
