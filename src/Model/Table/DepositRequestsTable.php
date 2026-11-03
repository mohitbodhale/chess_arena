<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * DepositRequests Model
 *
 * @method \App\Model\Entity\DepositRequest newEmptyEntity()
 * @method \App\Model\Entity\DepositRequest newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\DepositRequest[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\DepositRequest get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\DepositRequest findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\DepositRequest patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\DepositRequest[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\DepositRequest|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\DepositRequest saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\DepositRequest>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\DepositRequest>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\DepositRequest>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\DepositRequest>|false saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\DepositRequest>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\DepositRequest>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\DepositRequest>|\Cake\ORM\ResultSetInterface<\App\Model\Entity\DepositRequest>|false deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Approver
 */
class DepositRequestsTable extends Table
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

        $this->setTable('deposit_requests');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Approver', [
            'className' => 'Users',
            'foreignKey' => 'approved_by',
            'joinType' => 'LEFT',
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
            ->integer('user_id')
            ->notEmptyString('user_id');

        $validator
            ->decimal('amount')
            ->greaterThan('amount', 0)
            ->notEmptyString('amount');

        $validator
            ->scalar('upi_id')
            ->maxLength('upi_id', 100)
            ->allowEmptyString('upi_id');

        $validator
            ->scalar('reference')
            ->maxLength('reference', 100)
            ->notEmptyString('reference');

        $validator
            ->scalar('status')
            ->inList('status', ['pending', 'approved', 'rejected'])
            ->notEmptyString('status');

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

        return $rules;
    }
}
