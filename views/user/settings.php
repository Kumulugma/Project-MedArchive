<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Ustawienia';
$this->params['breadcrumbs'][] = ['label' => 'Profil', 'url' => ['profile']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-settings">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-cog"></i> <?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="fas fa-arrow-left"></i> Powrót do profilu', ['profile'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Preferencje użytkownika</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(['id' => 'settings-form']); ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <?= Html::label('Strefa czasowa', 'timezone', ['class' => 'form-label']) ?>
                                <?=
                                Html::dropDownList('timezone', 'Europe/Warsaw', [
                                    'Europe/Warsaw' => 'Europa/Warszawa (UTC+1)',
                                    'Europe/London' => 'Europa/Londyn (UTC+0)',
                                    'America/New_York' => 'Ameryka/Nowy Jork (UTC-5)',
                                    'Asia/Tokyo' => 'Azja/Tokio (UTC+9)'
                                        ], ['class' => 'form-control', 'id' => 'timezone'])
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <?= Html::label('Liczba wyników na stronie', 'results-per-page', ['class' => 'form-label']) ?>
                        <?=
                        Html::dropDownList('results_per_page', '20', [
                            '10' => '10',
                            '20' => '20',
                            '50' => '50',
                            '100' => '100'
                                ], ['class' => 'form-control', 'id' => 'results-per-page'])
                        ?>
                    </div>

                    <div class="form-check mb-3">
                        <?=
                        Html::checkbox('include_norms_in_export', true, [
                            'class' => 'form-check-input',
                            'id' => 'include-norms'
                        ])
                        ?>
                        <?=
                        Html::label('Dołączaj normy w eksportach', 'include-norms', [
                            'class' => 'form-check-label'
                        ])
                        ?>
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
                        <?=
                        Html::a('<i class="fas fa-download"></i> Pełny eksport danych', ['../export/full-export'], [
                            'class' => 'btn btn-outline-info export-btn',
                            'data-bs-toggle' => 'tooltip',
                            'title' => 'Eksportuj wszystkie dane w formacie Excel'
                        ])
                        ?>
                    </div>

                    <h6 class="mb-3 mt-4">Bezpieczeństwo</h6>
                    <div class="d-grid gap-2">
                        <?= Html::a('<i class="fas fa-key"></i> Zmień hasło', ['change-password'], ['class' => 'btn btn-outline-warning']) ?>
                        <?= Html::a('<i class="fas fa-shield-alt"></i> Historia logowań', ['login-history'], ['class' => 'btn btn-outline-secondary']) ?>
                    </div>

                    <div class="alert alert-warning mt-4">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Uwaga:</strong> Zmiany ustawień mogą wpłynąć na sposób wyświetlania danych w całej aplikacji.
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Szybki eksport</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Eksportuj konkretne dane</p>
                    <div class="d-grid gap-2">

                        <!-- Wyniki badań -->
                        <div class="d-flex gap-1 mb-2">
                            <span class="badge bg-secondary flex-shrink-0 align-self-center" style="min-width: 80px;">Wyniki</span>
                            <?=
                            Html::a('<i class="fas fa-file-excel"></i> Excel', ['../export/test-results', 'format' => 'excel'], [
                                'class' => 'btn btn-outline-primary btn-sm flex-fill export-btn',
                                'data-bs-toggle' => 'tooltip',
                                'title' => 'Eksportuj wyniki badań do Excel'
                            ])
                            ?>
                            <?=
                            Html::a('<i class="fas fa-file-pdf"></i> PDF', ['../export/test-results', 'format' => 'pdf'], [
                                'class' => 'btn btn-outline-danger btn-sm flex-fill export-btn',
                                'data-bs-toggle' => 'tooltip',
                                'title' => 'Eksportuj wyniki badań do PDF'
                            ])
                            ?>
                        </div>

                        <!-- Szablony badań -->
                        <div class="d-flex gap-1 mb-2">
                            <span class="badge bg-success flex-shrink-0 align-self-center" style="min-width: 80px;">Szablony</span>
                            <?=
                            Html::a('<i class="fas fa-file-excel"></i> Excel', ['../export/test-templates', 'format' => 'excel'], [
                                'class' => 'btn btn-outline-primary btn-sm flex-fill export-btn',
                                'data-bs-toggle' => 'tooltip',
                                'title' => 'Eksportuj szablony badań do Excel'
                            ])
                            ?>
                            <?=
                            Html::a('<i class="fas fa-file-pdf"></i> PDF', ['../export/test-templates', 'format' => 'pdf'], [
                                'class' => 'btn btn-outline-danger btn-sm flex-fill export-btn',
                                'data-bs-toggle' => 'tooltip',
                                'title' => 'Eksportuj szablony badań do PDF'
                            ])
                            ?>
                        </div>

                        <!-- Kolejka badań -->
                        <div class="d-flex gap-1 mb-2">
                            <span class="badge bg-info flex-shrink-0 align-self-center" style="min-width: 80px;">Kolejka</span>
                            <?=
                            Html::a('<i class="fas fa-file-excel"></i> Excel', ['../export/test-queue', 'format' => 'excel'], [
                                'class' => 'btn btn-outline-primary btn-sm flex-fill export-btn',
                                'data-bs-toggle' => 'tooltip',
                                'title' => 'Eksportuj kolejkę badań do Excel'
                            ])
                            ?>
                            <?=
                            Html::a('<i class="fas fa-file-pdf"></i> PDF', ['../export/test-queue', 'format' => 'pdf'], [
                                'class' => 'btn btn-outline-danger btn-sm flex-fill export-btn',
                                'data-bs-toggle' => 'tooltip',
                                'title' => 'Eksportuj kolejkę badań do PDF'
                            ])
                            ?>
                        </div>

                        <!-- Wszystkie dane -->
                        <div class="d-flex gap-1">
                            <span class="badge bg-warning flex-shrink-0 align-self-center" style="min-width: 80px;">Wszystko</span>
                            <?=
                            Html::a('<i class="fas fa-file-excel"></i> Excel', ['../export/full-export', 'format' => 'excel'], [
                                'class' => 'btn btn-primary btn-sm flex-fill export-btn',
                                'data-bs-toggle' => 'tooltip',
                                'title' => 'Pełny eksport wszystkich danych do Excel'
                            ])
                            ?>
                            <?=
                            Html::a('<i class="fas fa-file-pdf"></i> PDF', ['../export/full-export', 'format' => 'pdf'], [
                                'class' => 'btn btn-danger btn-sm flex-fill export-btn',
                                'data-bs-toggle' => 'tooltip',
                                'title' => 'Pełny eksport wszystkich danych do PDF'
                            ])
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Informacje o koncie</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted">Użytkownik:</td>
                            <td><strong><?= Html::encode(Yii::$app->user->identity->username) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status:</td>
                            <td><span class="badge bg-success">Aktywny</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Utworzono:</td>
                            <td><?= Yii::$app->formatter->asDate(Yii::$app->user->identity->created_at) ?></td>
                        </tr>
                    </table>

                    <div class="mt-3">
                        <?=
                        Html::a('<i class="fas fa-user"></i> Zobacz profil', ['profile'], [
                            'class' => 'btn btn-outline-secondary btn-sm w-100'
                        ])
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Inicjalizuj tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();

        // Obsługa przycisków eksportu
        $('.export-btn').on('click', function (e) {
            var $btn = $(this);
            var originalText = $btn.html();
            var originalClass = $btn.attr('class');

            // Pokaż loading
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Eksportowanie...');
            $btn.removeClass().addClass('btn btn-secondary').prop('disabled', true);

            // Po kliknięciu w link, przywróć przycisk po 3 sekundach
            setTimeout(function () {
                $btn.html(originalText);
                $btn.attr('class', originalClass);
                $btn.prop('disabled', false);

                // Pokaż powiadomienie o sukcesie
                showExportNotification('success', 'Eksport został rozpoczęty. Plik zostanie pobrany automatycznie.');
            }, 1500);
        });

        // Walidacja formularza ustawień
        $('#settings-form').on('submit', function (e) {
            // Sprawdź czy wszystkie wymagane pola są wypełnione
            var isValid = true;
            $(this).find('[required]').each(function () {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                showNotification('error', 'Proszę wypełnić wszystkie wymagane pola.');
            }
        });
    });

    // Funkcja do pokazywania powiadomień
    function showExportNotification(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
        
        const notification = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                <i class="${icon}"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('body').append(notification);
        
        // Auto-hide po 5 sekundach
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }

    function showNotification(type, message) {
        showExportNotification(type, message);
    }
</script>