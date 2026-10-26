<?php
declare(strict_types=1);

namespace App\Service\Tournament;

use App\Model\Entity\Tournament;
use App\Model\Entity\TournamentRegistration;
use App\Model\Entity\User;
use App\Model\Table\TournamentRegistrationsTable;
use App\Model\Table\TournamentsTable;
use App\Rbac\RoleRegistry;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;

/**
 * Creates contests, sells seats and settles the prize money.
 *
 * Every seat that is sold moves money through the wallet service so the ledger
 * always matches the number of filled slots. When a contest is settled the
 * collected fees are split: the platform keeps its commission and the players
 * share the rest.
 */
class TournamentService
{
    use LocatorAwareTrait;

    /**
     * @var \App\Service\Tournament\PayoutService
     */
    protected PayoutService $payouts;

    /**
     * @var \App\Service\Tournament\WalletService
     */
    protected WalletService $wallet;

    /**
     * @var \App\Service\Tournament\RankingService
     */
    protected RankingService $ranking;

    /**
     * Constructor.
     *
     * @param \App\Service\Tournament\PayoutService|null $payouts Payout service.
     * @param \App\Service\Tournament\WalletService|null $wallet Wallet service.
     * @param \App\Service\Tournament\RankingService|null $ranking Ranking service.
     */
    public function __construct(
        ?PayoutService $payouts = null,
        ?WalletService $wallet = null,
        ?RankingService $ranking = null,
    ) {
        $this->payouts = $payouts ?? new PayoutService();
        $this->wallet = $wallet ?? new WalletService();
        $this->ranking = $ranking ?? new RankingService();
    }

    /**
     * Create a contest from the data of the tournament creator.
     *
     * @param array<string, mixed> $data The submitted data.
     * @param int|null $organizerId The organizer that owns the contest.
     * @return \App\Model\Entity\Tournament
     * @throws \RuntimeException When the contest cannot be stored.
     */
    public function create(array $data, ?int $organizerId = null): Tournament
    {
        /** @var \App\Model\Table\TournamentsTable $tournaments */
        $tournaments = $this->getTableLocator()->get('Tournaments');

        $category = (string)($data['category'] ?? TimeControl::BLITZ);
        $meta = TimeControl::meta($category);
        $maxSlots = (int)($data['max_slots'] ?? 64);
        $entryFee = round((float)($data['entry_fee'] ?? 0), 2);

        $breakdown = $data['prize_breakdown'] ?? null;
        if (!is_array($breakdown) || $breakdown === []) {
            $winnerPercent = (float)($data['prize_winners_percent'] ?? 0.0);
            if ($winnerPercent > 0.0) {
                $breakdown = $this->payouts->breakdownByWinnerPercent($maxSlots, $winnerPercent);
            } else {
                $breakdown = $this->payouts->defaultBreakdown($maxSlots);
            }
        }

        $platformFee = $this->platformFee($data);

        $prizePool = isset($data['prize_pool']) && (float)$data['prize_pool'] > 0
            ? round((float)$data['prize_pool'], 2)
            : $this->payouts->announcedPool($entryFee, $maxSlots, $platformFee);

        $entity = $tournaments->newEntity([
            'title' => (string)($data['title'] ?? 'Untitled contest'),
            'category' => $category,
            'time_control' => (string)($data['time_control'] ?? $meta['display']),
            'entry_fee' => $entryFee,
            'prize_pool' => $prizePool,
            'platform_fee' => $platformFee,
            'platform_revenue' => 0.0,
            'max_slots' => $maxSlots,
            'filled_slots' => 0,
            'status' => 'Upcoming',
            'organizer_id' => $organizerId,
            'prize_breakdown' => $breakdown,
            'base_seconds' => (int)($data['base_seconds'] ?? $meta['base']),
            'increment_seconds' => (int)($data['increment_seconds'] ?? $meta['increment']),
            'rules' => (string)($data['rules'] ?? ''),
            'start_time' => $data['start_time'] ?? date('Y-m-d H:i:s'),
        ], ['validate' => true]);

        if ($entity->hasErrors()) {
            throw new RuntimeException(implode(' ', array_merge(...array_values($entity->getErrors()))));
        }

        $tournaments->saveOrFail($entity);

        return $entity;
    }

