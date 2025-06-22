<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $template app\models\TestTemplate */
/* @var $parameter app\models\TestParameter */
/* @var $norm app\models\ParameterNorm */
/* @var $this yii\web\View */

$isUpdate = !$norm->isNewRecord;
$actionUrl = $isUpdate 
    ? ['update-norm', 'id' => $template->id, 'parameterId' => $parameter->id, 'normId' => $norm->id]
    : ['add-norm', 'id' => $template->id, 'parameterId' => $parameter->id];
?>

<div class="norm-form-container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog"></i>
                        <?= $isUpdate ? 'Edycja normy' : 'Nowa norma' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Informacje o parametrze -->
                    <div class="alert alert-info">
                        <strong><i class="fas fa-chart-line"></i> Parametr:</strong> 
                        <?= Html::encode($parameter->name) ?>
                        <?php if ($parameter->unit): ?>
                            <span class="text-muted">(<?= Html::encode($parameter->unit) ?>)</span>
                        <?php endif; ?>
                        <?php if (isset($parameter->description) && $parameter->description): ?>
                            <br><small><?= Html::encode($parameter->description) ?></small>
                        <?php endif; ?>
                    </div>

                    <?php $form = ActiveForm::begin([
                        'action' => $actionUrl,
                        'options' => ['novalidate' => true], // WYŁĄCZ HTML5 validation
                        'enableClientValidation' => false,   // WYŁĄCZ JavaScript validation
                        'enableAjaxValidation' => false      // WYŁĄCZ AJAX validation
                    ]); ?>

                    <!-- Podstawowe informacje o normie -->
                    <div class="row">
                        <div class="col-md-8">
                            <?= $form->field($norm, 'name')->textInput([
                                'placeholder' => 'np. Norma laboratoryjna, Norma referencyjna',
                                'maxlength' => true
                            ]) ?>
                        </div>
                        <div class="col-md-4">
                            <?= $form->field($norm, 'is_primary')->checkbox([
                                'label' => 'Norma podstawowa',
                                'labelOptions' => ['class' => 'form-check-label']
                            ]) ?>
                        </div>
                    </div>

                    <!-- Typ normy -->
                    <?= $form->field($norm, 'type')->dropDownList([
                        '' => 'Wybierz typ normy...',
                        'positive_negative' => 'Pozytywny/Negatywny',
                        'range' => 'Zakres (min-max)',
                        'single_threshold' => 'Pojedynczy próg',
                        'multiple_thresholds' => 'Wiele progów'
                    ], [
                        'id' => 'norm-type-select',
                        'onchange' => 'toggleNormFields(this.value)'
                    ]) ?>

                    <!-- Pola specyficzne dla typu "positive_negative" -->
                    <div id="positive-negative-fields" style="display: none;">
                        <div class="alert alert-secondary">
                            <i class="fas fa-info-circle"></i>
                            <strong>Norma pozytywny/negatywny:</strong> 
                            Wynik jest normalny gdy wartość to "negatywny", "ujemny", "negative" lub podobne.
                        </div>
                    </div>

                    <!-- Pola specyficzne dla typu "range" -->
                    <div id="range-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($norm, 'min_value')->textInput([
                                    'type' => 'number',
                                    'step' => 'any',
                                    'placeholder' => 'np. 3.5'
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($norm, 'max_value')->textInput([
                                    'type' => 'number',
                                    'step' => 'any',
                                    'placeholder' => 'np. 5.2'
                                ]) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Pola specyficzne dla typu "single_threshold" -->
                    <div id="single-threshold-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($norm, 'threshold_value')->textInput([
                                    'type' => 'number',
                                    'step' => 'any',
                                    'placeholder' => 'np. 4.0'
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($norm, 'threshold_direction')->dropDownList([
                                    '' => 'Wybierz kierunek...',
                                    'below' => 'Normalny poniżej progu (≤)',
                                    'above' => 'Normalny powyżej progu (≥)'
                                ]) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Pola specyficzne dla typu "multiple_thresholds" -->
                    <div id="multiple-thresholds-fields" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Wiele progów:</strong> 
                            Definiuj różne progi wartości z własną interpretacją dla każdego zakresu.
                        </div>
                        
                        <!-- Wybór typu konfiguracji -->
                        <div class="mb-3">
                            <label class="form-label">Typ konfiguracji</label>
                            <select class="form-control" id="threshold-config-type" onchange="toggleThresholdConfigType(this.value)">
                                <option value="">Wybierz typ...</option>
                                <option value="breath_test">Test oddechowy (nietolerancja laktozy)</option>
                                <option value="standard">Standardowe progi wartości</option>
                                <option value="custom">Własna konfiguracja JSON</option>
                            </select>
                        </div>
                        
                        <!-- Konfiguracja dla testu oddechowego -->
                        <div id="breath-test-config" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Maksymalny wzrost (ppm)</label>
                                    <input type="number" step="0.1" class="form-control" id="breath-test-max-increase" 
                                           value="12" placeholder="12.0">
                                    <small class="text-muted">Domyślnie 12 ppm dla wodoru, 3 ppm dla metanu</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Typ gazu</label>
                                    <select class="form-control" id="breath-test-gas-type">
                                        <option value="hydrogen">Wodór (H₂)</option>
                                        <option value="methane">Metan (CH₄)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary btn-sm" onclick="generateBreathTestConfig()">
                                    <i class="fas fa-magic"></i> Generuj konfigurację testu oddechowego
                                </button>
                            </div>
                        </div>
                        
                        <!-- Konfiguracja standardowych progów -->
                        <div id="standard-thresholds-config" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Progi wartości</label>
                                <div id="thresholds-container">
                                    <div class="threshold-item border rounded p-3 mb-2">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label class="form-label small">Wartość progu</label>
                                                <input type="number" step="0.01" class="form-control form-control-sm threshold-value" placeholder="0.0">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Normalny?</label>
                                                <select class="form-control form-control-sm threshold-normal">
                                                    <option value="true">Tak - normalny</option>
                                                    <option value="false">Nie - nieprawidłowy</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Opis</label>
                                                <input type="text" class="form-control form-control-sm threshold-description" placeholder="Opis tego progu">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">&nbsp;</label>
                                                <button type="button" class="btn btn-danger btn-sm d-block" onclick="removeThreshold(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-success btn-sm" onclick="addThreshold()">
                                    <i class="fas fa-plus"></i> Dodaj próg
                                </button>
                            </div>
                            
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary btn-sm" onclick="generateStandardConfig()">
                                    <i class="fas fa-magic"></i> Generuj konfigurację progów
                                </button>
                            </div>
                        </div>
                        
                        <!-- Pole na konfigurację JSON -->
                        <div class="mb-3">
                            <?= $form->field($norm, 'thresholds_config')->textarea([
                                'rows' => 8,
                                'id' => 'thresholds-config-json',
                                'placeholder' => 'Konfiguracja JSON zostanie wygenerowana automatycznie...',
                                'class' => 'form-control font-monospace'
                            ])->label('Konfiguracja JSON') ?>
                            <small class="text-muted">
                                Możesz edytować konfigurację ręcznie lub użyć generatorów powyżej.
                            </small>
                        </div>
                    </div>

                    <!-- Sekcja ostrzeżeń -->
                    <div class="mt-4 pt-3 border-top">
                        <h6><i class="fas fa-bell"></i> Konfiguracja ostrzeżeń</h6>
                        
                        <?= $form->field($norm, 'warning_enabled')->checkbox([
                            'label' => 'Włącz ostrzeżenia o wartościach granicznych',
                            'onchange' => 'toggleWarningFields(this.checked)'
                        ]) ?>

                        <div id="warning-fields" style="display: none;">
                            <div class="alert alert-info">
                                <small>
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Ostrzeżenia:</strong> System będzie ostrzegać gdy wynik jest blisko przekroczenia normy.
                                    Margines określa jak blisko granicy normy ma być wynik, aby wywołać ostrzeżenie.
                                </small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <?= $form->field($norm, 'warning_margin_percent')->textInput([
                                        'type' => 'number',
                                        'min' => 0,
                                        'max' => 50,
                                        'step' => 0.1,
                                        'placeholder' => '10'
                                    ])->hint('Margines ostrzeżenia w procentach (np. 10%)') ?>
                                </div>
                                <div class="col-md-6">
                                    <?= $form->field($norm, 'caution_margin_percent')->textInput([
                                        'type' => 'number',
                                        'min' => 0,
                                        'max' => 30,
                                        'step' => 0.1,
                                        'placeholder' => '5'
                                    ])->hint('Margines uwagi w procentach (np. 5%)') ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sekcja konwersji jednostek -->
                    <div class="mt-4 pt-3 border-top">
                        <h6><i class="fas fa-exchange-alt"></i> Konwersja jednostek (zaawansowane)</h6>
                        
                        <div class="alert alert-secondary">
                            <small>
                                <i class="fas fa-info-circle"></i>
                                Konwersja pozwala na przeliczanie wartości między jednostkami.
                                Wzór: <code>wartość_docelowa = (wartość_wejściowa × współczynnik) + przesunięcie</code>
                            </small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($norm, 'conversion_factor')->textInput([
                                    'type' => 'number',
                                    'step' => 'any',
                                    'value' => $norm->conversion_factor ?: 1
                                ])->hint('Domyślnie: 1 (bez konwersji)') ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($norm, 'conversion_offset')->textInput([
                                    'type' => 'number',
                                    'step' => 'any',
                                    'value' => $norm->conversion_offset ?: 0
                                ])->hint('Domyślnie: 0 (bez przesunięcia)') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Przyciski akcji -->
                    <div class="form-actions mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between">
                            <div>
                                <?= Html::submitButton(
                                    '<i class="fas fa-save"></i> ' . ($isUpdate ? 'Aktualizuj normę' : 'Dodaj normę'),
                                    ['class' => 'btn btn-primary']
                                ) ?>
                                
                                <?= Html::a(
                                    '<i class="fas fa-times"></i> Anuluj',
                                    ['view', 'id' => $template->id],
                                    ['class' => 'btn btn-secondary ms-2']
                                ) ?>
                            </div>
                            
                            <?php if ($isUpdate): ?>
                                <div>
                                    <?= Html::a(
                                        '<i class="fas fa-trash text-danger"></i> Usuń normę',
                                        ['delete-norm', 'id' => $template->id, 'parameterId' => $parameter->id, 'normId' => $norm->id],
                                        [
                                            'class' => 'btn btn-outline-danger',
                                            'data-confirm' => 'Czy na pewno chcesz usunąć tę normę? Ta operacja jest nieodwracalna.',
                                            'data-method' => 'post'
                                        ]
                                    ) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <!-- Sidebar z pomocą i podglądem -->
        <div class="col-md-4">
            <!-- Podgląd strefy normy -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-eye"></i> Podgląd strefy normy
                    </h6>
                </div>
                <div class="card-body">
                    <div id="zones-preview">
                        <div class="text-muted text-center py-3">
                            <i class="fas fa-info-circle"></i>
                            <p class="small mb-0">Wybierz typ normy aby zobaczyć podgląd stref</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pomoc contextowa -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-question-circle"></i> Pomoc
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <p><strong>Typy norm:</strong></p>
                        <ul class="ps-3">
                            <li><strong>Pozytywny/Negatywny</strong> - dla testów jakościowych</li>
                            <li><strong>Zakres</strong> - wartość między min a max</li>
                            <li><strong>Próg</strong> - wartość powyżej/poniżej granicy</li>
                            <li><strong>Wiele progów</strong> - różne zakresy z interpretacją</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Przykłady -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-lightbulb"></i> Przykłady
                    </h6>
                </div>
                <div class="card-body">
                    <div class="btn-group-vertical w-100" role="group">
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="setExampleRange()">
                            Hemoglobina (zakres)
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="setExampleThreshold()">
                            Glukoza (próg)
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="setExamplePositiveNegative()">
                            Test HIV (pos/neg)
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="setExampleBreathTest()">
                            Test oddechowy
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript functions
function toggleNormFields(type) {
    console.log('toggleNormFields called with type:', type);
    
    // Hide all specific fields
    $('#positive-negative-fields, #range-fields, #single-threshold-fields, #multiple-thresholds-fields').hide();
    
    // Show specific fields based on type
    switch(type) {
        case 'positive_negative':
            $('#positive-negative-fields').show();
            break;
        case 'range':
            $('#range-fields').show();
            break;
        case 'single_threshold':
            $('#single-threshold-fields').show();
            break;
        case 'multiple_thresholds':
            $('#multiple-thresholds-fields').show();
            break;
    }
    
    // Update zones preview
    updateZonesPreview();
}

