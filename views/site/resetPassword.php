<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Resetowanie hasła';
?>

<div class="site-reset-password">
    <div class="card shadow-lg border-0 rounded-lg">
        <div class="card-header">
            <h3 class="text-center font-weight-light my-2">Ustaw nowe hasło</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-key"></i>
                Wprowadź nowe hasło dla swojego konta. Hasło musi mieć co najmniej 6 znaków.
            </div>

            <?php $form = ActiveForm::begin([
                'id' => 'reset-password-form',
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'form-label'],
                    'inputOptions' => ['class' => 'form-control'],
                    'errorOptions' => ['class' => 'invalid-feedback d-block'],
                ],
            ]); ?>

            <?= $form->field($model, 'password')->passwordInput([
                'autofocus' => true,
                'placeholder' => 'Wprowadź nowe hasło'
            ]) ?>

            <?= $form->field($model, 'confirmPassword')->passwordInput([
                'placeholder' => 'Potwierdź nowe hasło'
            ]) ?>

            <div class="d-grid gap-2 mt-4">
                <?= Html::submitButton('Zapisz nowe hasło', [
                    'class' => 'btn btn-success btn-lg'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
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