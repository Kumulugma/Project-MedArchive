<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * Model formularza zmiany hasła
 */
class ChangePasswordForm extends Model
{
    public $currentPassword;
    public $newPassword;
    public $confirmPassword;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['currentPassword', 'newPassword', 'confirmPassword'], 'required'],
            ['currentPassword', 'validateCurrentPassword'],
            ['newPassword', 'string', 'min' => 6],
            ['newPassword', 'match', 'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', 
             'message' => 'Hasło musi zawierać co najmniej jedną małą literę, jedną wielką literę i jedną cyfrę.'],
            ['confirmPassword', 'compare', 'compareAttribute' => 'newPassword', 
             'message' => 'Potwierdzenie hasła musi być identyczne z nowym hasłem.'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'currentPassword' => 'Aktualne hasło',
            'newPassword' => 'Nowe hasło',
            'confirmPassword' => 'Potwierdź nowe hasło',
        ];
    }

    /**
     * Walidacja aktualnego hasła
     */
    public function validateCurrentPassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = Yii::$app->user->identity;
            if (!$user || !$user->validatePassword($this->currentPassword)) {
                $this->addError($attribute, 'Aktualne hasło jest nieprawidłowe.');
            }
        }
    }

    /**
     * Zmiana hasła użytkownika
     * @return bool
     */
    public function changePassword()
    {
        if ($this->validate()) {
            $user = Yii::$app->user->identity;
            $user->setPassword($this->newPassword);
            $user->generateAuthKey();
            
            if ($user->save(false)) {
                // Zapisz w historii logowań informację o zmianie hasła
                $this->logPasswordChange($user);
                return true;
            }
        }
        return false;
    }

    /**
     * Zapisz w historii informację o zmianie hasła
     */
    private function logPasswordChange($user)
    {
        try {
            $loginHistory = new LoginHistory();
            $loginHistory->user_id = $user->id;
            $loginHistory->ip = Yii::$app->request->userIP;
            $loginHistory->user_agent = Yii::$app->request->userAgent;
            $loginHistory->success = true;
            $loginHistory->notes = 'Zmiana hasła';
            $loginHistory->created_at = date('Y-m-d H:i:s');
            $loginHistory->save();
        } catch (\Exception $e) {
            // Błąd zapisu historii nie powinien przerwać procesu zmiany hasła
            Yii::error('Błąd zapisu historii zmiany hasła: ' . $e->getMessage());
        }
    }
}