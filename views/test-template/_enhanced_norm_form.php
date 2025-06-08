<?php
// views/test-template/_enhanced_norm_form.php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>

<div class="enhanced-norm-form">
    <?php $form = ActiveForm::begin([
        'action' => ['test-template/add-norm', 'id' => $template->id, 'parameterId' => $parameter->id],
        'options' => ['class' => 'norm-form-container']
    ]); ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-ruler"></i> Definicja normy dla: 
                        <strong><?= Html::encode($parameter->name) ?></strong>
                        <?php if ($parameter->unit): ?>
                            <span class="text-muted">(<?= Html::encode($parameter->unit) ?>)</span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    
                    <!-- Podstawowe ustawienia normy -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2">Podstawowe ustawienia</h6>
                        
                        <?= $form->field($norm, 'name')->textInput([
                            'placeholder' => 'np. Norma laboratoryjna, Wartości referencyjne'
                        ]) ?>

                        <?= $form->field($norm, 'type')->dropDownList([
                            '' => 'Wybierz typ normy...',
                            'positive_negative' => 'Pozytywny/Negatywny',
                            'range' => 'Zakres min-max',
                            'single_threshold' => 'Pojedynczy próg',
                            'multiple_thresholds' => 'Wiele progów'
                        ], [
                            'id' => 'norm-type-select',
                            'onchange' => 'toggleNormFields(this.value)'
                        ]) ?>
                    </div>

                    <!-- Pola dla różnych typów norm -->
                    <div id="range-fields" class="norm-type-fields" style="display: none;">
                        <h6 class="border-bottom pb-2">Zakres wartości</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($norm, 'min_value')->textInput([
                                    'type' => 'number',
                                    'step' => '0.001',
                                    'placeholder' => 'Wartość minimalna'
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($norm, 'max_value')->textInput([
                                    'type' => 'number',
                                    'step' => '0.001',
                                    'placeholder' => 'Wartość maksymalna'
                                ]) ?>
                            </div>
                        </div>
                    </div>

                    <div id="threshold-fields" class="norm-type-fields" style="display: none;">
                        <h6 class="border-bottom pb-2">Pojedynczy próg</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($norm, 'threshold_value')->textInput([
                                    'type' => 'number',
                                    'step' => '0.001',
                                    'placeholder' => 'Wartość progowa'
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($norm, 'threshold_direction')->dropDownList([
                                    '' => 'Wybierz kierunek...',
                                    'above' => 'Powyżej progu (nieprawidłowy)',
                                    'below' => 'Poniżej progu (nieprawidłowy)'
                                ]) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Opcje podstawowe -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2">Opcje podstawowe</h6>
                        
                        <?= $form->field($norm, 'is_primary')->checkbox([
                            'label' => 'Norma podstawowa dla tego parametru'
                        ]) ?>

                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($norm, 'conversion_factor')->textInput([
                                    'type' => 'number',
                                    'step' => '0.000001',
                                    'value' => $norm->conversion_factor ?: 1,
                                    'placeholder' => '1.0'
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($norm, 'conversion_offset')->textInput([
                                    'type' => 'number',
                                    'step' => '0.001',
                                    'placeholder' => '0.0'
                                ]) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i> System ostrzeżeń
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle"></i> 
                            System ostrzeżeń pozwala na wykrywanie wartości granicznych, 
                            które choć mieszczą się w normie, mogą wymagać uwagi.
                        </small>
                    </div>

                    <?= $form->field($norm, 'warning_enabled')->checkbox([
                        'label' => 'Włącz system ostrzeżeń',
                        'onchange' => 'toggleWarningFields(this.checked)'
                    ]) ?>

                    <div id="warning-config" style="display: none;">
                        <h6 class="mt-3 mb-2">Marginesy ostrzeżeń</h6>
                        <small class="text-muted">
                            Określ jak blisko granic normy wartość musi być, aby wywołać ostrzeżenie
                        </small>

                        <div class="mt-3">
                            <label class="form-label">Margines ostrzeżenia</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <?= $form->field($norm, 'warning_margin_percent', [
                                        'template' => '{input}{error}',
                                        'inputOptions' => [
                                            'placeholder' => '10'
                                        ]
                                    ])->textInput([
                                        'type' => 'number',
                                        'step' => '0.1',
                                        'min' => '0',
                                        'max' => '50'
                                    ]) ?>
                                    <small class="text-muted">%</small>
                                </div>
                                <div class="col-md-6">
                                    <?= $form->field($norm, 'warning_margin_absolute', [
                                        'template' => '{input}{error}'
                                    ])->textInput([
                                        'type' => 'number',
                                        'step' => '0.001',
                                        'placeholder' => 'lub wartość bezwzględna'
                                    ]) ?>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Margines uwagi</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <?= $form->field($norm, 'caution_margin_percent', [
                                        'template' => '{input}{error}'
                                    ])->textInput([
                                        'type' => 'number',
                                        'step' => '0.1',
                                        'min' => '0',
                                        'max' => '30',
                                        'placeholder' => '5'
                                    ]) ?>
                                    <small class="text-muted">%</small>
                                </div>
                                <div class="col-md-6">
                                    <?= $form->field($norm, 'caution_margin_absolute', [
                                        'template' => '{input}{error}'
                                    ])->textInput([
                                        'type' => 'number',
                                        'step' => '0.001',
                                        'placeholder' => 'lub wartość bezwzględna'
                                    ]) ?>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h6 class="mb-2">Zakres optymalny</h6>
                            <small class="text-muted">
                                Opcjonalnie: zdefiniuj zakres optymalnych wartości (węższy niż norma)
                            </small>
                            
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <?= $form->field($norm, 'optimal_min_value', [
                                        'template' => '{input}{error}'
                                    ])->textInput([
                                        'type' => 'number',
                                        'step' => '0.001',
                                        'placeholder' => 'Min opt.'
                                    ]) ?>
                                </div>
                                <div class="col-md-6">
                                    <?= $form->field($norm, 'optimal_max_value', [
                                        'template' => '{input}{error}'
                                    ])->textInput([
                                        'type' => 'number',
                                        'step' => '0.001',
                                        'placeholder' => 'Max opt.'
                                    ]) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Predefiniowane ustawienia -->
                        <div class="mt-4">
                            <h6 class="mb-2">Szybkie ustawienia</h6>
                            <div class="btn-group-vertical d-grid gap-1">
                                <button type="button" class="btn btn-outline-secondary btn-sm" 
                                        onclick="applyDefaultMargins('conservative')">
                                    Konserwatywne (15%/8%)
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" 
                                        onclick="applyDefaultMargins('standard')">
                                    Standardowe (10%/5%)
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" 
                                        onclick="applyDefaultMargins('liberal')">
                                    Liberalne (5%/3%)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Podgląd -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-eye"></i> Podgląd stref
                    </h6>
                </div>
                <div class="card-body" id="zones-preview">
                    <small class="text-muted">Wprowadź wartości norm aby zobaczyć podgląd</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" onclick="history.back()">
                    <i class="fas fa-arrow-left"></i> Anuluj
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Zapisz normę
                </button>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<script>
function toggleNormFields(type) {
    // Ukryj wszystkie pola
    document.querySelectorAll('.norm-type-fields').forEach(el => el.style.display = 'none');
    
    // Pokaż odpowiednie pola
    if (type === 'range') {
        document.getElementById('range-fields').style.display = 'block';
    } else if (type === 'single_threshold') {
        document.getElementById('threshold-fields').style.display = 'block';
    }
    
    updateZonesPreview();
}

function toggleWarningFields(enabled) {
    document.getElementById('warning-config').style.display = enabled ? 'block' : 'none';
    updateZonesPreview();
}

function applyDefaultMargins(preset) {
    const presets = {
        'conservative': { warning: 15, caution: 8 },
        'standard': { warning: 10, caution: 5 },
        'liberal': { warning: 5, caution: 3 }
    };
    
    const margins = presets[preset];
    document.querySelector('input[name="ParameterNorm[warning_margin_percent]"]').value = margins.warning;
    document.querySelector('input[name="ParameterNorm[caution_margin_percent]"]').value = margins.caution;
    
    updateZonesPreview();
}

function updateZonesPreview() {
    const type = document.getElementById('norm-type-select').value;
    const warningEnabled = document.querySelector('input[name="ParameterNorm[warning_enabled]"]').checked;
    
    if (!type || !warningEnabled) {
        document.getElementById('zones-preview').innerHTML = 
            '<small class="text-muted">Wprowadź wartości norm i włącz ostrzeżenia aby zobaczyć podgląd</small>';
        return;
    }
    
    let preview = '';
    
    if (type === 'range') {
        const min = parseFloat(document.querySelector('input[name="ParameterNorm[min_value]"]').value) || 0;
        const max = parseFloat(document.querySelector('input[name="ParameterNorm[max_value]"]').value) || 100;
        const warningPercent = parseFloat(document.querySelector('input[name="ParameterNorm[warning_margin_percent]"]').value) || 10;
        const cautionPercent = parseFloat(document.querySelector('input[name="ParameterNorm[caution_margin_percent]"]').value) || 5;
        
        const range = max - min;
        const warningMargin = range * (warningPercent / 100);
        const cautionMargin = range * (cautionPercent / 100);
        
        preview = `
            <div class="zones-visualization">
                <div class="zone zone-critical" style="background: #dc3545; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    &lt; ${min.toFixed(2)} - Poza normą
                </div>
                <div class="zone zone-warning" style="background: #fd7e14; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    ${min.toFixed(2)} - ${(min + warningMargin).toFixed(2)} - Ostrzeżenie
                </div>
                <div class="zone zone-caution" style="background: #17a2b8; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    ${(min + warningMargin).toFixed(2)} - ${(min + cautionMargin).toFixed(2)} - Uwaga
                </div>
                <div class="zone zone-optimal" style="background: #28a745; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    ${(min + cautionMargin).toFixed(2)} - ${(max - cautionMargin).toFixed(2)} - Optymalne
                </div>
                <div class="zone zone-caution" style="background: #17a2b8; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    ${(max - cautionMargin).toFixed(2)} - ${(max - warningMargin).toFixed(2)} - Uwaga
                </div>
                <div class="zone zone-warning" style="background: #fd7e14; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    ${(max - warningMargin).toFixed(2)} - ${max.toFixed(2)} - Ostrzeżenie
                </div>
                <div class="zone zone-critical" style="background: #dc3545; color: white; padding: 2px; margin: 1px; border-radius: 2px;">
                    &gt; ${max.toFixed(2)} - Poza normą
                </div>
            </div>
        `;
    }
    
    document.getElementById('zones-preview').innerHTML = preview;
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
</style>