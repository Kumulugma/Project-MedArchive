<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Szablony badań';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="test-template-index">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Nowy szablon', ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'options' => ['class' => 'table-responsive'],
        'tableOptions' => ['class' => 'table table-striped table-sm'],
        'columns' => [
            'id',
            'name',
            'description:ntext',
            [
                'attribute' => 'parameters',
                'label' => 'Parametry',
                'value' => function($model) {
                    return count($model->parameters);
                }
            ],
            [
                'attribute' => 'status',
                'value' => function($model) {
                    return $model->status ? 'Aktywny' : 'Nieaktywny';
                },
                'contentOptions' => function($model) {
                    return ['class' => $model->status ? 'text-success' : 'text-muted'];
                }
            ],
            'created_at:datetime',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete}',
                'buttons' => [
                    'view' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-eye"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-primary',
                            'title' => 'Podgląd',
                        ]);
                    },
                    'update' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-edit"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-secondary',
                            'title' => 'Edytuj',
                        ]);
                    },
                    'delete' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-trash"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'title' => 'Usuń',
                            'data-confirm' => 'Czy na pewno chcesz usunąć ten szablon?',
                            'data-method' => 'post',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
</div>
