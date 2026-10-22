<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\ArenaMatch;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Matches Model
 *
 * @property \App\Model\Table\TournamentsTable&\Cake\ORM\Association\BelongsTo $Tournaments
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $WhitePlayers
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $BlackPlayers
 *
 * @method \App\Model\Entity\ArenaMatch newEmptyEntity()
 * @method \App\Model\Entity\ArenaMatch newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\ArenaMatch> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ArenaMatch get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ArenaMatch findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\ArenaMatch patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\ArenaMatch> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ArenaMatch|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\ArenaMatch saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\ArenaMatch>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\ArenaMatch>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ArenaMatch>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\ArenaMatch>|false saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ArenaMatch>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\ArenaMatch>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ArenaMatch>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\ArenaMatch>|false deleteManyOrFail(iterable $entities, array $options = [])
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
        $this->setEntityClass(ArenaMatch::class);

        $this->belongsTo('Tournaments', [
            'foreignKey' => 'tournament_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('WhitePlayers', [
            'className' => 'Users',
            'foreignKey' => 'white_player_id',
            'propertyName' => 'white_player',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('BlackPlayers', [
            'className' => 'Users',
            'foreignKey' => 'black_player_id',
            'propertyName' => 'black_player',
            'joinType' => 'LEFT',
        ]);

        // The move list is stored as JSON in a text column.
        $this->getSchema()->setColumnType('moves', 'json');
    }

    /**
     * Matches that are still being played.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query.
     * @param array<string, mixed> $options Finder options.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findLive(SelectQuery $query, array $options = []): SelectQuery
    {
        return $query->where(['Matches.status' => 'Live']);
    }

    /**
     * The matches of a single player.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query.
     * @param array<string, mixed> $options Finder options with a `user_id` key.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findForPlayer(SelectQuery $query, array $options = []): SelectQuery
    {
        $userId = (int)($options['userId'] ?? 0);
        if ($userId <= 0) {
            return $query->where(['1 = 0']);
        }

        return $query->where([
            'OR' => [
                'Matches.white_player_id' => $userId,
                'Matches.black_player_id' => $userId,
            ],
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
            ->inList('result', ['Pending', 'White_Win', 'Black_Win', 'Draw'])
            ->allowEmptyString('result');

        $validator
            ->scalar('termination')
            ->allowEmptyString('termination');

        $validator
            ->scalar('pgn')
            ->allowEmptyString('pgn');

        $validator
            ->scalar('status')
            ->inList('status', ['Pending', 'Live', 'Completed'])
            ->notEmptyString('status');

        $validator
            ->scalar('fen')
            ->maxLength('fen', 120)
            ->allowEmptyString('fen');

        $validator
            ->integer('white_time_ms')
            ->greaterThanOrEqual('white_time_ms', 0)
            ->allowEmptyString('white_time_ms');

        $validator
            ->integer('black_time_ms')
            ->greaterThanOrEqual('black_time_ms', 0)
            ->allowEmptyString('black_time_ms');

        $validator
            ->integer('increment_ms')
            ->greaterThanOrEqual('increment_ms', 0)
            ->allowEmptyString('increment_ms');

        $validator
            ->integer('round_number')
            ->greaterThanOrEqual('round_number', 1)
            ->allowEmptyString('round_number');

        $validator
            ->dateTime('started_at')
            ->allowEmptyDateTime('started_at');

        $validator
            ->dateTime('ended_at')
            ->allowEmptyDateTime('ended_at');

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
        $rules->add($rules->existsIn(['white_player_id'], 'WhitePlayers'), ['errorField' => 'white_player_id']);
        $rules->add($rules->existsIn(['black_player_id'], 'BlackPlayers'), ['errorField' => 'black_player_id']);

        return $rules;
    }
}
