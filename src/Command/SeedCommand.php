<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\Tournament\SimulationService;
use App\Service\Tournament\TournamentService;
use App\Service\Tournament\WalletService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Fills the arena with a playable set of demo data.
 *
 * The command uses the real services, so the demo data is produced by exactly
 * the same code path that a real contest goes through.
 */
class SeedCommand extends Command
{
    use LocatorAwareTrait;

    /**
     * The password every demo account uses.
     */
    public const PASSWORD = 'Arena@123';

    /**
     * Build the option parser.
     *
     * @param ConsoleOptionParser $parser The parser.
     * @return ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Seed the arena with demo accounts, contests and games.')
            ->addOption('fresh', [
                'short' => 'f',
                'help' => 'Remove the existing contests, games and ledger before seeding.',
                'boolean' => true,
                'default' => false,
            ]);

        return $parser;
    }

    /**
     * Run the command.
     *
     * @param \Cake\Console\Arguments $args The arguments.
     * @param \Cake\Console\ConsoleIo $io The console.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        if ($args->getOption('fresh')) {
            $this->truncate($io);
        }

        $users = $this->getTableLocator()->get('Users');
        $wallet = new WalletService();
        $tournaments = new TournamentService();
        $simulation = new SimulationService();

        $admin = $this->createUser($users, $wallet, 'admin', 'admin@chessarena.test', 1, 2200);
        $organizer = $this->createUser($users, $wallet, 'organizer', 'organizer@chessarena.test', 2, 1950);

        $players = [];
        $roster = [
            ['aria', 3, 2100],
            ['bhavya', 3, 1980],
            ['chetan', 3, 1870],
            ['divya', 3, 1760],
            ['eshan', 3, 1650],
            ['fatima', 3, 1540],
            ['gopal', 3, 1430],
            ['hina', 3, 1320],
        ];
        foreach ($roster as [$name, $roleId, $rating]) {
            $players[] = $this->createUser(
                $users,
                $wallet,
                $name,
                $name . '@chessarena.test',
                $roleId,
                $rating,
            );
        }

        $io->out(sprintf('<info>Accounts ready</info>: %d', count($players) + 2));

        $payouts = new \App\Service\Tournament\PayoutService();

        $blitz = $tournaments->create([
            'title' => 'Friday Night Blitz Championship',
            'category' => 'Blitz',
            'entry_fee' => 100,
            'max_slots' => 50,
            'platform_fee' => 10,
            'prize_breakdown' => $payouts->templateRows('top3'),
            'rules' => "Win = 3 points, draw = 1 point, loss = 0 points.\nUp to 2 time bonus points per game.",
            'start_time' => date('Y-m-d H:i:s', strtotime('-3 hours')),
        ], (int)$organizer->get('id'));

        $rapid = $tournaments->create([
            'title' => 'Rapid Rush Hour Ladder',
            'category' => 'Rapid',
            'entry_fee' => 50,
            'max_slots' => 30,
            'platform_fee' => 10,
            'prize_breakdown' => $payouts->templateRows('top5'),
            'rules' => "Win = 3 points, draw = 1 point, loss = 0 points.",
            'start_time' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        ], (int)$organizer->get('id'));

        $bullet = $tournaments->create([
            'title' => 'Bullet Blitz Hour',
            'category' => 'Bullet',
            'entry_fee' => 25,
            'max_slots' => 100,
            'platform_fee' => 10,
            'prize_breakdown' => $payouts->templateRows('top10'),
            'rules' => "Sixty seconds a side. Fast hands only.",
            'start_time' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ], (int)$admin->get('id'));

        $open = $tournaments->create([
            'title' => 'Weekend Rapid Arena',
            'category' => 'Rapid',
            'entry_fee' => 20,
            'max_slots' => 40,
            'platform_fee' => 10,
            'prize_breakdown' => $payouts->templateRows('top5'),
            'rules' => "Win = 3 points, draw = 1 point, loss = 0 points. Open for entries.",
            'start_time' => date('Y-m-d H:i:s', strtotime('+2 days')),
        ], (int)$organizer->get('id'));

        $io->out('<info>Contests ready</info>: 4');

        foreach (array_slice($players, 0, 6) as $player) {
            $tournaments->join($blitz, $player);
            $tournaments->join($bullet, $player);
        }
        foreach (array_slice($players, 4, 4) as $player) {
            $tournaments->join($rapid, $player);
        }
        foreach (array_slice($players, 0, 4) as $player) {
            $tournaments->join($open, $player);
        }

        $io->out('<info>Seats sold</info>: 20');

        $matches = new \App\Service\Tournament\MatchService();

        // Every finished contest pays 90% of the collected fees to the winners
        // and leaves the commission with the arena.
        foreach ([$blitz, $rapid, $bullet] as $contest) {
            $matches->start($contest);
            $games = $this->getTableLocator()->get('Matches')
                ->find()
                ->where(['Matches.tournament_id' => $contest->get('id')])
                ->all()
                ->toList();
            foreach ($games as $game) {
                $simulation->simulate($game, null, 40);
            }

            $settlement = $tournaments->settle($contest, (int)$admin->get('id'));
            $io->out(sprintf(
                '<info>Settled</info>: %s — ₹%s to the winners, ₹%s platform commission',
                $contest->get('title'),
                number_format(array_sum($settlement['payouts']), 2),
                number_format($settlement['platform_revenue'], 2),
            ));
        }

        $io->out('');
        $io->out('<success>The arena is ready.</success>');
        $io->out('Admin      admin@chessarena.test / ' . self::PASSWORD);
        $io->out('Organizer  organizer@chessarena.test / ' . self::PASSWORD);
        $io->out('Player     aria@chessarena.test / ' . self::PASSWORD);

        return static::CODE_SUCCESS;
    }

    /**
     * Create a player and give the account the welcome balance.
     *
     * @param \App\Model\Table\UsersTable $users The users table.
     * @param \App\Service\Tournament\WalletService $wallet The wallet service.
     * @param string $username The username.
     * @param string $email The email address.
     * @param int $roleId The role.
     * @param int $rating The starting rating.
     * @return \App\Model\Entity\User
     */
    protected function createUser(
        \App\Model\Table\UsersTable $users,
        WalletService $wallet,
        string $username,
        string $email,
        int $roleId,
        int $rating,
    ): \App\Model\Entity\User {
        $existing = $users->find()->where(['email' => $email])->first();
        if ($existing !== null) {
            return $existing;
        }

        $user = $users->newEntity([
            'role_id' => $roleId,
            'username' => $username,
            'email' => $email,
            'password' => self::PASSWORD,
            'plain_password' => self::PASSWORD,
            'deposit_balance' => '0.00',
            'winnings_balance' => '0.00',
            'rating' => $rating,
            'status' => 'Active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $users->saveOrFail($user);

        $wallet->credit(
            $user,
            5000.00,
            'signup_bonus',
            'Welcome bonus',
            null,
            null,
            false,
        );
        $users->save($user);

        return $user;
    }

    /**
     * Empty the contest tables so the seed can run again.
     *
     * @param \Cake\Console\ConsoleIo $io The console.
     * @return void
     */
    protected function truncate(ConsoleIo $io): void
    {
        $connection = $this->getTableLocator()->get('Tournaments')->getConnection();

        $connection->execute('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['wallet_transactions', 'matches', 'tournament_registrations', 'tournaments', 'users'] as $table) {
            $connection->execute('TRUNCATE TABLE `' . $table . '`');
        }
        $connection->execute('SET FOREIGN_KEY_CHECKS = 1');

        $io->out('<info>Cleared</info>: users, contests, registrations, matches and the ledger');
    }
}
