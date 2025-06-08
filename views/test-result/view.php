<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\assets\TestResultAsset;

/* @var $this yii\web\View */
/* @var $model app\models\TestResult */

TestResultAsset::register($this);

$this->title = 'Wynik badania #' . $model->id;
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
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    [
                        'attribute' => 'testTemplate.name',
                        'label' => 'Szablon badania',
                    ],
                    'test_date:date',
                    'comment:ntext',
                    [
                        'attribute' => 'has_abnormal_values',
                        'label' => 'Status ogólny',
                        'format' => 'raw',
                        'value' => function($model) {
                            $status = $model->getDetailedStatus();
                            return '<span class="badge ' . $status['badge_class'] . ' fs-6">' .
                                   '<i class="' . $status['icon'] . '"></i> ' .
                                   $status['message'] .
                                   ($status['warning_count'] > 0 && $status['status'] !== 'abnormal' ? ' (' . $status['warning_count'] . ')' : '') .
                                   '</span>';
                        },
                    ],
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
                        <th>Ostrzeżenie</th>
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
                            <td>
                                <?php
                                // Określ kolor wartości na podstawie statusu
                                $valueClass = 'text-success'; // default
                                if ($value->is_abnormal) {
                                    $valueClass = 'text-danger fw-bold';
                                } elseif ($value->warning_level) {
                                    switch ($value->warning_level) {
                                        case \app\models\ParameterNorm::WARNING_LEVEL_WARNING:
                                            $valueClass = 'text-warning fw-bold';
                                            break;
                                        case \app\models\ParameterNorm::WARNING_LEVEL_CAUTION:
                                            $valueClass = 'text-info fw-bold';
                                            break;
                                        case \app\models\ParameterNorm::WARNING_LEVEL_CRITICAL:
                                            $valueClass = 'text-danger fw-bold';
                                            break;
                                    }
                                }
                                ?>
                                <span class="<?= $valueClass ?>">
                                    <strong><?= Html::encode($value->value) ?></strong>
                                    <?php if ($value->parameter->unit): ?>
                                        <small class="text-muted"><?= Html::encode($value->parameter->unit) ?></small>
                                    <?php endif; ?>
                                </span>
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
                                                    echo ($value->norm->threshold_direction === 'below' ? '≤ ' : '≥ ') . Html::encode($value->norm->threshold_value);
                                                    break;
                                                case 'positive_negative':
                                                    echo '<span class="badge bg-secondary">+/-</span> ';
                                                    echo 'Negatywny = Normalny';
                                                    break;
                                                case 'multiple_thresholds':
                                                    echo '<span class="badge bg-dark">Progi</span> ';
                                                    echo 'Wiele progów';
                                                    break;
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Brak normy</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                // Określ status główny
                                if ($value->is_abnormal) {
                                    echo '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Nieprawidłowe</span>';
                                } else {
                                    echo '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Prawidłowe</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                // Pokaż szczegółowe ostrzeżenie
                                if ($value->warning_level && $value->warning_level !== \app\models\ParameterNorm::WARNING_LEVEL_NONE) {
                                    switch ($value->warning_level) {
                                        case \app\models\ParameterNorm::WARNING_LEVEL_OPTIMAL:
                                            echo '<span class="badge bg-success"><i class="fas fa-star"></i> Optymalna</span>';
                                            break;
                                        case \app\models\ParameterNorm::WARNING_LEVEL_CAUTION:
                                            echo '<span class="badge bg-info"><i class="fas fa-info-circle"></i> Do obserwacji</span>';
                                            break;
                                        case \app\models\ParameterNorm::WARNING_LEVEL_WARNING:
                                            echo '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> Ostrzeżenie</span>';
                                            break;
                                        case \app\models\ParameterNorm::WARNING_LEVEL_CRITICAL:
                                            echo '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Krytyczne</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-light text-dark">-</span>';
                                    }
                                    
                                    // Pokaż dodatkowe informacje
                                    if ($value->warning_message) {
                                        echo '<br><small class="text-muted mt-1">' . Html::encode($value->warning_message) . '</small>';
                                    }
                                    
                                    if ($value->distance_from_boundary && $value->distance_from_boundary > 0) {
                                        echo '<br><small class="text-muted">Odległość od granicy: ' . round($value->distance_from_boundary, 2) . '</small>';
                                    }
                                } else {
                                    echo '<span class="badge bg-light text-dark">-</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Rekomendacje jeśli są ostrzeżenia -->
    <?php
    $hasWarnings = false;
    $recommendations = [];
    foreach ($model->resultValues as $value) {
        if ($value->warning_level && in_array($value->warning_level, [
            \app\models\ParameterNorm::WARNING_LEVEL_WARNING,
            \app\models\ParameterNorm::WARNING_LEVEL_CAUTION,
            \app\models\ParameterNorm::WARNING_LEVEL_CRITICAL
        ])) {
            $hasWarnings = true;
            if ($value->recommendation) {
                $recommendations[] = [
                    'parameter' => $value->parameter->name,
                    'recommendation' => $value->recommendation,
                    'level' => $value->warning_level
                ];
            }
        }
    }
    ?>

    <?php if ($hasWarnings): ?>
        <div class="mt-4">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb"></i> Rekomendacje
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recommendations)): ?>
                        <div class="row">
                            <?php foreach ($recommendations as $rec): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="p-3 border rounded">
                                        <h6 class="text-primary"><?= Html::encode($rec['parameter']) ?></h6>
                                        <p class="mb-0 small"><?= Html::encode($rec['recommendation']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="mb-0">
                            <i class="fas fa-info-circle text-info"></i>
                            Wykryto wartości wymagające uwagi. Zalecana konsultacja z lekarzem w celu interpretacji wyników.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- CSS dla lepszego wyglądu -->
<style>
.badge.fs-6 {
    font-size: 1rem !important;
    padding: 0.5rem 0.75rem;
}

.text-warning {
    color: #fd7e14 !important;
}

.text-info {
    color: #0dcaf0 !important;
}

.norm-details {
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.norm-details .badge {
    font-size: 0.75rem;
}

.table td {
    vertical-align: middle;
}

.table .badge {
    font-size: 0.8rem;
}
</style>