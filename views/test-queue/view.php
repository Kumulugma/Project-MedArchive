<?php
use yii\helpers\Html;
use yii\widgets\DetailView;
use app\assets\TestQueueAsset;

TestQueueAsset::register($this);

$this->title = 'Zaplanowane badanie: ' . $model->testTemplate->name;
$this->params['breadcrumbs'][] = ['label' => 'Kolejka badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="test-queue-view">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?= Html::encode($this->title) ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <?php if ($model->status === 'pending'): ?>
                <?= Html::a('Oznacz jako wykonane', ['complete', 'id' => $model->id], [
                    'class' => 'btn btn-success',
                    'data-method' => 'post',
                    'data-confirm' => 'Czy na pewno chcesz oznaczyć to badanie jako wykonane?'
                ]) ?>
            <?php endif; ?>
            <?= Html::a('Edytuj', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Usuń', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Czy na pewno chcesz usunąć to zaplanowane badanie?',
                    'method' => 'post',
                ],
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table table-striped table-sm'],
                'attributes' => [
                    'id',
                    [
                        'attribute' => 'test_template_id',
                        'label' => 'Badanie',
                        'value' => $model->testTemplate->name,
                    ],
                    'scheduled_date:date',
                    'comment:ntext',
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
                    ],
                    [
                        'attribute' => 'reminder_sent',
                        'format' => 'raw',
                        'value' => $model->reminder_sent 
                            ? '<span class="badge bg-success">Wysłane</span>'
                            : '<span class="badge bg-secondary">Nie wysłane</span>',
                    ],
                    'created_at:datetime',
                    'updated_at:datetime',
                ],
            ]) ?>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Informacje o badaniu</h6>
                </div>
                <div class="card-body">
                    <h6><?= Html::encode($model->testTemplate->name) ?></h6>
                    <?php if ($model->testTemplate->description): ?>
                        <p class="text-muted small"><?= Html::encode($model->testTemplate->description) ?></p>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <h6 class="small">Parametry badania:</h6>
                    <ul class="list-unstyled small">
                        <?php foreach ($model->testTemplate->parameters as $parameter): ?>
                            <li>
                                • <?= Html::encode($parameter->name) ?>
                                <?php if ($parameter->unit): ?>
                                    <span class="text-muted">(<?= Html::encode($parameter->unit) ?>)</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <?php if ($model->status === 'pending'): ?>
                        <hr>
                        <div class="d-grid">
                            <?= Html::a('Wprowadź wyniki', ['/test-result/create', 'templateId' => $model->test_template_id], [
                                'class' => 'btn btn-outline-primary btn-sm'
                            ]) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>