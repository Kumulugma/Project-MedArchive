<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\assets\TestResultAsset;

TestResultAsset::register($this);

$this->title = 'Wynik badania: ' . $model->testTemplate->name;
$this->params['breadcrumbs'][] = ['label' => 'Wyniki badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="test-result-view">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?= Html::encode($this->title) ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <?= Html::a('Edytuj', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Porównaj', ['compare', 'templateId' => $model->test_template_id], ['class' => 'btn btn-info']) ?>
            <?= Html::a('Usuń', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Czy na pewno chcesz usunąć ten wynik?',
                    'method' => 'post',
                ],
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
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
                    'test_date:date',
                    'comment:ntext',
                    [
                        'attribute' => 'has_abnormal_values',
                        'format' => 'raw',
                        'value' => $model->has_abnormal_values 
                            ? '<span class="badge bg-danger">Nieprawidłowe</span>'
                            : '<span class="badge bg-success">Prawidłowe</span>',
                    ],
                    'created_at:datetime',
                    'updated_at:datetime',
                ],
            ]) ?>
        </div>
    </div>

    <div class="mt-4">
        <h3>Wartości parametrów</h3>
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Parametr</th>
                        <th>Wartość</th>
                        <th>Norma</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($model->resultValues as $value): ?>
                        <tr>
                            <td>
                                <strong><?= Html::encode($value->parameter->name) ?></strong>
                                <?php if ($value->parameter->unit): ?>
                                    <small class="text-muted">(<?= Html::encode($value->parameter->unit) ?>)</small>
                                <?php endif; ?>
                            </td>
                            <td class="<?= $value->is_abnormal ? 'abnormal-value' : '' ?>">
                                <?= Html::encode($value->value) ?>
                            </td>
                            <td>
                                <?php if ($value->norm): ?>
                                    <?= Html::encode($value->norm->name) ?>
                                    <?php if ($value->norm->type === 'range'): ?>
                                        <br><small class="text-muted">
                                            <?= $value->norm->min_value ?> - <?= $value->norm->max_value ?>
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Brak normy</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($value->is_abnormal): ?>
                                    <span class="badge bg-danger">
                                        Nieprawidłowy
                                        <?php if ($value->abnormality_type === 'low'): ?>
                                            (niski)
                                        <?php elseif ($value->abnormality_type === 'high'): ?>
                                            (wysoki)
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success">Prawidłowy</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>