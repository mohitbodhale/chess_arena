<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TournamentsFixture
 */
class TournamentsFixture extends TestFixture
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
                'title' => 'Lorem ipsum dolor sit amet',
                'category' => 'Lorem ipsum dolor sit amet',
                'time_control' => 'Lorem ipsum dolor ',
                'entry_fee' => 1.5,
                'prize_pool' => 1.5,
                'max_slots' => 1,
                'filled_slots' => 1,
                'status' => 'Lorem ipsum dolor sit amet',
                'start_time' => '2026-10-21 12:57:32',
                'created_at' => 1792587452,
            ],
        ];
        parent::init();
    }
}
