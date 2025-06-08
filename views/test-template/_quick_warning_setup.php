<?php
// views/test-template/_quick_warning_setup.php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\components\MedicalThresholdManager;

$thresholdManager = new MedicalThresholdManager();
$presets = $thresholdManager->getPresetOptions();
$parameterCategory = $thresholdManager->getParameterCategory($parameter->name);
$suggestedMargins = $thresholdManager->getParameterMargins($parameter->name);
?>

<div class="quick-warning-setup">
    <div class="alert alert-info">
        <h6><i class="fas fa-info-circle"></i> Parametr: <?= Html::encode($parameter->name) ?></h6>
        <p class="mb-0">
            Kategoria: <strong><?= Html::encode($parameterCategory) ?></strong>
            <?php if ($parameter->unit): ?>
                | Jednostka: <strong><?= Html::encode($parameter->unit) ?></strong>
            <?php endif; ?>
        </p>
    </div>

    <?php $form = ActiveForm::begin([
        'id' => 'quickSetupForm',
        'enableClientValidation' => false,
        'enableAjaxValidation' => false,
    ]); ?>

    <div class="row">
        <div class="col-md-6">
            <h6>Szybka konfiguracja</h6>
            
            <div class="mb-3">
                <label class="form-label">Preset marginesów</label>
                <select name="preset" class="form-control" onchange="applyPreset(this.value)">
                    <option value="">Wybierz preset...</option>
                    <?php foreach ($presets as $key => $preset): ?>
                        <option value="<?= $key ?>" <?= $key === 'standard' ? 'selected' : '' ?>>
                            <?= Html::encode($preset['name']) ?> 
                            (<?= $preset['warning'] ?>%/<?= $preset['caution'] ?>%)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted"><?= Html::encode($presets['standard']['description']) ?></small>
            </div>

            <div class="mb-3">
                <label class="form-label">Wiek pacjenta (opcjonalnie)</label>
                <input type="number" name="patient_age" class="form-control" 
                       placeholder="np. 45" min="0" max="120" onchange="updateMargins()">
                <small class="text-muted">Automatycznie dostosuje marginesy do wieku</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Schorzenia</label>
                <div class="form-check">
                    <input type="checkbox" name="conditions[]" value="diabetes" 
                           class="form-check-input" id="quick-diabetes" onchange="updateMargins()">
                    <label class="form-check-label" for="quick-diabetes">Cukrzyca</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="conditions[]" value="hypertension" 
                           class="form-check-input" id="quick-hypertension" onchange="updateMargins()">
                    <label class="form-check-label" for="quick-hypertension">Nadciśnienie</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="conditions[]" value="kidney_disease" 
                           class="form-check-input" id="quick-kidney" onchange="updateMargins()">
                    <label class="form-check-label" for="quick-kidney">Choroby nerek</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="conditions[]" value="liver_disease" 
                           class="form-check-input" id="quick-liver" onchange="updateMargins()">
                    <label class="form-check-label" for="quick-liver">Choroby wątroby</label>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <h6>Dokładne ustawienia</h6>
            
            <div class="mb-3">
                <label class="form-label">Margines ostrzeżenia (%)</label>
                <input type="number" name="warning_margin_percent" class="form-control" 
                       step="0.1" min="1" max="50" 
                       value="<?= $suggestedMargins['warning_percent'] ?>"
                       id="warningPercent">
                <small class="text-muted">Wartości w tej odległości od granic będą oznaczone jako ostrzeżenie</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Margines uwagi (%)</label>
                <input type="number" name="caution_margin_percent" class="form-control" 
                       step="0.1" min="0.5" max="30" 
                       value="<?= $suggestedMargins['caution_percent'] ?>"
                       id="cautionPercent">
                <small class="text-muted">Wartości w tej odległości będą oznaczone do obserwacji</small>
            </div>

            <!-- Podgląd dla zakresu -->
            <?php if ($parameter->primaryNorm && $parameter->primaryNorm->type === 'range'): ?>
                <div class="mb-3">
                    <label class="form-label">Podgląd stref</label>
                    <div id="zonesPreview" class="zones-preview">
                        <!-- Będzie wypełnione przez JavaScript -->
                    </div>
                </div>
            <?php endif; ?>

            <!-- Specjalne ustawienia dla konkretnych parametrów -->
            <div class="mb-3">
                <div class="card bg-light">
                    <div class="card-body p-2">
                        <small>
                            <strong>Zalecenia dla <?= Html::encode($parameter->name) ?>:</strong><br>
                            <?php
                            $advice = [
                                'glucose' => 'Glukoza wymaga ścisłego monitorowania - zalecane małe marginesy',
                                'cholesterol' => 'Cholesterol - standardowe marginesy, ważne trendy długoterminowe',
                                'alt_ast' => 'Transaminazy - większe marginesy ze względu na zmienność',
                                'creatinine' => 'Kreatynina - ważny marker funkcji nerek, średnie marginesy',
                                'tsh' => 'TSH - kluczowy dla funkcji tarczycy, standardowe marginesy'
                            ];
                            $normalizedName = strtolower(trim($parameter->name));
                            $paramAdvice = null;
                            foreach ($advice as $key => $text) {
                                if (strpos($normalizedName, $key) !== false) {
                                    $paramAdvice = $text;
                                    break;
                                }
                            }
                            echo $paramAdvice ?: 'Standardowe marginesy dla tego typu parametru';
                            ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="enable_warnings" value="1">

    <?php ActiveForm::end(); ?>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
        <button type="button" class="btn btn-primary" onclick="saveQuickSetup(<?= $parameter->id ?>)">
            <i class="fas fa-save"></i> Zapisz ostrzeżenia
        </button>
    </div>
