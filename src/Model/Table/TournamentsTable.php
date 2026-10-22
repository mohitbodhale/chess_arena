<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Tournaments Model
 *
 * @property \App\Model\Table\MatchesTable&\Cake\ORM\Association\HasMany $Matches
 * @property \App\Model\Table\TournamentRegistrationsTable&\Cake\ORM\Association\HasMany $TournamentRegistrations
 *
 * @method \App\Model\Entity\Tournament newEmptyEntity()
 * @method \App\Model\Entity\Tournament newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Tournament> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Tournament get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Tournament findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Tournament patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Tournament> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Tournament|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Tournament saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Tournament>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Tournament>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Tournament>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Tournament> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Tournament>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Tournament>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Tournament>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Tournament> deleteManyOrFail(iterable $entities, array $options = [])
 */
class TournamentsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('tournaments');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->hasMany('Matches', [
            'foreignKey' => 'tournament_id',
        ]);
        $this->hasMany('TournamentRegistrations', [
            'foreignKey' => 'tournament_id',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('title')
            ->maxLength('title', 150)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('category')
            ->notEmptyString('category');

        $validator
            ->scalar('time_control')
            ->maxLength('time_control', 20)
            ->notEmptyString('time_control');

        $validator
            ->decimal('entry_fee')
            ->notEmptyString('entry_fee');

        $validator
            ->decimal('prize_pool')
            ->notEmptyString('prize_pool');

        $validator
            ->integer('max_slots')
            ->notEmptyString('max_slots');

        $validator
            ->integer('filled_slots')
            ->notEmptyString('filled_slots');

        $validator
            ->scalar('status')
            ->notEmptyString('status');

        $validator
            ->dateTime('start_time')
            ->requirePresence('start_time', 'create')
            ->notEmptyDateTime('start_time');

        $validator
            ->dateTime('created_at')
            ->notEmptyDateTime('created_at');

        return $validator;
    }
}
