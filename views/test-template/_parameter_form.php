<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $template app\models\TestTemplate */
/* @var $parameter app\models\TestParameter */

$isUpdate = !$parameter->isNewRecord;
$actionUrl = $isUpdate 
    ? ['update-parameter', 'id' => $template->id, 'parameterId' => $parameter->id]
    : ['add-parameter', 'id' => $template->id];
?>

<div class="parameter-form-container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line"></i>
                        <?= $isUpdate ? 'Edycja parametru' : 'Nowy parametr' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Informacje o szablonie -->
                    <div class="alert alert-info">
                        <strong><i class="fas fa-file-medical"></i> Szablon:</strong> 
                        <?= Html::encode($template->name) ?>
                        <?php if (isset($template->description) && $template->description): ?>
                            <br><small><?= Html::encode($template->description) ?></small>
                        <?php endif; ?>
                    </div>

                    <?php $form = ActiveForm::begin(['action' => $actionUrl]); ?>

                    <!-- Podstawowe informacje o parametrze -->
                    <div class="row">
                        <div class="col-md-8">
                            <?= $form->field($parameter, 'name')->textInput([
                                'placeholder' => 'np. Hemoglobina, Cholesterol, Ciśnienie',
                                'maxlength' => true
                            ]) ?>
                        </div>
                        <div class="col-md-4">
                            <?= $form->field($parameter, 'unit')->textInput([
                                'placeholder' => 'np. g/dl, mg/dl, mmHg',
                                'maxlength' => true
                            ]) ?>
                        </div>
                    </div>

                    <!-- Opis parametru -->
                    <?= $form->field($parameter, 'description')->textarea([
                        'rows' => 3,
                        'placeholder' => 'Opcjonalny opis parametru, jego znaczenie, uwagi...'
                    ]) ?>

                    <!-- Typ parametru -->
                    <?= $form->field($parameter, 'type')->dropDownList(
                        $parameter::getTypeOptions(),
                        [
                            'prompt' => 'Wybierz typ parametru...',
                            'id' => 'parameter-type-select'
                        ]
                    ) ?>

                    <!-- Kolejność -->
                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($parameter, 'order_index')->textInput([
                                'type' => 'number',
                                'min' => 1,
                                'placeholder' => 'Automatyczna jeśli puste'
                            ])->hint('Kolejność wyświetlania w wynikach badań') ?>
                        </div>
                    </div>

                    <!-- Informacje o typach parametrów -->
                    <div class="mt-4 pt-3 border-top">
                        <h6><i class="fas fa-info-circle"></i> Informacje o typach parametrów</h6>
                        
                        <div class="accordion" id="parameterTypesAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNumeric">
                                        Parametry numeryczne
                                    </button>
                                </h2>
                                <div id="collapseNumeric" class="accordion-collapse collapse" data-bs-parent="#parameterTypesAccordion">
                                    <div class="accordion-body">
                                        <ul>
                                            <li><strong>Zakres min-max:</strong> Dla parametrów z określonym przedziałem normalnym (np. hemoglobina 12-16 g/dl)</li>
                                            <li><strong>Pojedynczy próg:</strong> Dla parametrów z jedną wartością graniczną (np. glukoza ≤ 100 mg/dl)</li>
                                            <li><strong>Numeryczny:</strong> Dla parametrów liczbowych bez z góry określonej normy</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOther">
                                        Parametry jakościowe
                                    </button>
                                </h2>
                                <div id="collapseOther" class="accordion-collapse collapse" data-bs-parent="#parameterTypesAccordion">
                                    <div class="accordion-body">
                                        <ul>
                                            <li><strong>Pozytywny/Negatywny:</strong> Dla testów z wynikiem tak/nie (np. test HIV, test ciążowy)</li>
                                            <li><strong>Tekstowy:</strong> Dla opisów lub komentarzy (np. morfologia krwi - opis)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Przyciski akcji -->
                    <div class="form-actions mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between">
                            <div>
                                <?= Html::submitButton(
                                    '<i class="fas fa-save"></i> ' . ($isUpdate ? 'Aktualizuj parametr' : 'Dodaj parametr'),
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
                                    <?php if ($parameter->canDelete()): ?>
                                        <?= Html::a(
                                            '<i class="fas fa-trash text-danger"></i> Usuń parametr',
                                            ['delete-parameter', 'id' => $template->id, 'parameterId' => $parameter->id],
                                            [
                                                'class' => 'btn btn-outline-danger',
                                                'data-confirm' => 'Czy na pewno chcesz usunąć ten parametr? Ta operacja usunie również wszystkie powiązane normy.',
                                                'data-method' => 'post'
                                            ]
                                        ) ?>
                                    <?php else: ?>
                                        <span class="text-muted small">
                                            <i class="fas fa-info-circle"></i>
                                            Nie można usunąć - parametr ma wprowadzone wyniki
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <!-- Podgląd i wskazówki (prawa kolumna) -->
        <div class="col-md-4">
            <!-- Podgląd parametru -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-eye"></i> Podgląd
                    </h6>
                </div>
                <div class="card-body">
                    <div id="parameter-preview">
                        <div class="text-muted text-center py-3">
                            <i class="fas fa-info-circle"></i>
                            <p class="small mb-0">Wypełnij formularz aby zobaczyć podgląd</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Następne kroki -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-list-ol"></i> Następne kroki
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <ol class="ps-3">
                            <li class="mb-2">
                                <strong>Zapisz parametr</strong><br>
                                <small class="text-muted">Utwórz podstawowy parametr</small>
                            </li>
                            <li class="mb-2">
                                <strong>Dodaj normy</strong><br>
                                <small class="text-muted">Zdefiniuj wartości referencyjne</small>
                            </li>
                            <li class="mb-2">
                                <strong>Skonfiguruj ostrzeżenia</strong><br>
                                <small class="text-muted">Ustaw marginesy ostrzeżeń</small>
                            </li>
                            <li class="mb-0">
                                <strong>Przetestuj</strong><br>
                                <small class="text-muted">Wprowadź przykładowe wyniki</small>
                            </li>
                        </ol>
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
                                onclick="setExampleHemoglobin()">
                            Hemoglobina
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="setExampleCholesterol()">
                            Cholesterol
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="setExampleHIV()">
                            Test HIV
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript dla formularza -->
<script>
// Aktualizacja podglądu parametru
function updateParameterPreview() {
    const name = document.querySelector('input[name="TestParameter[name]"]').value || 'Nazwa parametru';
    const unit = document.querySelector('input[name="TestParameter[unit]"]').value;
    const type = document.querySelector('select[name="TestParameter[type]"]').value;
    const description = document.querySelector('textarea[name="TestParameter[description]"]').value;
    
    const typeOptions = {
        'positive_negative': 'Pozytywny/Negatywny',
        'range': 'Zakres min-max',
        'single_threshold': 'Pojedynczy próg',
        'multiple_thresholds': 'Wiele progów',
        'numeric': 'Numeryczny',
        'text': 'Tekstowy'
    };
    
    let preview = `
        <div class="parameter-preview-item p-2 border rounded">
            <h6 class="mb-1">${name}</h6>
            ${unit ? `<small class="text-muted">Jednostka: ${unit}</small><br>` : ''}
            ${type ? `<span class="badge bg-primary">${typeOptions[type] || type}</span><br>` : ''}
            ${description ? `<small class="mt-2 d-block">${description}</small>` : ''}
        </div>
    `;
    
    document.getElementById('parameter-preview').innerHTML = preview;
}

// Przykłady konfiguracji
function setExampleHemoglobin() {
    document.querySelector('input[name="TestParameter[name]"]').value = 'Hemoglobina';
    document.querySelector('input[name="TestParameter[unit]"]').value = 'g/dl';
    document.querySelector('select[name="TestParameter[type]"]').value = 'range';
    document.querySelector('textarea[name="TestParameter[description]"]').value = 'Białko przenoszące tlen w czerwonych krwinkach';
    updateParameterPreview();
}

function setExampleCholesterol() {
    document.querySelector('input[name="TestParameter[name]"]').value = 'Cholesterol całkowity';
    document.querySelector('input[name="TestParameter[unit]"]').value = 'mg/dl';
    document.querySelector('select[name="TestParameter[type]"]').value = 'single_threshold';
    document.querySelector('textarea[name="TestParameter[description]"]').value = 'Całkowity poziom cholesterolu we krwi';
    updateParameterPreview();
}

function setExampleHIV() {
    document.querySelector('input[name="TestParameter[name]"]').value = 'Test HIV';
    document.querySelector('input[name="TestParameter[unit]"]').value = '';
    document.querySelector('select[name="TestParameter[type]"]').value = 'positive_negative';
    document.querySelector('textarea[name="TestParameter[description]"]').value = 'Test wykrywający przeciwciała przeciwko wirusowi HIV';
    updateParameterPreview();
}

// Inicjalizacja po załadowaniu strony
document.addEventListener('DOMContentLoaded', function() {
    // Dodaj listenery dla aktualizacji podglądu
    document.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('input', updateParameterPreview);
        input.addEventListener('change', updateParameterPreview);
    });
    
    // Pierwsza aktualizacja podglądu
    updateParameterPreview();
});
</script>

<style>
.parameter-form-container .card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.parameter-preview-item {
    background-color: #f8f9fa;
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

.accordion-button {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}

.accordion-body {
    font-size: 0.875rem;
}
</style>