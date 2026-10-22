<?php
/**
 * The Bootstrap flavoured pagination bar.
 *
 * The core templates of the paginator helper use the plain `prev`/`next`
 * classes, so the arena swaps them for the Bootstrap 4 markup.
 *
 * @var \App\View\AppView $this
 */

$paginator = $this->Paginator;

try {
    $params = $paginator->params();
} catch (\Cake\Core\Exception\CakeException $exception) {
    return;
}

if ((int)($params['pageCount'] ?? 1) <= 1) {
    return;
}

$paginator->setTemplates([
    'nextActive' => '<li class="page-item"><a class="page-link" rel="next" href="{{url}}">{{text}}</a></li>',
    'nextDisabled' => '<li class="page-item disabled"><a class="page-link" href="#">{{text}}</a></li>',
    'prevActive' => '<li class="page-item"><a class="page-link" rel="prev" href="{{url}}">{{text}}</a></li>',
    'prevDisabled' => '<li class="page-item disabled"><a class="page-link" href="#">{{text}}</a></li>',
    'first' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
    'last' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
    'number' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
    'current' => '<li class="page-item active"><a class="page-link" href="#">{{text}}</a></li>',
    'ellipsis' => '<li class="page-item disabled"><a class="page-link" href="#">&hellip;</a></li>',
]);
?>
<ul class="pagination pagination-sm mb-0" style="justify-content:center;">
    <?= $paginator->prev('&laquo;', ['escape' => false]) ?>
    <?= $paginator->numbers() ?>
    <?= $paginator->next('&raquo;', ['escape' => false]) ?>
</ul>
