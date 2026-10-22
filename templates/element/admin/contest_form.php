<?php
/**
 * The tournament creator form, shared by the admin and the organizer.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Tournament $tournament
 * @var array<string> $templates
 * @var string $formAction
 * @var array<\App\Model\Entity\User> $organizers
 * @var bool $showPlatformFee
 */

use App\Service\Tournament\TimeControl;

$categoryOptions = [];
foreach (TimeControl::CATEGORIES as $key => $meta) {
    $categoryOptions[$key] = $meta['label'] . ' · ' . $meta['display'];
}

$organizerOptions = [];
foreach (($organizers ?? []) as $organizer) {
    $organizerOptions[(int)$organizer->get('id')] = (string)$organizer->get('username')
        . ' (' . (string)($organizer->get('role')['name'] ?? 'Player') . ')';
}

$organizerValue = $tournament->get('organizer_id');
if ($organizerValue === null && $organizerOptions !== []) {
    $organizerValue = array_key_first($organizerOptions);
}

$startValue = $tournament->get('start_time');
if ($startValue instanceof \Cake\I18n\DateTime) {
    $startValue = $startValue->format('Y-m-d\TH:i');
} elseif ($startValue === null) {
    $startValue = date('Y-m-d\TH:i', strtotime('+1 hour'));
}
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Contest configuration</h3>
    </div>
    <div class="card-body">
        <?= $this->Form->create($tournament, ['url' => $formAction]) ?>
        <div class="row">
            <div class="col-md-7">
                <div class="field">
                    <?= $this->Form->label('title', 'Contest title') ?>
                    <?= $this->Form->text('title', [
                        'class' => 'form-control',
                        'placeholder' => 'Friday Night Blitz Championship',
                        'required' => true,
                    ]) ?>
                    <?php $titleErrors = $tournament->getErrors(); ?>
                    <?php if (isset($titleErrors['title'])) : ?>
                        <div class="error"><?= h((string)$titleErrors['title'][0]) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-5">
                <div class="field">
                    <?= $this->Form->label('category', 'Format') ?>
                    <?= $this->Form->select('category', $categoryOptions, ['class' => 'form-control']) ?>
                    <div class="help">
                        The clock follows the format:
                        <?= implode(', ', array_map(
                            fn ($meta) => $meta['label'] . ' ' . $meta['display'],
                            TimeControl::CATEGORIES,
                        )) ?>.
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 col-6">
                <div class="field">
                    <?= $this->Form->label('entry_fee', 'Entry fee (₹)') ?>
                    <?= $this->Form->text('entry_fee', ['class' => 'form-control', 'required' => true]) ?>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="field">
                    <?= $this->Form->label('max_slots', 'Total seats') ?>
                    <?= $this->Form->text('max_slots', ['class' => 'form-control', 'required' => true]) ?>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="field">
                    <?= $this->Form->label('prize_pool', 'Prize pool (₹)') ?>
                    <?= $this->Form->text('prize_pool', [
                        'class' => 'form-control',
                        'placeholder' => 'Auto: 80% of the collected fees',
                    ]) ?>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="field">
                    <?= $this->Form->label('start_time', 'Start time') ?>
                    <?= $this->Form->text('start_time', [
                        'class' => 'form-control',
                        'type' => 'datetime-local',
                        'value' => $startValue,
                        'required' => true,
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="field">
                    <?= $this->Form->label('prize_template', 'Payout ladder') ?>
                    <?= $this->Form->select('prize_template', array_combine(
                        $templates,
                        array_map(
                            fn ($key) => match ($key) {
                                'top1' => 'Winner takes all',
                                'top3' => 'Top 3 · 50 / 30 / 20',
                                'top5' => 'Top 5 · 40 / 25 / 15 / 12 / 8',
                                'top10' => 'Top 10 · shared ladder',
                                default => $key,
                            },
                            $templates,
                        ),
                    ), ['class' => 'form-control']) ?>
                    <div class="help">This is the ladder players see on the contest card.</div>
                </div>
            </div>
            <?php if ($organizerOptions !== []) : ?>
                <div class="col-md-4">
                    <div class="field">
                        <?= $this->Form->label('organizer_id', 'Organizer') ?>
                        <?= $this->Form->select('organizer_id', $organizerOptions, [
                            'class' => 'form-control',
                            'value' => $organizerValue,
                        ]) ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($showPlatformFee)) : ?>
                <div class="col-md-4">
                    <div class="field">
                        <?= $this->Form->label('platform_fee', 'Platform fee (%)') ?>
                        <?= $this->Form->text('platform_fee', [
                            'class' => 'form-control',
                            'value' => $tournament->get('platform_fee') ?? '20.00',
                            'required' => true,
                        ]) ?>
                        <div class="help">
                            The share of the collected entry fees the arena keeps. The rest is
                            the prize pool for the winners.
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="col-md-3">
                <div class="field">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-arena" style="width:100%;">
                        <i class="fas fa-check"></i> Save contest
                    </button>
                </div>
            </div>
        </div>

        <div class="field">
            <?= $this->Form->label('rules', 'Rules shown to the players') ?>
            <?= $this->Form->textarea('rules', [
                'class' => 'form-control',
                'rows' => 4,
                'placeholder' => "Win = 3 points, draw = 1 point, loss = 0 points.\nUp to 2 time bonus points per game.\nA player may enter a contest only once.",
            ]) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
