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
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-magic"></i> Automatyczna konfiguracja</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin([
                        'action' => ['configure-warnings', 'id' => $model->id],
                        'options' => ['class' => 'auto-setup-form']
                    ]); ?>
                    
                    <input type="hidden" name="auto_setup" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Preset marginesów</label>
                        <select name="preset" class="form-control">
                            <?php foreach ($presets as $key => $preset): ?>
                                <option value="<?= $key ?>" <?= $key === 'standard' ? 'selected' : '' ?>>
                                    <?= Html::encode($preset['name']) ?> 
                                    (<?= $preset['warning'] ?>%/<?= $preset['caution'] ?>%)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Automatycznie dobierze marginesy dla wszystkich parametrów</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Wiek pacjenta (opcjonalnie)</label>
                        <input type="number" name="patient_age" class="form-control" 
                               placeholder="np. 45" min="0" max="120">
                        <small class="text-muted">Pomoże dobrać marginesy odpowiednie dla wieku</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Schorzenia współistniejące</label>
                        <div class="form-check">
                            <input type="checkbox" name="conditions[]" value="diabetes" class="form-check-input" id="diabetes">
                            <label class="form-check-label" for="diabetes">Cukrzyca</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="conditions[]" value="hypertension" class="form-check-input" id="hypertension">
                            <label class="form-check-label" for="hypertension">Nadciśnienie</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="conditions[]" value="kidney_disease" class="form-check-input" id="kidney_disease">
                            <label class="form-check-label" for="kidney_disease">Choroby nerek</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="conditions[]" value="liver_disease" class="form-check-input" id="liver_disease">
                            <label class="form-check-label" for="liver_disease">Choroby wątroby</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-magic"></i> Skonfiguruj automatycznie
                    </button>
                    
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <!-- Kopiowanie z innego szablonu -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5><i class="fas fa-copy"></i> Kopiuj z innego szablonu</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin([
                        'action' => ['configure-warnings', 'id' => $model->id],
                        'options' => ['class' => 'clone-form']
                    ]); ?>
                    
                    <input type="hidden" name="clone_from" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Szablon źródłowy</label>
                        <select name="source_template_id" class="form-control" required>
                            <option value="">Wybierz szablon...</option>
                            <?php foreach ($otherTemplates as $template): ?>
                                <option value="<?= $template->id ?>">
                                    <?= Html::encode($template->name) ?>
                                    <?php
                                    $templateStats = $template->getWarningsStatistics();
                                    echo " ({$templateStats['coverage_percent']}% pokrycia)";
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Skopiuje konfigurację ostrzeżeń dla pasujących parametrów</small>
                    </div>

                    <button type="submit" class="btn btn-info w-100">
                        <i class="fas fa-copy"></i> Skopiuj konfigurację
                    </button>
                    
                    <?php ActiveForm::end(); ?>

                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Kopiowane będą tylko parametry o identycznych nazwach
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista parametrów bez ostrzeżeń -->
    <?php if (!empty($parametersWithoutWarnings)): ?>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-warning">
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
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="quickSetupWarning(<?= $parameter->id ?>)">
                                            <i class="fas fa-plus"></i> Dodaj
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Szczegółowa lista parametrów -->
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
                                    <th>Status ostrzeżeń</th>
                                    <th>Marginesy</th>
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
                                                <?php $norm = $parameter->primaryNorm; ?>
                                                <?php if ($norm->type === 'range'): ?>
                                                    <small><?= $norm->min_value ?> - <?= $norm->max_value ?></small>
                                                <?php elseif ($norm->type === 'single_threshold'): ?>
                                                    <small><?= $norm->threshold_direction === 'above' ? '≤' : '≥' ?> <?= $norm->threshold_value ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Brak normy</span>
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
                                                <small><?= $parameter->primaryNorm->getMarginsDescription() ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <?php if ($parameter->primaryNorm): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="quickSetupWarning(<?= $parameter->id ?>)">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    <?= Html::a('<i class="fas fa-edit"></i>', 
                                                        ['edit-norm', 'id' => $model->id, 'normId' => $parameter->primaryNorm->id], 
                                                        ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                                                <?php else: ?>
                                                    <?= Html::a('<i class="fas fa-plus"></i>', 
                                                        ['add-norm', 'id' => $model->id, 'parameterId' => $parameter->id], 
                                                        ['class' => 'btn btn-sm btn-outline-success']) ?>
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
    </div>
</div>

<!-- Modal dla szybkiej konfiguracji -->
<div class="modal fade" id="quickSetupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Szybka konfiguracja ostrzeżeń</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="quickSetupModalBody">
                <!-- Zawartość ładowana przez AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
function quickSetupWarning(parameterId) {
    $('#quickSetupModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Ładowanie...</div>');
    $('#quickSetupModal').modal('show');
    
    $.get('<?= yii\helpers\Url::to(['quick-setup-warning', 'id' => $model->id]) ?>', {parameterId: parameterId})
        .done(function(data) {
            $('#quickSetupModalBody').html(data);
        })
        .fail(function() {
            $('#quickSetupModalBody').html('<div class="alert alert-danger">Błąd ładowania formularza</div>');
        });
}

function saveQuickSetup(parameterId) {
    const formData = $('#quickSetupForm').serialize();
    
    $.post('<?= yii\helpers\Url::to(['quick-setup-warning', 'id' => $model->id]) ?>', formData + '&parameterId=' + parameterId)
        .done(function(response) {
            if (response.success) {
                $('#quickSetupModal').modal('hide');
                location.reload(); // Odśwież stronę aby pokazać zmiany
            } else {
                alert('Błąd: ' + response.message);
            }
        })
        .fail(function() {
            alert('Błąd komunikacji z serwerem');
        });
}
</script>

<style>
.stat-box {
    padding: 10px;
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
    background-color: rgba(255, 193, 7, 0.1);
}

.table th {
    font-size: 0.9rem;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
}
</style>