function toggleWarningFields(enabled) {
    console.log('toggleWarningFields called with enabled:', enabled);
    
    if (enabled) {
        $('#warning-fields').show();
    } else {
        $('#warning-fields').hide();
    }
    
    updateZonesPreview();
}

function toggleThresholdConfigType(type) {
    console.log('toggleThresholdConfigType called with:', type);
    
    // Ukryj wszystkie sekcje konfiguracji
    document.getElementById('breath-test-config').style.display = 'none';
    document.getElementById('standard-thresholds-config').style.display = 'none';
    
    // Pokaż odpowiednią sekcję
    if (type === 'breath_test') {
        document.getElementById('breath-test-config').style.display = 'block';
        console.log('Showing breath-test-config');
    } else if (type === 'standard') {
        document.getElementById('standard-thresholds-config').style.display = 'block';
        console.log('Showing standard-thresholds-config');
    }
}

function generateBreathTestConfig() {
    console.log('generateBreathTestConfig called');
    
    const maxIncrease = document.getElementById('breath-test-max-increase').value || 12;
    const gasType = document.getElementById('breath-test-gas-type').value;
    
    console.log('Max increase:', maxIncrease);
    console.log('Gas type:', gasType);
    
    const config = {
        "measurement_type": "hydrogen_breath_test",
        "max_increase": parseFloat(maxIncrease),
        "gas_type": gasType,
        "time_points": [0, 30, 60, 120, 180],
        "interpretation": {
            "normal": `Brak nietolerancji laktozy - wzrost ${gasType === 'hydrogen' ? 'wodoru' : 'metanu'} ≤ ${maxIncrease} ppm`,
            "abnormal": `Nietolerancja laktozy potwierdzona - wzrost ${gasType === 'hydrogen' ? 'wodoru' : 'metanu'} > ${maxIncrease} ppm`
        }
    };
    
    const jsonString = JSON.stringify(config, null, 2);
    console.log('Generated JSON:', jsonString);
    
    const jsonField = document.getElementById('thresholds-config-json');
    if (jsonField) {
        jsonField.value = jsonString;
        console.log('JSON written to field');
        
        // Sprawdź czy rzeczywiście zapisało
        console.log('Field value after writing:', jsonField.value);
    } else {
        console.error('Could not find thresholds-config-json field!');
    }
    
    alert('Konfiguracja testu oddechowego została wygenerowana!');
}

