<?php
declare(strict_types=1);

namespace App\Rbac;

use App\Model\Entity\User;
use Authorization\IdentityInterface;

/**
 * Reads the role and the identifier out of whatever identity object the
 * authentication plugin produced.
 *
 * The authentication plugin wraps the user entity in an identity and the
 * authorization plugin wraps that identity once more, so the entity is only
 * reachable through the original data of the identity.
 */
final class IdentityHelper
{
    /**
     * The user entity behind the identity.
     *
     * @param \Authorization\IdentityInterface|null $identity The identity.
     * @return \App\Model\Entity\User|null
     */
    public static function user(?IdentityInterface $identity): ?User
    {
        if ($identity === null) {
            return null;
        }

        $data = $identity->getOriginalData();

        return $data instanceof User ? $data : null;
    }

    /**
     * The role name of the identity.
     *
     * @param \Authorization\IdentityInterface|null $identity The identity.
     * @return string|null
     */
    public static function roleName(?IdentityInterface $identity): ?string
    {
        $user = self::user($identity);
        if ($user !== null) {
            return $user->getRoleName();
        }

        $data = $identity?->getOriginalData();
        if (is_array($data) && isset($data['role_id'])) {
            return RoleRegistry::nameForId((int)$data['role_id']);
        }

        return null;
    }

    /**
     * The primary key of the identity.
     *
     * @param \Authorization\IdentityInterface|null $identity The identity.
     * @return int|null
     */
    public static function userId(?IdentityInterface $identity): ?int
    {
        if ($identity === null) {
            return null;
        }

        $id = $identity->getIdentifier();
        if (is_int($id)) {
            return $id;
        }
        if (is_string($id) && ctype_digit($id)) {
            return (int)$id;
        }
        if (is_array($id) && isset($id['id'])) {
            return (int)$id['id'];
        }

        $user = self::user($identity);

        return $user === null ? null : (int)$user->get('id');
    }

    /**
     * Whether the identity holds a capability.
     *
     * @param \Authorization\IdentityInterface|null $identity The identity.
     * @param string $permission Capability identifier.
     * @return bool
     */
    public static function can(?IdentityInterface $identity, string $permission): bool
    {
        return RoleRegistry::can(self::roleName($identity), $permission);
    }

    /**
     * This class is a static helper.
     */
    private function __construct()
    {
    }
}
