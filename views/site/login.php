<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Logowanie do MedArchive';
?>

<div class="site-login">
    <div class="card shadow-lg border-0 rounded-lg">
        <div class="card-header">
            <h3 class="text-center font-weight-light my-2">Logowanie do MedArchive</h3>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'form-label'],
                    'inputOptions' => ['class' => 'form-control'],
                    'errorOptions' => ['class' => 'invalid-feedback d-block'],
                ],
            ]); ?>

            <?= $form->field($model, 'username')->textInput([
                'autofocus' => true,
                'placeholder' => 'Wprowadź nazwę użytkownika'
            ]) ?>

            <?= $form->field($model, 'password')->passwordInput([
                'placeholder' => 'Wprowadź hasło'
            ]) ?>

            <div class="form-check mb-3">
                <?= $form->field($model, 'rememberMe')->checkbox([
                    'template' => '<div class="form-check">{input} {label}</div>{error}',
                    'labelOptions' => ['class' => 'form-check-label'],
                    'inputOptions' => ['class' => 'form-check-input'],
                ]) ?>
            </div>

            <div class="d-grid gap-2 mt-4 mb-0">
                <?= Html::submitButton('Zaloguj się', [
                    'class' => 'btn btn-primary btn-lg',
                    'name' => 'login-button'
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