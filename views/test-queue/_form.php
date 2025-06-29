<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

?>

<div class="test-queue-form">
    <div class="card">
        <div class="card-body">
            <?php $form = ActiveForm::begin([
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'form-label'],
                    'inputOptions' => ['class' => 'form-control'],
                    'errorOptions' => ['class' => 'invalid-feedback d-block'],
                ],
            ]); ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'test_template_id')->dropDownList(
                        ArrayHelper::map($templates, 'id', 'name'),
                        [
                            'prompt' => 'Wybierz szablon badania...',
                            'class' => 'form-select'
                        ]
                    ) ?>
                </div>
                
                <div class="col-md-6">
                    <?= $form->field($model, 'scheduled_date')->input('date', [
                        'class' => 'form-control',
                        'placeholder' => 'Wybierz datę...',
                        'min' => date('Y-m-d'), // Nie pozwalaj na daty w przeszłości
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'status')->dropDownList($model->getStatusOptions(), [
                        'class' => 'form-select'
                    ]) ?>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check mt-4">
                        <?= $form->field($model, 'reminder_sent')->checkbox([
                            'template' => '<div class="form-check">{input} {label}</div>{error}',
                            'labelOptions' => ['class' => 'form-check-label'],
                            'inputOptions' => ['class' => 'form-check-input'],
                        ]) ?>
                    </div>
                </div>
            </div>

            <?= $form->field($model, 'comment')->textarea([
                'rows' => 4,
                'placeholder' => 'Dodatkowe uwagi dotyczące badania...'
            ]) ?>

            <div class="form-group mt-4">
                <div class="d-flex justify-content-between">
                    <?= Html::submitButton($model->isNewRecord ? 
                        '<i class="fas fa-save"></i> Zaplanuj badanie' : 
                        '<i class="fas fa-save"></i> Zapisz zmiany', [
                        'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary'
                    ]) ?>
                    
                    <?= Html::a('<i class="fas fa-times"></i> Anuluj', ['index'], [
                        'class' => 'btn btn-secondary'
                    ]) ?>
                </div>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Walidacja daty - nie pozwalaj na przeszłe daty
    $('#testqueue-scheduled_date').on('change', function() {
        var selectedDate = new Date($(this).val());
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            alert('Nie można zaplanować badania na datę z przeszłości!');
            $(this).val('');
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            
            // Sprawdź czy to weekend
            var dayOfWeek = selectedDate.getDay();
            if (dayOfWeek === 0 || dayOfWeek === 6) {
                alert('Uwaga: Wybrana data przypada na weekend');
            }
        }
    });
    
    // Auto-focus na pierwszym polu
    $('#testqueue-test_template_id').focus();
    
    // Walidacja formularza przed wysłaniem
    $('form').on('submit', function(e) {
        var isValid = true;
        
        // Sprawdź czy wybrano szablon
        if (!$('#testqueue-test_template_id').val()) {
            $('#testqueue-test_template_id').addClass('is-invalid');
            isValid = false;
        }
        
        // Sprawdź czy wybrano datę
        if (!$('#testqueue-scheduled_date').val()) {
            $('#testqueue-scheduled_date').addClass('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('Proszę wypełnić wszystkie wymagane pola');
        }
    });
});
</script>