    /**
     * Sell a seat to a player.
     *
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @param \App\Model\Entity\User $user The player.
     * @param int|null $createdBy The administrator that triggered the join.
     * @return array{ok: bool, message: string, registration: \App\Model\Entity\TournamentRegistration|null}
     */
    public function join(Tournament $tournament, User $user, ?int $createdBy = null): array
    {
        if ($tournament->get('status') !== 'Upcoming') {
            return $this->failed('This contest is no longer accepting entries.');
        }
        if ((int)$tournament->get('filled_slots') >= (int)$tournament->get('max_slots')) {
            return $this->failed('All seats of this contest are sold.');
        }

        /** @var \App\Model\Table\TournamentRegistrationsTable $registrations */
        $registrations = $this->getTableLocator()->get('TournamentRegistrations');
        $existing = $registrations->find()
            ->where([
                'tournament_id' => $tournament->get('id'),
                'user_id' => $user->get('id'),
            ])
            ->first();
        if ($existing !== null) {
            return $this->failed('You are already registered for this contest.');
        }

        $fee = round((float)$tournament->get('entry_fee'), 2);
        if ($fee > 0 && (float)$user->get('deposit_balance') < $fee) {
            return $this->failed('Your wallet does not have enough balance for this entry fee.');
        }

        /** @var \App\Model\Table\TournamentsTable $tournaments */
        $tournaments = $this->getTableLocator()->get('Tournaments');
        $connection = $tournaments->getConnection();

        return $connection->transactional(function () use (
            $tournament,
            $user,
            $fee,
            $registrations,
            $tournaments,
            $createdBy
        ): array {
            if ($fee > 0) {
                $debited = $this->wallet->debit(
                    $user,
                    $fee,
                    'entry_fee',
                    sprintf('Entry fee for %s', $tournament->get('title')),
                    (int)$tournament->get('id'),
                    $createdBy,
                );
                if (!$debited) {
                    return $this->failed('Your wallet does not have enough balance for this entry fee.');
                }
                $users = $this->getTableLocator()->get('Users');
                if (!$users->save($user)) {
                    return $this->failed('The entry fee could not be booked.');
                }
            }

            $registration = $registrations->newEntity([
                'tournament_id' => $tournament->get('id'),
                'user_id' => $user->get('id'),
                'score' => 0,
                'time_bonus' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'rank' => 0,
                'prize_won' => 0,
                'status' => 'Registered',
                'registered_at' => date('Y-m-d H:i:s'),
            ]);

            if (!$registrations->save($registration)) {
                return $this->failed('The registration could not be stored.');
            }

            $tournament->set('filled_slots', (int)$tournament->get('filled_slots') + 1);
            if (!$tournaments->save($tournament)) {
                return $this->failed('The contest could not be updated.');
            }

            // Recalculate prize pool based on filled seats
            $updatedPool = $this->payouts->announcedPool(
                (float)$tournament->get('entry_fee'),
                (int)$tournament->get('filled_slots'),
                (float)($tournament->get('platform_fee') ?? PayoutService::DEFAULT_PLATFORM_FEE),
            );
            $tournament->set('prize_pool', $updatedPool);
            $tournaments->save($tournament);

            return [
                'ok' => true,
                'message' => sprintf('You joined %s.', $tournament->get('title')),
                'registration' => $registration,
            ];
        });
    }

