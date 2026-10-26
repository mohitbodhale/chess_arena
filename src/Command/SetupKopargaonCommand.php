<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\Tournament\MatchService;
use App\Service\Tournament\PayoutService;
use App\Service\Tournament\SimulationService;
use App\Service\Tournament\TournamentService;
use App\Service\Tournament\WalletService;
use App\Service\Tournament\TimeControl;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Create, fill, play and settle a kopargaon contest end-to-end.
 *
 * Usage:
 *   bin/cake.php setup_kopargaon --title="kopargaon chess master s2" --prize-percent=40
 */
class SetupKopargaonCommand extends Command
{
    use LocatorAwareTrait;

    public const PASSWORD = 'Arena@123';

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Create, fill, play and settle a kopargaon contest.')
            ->addOption('title', [
                'short' => 't',
                'help' => 'Tournament title.',
                'default' => 'kopargaon chess master s1',
            ])
            ->addOption('prize-percent', [
                'short' => 'p',
                'help' => 'Share of the players that receive a prize (0-100). Empty = payout ladder template.',
                'default' => null,
            ]);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $title = (string)($args->getOption('title') ?? 'kopargaon chess master s1');
        $prizePercentRaw = $args->getOption('prize-percent');
        $prizePercent = $prizePercentRaw === null || $prizePercentRaw === ''
            ? null
            : (float)$prizePercentRaw;

        $users = $this->getTableLocator()->get('Users');
        $tournamentsTable = $this->getTableLocator()->get('Tournaments');
        $tournamentService = new TournamentService();
        $matchService = new MatchService();
        $simulation = new SimulationService();
        $wallet = new WalletService();
        $payouts = new PayoutService();

