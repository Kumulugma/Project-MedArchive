<?php
use yii\helpers\Html;
use yii\grid\GridView;
use app\assets\TestResultAsset;

TestResultAsset::register($this);

$this->title = 'Wyniki badań';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="test-result-index">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2"><?= Html::encode($this->title) ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('Nowy wynik', ['create'], ['class' => 'btn btn-success']) ?>
            </div>
        </div>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'table-responsive'],
        'tableOptions' => ['class' => 'table table-striped table-sm'],
        'columns' => [
            'id',
            [
                'attribute' => 'testTemplateName',
                'label' => 'Badanie',
                'value' => function($model) {
                    return $model->testTemplate->name;
                }
            ],
            [
                'attribute' => 'test_date',
                'format' => 'date',
                'filter' => Html::input('date', 'TestResultSearch[test_date]', $searchModel->test_date, ['class' => 'form-control'])
            ],
            [
                'label' => 'Kluczowe wartości',
                'format' => 'raw',
                'value' => function($model) {
                    $values = [];
                    $count = 0;
                    foreach ($model->resultValues as $resultValue) {
                        if ($count >= 3) break; // Pokazuj maksymalnie 3 wartości
                        
                        $value = $resultValue->value;
                        $unit = $resultValue->parameter->unit ? ' ' . $resultValue->parameter->unit : '';
                        
                        // Określ kolor na podstawie warning_level, nie tylko is_abnormal
                        $class = 'text-success'; // default
                        if ($resultValue->is_abnormal) {
                            $class = 'text-danger';
                        } elseif ($resultValue->warning_level) {
                            switch ($resultValue->warning_level) {
                                case \app\models\ParameterNorm::WARNING_LEVEL_WARNING:
                                    $class = 'text-warning';
                                    break;
                                case \app\models\ParameterNorm::WARNING_LEVEL_CAUTION:
                                    $class = 'text-info';
                                    break;
                                case \app\models\ParameterNorm::WARNING_LEVEL_CRITICAL:
                                    $class = 'text-danger';
                                    break;
                            }
                        }
                        
                        $values[] = '<span class="' . $class . '">' . 
                                   Html::encode($resultValue->parameter->name) . ': ' . 
                                   Html::encode($value) . $unit . '</span>';
                        $count++;
                    }
                    
                    if (count($model->resultValues) > 3) {
                        $values[] = '<small class="text-muted">i ' . (count($model->resultValues) - 3) . ' więcej...</small>';
                    }
                    
                    return implode('<br>', $values);
                }
            ],
            [
                'attribute' => 'has_abnormal_values',
                'label' => 'Status',
                'format' => 'raw',
                'value' => function($model) {
                    $status = $model->getDetailedStatus();
                    
                    $badge = '<span class="badge ' . $status['badge_class'] . '">';
                    $badge .= '<i class="' . $status['icon'] . '"></i> ';
                    $badge .= $status['message'];
                    
                    // Dodaj licznik ostrzeżeń jeśli są
                    if ($status['warning_count'] > 0 && $status['status'] !== 'abnormal') {
                        $badge .= ' (' . $status['warning_count'] . ')';
                    }
                    
                    $badge .= '</span>';
                    
                    return $badge;
                },
                'filter' => Html::dropDownList('TestResultSearch[has_abnormal_values]', $searchModel->has_abnormal_values, [
                    '' => 'Wszystkie',
                    '1' => 'Nieprawidłowe',
                    '0' => 'Prawidłowe'
                ], ['class' => 'form-control'])
            ],
            [
                'attribute' => 'comment',
                'value' => function($model) {
                    return $model->comment ? Html::encode(substr($model->comment, 0, 50)) . 
                           (strlen($model->comment) > 50 ? '...' : '') : '';
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete}',
                'buttons' => [
                    'view' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-eye"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-info',
                            'title' => 'Zobacz szczegóły'
                        ]);
                    },
                    'update' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-edit"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-secondary',
                            'title' => 'Edytuj'
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
    ]); ?>
</div>

<!-- CSS dla lepszych kolorów ostrzeżeń -->
<style>
.text-warning {
    color: #fd7e14 !important;
    font-weight: 500;
}

.text-info {
    color: #0dcaf0 !important;
    font-weight: 500;
}

.badge .fas {
    font-size: 0.75em;
}

.badge.bg-warning {
    color: #000 !important;
}
</style>