    /**
     * Give a seat back and refund the entry fee.
     *
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @param \App\Model\Entity\User $user The player.
     * @param int|null $createdBy The administrator that triggered the leave.
     * @return array{ok: bool, message: string, registration: \App\Model\Entity\TournamentRegistration|null}
     */
    public function leave(Tournament $tournament, User $user, ?int $createdBy = null): array
    {
        if ($tournament->get('status') !== 'Upcoming') {
            return $this->failed('Entries are locked because the contest already started.');
        }

        /** @var \App\Model\Table\TournamentRegistrationsTable $registrations */
        $registrations = $this->getTableLocator()->get('TournamentRegistrations');
        $registration = $registrations->find()
            ->where([
                'tournament_id' => $tournament->get('id'),
                'user_id' => $user->get('id'),
            ])
            ->first();
        if ($registration === null) {
            return $this->failed('You are not registered for this contest.');
        }

        /** @var \App\Model\Table\TournamentsTable $tournaments */
        $tournaments = $this->getTableLocator()->get('Tournaments');
        $connection = $tournaments->getConnection();

        return $connection->transactional(function () use (
            $tournament,
            $user,
            $registration,
            $registrations,
            $tournaments,
            $createdBy
        ): array {
            $fee = round((float)$tournament->get('entry_fee'), 2);
            if ($fee > 0) {
                $this->wallet->credit(
                    $user,
                    $fee,
                    'entry_refund',
                    sprintf('Refund for %s', $tournament->get('title')),
                    (int)$tournament->get('id'),
                    $createdBy,
                    false,
                );
                $users = $this->getTableLocator()->get('Users');
                $users->save($user);
            }

            if (!$registrations->delete($registration)) {
                return $this->failed('The registration could not be removed.');
            }

            $tournament->set('filled_slots', max(0, (int)$tournament->get('filled_slots') - 1));
            $tournaments->save($tournament);

            // Recalculate prize pool based on filled seats
            $updatedPool = $this->payouts->announcedPool(
                (float)$tournament->get('entry_fee'),
                (int)$tournament->get('filled_slots'),
                (float)($tournament->get('platform_fee') ?? PayoutService::DEFAULT_PLATFORM_FEE),
            );
            $tournament->set('prize_pool', $updatedPool);
            $tournaments->save($tournament);

            return [
                'ok' => true,
                'message' => 'Your entry was cancelled and the fee was refunded.',
                'registration' => null,
            ];
        });
    }

    /**
     * The registrations of a contest with their players attached.
     *
     * @param int $tournamentId The contest.
     * @return array<int, \App\Model\Entity\TournamentRegistration>
     */
    public function registrations(int $tournamentId): array
    {
        /** @var \App\Model\Table\TournamentRegistrationsTable $registrations */
        $registrations = $this->getTableLocator()->get('TournamentRegistrations');

        return $registrations->find()
            ->where(['TournamentRegistrations.tournament_id' => $tournamentId])
            ->contain(['Users'])
            ->all()
            ->toList();
    }

    /**
     * The standings of a contest.
     *
     * @param int $tournamentId The contest.
     * @return array<int, \App\Model\Entity\TournamentRegistration>
     */
    public function standings(int $tournamentId): array
    {
        $registrations = $this->registrations($tournamentId);

        $durations = $this->matchDurations($tournamentId);

        return $this->ranking->rank($registrations, $durations);
    }

    /**
     * Match duration in seconds keyed by registration id for a contest.
     *
     * @param int $tournamentId The contest.
     * @return array<int, float>
     */
    public function matchDurations(int $tournamentId): array
    {
        $durations = [];
        $matches = $this->getTableLocator()->get('Matches')
            ->find()
            ->where(['Matches.tournament_id' => $tournamentId])
            ->all()
            ->toList();
        foreach ($matches as $match) {
            $duration = $match->durationSeconds();
            if ($duration === null) {
                continue;
            }
            $whiteId = (int)$match->get('white_player_id');
            $blackId = (int)$match->get('black_player_id');
            $whiteReg = $this->registrationForUser($tournamentId, $whiteId);
            $blackReg = $this->registrationForUser($tournamentId, $blackId);
            if ($whiteReg !== null) {
                $durations[(int)$whiteReg->get('id')] = $duration;
            }
            if ($blackReg !== null) {
                $durations[(int)$blackReg->get('id')] = $duration;
            }
        }

        return $durations;
    }