        $organizer = $users->find()->where(['email' => 'admin@chessarena.test'])->first();
        if ($organizer === null) {
            $organizer = $users->newEntity([
                'role_id' => 1,
                'username' => 'admin',
                'email' => 'admin@chessarena.test',
                'password' => self::PASSWORD,
                'plain_password' => self::PASSWORD,
                'deposit_balance' => '5000.00',
                'winnings_balance' => '0.00',
                'rating' => 2200,
                'status' => 'Active',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $users->saveOrFail($organizer);
            $wallet->credit($organizer, 5000.00, 'signup_bonus', 'Welcome bonus', null, null, false);
            $users->save($organizer);
        }
        $io->out(sprintf('<info>Organizer:</info> %s (id=%d)', $organizer->get('username'), (int)$organizer->get('id')));

        $roster = [
            ['kopargaon_p1', 'p1@chessarena.test', 2100],
            ['kopargaon_p2', 'p2@chessarena.test', 2000],
            ['kopargaon_p3', 'p3@chessarena.test', 1900],
            ['kopargaon_p4', 'p4@chessarena.test', 1800],
            ['kopargaon_p5', 'p5@chessarena.test', 1700],
            ['kopargaon_p6', 'p6@chessarena.test', 1600],
            ['kopargaon_p7', 'p7@chessarena.test', 1500],
            ['kopargaon_p8', 'p8@chessarena.test', 1400],
            ['kopargaon_p9', 'p9@chessarena.test', 1300],
            ['kopargaon_p10', 'p10@chessarena.test', 1200],
        ];

        $players = [];
        foreach ($roster as [$username, $email, $rating]) {
            $user = $users->find()->where(['email' => $email])->first();
            if ($user === null) {
                $user = $users->newEntity([
                    'role_id' => 3,
                    'username' => $username,
                    'email' => $email,
                    'password' => self::PASSWORD,
                    'plain_password' => self::PASSWORD,
                    'deposit_balance' => '5000.00',
                    'winnings_balance' => '0.00',
                    'rating' => $rating,
                    'status' => 'Active',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $users->saveOrFail($user);
                $wallet->credit($user, 5000.00, 'signup_bonus', 'Welcome bonus', null, null, false);
                $users->save($user);
            }
            $players[] = $user;
            $io->out(sprintf('<info>Player:</info> %s (id=%d, rating=%d)', $user->get('username'), (int)$user->get('id'), $rating));
        }

        $existing = $tournamentsTable->find()->where(['Tournaments.title' => $title])->first();
        if ($existing !== null) {
            $io->out(sprintf('Clearing existing tournament "%s" (id=%d) and its data...', $title, (int)$existing->get('id')));
            $connection = $tournamentsTable->getConnection();
            $connection->execute('SET FOREIGN_KEY_CHECKS = 0');
            $connection->execute('DELETE FROM matches WHERE tournament_id = :id', ['id' => (int)$existing->get('id')]);
            $connection->execute('DELETE FROM tournament_registrations WHERE tournament_id = :id', ['id' => (int)$existing->get('id')]);
            $connection->execute('DELETE FROM tournaments WHERE id = :id', ['id' => (int)$existing->get('id')]);
            $connection->execute('SET FOREIGN_KEY_CHECKS = 1');
            $io->out('Cleared.');
        }

        $data = [
            'title' => $title,
            'category' => TimeControl::BLITZ,
            'entry_fee' => 100,
            'max_slots' => 10,
            'platform_fee' => 10,
            'rules' => 'Win = 3 points, draw = 1 point, loss = 0 points.',
            'start_time' => date('Y-m-d H:i:s'),
        ];
        if ($prizePercent !== null) {
            $data['prize_winners_percent'] = $prizePercent;
            $io->out(sprintf('<info>Prize facility:</info> top %s%% of players get paid', $prizePercent));
        } else {
            $data['prize_breakdown'] = $payouts->templateRows('top3');
            $io->out('<info>Prize facility:</info> default top-3 ladder');
        }

        $tournament = $tournamentService->create($data, (int)$organizer->get('id'));

        $io->out(sprintf(
            '<info>Tournament created:</info> %s (id=%d, max_slots=%d)',
            $tournament->get('title'),
            (int)$tournament->get('id'),
            (int)$tournament->get('max_slots')
        ));

        foreach ($players as $player) {
            $result = $tournamentService->join($tournament, $player, (int)$organizer->get('id'));
            if (!$result['ok']) {
                $io->error(sprintf('  Failed to join %s: %s', $player->get('username'), $result['message']));
                return static::CODE_ERROR;
            }
            $io->out(sprintf('  Joined: %s', $player->get('username')));
        }

        $tournament = $tournamentsTable->get($tournament->get('id'));
        $io->out(sprintf('Filled slots: %d/%d', (int)$tournament->get('filled_slots'), (int)$tournament->get('max_slots')));

        $created = $matchService->start($tournament);
        $io->out(sprintf('<info>Matches created:</info> %d', $created));

        $matchesTable = $this->getTableLocator()->get('Matches');
        $matches = $matchesTable->find()
            ->where(['Matches.tournament_id' => $tournament->get('id')])
            ->contain(['WhitePlayers', 'BlackPlayers'])
            ->all()
            ->toList();

        foreach ($matches as $match) {
            $result = $simulation->simulate($match, null, 160);
            $io->out(sprintf(
                '  Match #%d: %s vs %s -> %s (%d plies)',
                (int)$match->get('id'),
                $match->get('white_player')['username'] ?? '—',
                $match->get('black_player')['username'] ?? '—',
                $result['result'],
                $result['moves']
            ));
        }

        $settlement = $tournamentService->settle($tournament, (int)$organizer->get('id'));
        $io->out(sprintf(
            '<info>Settled:</info> ₹%s to winners, ₹%s platform revenue',
            number_format(array_sum($settlement['payouts']), 2),
            number_format($settlement['platform_revenue'], 2)
        ));

        $io->out(sprintf('<success>Done. "%s" is complete and settled.</success>', $title));
        return static::CODE_SUCCESS;
    }
}
