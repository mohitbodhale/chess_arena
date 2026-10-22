<?php
/**
 * The user directory.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedInterface $users
 * @var string $role
 */

use App\Rbac\RoleRegistry;

$this->assign('title', 'Players');

$filterOptions = ['' => 'Every role'];
$assignOptions = [];
foreach (RoleRegistry::ID_MAP as $roleId => $roleName) {
    $filterOptions[$roleName] = $roleName;
    $assignOptions[(int)$roleId] = $roleName;
}
?>
<div class="content-header">
    <div class="container-fluid">
        <span class="eyebrow">Administration</span>
        <h1 style="font-size:1.5rem;margin:.35rem 0 0;">Players and roles</h1>
    </div>
</div>

<div class="container-fluid">
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row align-items-end" style="gap:.6rem;">
                <div class="col-md-4 col-6">
                    <div class="field" style="margin-bottom:0;">
                        <label for="filter-role">Role</label>
                        <select class="form-control" id="filter-role" name="role">
                            <?php foreach ($filterOptions as $value => $label) : ?>
                                <option value="<?= h((string)$value) ?>" <?= (string)$role === (string)$value ? 'selected' : '' ?>>
                                    <?= h($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn-arena" style="width:100%;">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Player</th>
                        <th style="width:200px;">Email</th>
                        <th style="width:100px;">Rating</th>
                        <th style="width:150px;">Wallet</th>
                        <th style="width:180px;">Role</th>
                        <th style="width:110px;">Joined</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($users) === 0) : ?>
                        <tr>
                            <td colspan="7" class="muted" style="padding:2rem;text-align:center;">
                                No account matches this filter.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($users as $user) : ?>
                        <tr>
                            <td class="mono tiny"><?= (int)$user->get('id') ?></td>
                            <td><?= h((string)$user->get('username')) ?></td>
                            <td class="tiny muted"><?= h((string)$user->get('email')) ?></td>
                            <td class="mono tiny"><?= (int)$user->get('rating') ?></td>
                            <td class="mono tiny">
                                ₹<?= number_format((float)$user->get('deposit_balance'), 2) ?>
                                <span class="d-block muted" style="font-size:.7rem;">
                                    +₹<?= number_format((float)$user->get('winnings_balance'), 2) ?> won
                                </span>
                            </td>
                            <td>
                                <?= $this->Form->create(null, [
                                    'url' => ['controller' => 'Admin', 'action' => 'setRole'],
                                    'type' => 'post',
                                ]) ?>
                                <?= $this->Form->hidden('user_id', ['value' => $user->get('id')]) ?>
                                <div class="d-flex" style="gap:.35rem;">
                                    <?= $this->Form->select('role_id', $assignOptions, [
                                        'class' => 'form-control form-control-sm',
                                        'value' => $user->get('role_id'),
                                    ]) ?>
                                    <?= $this->Form->button('Save', [
                                        'class' => 'btn-arena',
                                        'style' => 'padding:.35rem .8rem;font-size:.78rem;',
                                    ]) ?>
                                </div>
                                <?= $this->Form->end() ?>
                            </td>
                            <td class="tiny muted"><?= h((string)($user->get('created_at')?->format('d M, Y') ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (count($users) > 0) : ?>
            <div class="card-footer" style="background:transparent;border-top:1px solid var(--line);">
                <?= $this->element('pagination') ?>
            </div>
        <?php endif; ?>
    </div>
</div>
