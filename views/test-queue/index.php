use yii\helpers\Html;
use yii\grid\GridView;
use app\assets\TestQueueAsset;

TestQueueAsset::register($this);

$this->title = 'Kolejka badań';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="test-queue-index">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?= Html::encode($this->title) ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <?= Html::a('Zaplanuj badanie', ['create'], ['class' => 'btn btn-success']) ?>
            <?= Html::button('Widok kalendarza', ['class' => 'btn btn-outline-secondary', 'id' => 'calendar-view-toggle']) ?>
        </div>
    </div>

    <div id="list-view">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'options' => ['class' => 'table-responsive'],
            'tableOptions' => ['class' => 'table table-striped table-sm'],
            'columns' => [
                [
                    'class' => 'yii\grid\CheckboxColumn',
                    'checkboxOptions' => function ($model, $key, $index, $column) {
                        return ['class' => 'queue-checkbox', 'value' => $model->id];
                    }
                ],
                'id',
                [
                    'attribute' => 'testTemplateName',
                    'label' => 'Badanie',
                    'value' => function($model) {
                        return $model->testTemplate->name;
                    }
                ],
                [
                    'attribute' => 'scheduled_date',
                    'format' => 'date',
                    'filter' => Html::input('date', 'TestQueueSearch[scheduled_date]', $searchModel->scheduled_date, ['class' => 'form-control']),
                    'contentOptions' => function($model) {
                        $class = '';
                        if ($model->isDue()) {
                            $class = 'table-warning';
                        }
                        if (strtotime($model->scheduled_date) < strtotime(date('Y-m-d')) && $model->status === 'pending') {
                            $class = 'table-danger';
                        }
                        return ['class' => $class];
                    }
                ],
                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'value' => function($model) {
                        $statusOptions = $model->getStatusOptions();
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
                        return '<span class="badge ' . $class . '">' . $statusOptions[$model->status] . '</span>';
                    },
                    'filter' => Html::dropDownList('TestQueueSearch[status]', $searchModel->status, 
                        array_merge(['' => 'Wszystkie'], \app\models\TestQueue::getStatusOptions()), 
                        ['class' => 'form-control']
                    )
                ],
                [
                    'attribute' => 'comment',
                    'value' => function($model) {
                        return $model->comment ? Html::encode(substr($model->comment, 0, 50)) . '...' : '';
                    }
                ],
                [
                    'attribute' => 'reminder_sent',
                    'format' => 'raw',
                    'label' => 'Przypomnienie',
                    'value' => function($model) {
                        if ($model->reminder_sent) {
                            return '<span class="badge bg-info">Wysłane</span>';
                        } elseif ($model->isDue()) {
                            return Html::button('Wyślij', [
                                'class' => 'btn btn-xs btn-outline-primary send-reminder',
                                'data-queue-id' => $model->id
                            ]);
                        }
                        return '<span class="text-muted">-</span>';
                    },
                    'filter' => Html::dropDownList('TestQueueSearch[reminder_sent]', $searchModel->reminder_sent, [
                        '' => 'Wszystkie',
                        '1' => 'Wysłane',
                        '0' => 'Nie wysłane'
                    ], ['class' => 'form-control'])
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view} {update} {complete} {delete}',
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
                        'complete' => function ($url, $model, $key) {
                            if ($model->status === 'pending') {
                                return Html::a('<i class="fas fa-check"></i>', $url, [
                                    'class' => 'btn btn-sm btn-outline-success mark-completed',
                                    'title' => 'Oznacz jako wykonane',
                                    'data-method' => 'post',
                                ]);
                            }
                            return '';
                        },
                        'delete' => function ($url, $model, $key) {
                            return Html::a('<i class="fas fa-trash"></i>', $url, [
                                'class' => 'btn btn-sm btn-outline-danger',
                                'title' => 'Usuń',
                                'data-confirm' => 'Czy na pewno chcesz usunąć to zaplanowane badanie?',
                                'data-method' => 'post',
                            ]);
                        },
                    ],
                ],
            ],
        ]); ?>

        <div class="mt-3">
            <div class="btn-group">
                <?= Html::button('Oznacz wybrane jako wykonane', [
                    'class' => 'btn btn-success', 
                    'id' => 'bulk-complete'
                ]) ?>
                <?= Html::button('Wyślij przypomnienia', [
                    'class' => 'btn btn-info', 
                    'id' => 'bulk-remind'
                ]) ?>
            </div>
        </div>
    </div>

    <div id="calendar-view" style="display: none;">
        <div class="calendar-container">
            <div id="calendar"></div>
        </div>
    </div>
</div>