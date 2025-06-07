<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\assets\TestResultAsset;

TestResultAsset::register($this);

$this->title = 'Wyniki badań';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="test-result-index">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?= Html::encode($this->title) ?></h1>
<?= Html::a('Nowy wynik', ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'table-responsive'],
        'tableOptions' => ['class' => 'table table-striped table-sm'],
        'columns' => [
            'id',
            [
                'attribute' => 'testTemplateName',
                'label' => 'Badanie',
                'value' => function ($model) {
                    return $model->testTemplate->name;
                }
            ],
            [
                'attribute' => 'test_date',
                'format' => 'date',
                'filter' => Html::input('date', 'TestResultSearch[test_date]', $searchModel->test_date, ['class' => 'form-control'])
            ],
            [
                'attribute' => 'has_abnormal_values',
                'label' => 'Status',
                'format' => 'raw',
                'value' => function ($model) {
                    if ($model->has_abnormal_values) {
                        return '<span class="badge bg-danger">Nieprawidłowe</span>';
                    }
                    return '<span class="badge bg-success">Prawidłowe</span>';
                },
                'filter' => Html::dropDownList('TestResultSearch[has_abnormal_values]', $searchModel->has_abnormal_values, [
                    '' => 'Wszystkie',
                    '1' => 'Nieprawidłowe',
                    '0' => 'Prawidłowe'
                        ], ['class' => 'form-control'])
            ],
            [
                'attribute' => 'comment',
                'value' => function ($model) {
                    return $model->comment ? Html::encode(substr($model->comment, 0, 50)) . '...' : '';
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {compare} {delete}',
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
                    'compare' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-chart-line"></i>', ['compare', 'templateId' => $model->test_template_id], [
                            'class' => 'btn btn-sm btn-outline-info',
                            'title' => 'Porównaj',
                        ]);
                    },
                    'delete' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-trash"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'title' => 'Usuń',
                            'data-confirm' => 'Czy na pewno chcesz usunąć ten wynik?',
                            'data-method' => 'post',
                        ]);
                    },
                ],
            ],
        ],
    ]);
    ?>
</div>