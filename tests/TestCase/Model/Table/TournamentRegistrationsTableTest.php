<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TournamentRegistrationsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TournamentRegistrationsTable Test Case
 */
class TournamentRegistrationsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TournamentRegistrationsTable
     */
    protected $TournamentRegistrations;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.TournamentRegistrations',
        'app.Tournaments',
        'app.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('TournamentRegistrations') ? [] : ['className' => TournamentRegistrationsTable::class];
        $this->TournamentRegistrations = $this->getTableLocator()->get('TournamentRegistrations', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->TournamentRegistrations);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\TournamentRegistrationsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\TournamentRegistrationsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
