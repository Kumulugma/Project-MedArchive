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
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Wiele progów:</strong> Funkcjonalność w rozwoju. 
                            Zalecamy użycie typu "zakres" lub "pojedynczy próg".
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

                            <div class="row">
                                <div class="col-md-6">
                                    <?= $form->field($norm, 'warning_margin_absolute')->textInput([
                                        'type' => 'number',
                                        'min' => 0,
                                        'step' => 'any',
                                        'placeholder' => '0.5'
                                    ])->hint('Alternatywnie: margines jako wartość bezwzględna') ?>
                                </div>
                                <div class="col-md-6">
                                    <?= $form->field($norm, 'caution_margin_absolute')->textInput([
                                        'type' => 'number',
                                        'min' => 0,
                                        'step' => 'any',
                                        'placeholder' => '0.2'
                                    ])->hint('Alternatywnie: margines jako wartość bezwzględna') ?>
                                </div>
                            </div>

                            <!-- Wartości optymalne -->
                            <div class="mt-3">
                                <h6><i class="fas fa-target"></i> Wartości optymalne (opcjonalne)</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= $form->field($norm, 'optimal_min_value')->textInput([
                                            'type' => 'number',
                                            'step' => 'any',
                                            'placeholder' => 'np. 4.0'
                                        ])->hint('Dolna granica optymalnego zakresu') ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= $form->field($norm, 'optimal_max_value')->textInput([
                                            'type' => 'number',
                                            'step' => 'any',
                                            'placeholder' => 'np. 4.8'
                                        ])->hint('Górna granica optymalnego zakresu') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Współczynniki konwersji (zaawansowane) -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6><i class="fas fa-calculator"></i> Konwersja jednostek (zaawansowane)</h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                    onclick="$('#conversion-fields').toggle()">
                                <i class="fas fa-eye"></i> Pokaż/Ukryj
                            </button>
                        </div>

                        <div id="conversion-fields" style="display: none;">
                            <div class="alert alert-secondary">
                                <small>
                                    <i class="fas fa-info-circle"></i>
                                    Używaj tylko jeśli musisz konwertować jednostki przed porównaniem z normą.
                                    Formuła: wartość_znormalizowana = (wartość × współczynnik) + offset
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

        <!-- Podgląd stref norm (prawa kolumna) -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-bar"></i> Podgląd stref
                    </h6>
                </div>
                <div class="card-body">
                    <div id="zones-preview" class="zones-visualization">
                        <div class="text-muted text-center py-3">
                            <i class="fas fa-info-circle"></i>
                            <p class="small mb-0">Wybierz typ normy aby zobaczyć podgląd stref</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Przykłady -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-lightbulb"></i> Przykłady
                    </h6>
                </div>
                <div class="card-body">
                    <div class="btn-group-vertical w-100" role="group">
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="setExampleRange()">
                            Hemoglobina (12-16 g/dl)
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="setExampleThreshold()">
                            Glukoza (≤ 100 mg/dl)
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="setExamplePositiveNegative()">
                            Test HIV (negatywny)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript dla formularza -->
<script>
// Funkcje przełączania pól
function toggleNormFields(type) {
    // Ukryj wszystkie specyficzne pola
    document.querySelectorAll('#positive-negative-fields, #range-fields, #single-threshold-fields, #multiple-thresholds-fields').forEach(el => {
        el.style.display = 'none';
    });
    
    // Pokaż odpowiednie pola
    if (type) {
        const targetField = document.getElementById(type.replace('_', '-') + '-fields');
        if (targetField) {
            targetField.style.display = 'block';
        }
    }
    
    updateZonesPreview();
}

function toggleWarningFields(enabled) {
    const warningFields = document.getElementById('warning-fields');
    if (warningFields) {
        warningFields.style.display = enabled ? 'block' : 'none';
    }
    updateZonesPreview();
}

