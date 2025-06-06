<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>

<div class="test-template-form">
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
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

            <?= $form->field($model, 'status')->dropDownList([
                1 => 'Aktywny',
                0 => 'Nieaktywny'
            ]) ?>

            <div class="form-group mt-3">
                <?= Html::submitButton('Zapisz', ['class' => 'btn btn-success']) ?>
                <?= Html::a('Anuluj', ['index'], ['class' => 'btn btn-secondary']) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>