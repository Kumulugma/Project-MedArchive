<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Ustawienia';
$this->params['breadcrumbs'][] = ['label' => 'Profil', 'url' => ['profile']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-settings">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2"><?= Html::encode($this->title) ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('<i class="fas fa-arrow-left"></i> Powrót do profilu', ['profile'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Preferencje użytkownika</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(); ?>

                    <h6 class="border-bottom pb-2 mb-3">Powiadomienia</h6>
                    
                    <div class="form-check mb-3">
                        <?= Html::checkbox('notifications_email', true, [
                            'class' => 'form-check-input',
                            'id' => 'notifications-email'
                        ]) ?>
                        <?= Html::label('Powiadomienia e-mail o nadchodzących badaniach', 'notifications-email', [
                            'class' => 'form-check-label'
                        ]) ?>
                    </div>

                    <div class="form-check mb-3">
                        <?= Html::checkbox('notifications_abnormal', true, [
                            'class' => 'form-check-input',
                            'id' => 'notifications-abnormal'
                        ]) ?>
                        <?= Html::label('Powiadomienia o nieprawidłowych wynikach', 'notifications-abnormal', [
                            'class' => 'form-check-label'
                        ]) ?>
                    </div>

                    <h6 class="border-bottom pb-2 mb-3 mt-4">Wyświetlanie</h6>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <?= Html::label('Format daty', 'date-format', ['class' => 'form-label']) ?>
                                <?= Html::dropDownList('date_format', 'Y-m-d', [
                                    'Y-m-d' => '2024-12-31',
                                    'd-m-Y' => '31-12-2024',
                                    'd/m/Y' => '31/12/2024',
                                    'm/d/Y' => '12/31/2024'
                                ], ['class' => 'form-control', 'id' => 'date-format']) ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <?= Html::label('Strefa czasowa', 'timezone', ['class' => 'form-label']) ?>
                                <?= Html::dropDownList('timezone', 'Europe/Warsaw', [
                                    'Europe/Warsaw' => 'Europa/Warszawa (UTC+1)',
                                    'Europe/London' => 'Europa/Londyn (UTC+0)',
                                    'Europe/Berlin' => 'Europa/Berlin (UTC+1)',
                                    'America/New_York' => 'Ameryka/Nowy Jork (UTC-5)'
                                ], ['class' => 'form-control', 'id' => 'timezone']) ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <?= Html::label('Liczba wyników na stronie', 'results-per-page', ['class' => 'form-label']) ?>
                        <?= Html::dropDownList('results_per_page', '20', [
                            '10' => '10',
                            '20' => '20',
                            '50' => '50',
                            '100' => '100'
                        ], ['class' => 'form-control', 'id' => 'results-per-page']) ?>
                    </div>

                    <h6 class="border-bottom pb-2 mb-3 mt-4">Eksport danych</h6>

                    <div class="form-group mb-3">
                        <?= Html::label('Domyślny format eksportu', 'export-format', ['class' => 'form-label']) ?>
                        <?= Html::dropDownList('export_format', 'pdf', [
                            'pdf' => 'PDF',
                            'excel' => 'Excel (.xlsx)',
                            'csv' => 'CSV'
                        ], ['class' => 'form-control', 'id' => 'export-format']) ?>
                    </div>

                    <div class="form-check mb-3">
                        <?= Html::checkbox('include_norms_in_export', true, [
                            'class' => 'form-check-input',
                            'id' => 'include-norms'
                        ]) ?>
                        <?= Html::label('Dołączaj normy w eksportach', 'include-norms', [
                            'class' => 'form-check-label'
                        ]) ?>
                    </div>

                    <div class="form-group mt-4">
                        <?= Html::submitButton('<i class="fas fa-save"></i> Zapisz ustawienia', ['class' => 'btn btn-success']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Zarządzanie danymi</h5>
                </div>
                <div class="card-body">
                    <h6 class="mb-3">Eksport danych</h6>
                    <p class="text-muted small">Eksportuj wszystkie swoje dane medyczne</p>
                    <div class="d-grid gap-2 mb-3">
                        <?= Html::a('<i class="fas fa-download"></i> Eksportuj dane', '#', ['class' => 'btn btn-outline-info']) ?>
                    </div>

                    <h6 class="mb-3 mt-4">Bezpieczeństwo</h6>
                    <div class="d-grid gap-2">
                        <?= Html::a('<i class="fas fa-key"></i> Zmień hasło', ['change-password'], ['class' => 'btn btn-outline-warning']) ?>
                        <?= Html::a('<i class="fas fa-shield-alt"></i> Historia logowań', '#', ['class' => 'btn btn-outline-secondary']) ?>
                    </div>

                    <div class="alert alert-warning mt-4">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Uwaga:</strong> Zmiany ustawień mogą wpłynąć na sposób wyświetlania danych w całej aplikacji.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>