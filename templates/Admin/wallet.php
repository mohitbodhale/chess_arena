<?php
/**
 * The wallet auditor.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedInterface $entries
 * @var array<\App\Model\Entity\User> $players
 * @var array<int, string> $types
 */

$this->assign('title', 'Wallet auditor');

$playerOptions = [];
foreach ($players as $player) {
    $playerOptions[(int)$player->get('id')] = (string)$player->get('username')
        . ' — ₹' . number_format((float)$player->get('deposit_balance'), 2);
}
?>
<div class="content-header">
    <div class="container-fluid">
        <span class="eyebrow">Administration</span>
        <h1 style="font-size:1.5rem;margin:.35rem 0 0;">Wallet auditor</h1>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Manual adjustment</h3>
                </div>
                <div class="card-body">
                    <?= $this->Form->create(null, ['url' => ['controller' => 'Admin', 'action' => 'adjustWallet']]) ?>
                    <div class="field">
                        <?= $this->Form->label('user_id', 'Player') ?>
                        <?= $this->Form->select('user_id', $playerOptions, ['class' => 'form-control']) ?>
                    </div>
                    <div class="field">
                        <?= $this->Form->label('amount', 'Amount (₹, negative to debit)') ?>
                        <?= $this->Form->text('amount', ['class' => 'form-control', 'placeholder' => '-500', 'required' => true]) ?>
                    </div>
                    <div class="field">
                        <?= $this->Form->label('description', 'Reason') ?>
                        <?= $this->Form->text('description', [
                            'class' => 'form-control',
                            'placeholder' => 'Manual correction',
                        ]) ?>
                    </div>
                    <div class="field">
                        <label>
                            <?= $this->Form->checkbox('to_winnings', ['class' => 'form-check-input']) ?>
                            Land the credit in the winnings balance
                        </label>
                    </div>
                    <?= $this->Form->end() ?>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Filter the ledger</h3>
                </div>
                <div class="card-body">
                    <form method="get">
                        <div class="field">
                            <label for="wallet-user">Player</label>
                            <select class="form-control" id="wallet-user" name="user_id">
                                <option value="">Every player</option>
                                <?php foreach ($players as $player) : ?>
                                    <option value="<?= (int)$player->get('id') ?>">
                                        <?= h((string)$player->get('username')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="wallet-type">Type</label>
                            <select class="form-control" id="wallet-type" name="type">
                                <option value="">Every type</option>
                                <?php foreach ($types as $type) : ?>
                                    <option value="<?= h($type) ?>"><?= h(str_replace('_', ' ', $type)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-arena" style="width:100%;">
                            <i class="fas fa-filter"></i> Apply
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ledger</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th>Player</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th style="width:120px;">Amount</th>
                                <th style="width:120px;">Balance</th>
                                <th style="width:130px;">When</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (count($entries) === 0) : ?>
                                <tr>
                                    <td colspan="6" class="muted" style="padding:2rem;text-align:center;">
                                        No wallet movement matches this filter.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($entries as $entry) : ?>
                                <tr>
                                    <td class="tiny"><?= h((string)($entry->get('user')['username'] ?? '—')) ?></td>
                                    <td>
                                        <span class="pill is-done"><?= h(str_replace('_', ' ', (string)$entry->get('type'))) ?></span>
                                    </td>
                                    <td class="tiny muted"><?= h((string)($entry->get('description') ?? '')) ?></td>
                                    <td class="mono tiny" style="color:<?= $entry->get('direction') === 'credit' ? 'var(--accent)' : 'var(--danger)' ?>;">
                                        <?= $entry->get('direction') === 'credit' ? '+' : '−' ?>
                                        ₹<?= number_format(abs((float)$entry->get('amount')), 2) ?>
                                    </td>
                                    <td class="mono tiny">₹<?= number_format((float)($entry->get('balance_after') ?? 0), 2) ?></td>
                                    <td class="tiny muted"><?= h((string)($entry->get('created_at')?->format('d M, H:i') ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if (count($entries) > 0) : ?>
                    <div class="card-footer" style="background:transparent;border-top:1px solid var(--line);">
                        <?= $this->element('pagination') ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