function generateStandardConfig() {
    const thresholds = [];
    const thresholdItems = document.querySelectorAll('.threshold-item');
    
    thresholdItems.forEach(item => {
        const value = item.querySelector('.threshold-value').value;
        const isNormal = item.querySelector('.threshold-normal').value === 'true';
        const description = item.querySelector('.threshold-description').value;
        
        if (value) {
            thresholds.push({
                "value": parseFloat(value),
                "is_normal": isNormal,
                "description": description || `Próg ${value}`
            });
        }
    });
    
    if (thresholds.length === 0) {
        showNotification('Dodaj przynajmniej jeden próg!', 'warning');
        return;
    }
    
    // Sortuj progi według wartości
    thresholds.sort((a, b) => a.value - b.value);
    
    const config = {
        "measurement_type": "standard_thresholds",
        "thresholds": thresholds
    };
    
    document.getElementById('thresholds-config-json').value = JSON.stringify(config, null, 2);
    
    showNotification('Konfiguracja progów została wygenerowana!', 'success');
}

function addThreshold() {
    const container = document.getElementById('thresholds-container');
    const newThreshold = document.querySelector('.threshold-item').cloneNode(true);
    
    // Wyczyść wartości w nowym elemencie
    newThreshold.querySelectorAll('input').forEach(input => input.value = '');
    newThreshold.querySelector('select').selectedIndex = 0;
    
    container.appendChild(newThreshold);
}

