<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Service\Tournament\TimeControl;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Tournaments Model
 *
 * @property \App\Model\Table\MatchesTable&\Cake\ORM\Association\HasMany $Matches
 * @property \App\Model\Table\TournamentRegistrationsTable&\Cake\ORM\Association\HasMany $TournamentRegistrations
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Organizer
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
 * @method iterable<\App\Model\Entity\Tournament>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\Tournament>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Tournament>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\Tournament>|false saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Tournament>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\Tournament>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Tournament>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\Tournament>|false deleteManyOrFail(iterable $entities, array $options = [])
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
        $this->belongsTo('Organizer', [
            'className' => 'Users',
            'foreignKey' => 'organizer_id',
            'propertyName' => 'organizer',
            'joinType' => 'LEFT',
        ]);

        // The payout ladder is stored as JSON in a text column.
        $this->getSchema()->setColumnType('prize_breakdown', 'json');
    }

    /**
     * Contests that anybody may browse.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query.
     * @param array<string, mixed> $options Finder options.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findOpen(SelectQuery $query, array $options = []): SelectQuery
    {
        return $query->where(['Tournaments.status IN' => ['Upcoming', 'Live']]);
    }

    /**
     * Contests that are currently being played.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query.
     * @param array<string, mixed> $options Finder options.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findLive(SelectQuery $query, array $options = []): SelectQuery
    {
        return $query->where(['Tournaments.status' => 'Live']);
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
            ->inList('category', array_keys(TimeControl::CATEGORIES))
            ->notEmptyString('category');

        $validator
            ->scalar('time_control')
            ->maxLength('time_control', 20)
            ->notEmptyString('time_control');

        $validator
            ->decimal('entry_fee')
            ->greaterThanOrEqual('entry_fee', 0)
            ->notEmptyString('entry_fee');

        $validator
            ->decimal('prize_pool')
            ->greaterThanOrEqual('prize_pool', 0)
            ->notEmptyString('prize_pool');

        $validator
            ->decimal('platform_fee')
            ->greaterThanOrEqual('platform_fee', 0)
            ->lessThanOrEqual('platform_fee', 100)
            ->allowEmptyString('platform_fee');

        $validator
            ->decimal('platform_revenue')
            ->greaterThanOrEqual('platform_revenue', 0)
            ->allowEmptyString('platform_revenue');

        $validator
            ->integer('max_slots')
            ->greaterThanOrEqual('max_slots', 2)
            ->lessThanOrEqual('max_slots', 100000)
            ->notEmptyString('max_slots');

        $validator
            ->integer('filled_slots')
            ->greaterThanOrEqual('filled_slots', 0)
            ->notEmptyString('filled_slots');

        $validator
            ->scalar('status')
            ->inList('status', ['Upcoming', 'Live', 'Completed'])
            ->notEmptyString('status');

        $validator
            ->integer('organizer_id')
            ->allowEmptyString('organizer_id');

        $validator
            ->integer('base_seconds')
            ->greaterThanOrEqual('base_seconds', 10)
            ->allowEmptyString('base_seconds');

        $validator
            ->integer('increment_seconds')
            ->greaterThanOrEqual('increment_seconds', 0)
            ->allowEmptyString('increment_seconds');

        $validator
            ->scalar('rules')
            ->allowEmptyString('rules');

        $validator
            ->dateTime('start_time')
            ->requirePresence('start_time', 'create')
            ->notEmptyDateTime('start_time');

        $validator
            ->dateTime('created_at')
            ->notEmptyDateTime('created_at');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['organizer_id'], 'Organizer'), ['errorField' => 'organizer_id']);

        return $rules;
    }
}