    /**
     * Find the registration of a user in a contest.
     *
     * @param int $tournamentId The contest.
     * @param int $userId The user.
     * @return \App\Model\Entity\TournamentRegistration|null
     */
    protected function registrationForUser(int $tournamentId, int $userId): ?\App\Model\Entity\TournamentRegistration
    {
        return $this->getTableLocator()->get('TournamentRegistrations')
            ->find()
            ->where([
                'tournament_id' => $tournamentId,
                'user_id' => $userId,
            ])
            ->first();
    }

    /**
     * Explain why one player is ranked above another (tie-break basis).
     *
     * @param \App\Model\Entity\TournamentRegistration $a The higher ranked player.
     * @param \App\Model\Entity\TournamentRegistration $b The lower ranked player.
     * @param int $tournamentId The contest (used to look up match durations).
     * @return string|null
     */
    public function rankingTieBreakBasis(TournamentRegistration $a, TournamentRegistration $b, int $tournamentId): ?string
    {
        $durations = $this->matchDurations($tournamentId);

        return $this->ranking->tieBreakBasis($a, $b, $durations);
    }

    /**
     * Settle a contest and pay out the prize money.
     *
     * The money that was actually collected decides the split: the platform
     * keeps its commission and the rest is shared by the winners.
     *
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @param int|null $createdBy The administrator that triggered the settlement.
     * @return array{payouts: array<int, float>, unclaimed: float, standings: array<int, \App\Model\Entity\TournamentRegistration>}
     */
    public function settle(Tournament $tournament, ?int $createdBy = null): array
    {
        /** @var \App\Model\Table\TournamentsTable $tournaments */
        $tournaments = $this->getTableLocator()->get('Tournaments');
        /** @var \App\Model\Table\TournamentRegistrationsTable $registrations */
        $registrations = $this->getTableLocator()->get('TournamentRegistrations');

        $standings = $this->standings((int)$tournament->get('id'));

        // The collected fees are split between the platform and the winners.
        $platformCut = $this->payouts->platformCut($tournament);
        $prizePool = $this->payouts->distributablePool($tournament);
        $tournament->set('prize_pool', $prizePool);
        $tournament->set('platform_revenue', $platformCut);

        // Places that nobody reached are folded into the places that were
        // reached, so the whole prize pool belongs to the winners.
        $breakdown = $this->payouts->fitBreakdown(
            (array)$tournament->get('prize_breakdown'),
            $standings,
        );
        if ($breakdown !== []) {
            $tournament->set('prize_breakdown', $breakdown);
        }

        $payouts = $this->payouts->distribute($tournament, $standings);
        $users = $this->getTableLocator()->get('Users');
        $platformAccount = $this->platformAccount();

        $byId = [];
        foreach ($standings as $registration) {
            $byId[(int)$registration->get('id')] = $registration;
        }

        $connection = $tournaments->getConnection();
        $connection->transactional(function () use (
            $payouts,
            $byId,
            $registrations,
            $users,
            $tournaments,
            $tournament,
            $createdBy,
            $platformAccount,
            $platformCut
        ): void {
            foreach ($payouts as $registrationId => $amount) {
                $registration = $byId[$registrationId] ?? null;
                if ($registration === null) {
                    continue;
                }

                $registration->set('prize_won', $amount);
                $registration->set('status', 'Finished');
                $registrations->save($registration);

                $user = $registration->get('user');
                if ($user === null) {
                    continue;
                }

                $this->wallet->credit(
                    $user,
                    $amount,
                    'prize',
                    sprintf('Prize for rank %d in %s', (int)$registration->get('rank'), $tournament->get('title')),
                    (int)$tournament->get('id'),
                    $createdBy,
                    true,
                );
                $users->save($user);
            }

            // The commission of the platform is the profit of the arena.
            if ($platformAccount !== null && $platformCut > 0) {
                $this->wallet->credit(
                    $platformAccount,
                    $platformCut,
                    'platform_commission',
                    sprintf('Platform commission for %s', $tournament->get('title')),
                    (int)$tournament->get('id'),
                    $createdBy,
                    false,
                );
                $users->save($platformAccount);
            }

            $tournament->set('status', 'Completed');
            $tournaments->save($tournament);
        });

        return [
            'payouts' => $payouts,
            'unclaimed' => $this->payouts->unclaimed($tournament, $standings),
            'standings' => $standings,
            'platform_revenue' => $platformCut,
            'prize_pool' => $prizePool,
        ];
    }

