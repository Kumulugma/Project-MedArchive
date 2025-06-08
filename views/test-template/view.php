<?php
// views/test-template/view.php - rozszerzony o informacje o ostrzeżeniach

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\bootstrap5\Progress;
use app\components\MedicalThresholdManager;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Szablony badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Pobierz statystyki ostrzeżeń
$warningsStats = $model->getWarningsStatistics();
$hasCompleteSetup = $model->hasCompleteWarningsSetup();
?>

<div class="test-template-view">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2"><?= Html::encode($this->title) ?></h1>
            <div class="btn-toolbar">
                <?= Html::a('<i class="fas fa-exclamation-triangle"></i> Konfiguruj ostrzeżenia', 
                    ['configure-warnings', 'id' => $model->id], 
                    ['class' => 'btn btn-warning']) ?>
                <?= Html::a('<i class="fas fa-edit"></i> Edytuj', 
                    ['update', 'id' => $model->id], 
                    ['class' => 'btn btn-primary']) ?>
                <?= Html::a('<i class="fas fa-plus"></i> Nowy wynik', 
                    ['test-result/create', 'template_id' => $model->id], 
                    ['class' => 'btn btn-success']) ?>
            </div>
        </div>
    </div>

    <!-- Alert o statusie ostrzeżeń -->
    <?php if (!$hasCompleteSetup): ?>
        <div class="alert alert-warning" role="alert">
            <h6><i class="fas fa-exclamation-triangle"></i> Niekompletna konfiguracja ostrzeżeń</h6>
            <p class="mb-2">
                Ten szablon ma tylko <?= $warningsStats['coverage_percent'] ?>% pokrycia ostrzeżeniami. 
                Zaleca się skonfigurowanie ostrzeżeń dla wszystkich parametrów.
            </p>
            <?= Html::a('<i class="fas fa-magic"></i> Skonfiguruj automatycznie', 
                ['configure-warnings', 'id' => $model->id], 
                ['class' => 'btn btn-sm btn-warning']) ?>
        </div>
    <?php else: ?>
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle"></i>
            Szablon ma kompletnie skonfigurowane ostrzeżenia (<?= $warningsStats['coverage_percent'] ?>% pokrycia).
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Szczegóły szablonu -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Szczegóły szablonu</h5>
                </div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'name',
                            'description:ntext',
                            'category',
                            'created_at:datetime',
                            'updated_at:datetime',
                        ],
                    ]) ?>
                </div>
            </div>

            <!-- Parametry z ostrzeżeniami -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5><i class="fas fa-flask"></i> Parametry i ostrzeżenia</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Parametr</th>
                                    <th>Typ</th>
                                    <th>Norma</th>
                                    <th>Ostrzeżenia</th>
                                    <th>Status</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($model->parameters as $parameter): ?>
                                    <tr>
                                        <td>
                                            <strong><?= Html::encode($parameter->name) ?></strong>
                                            <?php if ($parameter->unit): ?>
                                                <small class="text-muted d-block">(<?= Html::encode($parameter->unit) ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= Html::encode($parameter->type) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($parameter->primaryNorm): ?>
                                                <?php $norm = $parameter->primaryNorm; ?>
                                                <div class="norm-display">
                                                    <?php if ($norm->type === 'range'): ?>
                                                        <small class="text-success">
                                                            <?= $norm->min_value ?> - <?= $norm->max_value ?>
                                                        </small>
                                                    <?php elseif ($norm->type === 'single_threshold'): ?>
                                                        <small class="text-info">
                                                            <?= $norm->threshold_direction === 'above' ? '≤' : '≥' ?> 
                                                            <?= $norm->threshold_value ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <small class="text-secondary"><?= $norm->type ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Brak normy</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($parameter->primaryNorm && $parameter->primaryNorm->warning_enabled): ?>
                                                <div class="warnings-info">
                                                    <small class="text-success">
                                                        <i class="fas fa-check-circle"></i>
                                                        Ostrzeżenie: <?= $parameter->primaryNorm->warning_margin_percent ?>%
                                                    </small>
                                                    <br>
                                                    <small class="text-info">
                                                        <i class="fas fa-eye"></i>
                                                        Uwaga: <?= $parameter->primaryNorm->caution_margin_percent ?>%
                                                    </small>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    <i class="fas fa-times-circle"></i> Brak
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $isCritical = false;
                                            if ($parameter->primaryNorm) {
                                                $thresholdManager = new \app\components\MedicalThresholdManager();
                                                $isCritical = $thresholdManager->getParameterCategory($parameter->name) === 'critical';
                                            }
                                            ?>
                                            
                                            <?php if ($parameter->primaryNorm && $parameter->primaryNorm->warning_enabled): ?>
                                                <span class="badge bg-success">Gotowe</span>
                                            <?php elseif ($isCritical): ?>
                                                <span class="badge bg-danger">Krytyczny - wymaga konfiguracji</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Do skonfigurowania</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <?php if ($parameter->primaryNorm): ?>
                                                    <?= Html::a('<i class="fas fa-cog"></i>', 
                                                        ['edit-norm', 'id' => $model->id, 'normId' => $parameter->primaryNorm->id], 
                                                        [
                                                            'class' => 'btn btn-sm btn-outline-primary',
                                                            'title' => 'Edytuj normę'
                                                        ]) ?>
                                                    
                                                    <?php if (!$parameter->primaryNorm->warning_enabled): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                onclick="quickEnableWarning(<?= $parameter->id ?>)"
                                                                title="Włącz ostrzeżenia">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?= Html::a('<i class="fas fa-plus"></i>', 
                                                        ['add-norm', 'id' => $model->id, 'parameterId' => $parameter->id], 
                                                        [
                                                            'class' => 'btn btn-sm btn-outline-success',
                                                            'title' => 'Dodaj normę'
                                                        ]) ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel boczny z informacjami -->
        <div class="col-md-4">
            <!-- Statystyki ostrzeżeń -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6><i class="fas fa-chart-bar"></i> Statystyki ostrzeżeń</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Pokrycie ostrzeżeniami:</label>
                        <?= Progress::widget([
                            'percent' => $warningsStats['coverage_percent'],
                            'label' => $warningsStats['coverage_percent'] . '%',
                            'options' => [
                                'class' => $warningsStats['coverage_percent'] >= 80 ? 'bg-success' : 
                                          ($warningsStats['coverage_percent'] >= 50 ? 'bg-warning' : 'bg-danger')
                            ]
                        ]) ?>
                    </div>

                    <div class="row text-center">
                        <div class="col-6">
                            <div class="stat-card p-2 mb-2 border rounded">
                                <div class="h4 text-primary mb-0"><?= $warningsStats['total_parameters'] ?></div>
                                <small class="text-muted">Łącznie parametrów</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card p-2 mb-2 border rounded">
                                <div class="h4 text-success mb-0"><?= $warningsStats['warnings_enabled'] ?></div>
                                <small class="text-muted">Z ostrzeżeniami</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card p-2 mb-2 border rounded">
                                <div class="h4 text-warning mb-0"><?= $warningsStats['warnings_disabled'] ?></div>
                                <small class="text-muted">Bez ostrzeżeń</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card p-2 mb-2 border rounded">
                                <div class="h4 text-danger mb-0"><?= $warningsStats['critical_parameters'] ?></div>
                                <small class="text-muted">Krytyczne</small>
                            </div>
                        </div>
                    </div>

                    <?php if ($warningsStats['coverage_percent'] < 100): ?>
                        <div class="d-grid">
                            <?= Html::a('<i class="fas fa-magic"></i> Auto-konfiguracja', 
                                ['configure-warnings', 'id' => $model->id], 
                                ['class' => 'btn btn-warning btn-sm']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Szybkie akcje -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-bolt"></i> Szybkie akcje</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?= Html::a('<i class="fas fa-plus"></i> Dodaj parametr', 
                            ['add-parameter', 'id' => $model->id], 
                            ['class' => 'btn btn-outline-primary btn-sm']) ?>
                        
                        <?= Html::a('<i class="fas fa-copy"></i> Klonuj szablon', 
                            ['clone', 'id' => $model->id], 
                            ['class' => 'btn btn-outline-secondary btn-sm']) ?>
                        
                        <?= Html::a('<i class="fas fa-file-export"></i> Eksportuj', 
                            ['export', 'id' => $model->id], 
                            ['class' => 'btn btn-outline-info btn-sm']) ?>
                    </div>
                </div>
            </div>

            <!-- Ostatnie wyniki -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-history"></i> Ostatnie wyniki</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Pobierz ostatnie 5 wyników dla tego szablonu
                    $recentResults = \app\models\TestResult::find()
                        ->where(['test_template_id' => $model->id])
                        ->orderBy(['created_at' => SORT_DESC])
                        ->limit(5)
                        ->all();
                    ?>
                    
                    <?php if (!empty($recentResults)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentResults as $result): ?>
                                <div class="list-group-item px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">
                                                <?= Yii::$app->formatter->asDate($result->test_date) ?>
                                            </small>
                                            <br>
                                            <?php
                                            $abnormalCount = count(array_filter($result->resultValues, function($v) {
                                                return $v->is_abnormal;
                                            }));
                                            $warningCount = count(array_filter($result->resultValues, function($v) {
                                                return !$v->is_abnormal && in_array($v->warning_level, ['warning', 'caution']);
                                            }));
                                            ?>
                                            <small>
                                                <?php if ($abnormalCount > 0): ?>
                                                    <span class="badge bg-danger"><?= $abnormalCount ?> nieprawidłowych</span>
                                                <?php endif; ?>
                                                <?php if ($warningCount > 0): ?>
                                                    <span class="badge bg-warning"><?= $warningCount ?> ostrzeżeń</span>
                                                <?php endif; ?>
                                                <?php if ($abnormalCount == 0 && $warningCount == 0): ?>
                                                    <span class="badge bg-success">Wszystko OK</span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?= Html::a('<i class="fas fa-eye"></i>', 
                                                ['test-result/view', 'id' => $result->id], 
                                                ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-2">
                            <?= Html::a('Zobacz wszystkie wyniki', 
                                ['test-result/index', 'TestResultSearch[test_template_id]' => $model->id], 
                                ['class' => 'btn btn-sm btn-outline-secondary w-100']) ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Brak wyników dla tego szablonu</p>
                        <?= Html::a('<i class="fas fa-plus"></i> Dodaj pierwszy wynik', 
                            ['test-result/create', 'template_id' => $model->id], 
                            ['class' => 'btn btn-success btn-sm w-100']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function quickEnableWarning(parameterId) {
    if (confirm('Czy chcesz włączyć standardowe ostrzeżenia dla tego parametru?')) {
        $.post('<?= yii\helpers\Url::to(['quick-enable-warning', 'id' => $model->id]) ?>', {
            parameterId: parameterId
        })
        .done(function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Błąd: ' + response.message);
            }
        })
        .fail(function() {
            alert('Błąd komunikacji z serwerem');
        });
    }
}
</script>

<style>
.warnings-info {
    font-size: 0.8em;
}

.norm-display {
    font-size: 0.9em;
}

.stat-card {
    background-color: rgba(248, 249, 250, 0.5);
}

.list-group-item {
    border-left: none;
    border-right: none;
}

.list-group-item:first-child {
    border-top: none;
}

.list-group-item:last-child {
    border-bottom: none;
}

.table th {
    font-size: 0.9rem;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .btn-group .btn {
        font-size: 0.75rem;
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
}
</style>