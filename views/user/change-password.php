<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ChangePasswordForm */

$this->title = 'Zmiana hasła';
$this->params['breadcrumbs'][] = ['label' => 'Ustawienia', 'url' => ['settings']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-change-password">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2"><?= Html::encode($this->title) ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('<i class="fas fa-arrow-left"></i> Powrót do ustawień', ['settings'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-key text-warning"></i> 
                        Zmiana hasła
                    </h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin([
                        'id' => 'change-password-form',
                        'options' => ['class' => 'needs-validation', 'novalidate' => true],
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => 'form-label'],
                            'inputOptions' => ['class' => 'form-control'],
                            'errorOptions' => ['class' => 'invalid-feedback d-block'],
                        ],
                    ]); ?>

                    <div class="mb-3">
                        <?= $form->field($model, 'currentPassword')->passwordInput([
                            'maxlength' => true,
                            'placeholder' => 'Wprowadź aktualne hasło',
                            'required' => true,
                            'id' => 'current-password'
                        ]) ?>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Potwierdź swoją tożsamość wprowadzając aktualne hasło
                        </small>
                    </div>

                    <div class="mb-3">
                        <?= $form->field($model, 'newPassword')->passwordInput([
                            'maxlength' => true,
                            'placeholder' => 'Wprowadź nowe hasło (min. 6 znaków)',
                            'required' => true,
                            'id' => 'new-password',
                            'pattern' => '^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$'
                        ]) ?>
                        <div class="password-requirements mt-2">
                            <small class="text-muted">Hasło musi zawierać:</small>
                            <ul class="list-unstyled small text-muted">
                                <li><i class="fas fa-check text-success"></i> Co najmniej 6 znaków</li>
                                <li><i class="fas fa-check text-success"></i> Jedną małą literę (a-z)</li>
                                <li><i class="fas fa-check text-success"></i> Jedną wielką literę (A-Z)</li>
                                <li><i class="fas fa-check text-success"></i> Jedną cyfrę (0-9)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mb-3">
                        <?= $form->field($model, 'confirmPassword')->passwordInput([
                            'maxlength' => true,
                            'placeholder' => 'Potwierdź nowe hasło',
                            'required' => true,
                            'id' => 'confirm-password'
                        ]) ?>
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
    
    // Walidacja siły hasła w czasie rzeczywistym
    function validatePasswordStrength() {
        const password = newPassword.value;
        const requirements = document.querySelectorAll('.password-requirements li i');
        
        // Co najmniej 6 znaków
        if (password.length >= 6) {
            requirements[0].className = 'fas fa-check text-success';
        } else {
            requirements[0].className = 'fas fa-times text-danger';
        }
        
        // Mała litera
        if (/[a-z]/.test(password)) {
            requirements[1].className = 'fas fa-check text-success';
        } else {
            requirements[1].className = 'fas fa-times text-danger';
        }
        
        // Wielka litera
        if (/[A-Z]/.test(password)) {
            requirements[2].className = 'fas fa-check text-success';
        } else {
            requirements[2].className = 'fas fa-times text-danger';
        }
        
        // Cyfra
        if (/\d/.test(password)) {
            requirements[3].className = 'fas fa-check text-success';
        } else {
            requirements[3].className = 'fas fa-times text-danger';
        }
    }
    
    newPassword.addEventListener('input', function() {
        validatePasswords();
        validatePasswordStrength();
    });
    
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

<style>
.password-requirements {
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 5px;
    border-left: 3px solid #007bff;
}

.was-validated .form-control:valid {
    border-color: #28a745;
}

.was-validated .form-control:invalid {
    border-color: #dc3545;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

#change-password-btn {
    padding: 12px;
    font-weight: 600;
}
</style>