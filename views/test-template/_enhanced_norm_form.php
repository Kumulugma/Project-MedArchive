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
                        'options' => ['novalidate' => true],
                        'enableClientValidation' => false,
                        'enableAjaxValidation' => false
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
                                            'data-confirm' => 'Czy na pewno chcesz usunąć tę normę?',
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

        <!-- Sidebar z pomocą -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-lightbulb"></i> Przykłady
                    </h6>
                </div>
                <div class="card-body">
                    <div class="btn-group-vertical w-100" role="group">
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="setExampleBreathTest()">
                            Test oddechowy
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="setExampleRange()">
                            Hemoglobina (zakres)
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="setExampleThreshold()">
                            Glukoza (próg)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript functions - BEZ JQUERY
function toggleNormFields(type) {
    console.log('toggleNormFields called with type:', type);
    
    // Hide all specific fields
    hideElement('positive-negative-fields');
    hideElement('range-fields');
    hideElement('single-threshold-fields');
    hideElement('multiple-thresholds-fields');
    
    // Show specific fields based on type
    switch(type) {
        case 'positive_negative':
            showElement('positive-negative-fields');
            break;
        case 'range':
            showElement('range-fields');
            break;
        case 'single_threshold':
            showElement('single-threshold-fields');
            break;
        case 'multiple_thresholds':
            showElement('multiple-thresholds-fields');
            console.log('Showing multiple-thresholds-fields');
            break;
    }
}

function toggleWarningFields(enabled) {
    if (enabled) {
        showElement('warning-fields');
    } else {
        hideElement('warning-fields');
    }
}

function toggleThresholdConfigType(type) {
    console.log('toggleThresholdConfigType called with:', type);
    
    // Ukryj wszystkie sekcje konfiguracji
    hideElement('breath-test-config');
    hideElement('standard-thresholds-config');
    
    // Pokaż odpowiednią sekcję
    if (type === 'breath_test') {
        showElement('breath-test-config');
        console.log('Showing breath-test-config');
    } else if (type === 'standard') {
        showElement('standard-thresholds-config');
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
        console.log('Field value after writing:', jsonField.value);
        
        // Sprawdź czy pole jest widoczne i ma wartość
        console.log('Field is visible:', jsonField.offsetParent !== null);
        console.log('Field name:', jsonField.name);
        
        alert('Konfiguracja testu oddechowego została wygenerowana!');
    } else {
        console.error('Could not find thresholds-config-json field!');
        alert('Błąd: Nie znaleziono pola JSON!');
    }
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
        alert('Dodaj przynajmniej jeden próg!');
        return;
    }
    
    thresholds.sort((a, b) => a.value - b.value);
    
    const config = {
        "measurement_type": "standard_thresholds",
        "thresholds": thresholds
    };
    
    document.getElementById('thresholds-config-json').value = JSON.stringify(config, null, 2);
    alert('Konfiguracja progów została wygenerowana!');
}

function addThreshold() {
    const container = document.getElementById('thresholds-container');
    const newThreshold = document.querySelector('.threshold-item').cloneNode(true);
    
    newThreshold.querySelectorAll('input').forEach(input => input.value = '');
    newThreshold.querySelector('select').selectedIndex = 0;
    
    container.appendChild(newThreshold);
}

function removeThreshold(button) {
    const thresholdItems = document.querySelectorAll('.threshold-item');
    if (thresholdItems.length > 1) {
        button.closest('.threshold-item').remove();
    } else {
        alert('Musi zostać przynajmniej jeden próg!');
    }
}

function setExampleBreathTest() {
    console.log('setExampleBreathTest called');
    
    document.getElementById('norm-type-select').value = 'multiple_thresholds';
    document.querySelector('input[name="ParameterNorm[name]"]').value = 'Test oddechowy - wodór';
    toggleNormFields('multiple_thresholds');
    
    // Ustaw konfigurację testu oddechowego
    setTimeout(() => {
        console.log('Setting breath test config...');
        document.getElementById('threshold-config-type').value = 'breath_test';
        toggleThresholdConfigType('breath_test');
        
        // Automatycznie wygeneruj konfigurację
        setTimeout(() => {
            console.log('Auto-generating config...');
            generateBreathTestConfig();
        }, 200);
    }, 200);
}

function setExampleRange() {
    document.getElementById('norm-type-select').value = 'range';
    document.querySelector('input[name="ParameterNorm[name]"]').value = 'Hemoglobina - norma laboratoryjna';
    toggleNormFields('range');
}

function setExampleThreshold() {
    document.getElementById('norm-type-select').value = 'single_threshold';
    document.querySelector('input[name="ParameterNorm[name]"]').value = 'Glukoza na czczo';
    toggleNormFields('single_threshold');
}

// Helper functions
function showElement(id) {
    const element = document.getElementById(id);
    if (element) {
        element.style.display = 'block';
    }
}

function hideElement(id) {
    const element = document.getElementById(id);
    if (element) {
        element.style.display = 'none';
    }
}

// Inicjalizacja po załadowaniu strony
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing...');
    
    const currentType = document.getElementById('norm-type-select').value;
    console.log('Current type on load:', currentType);
    
    if (currentType) {
        toggleNormFields(currentType);
    }
    
    const warningEnabled = document.querySelector('input[name="ParameterNorm[warning_enabled]"]');
    if (warningEnabled && warningEnabled.checked) {
        toggleWarningFields(true);
    }
    
    console.log('Initialization complete');
});

// DEBUG - sprawdź czy pole istnieje
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const jsonField = document.getElementById('thresholds-config-json');
        console.log('DEBUG - JSON field exists:', !!jsonField);
        if (jsonField) {
            console.log('DEBUG - JSON field name:', jsonField.name);
            console.log('DEBUG - JSON field value:', jsonField.value);
        }
    }, 1000);
});
</script>

<style>
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