// Aktualizacja podglądu stref
function updateZonesPreview() {
    const type = document.getElementById('norm-type-select').value;
    const warningEnabled = document.querySelector('input[name="ParameterNorm[warning_enabled]"]').checked;
    
    let preview = '';
    
    if (type === 'range') {
        const min = parseFloat(document.querySelector('input[name="ParameterNorm[min_value]"]').value) || 0;
        const max = parseFloat(document.querySelector('input[name="ParameterNorm[max_value]"]').value) || 10;
        const warningMargin = parseFloat(document.querySelector('input[name="ParameterNorm[warning_margin_percent]"]').value) || 10;
        const cautionMargin = parseFloat(document.querySelector('input[name="ParameterNorm[caution_margin_percent]"]').value) || 5;
        
        const range = max - min;
        const warningMarginValue = range * (warningMargin / 100);
        const cautionMarginValue = range * (cautionMargin / 100);
        
        preview = `
            <div class="zones-container">
                <div class="zone zone-critical" style="background: #dc3545; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    &lt; ${min.toFixed(2)} - Poza normą
                </div>
                ${warningEnabled ? `
                <div class="zone zone-warning" style="background: #fd7e14; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    ${min.toFixed(2)} - ${(min + warningMarginValue).toFixed(2)} - Ostrzeżenie
                </div>
                <div class="zone zone-caution" style="background: #ffc107; color: black; padding: 2px; margin: 1px; border-radius: 2px;">
                    ${(min + warningMarginValue).toFixed(2)} - ${(min + cautionMarginValue).toFixed(2)} - Uwaga
                </div>
                <div class="zone zone-optimal" style="background: #28a745; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    ${(min + cautionMarginValue).toFixed(2)} - ${(max - cautionMarginValue).toFixed(2)} - Optymalna
                </div>
                <div class="zone zone-caution" style="background: #ffc107; color: black; padding: 2px; margin: 1px; border-radius: 2px;">
                    ${(max - cautionMarginValue).toFixed(2)} - ${(max - warningMarginValue).toFixed(2)} - Uwaga
                </div>
                <div class="zone zone-warning" style="background: #fd7e14; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    ${(max - warningMarginValue).toFixed(2)} - ${max.toFixed(2)} - Ostrzeżenie
                </div>
                ` : `
                <div class="zone zone-normal" style="background: #28a745; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    ${min.toFixed(2)} - ${max.toFixed(2)} - Normalna
                </div>
                `}
                <div class="zone zone-critical" style="background: #dc3545; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    &gt; ${max.toFixed(2)} - Poza normą
                </div>
            </div>
        `;
    } else if (type === 'single_threshold') {
        const threshold = parseFloat(document.querySelector('input[name="ParameterNorm[threshold_value]"]').value) || 5;
        const direction = document.querySelector('select[name="ParameterNorm[threshold_direction]"]').value;
        
        if (direction === 'below') {
            preview = `
                <div class="zones-container">
                    <div class="zone zone-normal" style="background: #28a745; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                        ≤ ${threshold.toFixed(2)} - Normalna
                    </div>
                    <div class="zone zone-critical" style="background: #dc3545; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                        &gt; ${threshold.toFixed(2)} - Poza normą
                    </div>
                </div>
            `;
        } else if (direction === 'above') {
            preview = `
                <div class="zones-container">
                    <div class="zone zone-critical" style="background: #dc3545; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                        &lt; ${threshold.toFixed(2)} - Poza normą
                    </div>
                    <div class="zone zone-normal" style="background: #28a745; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                        ≥ ${threshold.toFixed(2)} - Normalna
                    </div>
                </div>
            `;
        }
    } else if (type === 'positive_negative') {
        preview = `
            <div class="zones-container">
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

// Inicjalizacja po załadowaniu strony
document.addEventListener('DOMContentLoaded', function() {
    const currentType = document.getElementById('norm-type-select').value;
    if (currentType) {
        toggleNormFields(currentType);
    }
    
    const warningEnabled = document.querySelector('input[name="ParameterNorm[warning_enabled]"]').checked;
    if (warningEnabled) {
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
</style>