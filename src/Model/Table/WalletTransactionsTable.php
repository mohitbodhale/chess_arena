<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * WalletTransactions Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\TournamentsTable&\Cake\ORM\Association\BelongsTo $Tournaments
 *
 * @method \App\Model\Entity\WalletTransaction newEmptyEntity()
 * @method \App\Model\Entity\WalletTransaction newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\WalletTransaction> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\WalletTransaction get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\WalletTransaction findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\WalletTransaction patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\WalletTransaction> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\WalletTransaction|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\WalletTransaction saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\WalletTransaction>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\WalletTransaction>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WalletTransaction>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\WalletTransaction>|false saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WalletTransaction>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\WalletTransaction>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WalletTransaction>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\WalletTransaction>|false deleteManyOrFail(iterable $entities, array $options = [])
 */
class WalletTransactionsTable extends Table
{
    /**
     * Transaction types written by the wallet service.
     *
     * @var array<int, string>
     */
    public const TYPES = [
        'signup_bonus',
        'deposit',
        'entry_fee',
        'entry_refund',
        'prize',
        'platform_commission',
        'adjustment',
    ];

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('wallet_transactions');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Tournaments', [
            'foreignKey' => 'tournament_id',
            'joinType' => 'LEFT',
        ]);
    }

    /**
     * Ledger entries for a single wallet, newest first.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query.
     * @param array<string, mixed> $options Finder options.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findLedger(SelectQuery $query, array $options = []): SelectQuery
    {
        return $query->orderBy(['WalletTransactions.id' => 'DESC']);
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
            ->integer('user_id')
            ->notEmptyString('user_id');

        $validator
            ->integer('tournament_id')
            ->allowEmptyString('tournament_id');

        $validator
            ->scalar('type')
            ->inList('type', self::TYPES)
            ->notEmptyString('type');

        $validator
            ->scalar('direction')
            ->inList('direction', ['credit', 'debit'])
            ->notEmptyString('direction');

        $validator
            ->decimal('amount')
            ->greaterThanOrEqual('amount', 0)
            ->notEmptyString('amount');

        $validator
            ->decimal('balance_after')
            ->greaterThanOrEqual('balance_after', 0)
            ->allowEmptyString('balance_after');

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->allowEmptyString('description');

        $validator
            ->integer('created_by')
            ->allowEmptyString('created_by');

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
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['tournament_id'], 'Tournaments'), ['errorField' => 'tournament_id']);

        return $rules;
    }
}
