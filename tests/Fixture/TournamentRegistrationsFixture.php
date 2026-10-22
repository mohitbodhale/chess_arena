<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TournamentRegistrationsFixture
 */
class TournamentRegistrationsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'tournament_id' => 1,
                'user_id' => 1,
                'score' => 1.5,
                'rank' => 1,
                'prize_won' => 1.5,
                'registered_at' => 1792587459,
            ],
        ];
        parent::init();
    }
}
