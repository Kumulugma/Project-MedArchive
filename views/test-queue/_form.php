<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

?>

<div class="test-queue-form">
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
            <?= $form->field($model, 'test_template_id')->dropDownList(
                ArrayHelper::map($templates, 'id', 'name'),
                ['prompt' => 'Wybierz badanie...']
            ) ?>

            <?= $form->field($model, 'scheduled_date')->input('date', [
                'min' => date('Y-m-d'),
                'value' => $model->scheduled_date ?: date('Y-m-d', strtotime('+1 week'))
            ]) ?>

            <?= $form->field($model, 'comment')->textarea(['rows' => 4]) ?>

            <?= $form->field($model, 'status')->dropDownList(
                $model->getStatusOptions(),
                ['options' => ['pending' => ['selected' => true]]]
            ) ?>

            <div class="form-group mt-3">
                <?= Html::submitButton('Zapisz', ['class' => 'btn btn-success']) ?>
                <?= Html::a('Anuluj', ['index'], ['class' => 'btn btn-secondary']) ?>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Przypomnienia</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Przypomnienie zostanie wysłane automatycznie na 7 dni przed zaplanowaną datą badania.
                    </p>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="auto-reminder" checked>
                        <label class="form-check-label small" for="auto-reminder">
                            Automatyczne przypomnienia
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
