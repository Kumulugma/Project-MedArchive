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
                    <h5 class="mb-0">
                        <i class="fas fa-key"></i> Zmień hasło
                    </h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin([
                        'id' => 'change-password-form',
                        'options' => ['class' => 'needs-validation', 'novalidate' => true],
                    ]); ?>

                    <div class="form-group mb-3">
                        <?= Html::label('Obecne hasło', 'current-password', ['class' => 'form-label']) ?>
                        <?= Html::passwordInput('current_password', '', [
                            'class' => 'form-control',
                            'id' => 'current-password',
                            'required' => true,
                            'placeholder' => 'Wprowadź obecne hasło'
                        ]) ?>
                        <div class="invalid-feedback">
                            Proszę wprowadzić obecne hasło.
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <?= Html::label('Nowe hasło', 'new-password', ['class' => 'form-label']) ?>
                        <?= Html::passwordInput('new_password', '', [
                            'class' => 'form-control',
                            'id' => 'new-password',
                            'required' => true,
                            'minlength' => 8,
                            'placeholder' => 'Wprowadź nowe hasło (min. 8 znaków)'
                        ]) ?>
                        <div class="invalid-feedback">
                            Nowe hasło musi mieć co najmniej 8 znaków.
                        </div>
                        <div class="form-text">
                            Hasło powinno zawierać co najmniej 8 znaków, w tym wielkie i małe litery oraz cyfry.
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <?= Html::label('Potwierdź nowe hasło', 'confirm-password', ['class' => 'form-label']) ?>
                        <?= Html::passwordInput('confirm_password', '', [
                            'class' => 'form-control',
                            'id' => 'confirm-password',
                            'required' => true,
                            'placeholder' => 'Potwierdź nowe hasło'
                        ]) ?>
                        <div class="invalid-feedback">
                            Hasła muszą być identyczne.
                        </div>
                    </div>

                    <div class="d-grid">
                        <?= Html::submitButton('<i class="fas fa-save"></i> Zmień hasło', [
                            'class' => 'btn btn-primary',
                            'id' => 'change-password-btn'
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Wskazówki dotyczące bezpieczeństwa</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success"></i>
                            Używaj unikalnego hasła dla tego systemu
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success"></i>
                            Mieszaj wielkie i małe litery, cyfry oraz znaki specjalne
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success"></i>
                            Zmieniaj hasło regularnie (co 3-6 miesięcy)
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-check text-success"></i>
                            Nie udostępniaj hasła innym osobom
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Walidacja formularza zmiany hasła
(function() {
    'use strict';
    
    const form = document.getElementById('change-password-form');
    const newPassword = document.getElementById('new-password');
    const confirmPassword = document.getElementById('confirm-password');
    
    function validatePasswords() {
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Hasła muszą być identyczne');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('input', validatePasswords);
    confirmPassword.addEventListener('input', validatePasswords);
    
    form.addEventListener('submit', function(event) {
        validatePasswords();
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
})();
</script>
