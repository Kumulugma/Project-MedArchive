<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Zmiana hasła';
$this->params['breadcrumbs'][] = ['label' => 'Profil', 'url' => ['profile']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-change-password">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2"><?= Html::encode($this->title) ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('<i class="fas fa-arrow-left"></i> Powrót do profilu', ['profile'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-key"></i> Zmiana hasła</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Hasło musi mieć co najmniej 6 znaków.
                    </div>

                    <?php $form = ActiveForm::begin([
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => 'form-label'],
                            'inputOptions' => ['class' => 'form-control'],
                            'errorOptions' => ['class' => 'invalid-feedback d-block'],
                        ],
                    ]); ?>

                    <?= $form->field($model, 'currentPassword')->passwordInput([
                        'placeholder' => 'Wprowadź obecne hasło'
                    ]) ?>

                    <?= $form->field($model, 'newPassword')->passwordInput([
                        'placeholder' => 'Wprowadź nowe hasło'
                    ]) ?>

                    <?= $form->field($model, 'confirmPassword')->passwordInput([
                        'placeholder' => 'Potwierdź nowe hasło'
                    ]) ?>

                    <div class="d-grid gap-2 mt-4">
                        <?= Html::submitButton('<i class="fas fa-save"></i> Zmień hasło', [
                            'class' => 'btn btn-success btn-lg'
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>