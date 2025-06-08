<?php
// views/test-template/view.php

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

// Określ status na podstawie pokrycia ostrzeżeniami
$configurationStatus = 'Do skonfigurowania';
if ($warningsStats['coverage_percent'] >= 80) {
    $configurationStatus = 'Skonfigurowano';
} elseif ($warningsStats['coverage_percent'] >= 50) {
    $configurationStatus = 'Częściowo skonfigurowano';
}
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
                    <?php
                    // Określ klasę CSS na podstawie pokrycia ostrzeżeniami
                    $statusClass = 'text-danger fw-bold';
                    if ($warningsStats['coverage_percent'] >= 80) {
                        $statusClass = 'text-success fw-bold';
                    } elseif ($warningsStats['coverage_percent'] >= 50) {
                        $statusClass = 'text-warning fw-bold';
                    }
                    ?>
                    
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'name',
                            'description:ntext',
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
                    <div class="table-responsive table-with-dropdown">
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
                                            <?php if (!empty($parameter->norms)): ?>
                                                <?php foreach ($parameter->norms as $norm): ?>
                                                    <span class="badge bg-info me-1">
                                                        <?= ucfirst($norm->type) ?>
                                                        <?php if ($norm->is_primary): ?>
                                                            <i class="fas fa-star text-warning ms-1" title="Norma podstawowa"></i>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Brak norm</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($parameter->norms)): ?>
                                                <?php foreach ($parameter->norms as $index => $norm): ?>
                                                    <?php if ($index > 0): ?><br><?php endif; ?>
                                                    <div class="norm-display mb-1">
                                                        <strong><?= Html::encode($norm->name) ?></strong>
                                                        <?php if ($norm->type === 'range'): ?>
                                                            <div class="small text-muted">
                                                                <?= $norm->min_value ?> - <?= $norm->max_value ?>
                                                                <?php if ($parameter->unit): ?>
                                                                    <?= Html::encode($parameter->unit) ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php elseif ($norm->type === 'single_threshold'): ?>
                                                            <div class="small text-muted">
                                                                <?= $norm->threshold_direction === 'above' ? '≤' : '≥' ?>
                                                                <?= $norm->threshold_value ?>
                                                                <?php if ($parameter->unit): ?>
                                                                    <?= Html::encode($parameter->unit) ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php elseif ($norm->type === 'positive_negative'): ?>
                                                            <div class="small text-muted">Pozytywny/Negatywny</div>
                                                        <?php elseif ($norm->type === 'multiple_thresholds'): ?>
                                                            <div class="small text-muted">Wielokrotne progi</div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Brak norm</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $hasEnabledWarnings = false;
                                            if (!empty($parameter->norms)) {
                                                foreach ($parameter->norms as $norm) {
                                                    if ($norm->warning_enabled) {
                                                        $hasEnabledWarnings = true;
                                                        break;
                                                    }
                                                }
                                            }
                                            ?>
                                            <?php if ($hasEnabledWarnings): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-bell"></i> Włączone
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-bell-slash"></i> Wyłączone
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($parameter->norms)): ?>
                                                <?php if ($hasEnabledWarnings): ?>
                                                    <span class="badge bg-success">Gotowy</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Wymaga konfiguracji</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Brak norm</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <!-- Operacje na parametrze -->
                                                <?= Html::a('<i class="fas fa-edit"></i>', 
                                                    ['edit-parameter', 'id' => $model->id, 'parameterId' => $parameter->id], 
                                                    ['class' => 'btn btn-outline-secondary btn-sm', 'title' => 'Edytuj parametr']) ?>
                                                
                                                <!-- Dodaj normę jeśli brak -->
                                                <?php if (empty($parameter->norms)): ?>
                                                    <?= Html::a('<i class="fas fa-plus"></i>', 
                                                        ['add-norm', 'id' => $model->id, 'parameterId' => $parameter->id], 
                                                        ['class' => 'btn btn-outline-success btn-sm', 'title' => 'Dodaj normę']) ?>
                                                <?php endif; ?>
                                                
                                                <!-- Sidebar z operacjami na normach -->
                                                <?php if (!empty($parameter->norms)): ?>
                                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                                            onclick="openNormsSidebar(<?= $parameter->id ?>, '<?= Html::encode($parameter->name) ?>')"
                                                            title="Zarządzaj normami">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <!-- Usuń parametr -->
                                                <?= Html::a('<i class="fas fa-trash"></i>', 
                                                    ['delete-parameter', 'id' => $model->id, 'parameterId' => $parameter->id], 
                                                    [
                                                        'class' => 'btn btn-outline-danger btn-sm',
                                                        'title' => 'Usuń parametr',
                                                        'data-method' => 'post',
                                                        'data-confirm' => 'Czy na pewno chcesz usunąć ten parametr? Wszystkie powiązane normy również zostaną usunięte.'
                                                    ]) ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <?= Html::a('<i class="fas fa-plus"></i> Dodaj parametr', 
                            ['add-parameter', 'id' => $model->id], 
                            ['class' => 'btn btn-outline-primary']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel boczny ze statystykami -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie"></i> Pokrycie ostrzeżeniami</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
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

            <!-- Ostatnie wyniki -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> Ostatnie wyniki</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($model->results)): ?>
                        <?php foreach (array_slice($model->results, 0, 5) as $result): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <div>
                                    <small class="text-muted"><?= Yii::$app->formatter->asDate($result->test_date) ?></small>
                                    <?php if ($result->has_abnormal_values): ?>
                                        <span class="badge bg-danger ms-1">Nieprawidłowe</span>
                                    <?php endif; ?>
                                </div>
                                <?= Html::a('<i class="fas fa-eye"></i>', 
                                    ['test-result/view', 'id' => $result->id], 
                                    ['class' => 'btn btn-outline-primary btn-sm']) ?>
                            </div>
                        <?php endforeach; ?>
                        <div class="mt-2">
                            <?= Html::a('Zobacz wszystkie', 
                                ['test-result/index', 'TestResultSearch[test_template_id]' => $model->id], 
                                ['class' => 'btn btn-outline-secondary btn-sm w-100']) ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Brak wyników badań</p>
                        <?= Html::a('<i class="fas fa-plus"></i> Dodaj pierwszy wynik', 
                            ['test-result/create', 'template_id' => $model->id], 
                            ['class' => 'btn btn-outline-success btn-sm w-100 mt-2']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>