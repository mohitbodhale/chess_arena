<?php
declare(strict_types=1);

namespace App\Rbac;

use App\Model\Entity\User;

/**
 * Single source of truth for the role to capability mapping.
 *
 * Three roles exist in the arena and each one owns a distinct set of
 * capabilities:
 *
 * - `Admin`     – full control over contests, wallets and users.
 * - `Organizer` – runs their own contests and may audit only their own ledger.
 * - `Player`    – joins contests, plays matches and watches standings.
 */
final class RoleRegistry
{
    public const ADMIN = 'Admin';
    public const ORGANIZER = 'Organizer';
    public const PLAYER = 'Player';

    /**
     * Role names keyed by their primary key in the `roles` table.
     *
     * @var array<int, string>
     */
    public const ID_MAP = [
        1 => self::ADMIN,
        2 => self::ORGANIZER,
        3 => self::PLAYER,
    ];

    /**
     * Capabilities granted to each role.
     *
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        self::ADMIN => Permission::ALL,
        self::ORGANIZER => [
            Permission::TOURNAMENT_VIEW,
            Permission::TOURNAMENT_CREATE,
            Permission::TOURNAMENT_UPDATE,
            Permission::TOURNAMENT_DELETE,
            Permission::TOURNAMENT_MANAGE,
            Permission::MATCH_MANAGE,
            Permission::MATCH_SIMULATE,
            Permission::WALLET_VIEW_OWN,
            Permission::WALLET_AUDIT,
            Permission::LEADERBOARD_VIEW,
            Permission::REPORT_VIEW,
        ],
        self::PLAYER => [
            Permission::TOURNAMENT_VIEW,
            Permission::TOURNAMENT_JOIN,
            Permission::MATCH_PLAY,
            Permission::WALLET_VIEW_OWN,
            Permission::LEADERBOARD_VIEW,
        ],
    ];

    /**
     * Human readable summary of what every role may do. Used by the admin
     * dashboard to render the access matrix.
     *
     * @var array<string, string>
     */
    private const ROLE_DESCRIPTIONS = [
        self::ADMIN => 'Full control: contests, slot management, wallet auditing and match simulation.',
        self::ORGANIZER => 'Runs their own contests, manages slots and audits their own tournament ledger.',
        self::PLAYER => 'Joins paid contests, plays live matches and tracks their own rank and wallet.',
    ];

    /**
     * All known role names.
     *
     * @return array<int, string>
     */
    public static function roles(): array
    {
        return array_keys(self::ROLE_PERMISSIONS);
    }

    /**
     * Capabilities granted to a role.
     *
     * @param string|null $role Role name.
     * @return array<int, string>
     */
    public static function permissionsFor(?string $role): array
    {
        if ($role === null) {
            return [];
        }

        return self::ROLE_PERMISSIONS[$role] ?? [];
    }

    /**
     * Whether a role holds a capability.
     *
     * @param string|null $role Role name.
     * @param string $permission Capability identifier.
     * @return bool
     */
    public static function can(?string $role, string $permission): bool
    {
        return in_array($permission, self::permissionsFor($role), true);
    }

    /**
     * Resolve the role name for a role primary key.
     *
     * @param int|null $roleId Role primary key.
     * @return string|null
     */
    public static function nameForId(?int $roleId): ?string
    {
        if ($roleId === null) {
            return null;
        }

        return self::ID_MAP[$roleId] ?? null;
    }

    /**
     * Resolve the role name for a user.
     *
     * @param \App\Model\Entity\User|null $user The user.
     * @return string|null
     */
    public static function nameForUser(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $role = $user->get('role');
        if ($role !== null && !empty($role->get('name'))) {
            return (string)$role->get('name');
        }

        return self::nameForId($user->get('role_id') === null ? null : (int)$user->get('role_id'));
    }

    /**
     * Short description of a role.
     *
     * @param string $role Role name.
     * @return string
     */
    public static function describe(string $role): string
    {
        return self::ROLE_DESCRIPTIONS[$role] ?? '';
    }

    /**
     * The full access matrix, ready for rendering.
     *
     * @return array<string, array<string, array{label: string, description: string, permissions: array<int, string>}>>
     */
    public static function matrix(): array
    {
        $matrix = [];
        foreach (self::ROLE_PERMISSIONS as $role => $permissions) {
            $matrix[$role] = [
                'label' => $role,
                'description' => self::describe($role),
                'permissions' => $permissions,
            ];
        }

        return $matrix;
    }
}
