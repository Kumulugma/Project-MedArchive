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
        
        if ($result && !$result->isNewRecord) {
            foreach ($result->resultValues as $resultValue) {
                if ($resultValue->parameter_id == $parameter->id) {
                    $existingValue = $resultValue->value;
                    $selectedNormId = $resultValue->norm_id;
                    break;
                }
            }
        }
        ?>
        
        <div class="parameter-group">
            <h6><?= Html::encode($parameter->name) ?>
                <?php if ($parameter->unit): ?>
                    <small class="text-muted">(<?= Html::encode($parameter->unit) ?>)</small>
                <?php endif; ?>
            </h6>
            
            <div class="row">
                <div class="col-md-6">
                    <?= Html::textInput("parameter_{$parameter->id}", $existingValue, [
                        'class' => 'form-control value-input',
                        'placeholder' => 'Wprowadź wartość...',
                        'data-parameter-id' => $parameter->id,
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
                            ]
                        ) ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="invalid-feedback"></div>
        </div>
    <?php endforeach; ?>
</div>