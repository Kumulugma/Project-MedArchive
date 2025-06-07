<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Resetowanie hasła';
?>

<div class="site-request-password-reset">
    <div class="card shadow-lg border-0 rounded-lg">
        <div class="card-header">
            <h3 class="text-center font-weight-light my-2">Resetowanie hasła</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Podaj adres e-mail powiązany z Twoim kontem, a wyślemy Ci link do resetowania hasła.
            </div>

            <?php $form = ActiveForm::begin([
                'id' => 'request-password-reset-form',
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'form-label'],
                    'inputOptions' => ['class' => 'form-control'],
                    'errorOptions' => ['class' => 'invalid-feedback d-block'],
                ],
            ]); ?>

            <?= $form->field($model, 'email')->textInput([
                'autofocus' => true,
                'type' => 'email',
                'placeholder' => 'Wprowadź adres e-mail'
            ]) ?>

            <div class="d-grid gap-2 mt-4">
                <?= Html::submitButton('Wyślij link resetujący', [
                    'class' => 'btn btn-primary btn-lg'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>

            <div class="text-center mt-3">
                <?= Html::a('<i class="fas fa-arrow-left"></i> Powrót do logowania', ['login'], [
                    'class' => 'btn btn-outline-secondary'
                ]) ?>
            </div>
        </div>
        <div class="card-footer text-center py-3">
            <div class="small text-muted">
                <strong>MedArchive</strong> &copy; <?= date('Y') ?>
                <br>
                <small>System archiwizacji wyników badań medycznych</small>
            </div>
        </div>
    </div>
</div>