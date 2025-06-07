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
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2"><?= Html::encode($this->title) ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('<i class="fas fa-edit"></i> Edytuj', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                <?= Html::a('<i class="fas fa-chart-line"></i> Porównaj wyniki', ['compare', 'templateId' => $model->test_template_id], ['class' => 'btn btn-info']) ?>
                <?= Html::a('<i class="fas fa-arrow-left"></i> Powrót do listy', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
                <?= Html::a('<i class="fas fa-trash"></i> Usuń', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-outline-danger',
                    'data' => [
                        'confirm' => 'Czy na pewno chcesz usunąć ten wynik badania?',
                        'method' => 'post',
                    ],
                ]) ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table table-striped table-bordered detail-view'],
                'attributes' => [
                    'id',
                    [
                        'attribute' => 'testTemplate.name',
                        'label' => 'Badanie'
                    ],
                    'test_date:date',
                    'comment:ntext',
                    [
                        'attribute' => 'has_abnormal_values',
                        'label' => 'Status ogólny',
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
                            <td class="<?= $value->is_abnormal ? 'text-danger' : 'text-success' ?>">
                                <strong><?= Html::encode($value->value) ?></strong>
                                <?php if ($value->parameter->unit): ?>
                                    <small class="text-muted"><?= Html::encode($value->parameter->unit) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($value->norm): ?>
                                    <div class="norm-info">
                                        <strong><?= Html::encode($value->norm->name) ?></strong>
                                        <div class="norm-details">
                                            <?php
                                            switch($value->norm->type) {
                                                case 'range':
                                                    echo '<span class="badge bg-info">Zakres</span> ';
                                                    echo Html::encode($value->norm->min_value) . ' - ' . Html::encode($value->norm->max_value);
                                                    break;
                                                case 'single_threshold':
                                                    echo '<span class="badge bg-warning">Próg</span> ';
                                                    echo ($value->norm->threshold_direction === 'above' ? '≤ ' : '≥ ') . Html::encode($value->norm->threshold_value);
                                                    break;
                                                case 'positive_negative':
                                                    echo '<span class="badge bg-secondary">Dodatni/Ujemny</span>';
                                                    break;
                                                case 'multiple_thresholds':
                                                    echo '<span class="badge bg-primary">Wielokrotne progi</span>';
                                                    if ($value->norm->thresholds_config) {
                                                        $thresholds = json_decode($value->norm->thresholds_config, true);
                                                        if ($thresholds) {
                                                            echo '<ul class="list-unstyled mt-1 mb-0">';
                                                            foreach ($thresholds as $threshold) {
                                                                $class = $threshold['is_normal'] ? 'text-success' : 'text-danger';
                                                                echo '<li class="' . $class . '">';
                                                                echo Html::encode($threshold['value']);
                                                                if (!empty($threshold['label'])) {
                                                                    echo ' - ' . Html::encode($threshold['label']);
                                                                }
                                                                echo '</li>';
                                                            }
                                                            echo '</ul>';
                                                        }
                                                    }
                                                    break;
                                            }
                                            ?>
                                            <?php if ($value->parameter->unit): ?>
                                                <small class="text-muted d-block"><?= Html::encode($value->parameter->unit) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Brak zdefiniowanej normy</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($value->is_abnormal): ?>
                                    <span class="badge bg-danger">Nieprawidłowy</span>
                                    <?php
                                    // Pokaż szczegóły dlaczego nieprawidłowy
                                    if ($value->norm) {
                                        $numericValue = is_numeric($value->value) ? (float)$value->value : null;
                                        if ($numericValue !== null) {
                                            switch($value->norm->type) {
                                                case 'range':
                                                    if ($numericValue < $value->norm->min_value) {
                                                        echo '<br><small class="text-muted">Poniżej normy</small>';
                                                    } elseif ($numericValue > $value->norm->max_value) {
                                                        echo '<br><small class="text-muted">Powyżej normy</small>';
                                                    }
                                                    break;
                                                case 'single_threshold':
                                                    if ($value->norm->threshold_direction === 'above' && $numericValue > $value->norm->threshold_value) {
                                                        echo '<br><small class="text-muted">Powyżej progu</small>';
                                                    } elseif ($value->norm->threshold_direction === 'below' && $numericValue < $value->norm->threshold_value) {
                                                        echo '<br><small class="text-muted">Poniżej progu</small>';
                                                    }
                                                    break;
                                            }
                                        }
                                    }
                                    ?>
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