function removeThreshold(button) {
    const thresholdItems = document.querySelectorAll('.threshold-item');
    if (thresholdItems.length > 1) {
        button.closest('.threshold-item').remove();
    } else {
        showNotification('Musi zostać przynajmniej jeden próg!', 'warning');
    }
}

function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

function updateZonesPreview() {
    const type = document.getElementById('norm-type-select').value;
    let preview = '';
    
    if (type === 'range') {
        preview = `
            <div id="zones-container">
                <div class="zone zone-critical" style="background: #dc3545; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    Poniżej normy - Krytyczne
                </div>
                <div class="zone zone-normal" style="background: #28a745; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    W normie - Normalny
                </div>
                <div class="zone zone-critical" style="background: #dc3545; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    Powyżej normy - Krytyczne
                </div>
            </div>
        `;
    } else if (type === 'positive_negative') {
        preview = `
            <div id="zones-container">
                <div class="zone zone-normal" style="background: #28a745; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    Negatywny - Normalny
                </div>
                <div class="zone zone-critical" style="background: #dc3545; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    Pozytywny - Nieprawidłowy
                </div>
            </div>
        `;
    } else {
        preview = `
            <div class="text-muted text-center py-3">
                <i class="fas fa-info-circle"></i>
                <p class="small mb-0">Wybierz typ normy aby zobaczyć podgląd stref</p>
            </div>
        `;
    }
    
    document.getElementById('zones-preview').innerHTML = preview;
}

