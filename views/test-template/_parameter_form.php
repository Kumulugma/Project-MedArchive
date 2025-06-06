<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>

<div class="parameter-form">
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'needs-validation'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{error}",
            'labelOptions' => ['class' => 'form-label'],
            'inputOptions' => ['class' => 'form-control'],
            'errorOptions' => ['class' => 'invalid-feedback'],
        ]
    ]); ?>

    <div class="row">
        <div class="col-md-8">
            <?= $form->field($parameter, 'name')->textInput(['maxlength' => true]) ?>

            <?= $form->field($parameter, 'unit')->textInput(['maxlength' => 50, 'placeholder' => 'np. mg/dl, mmol/l']) ?>

            <?= $form->field($parameter, 'type')->dropDownList(
                $parameter->getTypeOptions(),
                ['prompt' => 'Wybierz typ parametru...']
            ) ?>

            <?= $form->field($parameter, 'order_index')->input('number', ['min' => 0]) ?>

            <div class="form-group mt-3">
                <?= Html::submitButton('Zapisz', ['class' => 'btn btn-success']) ?>
                <?= Html::a('Anuluj', ['view', 'id' => $template->id], ['class' => 'btn btn-secondary']) ?>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Typy parametrów</h6>
                </div>
                <div class="card-body">
                    <dl class="row small">
                        <dt class="col-sm-12">Pozytywny/Negatywny</dt>
                        <dd class="col-sm-12 text-muted">Wyniki tekstowe typu tak/nie</dd>
                        
                        <dt class="col-sm-12">Zakres min-max</dt>
                        <dd class="col-sm-12 text-muted">Wartości liczbowe w przedziale</dd>
                        
                        <dt class="col-sm-12">Pojedynczy próg</dt>
                        <dd class="col-sm-12 text-muted">Przekroczenie wartości granicznej</dd>
                        
                        <dt class="col-sm-12">Wiele progów</dt>
                        <dd class="col-sm-12 text-muted">Różne zakresy z etykietami</dd>
                        
                        <dt class="col-sm-12">Numeryczny</dt>
                        <dd class="col-sm-12 text-muted">Podstawowy typ liczbowy</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
