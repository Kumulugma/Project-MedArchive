<?php
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Szablony badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="test-template-view">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?= Html::encode($this->title) ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <?= Html::a('Edytuj', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Dodaj parametr', ['add-parameter', 'id' => $model->id], ['class' => 'btn btn-success']) ?>
            <?= Html::a('Usuń', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Czy na pewno chcesz usunąć ten szablon?',
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
                    'name',
                    'description:ntext',
                    [
                        'attribute' => 'status',
                        'value' => $model->status ? 'Aktywny' : 'Nieaktywny',
                    ],
                    'created_at:datetime',
                    'updated_at:datetime',
                ],
            ]) ?>
        </div>
    </div>

    <div class="mt-4">
        <h3>Parametry badania</h3>
        <?php if (empty($model->parameters)): ?>
            <p class="text-muted">Brak zdefiniowanych parametrów</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Nazwa</th>
                            <th>Jednostka</th>
                            <th>Typ</th>
                            <th>Normy</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($model->parameters as $parameter): ?>
                            <tr>
                                <td><?= Html::encode($parameter->name) ?></td>
                                <td><?= Html::encode($parameter->unit) ?></td>
                                <td><?= Html::encode($parameter->getTypeOptions()[$parameter->type] ?? $parameter->type) ?></td>
                                <td>
                                    <?= count($parameter->norms) ?>
                                    <?= Html::a('<i class="fas fa-plus"></i>', ['add-norm', 'id' => $model->id, 'parameterId' => $parameter->id], [
                                        'class' => 'btn btn-xs btn-outline-success',
                                        'title' => 'Dodaj normę'
                                    ]) ?>
                                </td>
                                <td>
                                    <?= Html::a('<i class="fas fa-edit"></i>', ['update-parameter', 'id' => $model->id, 'parameterId' => $parameter->id], [
                                        'class' => 'btn btn-sm btn-outline-secondary',
                                        'title' => 'Edytuj'
                                    ]) ?>
                                    <?= Html::a('<i class="fas fa-trash"></i>', ['delete-parameter', 'id' => $model->id, 'parameterId' => $parameter->id], [
                                        'class' => 'btn btn-sm btn-outline-danger',
                                        'title' => 'Usuń',
                                        'data-confirm' => 'Czy na pewno chcesz usunąć ten parametr?',
                                        'data-method' => 'post',
                                    ]) ?>
                                </td>
                            </tr>
                            <?php if (!empty($parameter->norms)): ?>
                                <?php foreach ($parameter->norms as $norm): ?>
                                    <tr class="table-light">
                                        <td class="ps-4">
                                            <small><?= Html::encode($norm->name) ?></small>
                                            <?php if ($norm->is_primary): ?>
                                                <span class="badge bg-primary">Podstawowa</span>
                                            <?php endif; ?>
                                        </td>
                                        <td colspan="4">
                                            <small class="text-muted">
                                                <?php if ($norm->type === 'range'): ?>
                                                    Zakres: <?= $norm->min_value ?> - <?= $norm->max_value ?>
                                                <?php elseif ($norm->type === 'single_threshold'): ?>
                                                    Próg: <?= $norm->threshold_value ?> (<?= $norm->threshold_direction ?>)
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
