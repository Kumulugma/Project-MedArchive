<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

?>

<div class="test-result-form">
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
                [
                    'prompt' => 'Wybierz badanie...',
                    'id' => 'test-template-select',
                    'data-url' => Url::to(['get-template-parameters'])
                ]
            ) ?>

            <?= $form->field($model, 'test_date')->input('date') ?>

            <?= $form->field($model, 'comment')->textarea(['rows' => 4]) ?>

            <div id="parameters-container">
                <?php if ($model->test_template_id && !$model->isNewRecord): ?>
                    <?= $this->render('_parameters', [
                        'template' => $model->testTemplate,
                        'result' => $model,
                    ]) ?>
                <?php endif; ?>
            </div>

            <div class="form-group mt-3">
                <?= Html::submitButton('Zapisz', ['class' => 'btn btn-success']) ?>
                <?= Html::a('Anuluj', ['index'], ['class' => 'btn btn-secondary']) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<script>
$(document).ready(function() {
    $('#test-template-select').on('change', function() {
        var templateId = $(this).val();
        var url = $(this).data('url');
        
        if (templateId) {
            $.get(url, { templateId: templateId })
                .done(function(data) {
                    $('#parameters-container').html(data);
                })
                .fail(function() {
                    alert('Błąd podczas ładowania parametrów');
                });
        } else {
            $('#parameters-container').empty();
        }
    });
});
</script>
