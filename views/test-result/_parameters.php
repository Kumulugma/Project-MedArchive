<?php
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

?>

<div class="parameters-section mt-4">
    <h4>Parametry badania</h4>
    
    <?php foreach ($template->parameters as $parameter): ?>
        <?php
        // Find existing value for this parameter
        $existingValue = null;
        $selectedNormId = null;
        $selectedNorm = null;
        
        if ($result && !$result->isNewRecord) {
            foreach ($result->resultValues as $resultValue) {
                if ($resultValue->parameter_id == $parameter->id) {
                    $existingValue = $resultValue->value;
                    $selectedNormId = $resultValue->norm_id;
                    $selectedNorm = $resultValue->norm;
                    break;
                }
            }
        }
        
        // Jeśli nie ma wybranej normy, ale parametr ma normy, użyj podstawowej
        if (!$selectedNorm && !empty($parameter->norms)) {
            foreach ($parameter->norms as $norm) {
                if ($norm->is_primary) {
                    $selectedNorm = $norm;
                    $selectedNormId = $norm->id;
                    break;
                }
            }
            // Jeśli nie ma podstawowej, użyj pierwszej dostępnej
            if (!$selectedNorm) {
                $selectedNorm = $parameter->norms[0];
                $selectedNormId = $selectedNorm->id;
            }
        }
        ?>
        
        <div class="parameter-group parameter-row mb-3">
            <h6><?= Html::encode($parameter->name) ?>
                <?php if ($parameter->unit): ?>
                    <small class="text-muted">(<?= Html::encode($parameter->unit) ?>)</small>
                <?php endif; ?>
                <?php if ($parameter->type): ?>
                    <span class="badge bg-info ms-2"><?= Html::encode($parameter->getTypeName()) ?></span>
                <?php endif; ?>
            </h6>
            
            <div class="row">
                <div class="col-md-6">
                    <?= Html::textInput("parameter_{$parameter->id}", $existingValue, [
                        'class' => 'form-control value-input',
                        'placeholder' => 'Wprowadź wartość...',
                        'data-parameter-id' => $parameter->id,
                        'data-parameter-type' => $parameter->type,
                        'data-norm-type' => $selectedNorm ? $selectedNorm->type : null,
                        'data-norm-id' => $selectedNormId,
                    ]) ?>
                </div>
                
                <?php if (!empty($parameter->norms)): ?>
                    <div class="col-md-6">
                        <?= Html::dropDownList("norm_{$parameter->id}", $selectedNormId, 
                            ArrayHelper::map($parameter->norms, 'id', function($norm) {
                                return $norm->name . ($norm->is_primary ? ' (podstawowa)' : '');
                            }),
                            [
                                'class' => 'form-control norm-select',
                                'prompt' => 'Wybierz normę...',
                                'data-parameter-id' => $parameter->id,
                                'onchange' => 'updateNormType(this)',
                            ]
                        ) ?>
                    </div>
                <?php else: ?>
                    <div class="col-md-6">
                        <span class="form-control-plaintext text-muted">Brak norm dla tego parametru</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="invalid-feedback"></div>
            
            <?php if ($selectedNorm): ?>
                <small class="text-muted mt-1">
                    <i class="fas fa-info-circle"></i>
                    Norma: <?= Html::encode($selectedNorm->getRangeText()) ?>
                </small>
            <?php endif; ?>
            
            <?php
            // Sprawdź czy parametr ma normę typu multiple_thresholds z testem oddechowym
            if ($selectedNorm && $selectedNorm->type === 'multiple_thresholds') {
                $config = json_decode($selectedNorm->thresholds_config, true);
                
                if (isset($config['measurement_type']) && $config['measurement_type'] === 'hydrogen_breath_test') {
                    // Ukryj standardowe pole input
                    echo '<script>
                        $(document).ready(function() {
                            $("input[name=\'parameter_' . $parameter->id . '\']").closest(".row").hide();
                        });
                    </script>';
                    
                    // Pokaż specjalny interfejs dla testu oddechowego
                    echo $this->render('_breath_test_interface', [
                        'parameter' => $parameter,
                        'norm' => $selectedNorm,
                        'config' => $config,
                        'existingValue' => $existingValue
                    ]);
                }
            }
            ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
// Funkcja do aktualizacji typu normy w data-attribute
function updateNormType(selectElement) {
    var normId = selectElement.value;
    var parameterId = selectElement.dataset.parameterId;
    var valueInput = document.querySelector('input[data-parameter-id="' + parameterId + '"]');
    
    if (normId && valueInput) {
        // Pobierz informacje o normie przez AJAX
        $.get('/test-template/get-norm-info', {normId: normId})
            .done(function(data) {
                if (data.success) {
                    valueInput.setAttribute('data-norm-type', data.norm.type);
                    valueInput.setAttribute('data-norm-id', normId);
                    
                    // Ponowna walidacja wartości po zmianie normy
                    if (typeof validateInputValue === 'function') {
                        validateInputValue($(valueInput));
                    }
                    
                    // Sprawdź czy to test oddechowy - przeładuj stronę jeśli trzeba
                    if (data.norm.type === 'multiple_thresholds') {
                        // Możesz dodać logikę do dynamicznego ładowania interfejsu
                        // lub po prostu przeładować parametry
                        location.reload();
                    }
                }
            })
            .fail(function() {
                console.warn('Nie udało się pobrać informacji o normie');
            });
    }
}

// Inicjalizacja walidacji dla wszystkich pól po załadowaniu
$(document).ready(function() {
    $('.value-input').each(function() {
        var $input = $(this);
        
        // Dodaj walidację w czasie rzeczywistym
        $input.on('input blur', function() {
            if (typeof validateInputValue === 'function') {
                validateInputValue($(this));
            }
        });
    });
    
    // Obsługa zmiany norm
    $('.norm-select').on('change', function() {
        updateNormType(this);
    });
});
</script>