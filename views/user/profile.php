<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Profil użytkownika';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-profile">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2"><?= Html::encode($this->title) ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('<i class="fas fa-key"></i> Zmień hasło', ['change-password'], ['class' => 'btn btn-outline-warning']) ?>
                <?= Html::a('<i class="fas fa-cog"></i> Ustawienia', ['settings'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informacje podstawowe</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(); ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'username')->textInput(['readonly' => true]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'email')->textInput(['type' => 'email']) ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'first_name')->textInput(['maxlength' => true]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'last_name')->textInput(['maxlength' => true]) ?>
                        </div>
                    </div>

                    <?= $form->field($model, 'phone')->textInput(['maxlength' => true]) ?>

                    <div class="form-group">
                        <?= Html::submitButton('<i class="fas fa-save"></i> Zapisz zmiany', ['class' => 'btn btn-success']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informacje o koncie</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-6">Status:</dt>
                        <dd class="col-sm-6">
                            <?php if ($model->status == \app\models\User::STATUS_ACTIVE): ?>
                                <span class="badge bg-success">Aktywne</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Nieaktywne</span>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-6">Utworzone:</dt>
                        <dd class="col-sm-6"><?= Yii::$app->formatter->asDatetime($model->created_at) ?></dd>

                        <dt class="col-sm-6">Ostatnie logowanie:</dt>
                        <dd class="col-sm-6">
                            <?= $model->last_login_at ? Yii::$app->formatter->asDatetime($model->last_login_at) : 'Nigdy' ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Szybkie akcje</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?= Html::a('<i class="fas fa-key"></i> Zmień hasło', ['change-password'], ['class' => 'btn btn-outline-warning']) ?>
                        <?= Html::a('<i class="fas fa-cog"></i> Ustawienia', ['settings'], ['class' => 'btn btn-outline-secondary']) ?>
                        <?= Html::a('<i class="fas fa-tachometer-alt"></i> Dashboard', ['/dashboard/index'], ['class' => 'btn btn-outline-primary']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>