<?php
declare(strict_types=1);

namespace App\Rbac;

/**
 * Capability identifiers used across the role based access control layer.
 *
 * Policies never compare raw role names. They ask for a capability so that a
 * new role can be introduced without touching every policy.
 */
final class Permission
{
    /**
     * Browse the lobby, contest cards and public leaderboards.
     */
    public const TOURNAMENT_VIEW = 'tournament.view';

    /**
     * Create a new tournament contest.
     */
    public const TOURNAMENT_CREATE = 'tournament.create';

    /**
     * Edit the configuration of a tournament.
     */
    public const TOURNAMENT_UPDATE = 'tournament.update';

    /**
     * Cancel or delete a tournament.
     */
    public const TOURNAMENT_DELETE = 'tournament.delete';

    /**
     * Pay an entry fee and take a slot.
     */
    public const TOURNAMENT_JOIN = 'tournament.join';

    /**
     * Manage slots, start/stop rounds and force results.
     */
    public const TOURNAMENT_MANAGE = 'tournament.manage';

    /**
     * Play a live match.
     */
    public const MATCH_PLAY = 'match.play';

    /**
     * Intervene on a live or finished match.
     */
    public const MATCH_MANAGE = 'match.manage';

    /**
     * Run a simulated match to preview or stress the engine.
     */
    public const MATCH_SIMULATE = 'match.simulate';

    /**
     * Read the signed-in user's own wallet and ledger.
     */
    public const WALLET_VIEW_OWN = 'wallet.view.own';

    /**
     * Inspect wallet ledgers of other users.
     */
    public const WALLET_AUDIT = 'wallet.audit';

    /**
     * Credit or debit another user's wallet.
     */
    public const WALLET_ADJUST = 'wallet.adjust';

    /**
     * Manage user accounts and role assignment.
     */
    public const USER_MANAGE = 'user.manage';

    /**
     * Read leaderboards and standings.
     */
    public const LEADERBOARD_VIEW = 'leaderboard.view';

    /**
     * Read financial and operational reports.
     */
    public const REPORT_VIEW = 'report.view';

    /**
     * Every capability known to the application.
     *
     * @var array<int, string>
     */
    public const ALL = [
        self::TOURNAMENT_VIEW,
        self::TOURNAMENT_CREATE,
        self::TOURNAMENT_UPDATE,
        self::TOURNAMENT_DELETE,
        self::TOURNAMENT_JOIN,
        self::TOURNAMENT_MANAGE,
        self::MATCH_PLAY,
        self::MATCH_MANAGE,
        self::MATCH_SIMULATE,
        self::WALLET_VIEW_OWN,
        self::WALLET_AUDIT,
        self::WALLET_ADJUST,
        self::USER_MANAGE,
        self::LEADERBOARD_VIEW,
        self::REPORT_VIEW,
    ];

    /**
     * This class is a constant holder.
     */
    private function __construct()
    {
    }
}
