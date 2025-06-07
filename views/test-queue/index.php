<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\assets\TestQueueAsset;

TestQueueAsset::register($this);

$this->title = 'Kolejka badań';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="test-queue-index">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2"><i class="fas fa-calendar-alt"></i> <?= Html::encode($this->title) ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('<i class="fas fa-plus"></i> Zaplanuj badanie', ['create'], ['class' => 'btn btn-success']) ?>
                <?= Html::a('<i class="fas fa-check-double"></i> Oznacz wykonane', '#', [
                    'class' => 'btn btn-primary',
                    'id' => 'bulk-complete',
                    'style' => 'display: none;'
                ]) ?>
            </div>
        </div>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'table-responsive'],
        'tableOptions' => ['class' => 'table table-striped table-sm'],
        'rowOptions' => function($model) {
            $options = [];
            if ($model->isDue()) {
                $options['data-urgent'] = 'true';
            }
            if ($model->isOverdue()) {
                $options['data-overdue'] = 'true';
            }
            return $options;
        },
        'columns' => [
            [
                'class' => 'yii\grid\CheckboxColumn',
                'headerOptions' => ['style' => 'width: 40px'],
                'checkboxOptions' => function($model) {
                    return [
                        'value' => $model->id,
                        'class' => 'queue-checkbox',
                        'disabled' => $model->status !== 'pending'
                    ];
                }
            ],
            [
                'attribute' => 'id',
                'headerOptions' => ['style' => 'width: 80px'],
                'contentOptions' => ['data-label' => 'ID'],
            ],
            [
                'attribute' => 'testTemplateName',
                'label' => 'Badanie',
                'value' => function($model) {
                    return $model->testTemplate->name;
                },
                'contentOptions' => ['data-label' => 'Badanie'],
            ],
            [
                'attribute' => 'scheduled_date',
                'format' => 'date',
                'filter' => Html::input('date', 'TestQueueSearch[scheduled_date]', $searchModel->scheduled_date, ['class' => 'form-control']),
                'value' => function($model) {
                    $date = Yii::$app->formatter->asDate($model->scheduled_date);
                    if ($model->isOverdue()) {
                        return $date . ' <span class="badge bg-danger ms-1">Przeterminowane</span>';
                    } elseif ($model->isDue()) {
                        return $date . ' <span class="badge bg-warning ms-1">Dziś</span>';
                    }
                    return $date;
                },
                'format' => 'raw',
                'contentOptions' => ['data-label' => 'Data badania'],
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => function($model) {
                    $options = $model->getStatusOptions();
                    $class = '';
                    switch($model->status) {
                        case 'pending':
                            $class = $model->isDue() ? 'bg-warning' : 'bg-secondary';
                            break;
                        case 'completed':
                            $class = 'bg-success';
                            break;
                        case 'cancelled':
                            $class = 'bg-danger';
                            break;
                    }
                    return '<span class="badge ' . $class . '">' . $options[$model->status] . '</span>';
                },
                'filter' => Html::dropDownList('TestQueueSearch[status]', $searchModel->status, 
                    array_merge(['' => 'Wszystkie'], $searchModel->getStatusOptions()), 
                    ['class' => 'form-control']
                ),
                'contentOptions' => ['data-label' => 'Status'],
            ],
            [
                'attribute' => 'comment',
                'value' => function($model) {
                    return $model->comment ? Html::encode(substr($model->comment, 0, 50)) . '...' : '';
                },
                'contentOptions' => ['data-label' => 'Komentarz'],
            ],
            [
                'attribute' => 'reminder_sent',
                'format' => 'raw',
                'value' => function($model) {
                    return $model->reminder_sent 
                        ? '<span class="badge bg-info"><i class="fas fa-bell"></i> Wysłane</span>'
                        : '<span class="badge bg-secondary"><i class="fas fa-bell-slash"></i> Nie wysłane</span>';
                },
                'filter' => Html::dropDownList('TestQueueSearch[reminder_sent]', $searchModel->reminder_sent, [
                    '' => 'Wszystkie',
                    '1' => 'Wysłane',
                    '0' => 'Nie wysłane'
                ], ['class' => 'form-control']),
                'contentOptions' => ['data-label' => 'Przypomnienie'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {complete} {delete}',
                'headerOptions' => ['style' => 'width: 140px'],
                'contentOptions' => ['data-label' => 'Akcje'],
                'buttons' => [
                    'view' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-eye"></i>', $url, [
                            'class' => 'btn btn-outline-primary btn-sm',
                            'title' => 'Podgląd',
                            'data-bs-toggle' => 'tooltip',
                        ]);
                    },
                    'update' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-edit"></i>', $url, [
                            'class' => 'btn btn-outline-secondary btn-sm',
                            'title' => 'Edytuj',
                            'data-bs-toggle' => 'tooltip',
                        ]);
                    },
                    'complete' => function ($url, $model, $key) {
                        if ($model->status === 'pending') {
                            return Html::a('<i class="fas fa-check"></i>', ['complete', 'id' => $model->id], [
                                'class' => 'btn btn-outline-success btn-sm',
                                'title' => 'Oznacz jako wykonane',
                                'data-bs-toggle' => 'tooltip',
                                'data-method' => 'post',
                                'data-confirm' => 'Czy oznaczyć jako wykonane?',
                            ]);
                        }
                        return '';
                    },
                    'delete' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-trash"></i>', $url, [
                            'class' => 'btn btn-outline-danger btn-sm',
                            'title' => 'Usuń',
                            'data-bs-toggle' => 'tooltip',
                            'data-confirm' => 'Czy na pewno chcesz usunąć to zaplanowane badanie?',
                            'data-method' => 'post',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
</div>

<script>
$(document).ready(function() {
    // Bulk operations
    $('.queue-checkbox').on('change', function() {
        var checkedCount = $('.queue-checkbox:checked').length;
        if (checkedCount > 0) {
            $('#bulk-complete').show();
        } else {
            $('#bulk-complete').hide();
        }
    });
    
    // Bulk complete action
    $('#bulk-complete').on('click', function(e) {
        e.preventDefault();
        var selected = [];
        $('.queue-checkbox:checked').each(function() {
            selected.push($(this).val());
        });
        
        if (selected.length === 0) {
            alert('Nie wybrano żadnych elementów!');
            return;
        }
        
        if (confirm('Czy oznaczyć wybrane badania (' + selected.length + ') jako wykonane?')) {
            $.post('bulk-complete', {
                ids: selected,
                _csrf: $('meta[name=csrf-token]').attr('content')
            }).done(function(response) {
                location.reload();
            }).fail(function() {
                alert('Wystąpił błąd podczas oznaczania badań jako wykonane.');
            });
        }
    });
    
    // Tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>