    /**
     * Remove a player from a contest and refund the entry fee.
     *
     * The administration and the organizer use this when they take a seat away
     * from a player. The fee is refunded because the player cannot win a prize
     * in a contest they are no longer part of.
     *
     * @param \App\Model\Entity\Tournament $tournament The contest.
     * @param int $userId The player that is removed.
     * @param int|null $createdBy The administrator that triggered the removal.
     * @return array{ok: bool, message: string, registration: \App\Model\Entity\TournamentRegistration|null}
     */
    public function removePlayer(Tournament $tournament, int $userId, ?int $createdBy = null): array
    {
        if ($tournament->get('status') !== 'Upcoming') {
            return $this->failed('Entries are locked because the contest already started.');
        }

        /** @var \App\Model\Table\TournamentRegistrationsTable $registrations */
        $registrations = $this->getTableLocator()->get('TournamentRegistrations');
        $registration = $registrations->find()
            ->where([
                'tournament_id' => $tournament->get('id'),
                'user_id' => $userId,
            ])
            ->first();
        if ($registration === null) {
            return $this->failed('That player is not registered for this contest.');
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->getTableLocator()->get('Users');
        $user = $users->get($userId);

        /** @var \App\Model\Table\TournamentsTable $tournaments */
        $tournaments = $this->getTableLocator()->get('Tournaments');
        $connection = $tournaments->getConnection();

        return $connection->transactional(function () use (
            $tournament,
            $user,
            $registration,
            $registrations,
            $tournaments,
            $users,
            $createdBy
        ): array {
            $fee = round((float)$tournament->get('entry_fee'), 2);
            if ($fee > 0) {
                $this->wallet->credit(
                    $user,
                    $fee,
                    'entry_refund',
                    sprintf('Refund for %s', $tournament->get('title')),
                    (int)$tournament->get('id'),
                    $createdBy,
                    false,
                );
                $users->save($user);
            }

            if (!$registrations->delete($registration)) {
                return $this->failed('The registration could not be removed.');
            }

            $tournament->set('filled_slots', max(0, (int)$tournament->get('filled_slots') - 1));
            $tournaments->save($tournament);

            // Recalculate prize pool based on filled seats
            $updatedPool = $this->payouts->announcedPool(
                (float)$tournament->get('entry_fee'),
                (int)$tournament->get('filled_slots'),
                (float)($tournament->get('platform_fee') ?? PayoutService::DEFAULT_PLATFORM_FEE),
            );
            $tournament->set('prize_pool', $updatedPool);
            $tournaments->save($tournament);

            return [
                'ok' => true,
                'message' => 'The player was removed and the entry fee was refunded.',
                'registration' => null,
            ];
        });
    }

    /**
     * The platform fee of a contest in percent.
     *
     * @param array<string, mixed> $data The submitted data.
     * @return float
     */
    protected function platformFee(array $data): float
    {
        $fee = $data['platform_fee'] ?? null;
        if ($fee === null || $fee === '') {
            return PayoutService::DEFAULT_PLATFORM_FEE;
        }

        return max(0.0, min(100.0, round((float)$fee, 2)));
    }

    /**
     * The account that collects the commission of the platform.
     *
     * @return \App\Model\Entity\User|null
     */
    protected function platformAccount(): ?User
    {
        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->getTableLocator()->get('Users');

        return $users->find()
            ->innerJoinWith('Roles')
            ->where(['Roles.name' => RoleRegistry::ADMIN])
            ->orderBy(['Users.id' => 'ASC'])
            ->first();
    }


    /**
     * Build a failed result.
     *
     * @param string $message The reason.
     * @return array{ok: bool, message: string, registration: \App\Model\Entity\TournamentRegistration|null}
     */
    protected function failed(string $message): array
    {
        return ['ok' => false, 'message' => $message, 'registration' => null];
    }
}
