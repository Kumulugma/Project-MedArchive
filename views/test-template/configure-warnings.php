<?php
// views/test-template/configure-warnings.php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\bootstrap5\Progress;

$this->title = 'Konfiguracja ostrzeżeń: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Szablony badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Konfiguracja ostrzeżeń';
?>

<div class="test-template-configure-warnings">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                Konfiguracja ostrzeżeń
            </h1>
            <div class="btn-toolbar">
                <?= Html::a('<i class="fas fa-arrow-left"></i> Powrót', ['view', 'id' => $model->id], ['class' => 'btn btn-secondary']) ?>
            </div>
        </div>
        <p class="text-muted">Szablon: <strong><?= Html::encode($model->name) ?></strong></p>
    </div>

    <!-- Statystyki pokrycia -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie"></i> Pokrycie ostrzeżeniami</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <?= Progress::widget([
                                'percent' => $stats['coverage_percent'],
                                'label' => $stats['coverage_percent'] . '% parametrów ma skonfigurowane ostrzeżenia',
                                'options' => [
                                    'class' => $stats['coverage_percent'] >= 80 ? 'progress-bar-success' : 
                                              ($stats['coverage_percent'] >= 50 ? 'progress-bar-warning' : 'progress-bar-danger')
                                ]
                            ]) ?>
                        </div>
                        <div class="col-md-4">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="stat-box">
                                        <div class="stat-number text-primary"><?= $stats['total_parameters'] ?></div>
                                        <div class="stat-label">Łącznie</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-box">
                                        <div class="stat-number text-success"><?= $stats['warnings_enabled'] ?></div>
                                        <div class="stat-label">Z ostrzeżeniami</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-box">
                                        <div class="stat-number text-danger"><?= $stats['critical_parameters'] ?></div>
                                        <div class="stat-label">Krytyczne</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Automatyczna konfiguracja -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-magic"></i> Automatyczna konfiguracja</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin([
                        'action' => ['configure-warnings', 'id' => $model->id],
                        'options' => ['class' => 'auto-setup-form']
                    ]); ?>
                    
                    <input type="hidden" name="auto_setup" value="1">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Preset marginesów</label>
                                <select name="preset" class="form-control">
                                    <?php foreach ($presets as $key => $preset): ?>
                                        <option value="<?= $key ?>" <?= $key === 'standard' ? 'selected' : '' ?>>
                                            <?= Html::encode($preset['name']) ?>
                                            (Ostrzeżenie: <?= $preset['warning_percent'] ?>%, Uwaga: <?= $preset['caution_percent'] ?>%)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Wybierz domyślne marginesy dla wszystkich parametrów</small>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Jak działają presety:</h6>
                                <ul class="mb-0 small">
                                    <li><strong>Konserwatywny:</strong> Większe marginesy bezpieczeństwa, więcej ostrzeżeń</li>
                                    <li><strong>Standardowy:</strong> Zrównoważone podejście, zalecane dla większości przypadków</li>
                                    <li><strong>Liberalny:</strong> Mniejsze marginesy, mniej ostrzeżeń</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-magic"></i> Skonfiguruj automatycznie
                    </button>
                    
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista parametrów bez ostrzeżeń -->
    <?php if (!empty($parametersWithoutWarnings)): ?>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-exclamation-triangle"></i> Parametry bez ostrzeżeń</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Następujące parametry nie mają skonfigurowanych ostrzeżeń. 
                            Kliknij przycisk aby szybko je skonfigurować.
                        </p>
                        
                        <div class="row">
                            <?php foreach ($parametersWithoutWarnings as $parameter): ?>
                                <div class="col-md-4 mb-2">
                                    <div class="parameter-item p-2 border rounded d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= Html::encode($parameter->name) ?></strong>
                                            <?php if ($parameter->unit): ?>
                                                <small class="text-muted">(<?= Html::encode($parameter->unit) ?>)</small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($parameter->primaryNorm): ?>
                                            <?= Html::a('<i class="fas fa-cog"></i> Konfiguruj', 
                                                ['update-norm', 'id' => $model->id, 'parameterId' => $parameter->id, 'normId' => $parameter->primaryNorm->id], 
                                                ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                        <?php else: ?>
                                            <?= Html::a('<i class="fas fa-plus"></i> Dodaj normę', 
                                                ['add-norm', 'id' => $model->id, 'parameterId' => $parameter->id], 
                                                ['class' => 'btn btn-sm btn-outline-success']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Podgląd wszystkich parametrów -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Wszystkie parametry</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Parametr</th>
                                    <th>Norma</th>
                                    <th>Ostrzeżenia</th>
                                    <th>Margines ostrzeżenia</th>
                                    <th>Margines uwagi</th>
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
                                            <?php if ($parameter->primaryNorm): ?>
                                                <?php if ($parameter->primaryNorm->type === 'range'): ?>
                                                    <?= $parameter->primaryNorm->min_value ?> - <?= $parameter->primaryNorm->max_value ?>
                                                <?php elseif ($parameter->primaryNorm->type === 'single_threshold'): ?>
                                                    <?= $parameter->primaryNorm->threshold_direction === 'above' ? '≤' : '≥' ?>
                                                    <?= $parameter->primaryNorm->threshold_value ?>
                                                <?php else: ?>
                                                    <small class="text-muted">Wielokrotne progi</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-danger">Brak normy</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($parameter->primaryNorm && $parameter->primaryNorm->warning_enabled): ?>
                                                <span class="badge bg-success">Włączone</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Wyłączone</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($parameter->primaryNorm && $parameter->primaryNorm->warning_enabled): ?>
                                                <?= $parameter->primaryNorm->warning_margin_percent ?>%
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($parameter->primaryNorm && $parameter->primaryNorm->warning_enabled): ?>
                                                <?= $parameter->primaryNorm->caution_margin_percent ?>%
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($parameter->primaryNorm): ?>
                                                <?= Html::a('<i class="fas fa-edit"></i>', 
                                                    ['update-norm', 'id' => $model->id, 'parameterId' => $parameter->id, 'normId' => $parameter->primaryNorm->id], 
                                                    ['class' => 'btn btn-outline-primary btn-sm', 'title' => 'Edytuj']) ?>
                                            <?php else: ?>
                                                <?= Html::a('<i class="fas fa-plus"></i>', 
                                                    ['add-norm', 'id' => $model->id, 'parameterId' => $parameter->id], 
                                                    ['class' => 'btn btn-outline-success btn-sm', 'title' => 'Dodaj normę']) ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-box {
    text-align: center;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
}

.stat-label {
    font-size: 0.8rem;
    color: #6c757d;
}

.parameter-item {
    transition: all 0.2s;
}

.parameter-item:hover {
    background-color: #f8f9fa;
    border-color: #007bff !important;
}
</style>