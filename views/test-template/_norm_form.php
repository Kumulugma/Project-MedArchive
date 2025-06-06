<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

// Testowy formularz bez JavaScript - tylko podstawowe pola
?>

<div class="norm-form">
    
    <div class="alert alert-info">
        <strong>TEST FORMULARZ - bez JS</strong><br>
        Template ID: <?= $template->id ?>, 
        Parameter ID: <?= $parameter->id ?><br>
        Action URL: <?= Html::encode(Yii::$app->urlManager->createUrl(['test-template/add-norm', 'id' => $template->id, 'parameterId' => $parameter->id])) ?>
    </div>

    <!-- PROSTY FORMULARZ HTML -->
    <form method="post" action="<?= Yii::$app->urlManager->createUrl(['test-template/add-norm', 'id' => $template->id, 'parameterId' => $parameter->id]) ?>">
        
        <!-- CSRF Token -->
        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">

        <div class="row">
            <div class="col-md-8">
                
                <div class="alert alert-secondary">
                    <strong>Parametr:</strong> <?= Html::encode($parameter->name) ?>
                    <?php if ($parameter->unit): ?>
                        <span class="text-muted">(<?= Html::encode($parameter->unit) ?>)</span>
                    <?php endif; ?>
                </div>

                <!-- Nazwa normy -->
                <div class="mb-3">
                    <label for="norm-name" class="form-label">Nazwa normy *</label>
                    <input type="text" class="form-control" id="norm-name" name="ParameterNorm[name]" 
                           value="<?= Html::encode($norm->name) ?>" required 
                           placeholder="np. Norma laboratoryjna">
                </div>

                <!-- Typ normy -->
                <div class="mb-3">
                    <label for="norm-type" class="form-label">Typ normy *</label>
                    <select class="form-control" id="norm-type" name="ParameterNorm[type]" required>
                        <option value="">Wybierz typ normy...</option>
                        <option value="positive_negative" <?= $norm->type === 'positive_negative' ? 'selected' : '' ?>>Pozytywny/Negatywny</option>
                        <option value="range" <?= $norm->type === 'range' ? 'selected' : '' ?>>Zakres min-max</option>
                        <option value="single_threshold" <?= $norm->type === 'single_threshold' ? 'selected' : '' ?>>Pojedynczy próg</option>
                        <option value="multiple_thresholds" <?= $norm->type === 'multiple_thresholds' ? 'selected' : '' ?>>Wiele progów</option>
                    </select>
                </div>

                <!-- Pola dla range -->
                <div id="range-fields" style="display: <?= $norm->type === 'range' ? 'block' : 'none' ?>;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="min-value" class="form-label">Wartość minimalna</label>
                                <input type="number" step="0.01" class="form-control" id="min-value" 
                                       name="ParameterNorm[min_value]" value="<?= $norm->min_value ?>" 
                                       placeholder="Wartość minimalna">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max-value" class="form-label">Wartość maksymalna</label>
                                <input type="number" step="0.01" class="form-control" id="max-value" 
                                       name="ParameterNorm[max_value]" value="<?= $norm->max_value ?>" 
                                       placeholder="Wartość maksymalna">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pola dla single threshold -->
                <div id="threshold-fields" style="display: <?= $norm->type === 'single_threshold' ? 'block' : 'none' ?>;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="threshold-value" class="form-label">Wartość progowa</label>
                                <input type="number" step="0.01" class="form-control" id="threshold-value" 
                                       name="ParameterNorm[threshold_value]" value="<?= $norm->threshold_value ?>" 
                                       placeholder="Wartość progowa">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="threshold-direction" class="form-label">Kierunek progu</label>
                                <select class="form-control" id="threshold-direction" name="ParameterNorm[threshold_direction]">
                                    <option value="">Wybierz kierunek...</option>
                                    <option value="above" <?= $norm->threshold_direction === 'above' ? 'selected' : '' ?>>Powyżej progu (nieprawidłowy)</option>
                                    <option value="below" <?= $norm->threshold_direction === 'below' ? 'selected' : '' ?>>Poniżej progu (nieprawidłowy)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Norma podstawowa -->
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="hidden" name="ParameterNorm[is_primary]" value="0">
                        <input class="form-check-input" type="checkbox" id="is-primary" 
                               name="ParameterNorm[is_primary]" value="1" <?= $norm->is_primary ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is-primary">
                            Norma podstawowa
                        </label>
                    </div>
                </div>

                <!-- Konwersja -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="conversion-factor" class="form-label">Współczynnik konwersji</label>
                            <input type="number" step="0.000001" class="form-control" id="conversion-factor" 
                                   name="ParameterNorm[conversion_factor]" value="<?= $norm->conversion_factor ?: 1 ?>" 
                                   placeholder="1.0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="conversion-offset" class="form-label">Offset konwersji</label>
                            <input type="number" step="0.01" class="form-control" id="conversion-offset" 
                                   name="ParameterNorm[conversion_offset]" value="<?= $norm->conversion_offset ?: 0 ?>" 
                                   placeholder="0.0">
                        </div>
                    </div>
                </div>

                <!-- Przyciski -->
                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-success btn-lg">ZAPISZ NORMĘ (TEST)</button>
                    <a href="<?= Yii::$app->urlManager->createUrl(['test-template/view', 'id' => $template->id]) ?>" class="btn btn-secondary">Anuluj</a>
                </div>

            </div>
            
            <div class="col-md-4">
                <!-- Debug errors -->
                <?php if ($norm->hasErrors()): ?>
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h6 class="card-title mb-0">Błędy walidacji</h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($norm->getErrors() as $attribute => $errors): ?>
                                <p><strong><?= $attribute ?>:</strong> <?= implode(', ', $errors) ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </form>

</div>

<script>
// Prosty JavaScript do pokazywania/ukrywania pól
document.getElementById('norm-type').addEventListener('change', function() {
    const type = this.value;
    
    // Ukryj wszystkie pola
    document.getElementById('range-fields').style.display = 'none';
    document.getElementById('threshold-fields').style.display = 'none';
    
    // Pokaż odpowiednie pola
    if (type === 'range') {
        document.getElementById('range-fields').style.display = 'block';
    } else if (type === 'single_threshold') {
        document.getElementById('threshold-fields').style.display = 'block';
    }
});

// Debug submit
document.querySelector('form').addEventListener('submit', function(e) {
    console.log('NATIVE FORM SUBMIT EVENT');
    console.log('Form action:', this.action);
    console.log('Form method:', this.method);
    
    // Sprawdź czy wszystkie wymagane pola są wypełnione
    const name = document.getElementById('norm-name').value;
    const type = document.getElementById('norm-type').value;
    
    console.log('Name:', name);
    console.log('Type:', type);
    
    if (!name || !type) {
        alert('Wypełnij wymagane pola!');
        e.preventDefault();
        return false;
    }
    
    console.log('Form will submit normally');
    return true;
});
</script>