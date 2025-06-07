<?php
use yii\helpers\Html;

$this->title = 'Profil użytkownika';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-profile">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2"><?= Html::encode($this->title) ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('<i class="fas fa-cog"></i> Ustawienia', ['settings'], ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user"></i> Informacje o użytkowniku
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar-placeholder bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem;">
                            <?= strtoupper(substr($user->username, 0, 1)) ?>
                        </div>
                    </div>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td><strong>Nazwa użytkownika:</strong></td>
                            <td><?= Html::encode($user->username) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td><?= Html::encode($user->email) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Data rejestracji:</strong></td>
                            <td><?= Yii::$app->formatter->asDate($user->created_at) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Ostatnie logowanie:</strong></td>
                            <td>
                                <?= $stats['last_login'] 
                                    ? Yii::$app->formatter->asDatetime($stats['last_login']->login_time)
                                    : 'Brak danych' ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Szybkie akcje</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?= Html::a('<i class="fas fa-key"></i> Zmień hasło', ['change-password'], ['class' => 'btn btn-outline-warning btn-sm']) ?>
                        <?= Html::a('<i class="fas fa-history"></i> Historia logowań', ['login-history'], ['class' => 'btn btn-outline-info btn-sm']) ?>
                        <?= Html::a('<i class="fas fa-download"></i> Eksportuj dane', ['../export/full-export'], ['class' => 'btn btn-outline-success btn-sm']) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar"></i> Statystyki
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0"><?= $stats['total_results'] ?></h4>
                                            <small>Wyników badań</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-clipboard-list fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0"><?= $stats['total_templates'] ?></h4>
                                            <small>Szablonów badań</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-file-medical fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0"><?= $stats['pending_tests'] ?></h4>
                                            <small>Oczekujących badań</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-calendar-alt fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">
                                                <?= $stats['last_login'] 
                                                    ? Yii::$app->formatter->asRelativeTime($stats['last_login']->login_time)
                                                    : 'Nigdy' ?>
                                            </h4>
                                            <small>Ostatnie logowanie</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-sign-in-alt fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-download"></i> Eksport danych
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">Eksportuj swoje dane medyczne w różnych formatach.</p>
                    <div class="row">
                        <div class="row">
    <div class="col-md-4 mb-2">
        <div class="dropdown">
            <button class="btn btn-outline-primary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-clipboard-list"></i> Wyniki badań
            </button>
            <ul class="dropdown-menu">
                <li><?= Html::a('<i class="fas fa-file-excel"></i> Excel', ['../export/test-results', 'format' => 'excel'], ['class' => 'dropdown-item']) ?></li>
                <li><?= Html::a('<i class="fas fa-file-pdf"></i> PDF', ['../export/test-results', 'format' => 'pdf'], ['class' => 'dropdown-item']) ?></li>
            </ul>
        </div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="dropdown">
            <button class="btn btn-outline-success dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-file-medical"></i> Szablony
            </button>
            <ul class="dropdown-menu">
                <li><?= Html::a('<i class="fas fa-file-excel"></i> Excel', ['../export/test-templates', 'format' => 'excel'], ['class' => 'dropdown-item']) ?></li>
                <li><?= Html::a('<i class="fas fa-file-pdf"></i> PDF', ['../export/test-templates', 'format' => 'pdf'], ['class' => 'dropdown-item']) ?></li>
            </ul>
        </div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="dropdown">
            <button class="btn btn-outline-info dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-database"></i> Wszystkie dane
            </button>
            <ul class="dropdown-menu">
                <li><?= Html::a('<i class="fas fa-file-excel"></i> Excel', ['../export/full-export', 'format' => 'excel'], ['class' => 'dropdown-item']) ?></li>
                <li><?= Html::a('<i class="fas fa-file-pdf"></i> PDF', ['../export/full-export', 'format' => 'pdf'], ['class' => 'dropdown-item']) ?></li>
            </ul>
        </div>
    </div>
</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Fix dla dropdown position
    $('.dropdown-toggle').on('click', function() {
        var $dropdown = $(this).next('.dropdown-menu');
        var $card = $(this).closest('.card');
        
        // Sprawdź czy dropdown wykracza poza kartę
        setTimeout(function() {
            var dropdownBottom = $dropdown.offset().top + $dropdown.outerHeight();
            var cardBottom = $card.offset().top + $card.outerHeight();
            
            if (dropdownBottom > cardBottom) {
                // Przenieś dropdown do góry
                $dropdown.addClass('dropup');
            }
        }, 10);
    });
    
    // Reset position po zamknięciu
    $(document).on('hidden.bs.dropdown', function() {
        $('.dropdown-menu').removeClass('dropup');
    });
});
</script>

<style>
.dropdown-menu.dropup {
    top: auto !important;
    bottom: 100% !important;
    margin-bottom: 2px;
}
</style>