</div>

<script>
function applyPreset(presetKey) {
    const presets = {
        'conservative': { warning: 15, caution: 8 },
        'standard': { warning: 10, caution: 5 },
        'liberal': { warning: 5, caution: 3 }
    };
    
    if (presets[presetKey]) {
        document.getElementById('warningPercent').value = presets[presetKey].warning;
        document.getElementById('cautionPercent').value = presets[presetKey].caution;
        updateZonesPreview();
    }
}

function updateMargins() {
    // Pobierz wartości formularza
    const age = document.querySelector('input[name="patient_age"]').value;
    const conditions = Array.from(document.querySelectorAll('input[name="conditions[]"]:checked'))
                          .map(cb => cb.value);
    
    // Wywołaj AJAX aby otrzymać zaktualizowane marginesy
    $.post('<?= yii\helpers\Url::to(['get-suggested-margins']) ?>', {
        parameterName: '<?= Html::encode($parameter->name) ?>',
        age: age,
        conditions: conditions
    })
    .done(function(response) {
        if (response.success) {
            document.getElementById('warningPercent').value = response.margins.warning_percent;
            document.getElementById('cautionPercent').value = response.margins.caution_percent;
            updateZonesPreview();
        }
    });
}

function updateZonesPreview() {
    <?php if ($parameter->primaryNorm && $parameter->primaryNorm->type === 'range'): ?>
        const norm = <?= json_encode([
            'min_value' => $parameter->primaryNorm->min_value,
            'max_value' => $parameter->primaryNorm->max_value
        ]) ?>;
        
        const warningPercent = parseFloat(document.getElementById('warningPercent').value) || 10;
        const cautionPercent = parseFloat(document.getElementById('cautionPercent').value) || 5;
        
        const range = norm.max_value - norm.min_value;
        const warningMargin = range * (warningPercent / 100);
        const cautionMargin = range * (cautionPercent / 100);
        
        const zones = [
            { label: 'Poza normą', range: `< ${norm.min_value}`, class: 'bg-danger' },
            { label: 'Ostrzeżenie', range: `${norm.min_value} - ${(norm.min_value + warningMargin).toFixed(2)}`, class: 'bg-warning' },
            { label: 'Uwaga', range: `${(norm.min_value + warningMargin).toFixed(2)} - ${(norm.min_value + cautionMargin).toFixed(2)}`, class: 'bg-info' },
            { label: 'Optymalne', range: `${(norm.min_value + cautionMargin).toFixed(2)} - ${(norm.max_value - cautionMargin).toFixed(2)}`, class: 'bg-success' },
            { label: 'Uwaga', range: `${(norm.max_value - cautionMargin).toFixed(2)} - ${(norm.max_value - warningMargin).toFixed(2)}`, class: 'bg-info' },
            { label: 'Ostrzeżenie', range: `${(norm.max_value - warningMargin).toFixed(2)} - ${norm.max_value}`, class: 'bg-warning' },
            { label: 'Poza normą', range: `> ${norm.max_value}`, class: 'bg-danger' }
        ];
        
        let html = '';
        zones.forEach(zone => {
            html += `<div class="zone ${zone.class} text-white p-1 mb-1 rounded" style="font-size: 0.75rem;">
                        <strong>${zone.label}:</strong> ${zone.range}
                     </div>`;
        });
        
        document.getElementById('zonesPreview').innerHTML = html;
    <?php endif; ?>
}

// Inicjalizacja
document.addEventListener('DOMContentLoaded', function() {
    applyPreset('standard');
});
</script>

<style>
.zones-preview {
    max-height: 200px;
    overflow-y: auto;
}

.zone {
    font-size: 0.75rem;
    text-align: center;
}

.card.bg-light {
    border: 1px solid #dee2e6;
}

.form-check {
    margin-bottom: 0.25rem;
}

.alert-info {
    font-size: 0.9rem;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
    padding: 1rem;
    margin: 0 -1rem -1rem -1rem;
    background-color: #f8f9fa;
}
</style>