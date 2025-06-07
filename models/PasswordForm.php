<?php

namespace app\models;

use Yii;
use yii\base\Model;

class PasswordForm extends Model
{
    public $currentPassword;
    public $newPassword;
    public $confirmPassword;
    public $user;

    public function rules()
    {
        return [
            [['currentPassword', 'newPassword', 'confirmPassword'], 'required'],
            ['currentPassword', 'validateCurrentPassword'],
            ['newPassword', 'string', 'min' => 6],
            ['confirmPassword', 'compare', 'compareAttribute' => 'newPassword', 'message' => 'Hasła muszą być identyczne.'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'currentPassword' => 'Obecne hasło',
            'newPassword' => 'Nowe hasło',
            'confirmPassword' => 'Potwierdź nowe hasło',
        ];
    }

    public function validateCurrentPassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if (!$this->user || !$this->user->validatePassword($this->currentPassword)) {
                $this->addError($attribute, 'Obecne hasło jest nieprawidłowe.');
            }
        }
    }

    public function changePassword()
    {
        if ($this->validate()) {
            $this->user->setPassword($this->newPassword);
            return $this->user->save(false);
        }
        return false;
    }
}