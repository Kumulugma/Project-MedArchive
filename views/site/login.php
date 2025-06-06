<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Logowanie';
?>

<div class="site-login">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card shadow-lg border-0 rounded-lg mt-5">
                <div class="card-header">
                    <h3 class="text-center font-weight-light my-4"><?= Html::encode($this->title) ?></h3>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin([
                        'id' => 'login-form',
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => 'small mb-1'],
                            'inputOptions' => ['class' => 'form-control py-4'],
                            'errorOptions' => ['class' => 'invalid-feedback d-block'],
                        ],
                    ]); ?>

                    <?= $form->field($model, 'username')->textInput(['autofocus' => true]) ?>

                    <?= $form->field($model, 'password')->passwordInput() ?>

                    <div class="form-check mb-3">
                        <?= $form->field($model, 'rememberMe')->checkbox([
                            'template' => "<div class=\"custom-control custom-checkbox\">{input} {label}</div>\n{error}",
                            'labelOptions' => ['class' => 'custom-control-label'],
                            'inputOptions' => ['class' => 'custom-control-input'],
                        ]) ?>
                    </div>

                    <div class="form-group d-flex align-items-center justify-content-between mt-4 mb-0">
                        <?= Html::submitButton('Zaloguj', ['class' => 'btn btn-primary btn-block', 'name' => 'login-button']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
                <div class="card-footer text-center py-3">
                    <div class="small text-muted">MedArchive &copy; <?= date('Y') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>