<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Matches Model
 *
 * @property \App\Model\Table\TournamentsTable&\Cake\ORM\Association\BelongsTo $Tournaments
 *
 * @method \App\Model\Entity\Match newEmptyEntity()
 * @method \App\Model\Entity\Match newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Match> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Match get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Match findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Match patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Match> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Match|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Match saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Match>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Match>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Match>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Match> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Match>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Match>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Match>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Match> deleteManyOrFail(iterable $entities, array $options = [])
 */
class MatchesTable extends Table
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

        $this->setTable('matches');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Tournaments', [
            'foreignKey' => 'tournament_id',
            'joinType' => 'INNER',
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
            ->integer('tournament_id')
            ->notEmptyString('tournament_id');

        $validator
            ->integer('white_player_id')
            ->requirePresence('white_player_id', 'create')
            ->notEmptyString('white_player_id');

        $validator
            ->integer('black_player_id')
            ->requirePresence('black_player_id', 'create')
            ->notEmptyString('black_player_id');

        $validator
            ->scalar('result')
            ->allowEmptyString('result');

        $validator
            ->scalar('pgn')
            ->allowEmptyString('pgn');

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
        $rules->add($rules->existsIn(['tournament_id'], 'Tournaments'), ['errorField' => 'tournament_id']);

        return $rules;
    }
}
