<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\ArenaMatch;
use App\Rbac\IdentityHelper;
use App\Rbac\Permission;
use App\Rbac\RoleRegistry;
use Authorization\IdentityInterface;

/**
 * Decides who may play, intervene on or simulate a match.
 */
class MatchPolicy
{
    /**
     * Only the two players of a live match may move.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @return bool
     */
    public function canPlay(?IdentityInterface $user, ArenaMatch $match): bool
    {
        if (!IdentityHelper::can($user, Permission::MATCH_PLAY)) {
            return false;
        }

        return $this->isPlayer($user, $match) && $match->get('status') === 'Live';
    }

    /**
     * The board of a match stays readable for its players, also once the game
     * is over, so the final position can be reviewed.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @return bool
     */
    public function canView(?IdentityInterface $user, ArenaMatch $match): bool
    {
        if (IdentityHelper::can($user, Permission::MATCH_MANAGE)) {
            return true;
        }

        return $this->isPlayer($user, $match);
    }

    /**
     * Whether the identity belongs to one of the two players.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @return bool
     */
    protected function isPlayer(?IdentityInterface $user, ArenaMatch $match): bool
    {
        $userId = IdentityHelper::userId($user);
        if ($userId === null) {
            return false;
        }

        return (int)$match->get('white_player_id') === $userId
            || (int)$match->get('black_player_id') === $userId;
    }

    /**
     * Admins may intervene on every match, organizers on the matches of their
     * own contests.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @return bool
     */
    public function canManage(?IdentityInterface $user, ArenaMatch $match): bool
    {
        if (IdentityHelper::can($user, Permission::MATCH_MANAGE)) {
            return true;
        }

        if (IdentityHelper::roleName($user) !== RoleRegistry::ORGANIZER) {
            return false;
        }

        $organizerId = $match->get('tournament')['organizer_id'] ?? null;
        $userId = IdentityHelper::userId($user);

        return $organizerId !== null && $userId !== null && (int)$organizerId === $userId;
    }

    /**
     * Who may run the match simulator.
     *
     * @param \Authorization\IdentityInterface|null $user The identity.
     * @param \App\Model\Entity\ArenaMatch $match The match.
     * @return bool
     */
    public function canSimulate(?IdentityInterface $user, ArenaMatch $match): bool
    {
        if (IdentityHelper::can($user, Permission::MATCH_SIMULATE)) {
            return true;
        }

        if (IdentityHelper::roleName($user) !== RoleRegistry::ORGANIZER) {
            return false;
        }

        $organizerId = $match->get('tournament')['organizer_id'] ?? null;
        $userId = IdentityHelper::userId($user);

        return $organizerId !== null && $userId !== null && (int)$organizerId === $userId;
    }
}