// Przykłady konfiguracji
function setExampleRange() {
    document.getElementById('norm-type-select').value = 'range';
    document.querySelector('input[name="ParameterNorm[name]"]').value = 'Hemoglobina - norma laboratoryjna';
    document.querySelector('input[name="ParameterNorm[min_value]"]').value = '12';
    document.querySelector('input[name="ParameterNorm[max_value]"]').value = '16';
    document.querySelector('input[name="ParameterNorm[warning_enabled]"]').checked = true;
    document.querySelector('input[name="ParameterNorm[warning_margin_percent]"]').value = '10';
    document.querySelector('input[name="ParameterNorm[caution_margin_percent]"]').value = '5';
    toggleNormFields('range');
    toggleWarningFields(true);
}

function setExampleThreshold() {
    document.getElementById('norm-type-select').value = 'single_threshold';
    document.querySelector('input[name="ParameterNorm[name]"]').value = 'Glukoza na czczo';
    document.querySelector('input[name="ParameterNorm[threshold_value]"]').value = '100';
    document.querySelector('select[name="ParameterNorm[threshold_direction]"]').value = 'below';
    toggleNormFields('single_threshold');
}

function setExamplePositiveNegative() {
    document.getElementById('norm-type-select').value = 'positive_negative';
    document.querySelector('input[name="ParameterNorm[name]"]').value = 'Test HIV';
    toggleNormFields('positive_negative');
}

function setExampleBreathTest() {
    document.getElementById('norm-type-select').value = 'multiple_thresholds';
    document.querySelector('input[name="ParameterNorm[name]"]').value = 'Test oddechowy - wodór';
    toggleNormFields('multiple_thresholds');
    
    // Ustaw konfigurację testu oddechowego
    document.getElementById('threshold-config-type').value = 'breath_test';
    toggleThresholdConfigType('breath_test');
    
    // Automatycznie wygeneruj konfigurację
    setTimeout(() => {
        generateBreathTestConfig();
    }, 500);
}

// Inicjalizacja po załadowaniu strony
document.addEventListener('DOMContentLoaded', function() {
    const currentType = document.getElementById('norm-type-select').value;
    if (currentType) {
        toggleNormFields(currentType);
    }
    
    const warningEnabled = document.querySelector('input[name="ParameterNorm[warning_enabled]"]');
    if (warningEnabled && warningEnabled.checked) {
        toggleWarningFields(true);
    }
    
    // Dodaj listenery dla aktualizacji podglądu
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('input', updateZonesPreview);
    });
    
    document.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', updateZonesPreview);
    });
    
    // Pierwsza aktualizacja podglądu
    updateZonesPreview();
});
</script>

<style>
.norm-form-container .card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.zones-visualization {
    font-size: 0.75rem;
}

.zone {
    text-align: center;
    font-weight: 500;
}

.btn-group-vertical .btn-sm {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.form-actions {
    background-color: #f8f9fa;
    margin: 0 -1.25rem -1.25rem -1.25rem;
    padding: 1rem 1.25rem;
}

.font-monospace {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}

.threshold-item {
    background-color: #f8f9fa;
}

.threshold-item:hover {
    background-color: #e9ecef;
}
</style>