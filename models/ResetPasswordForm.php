<?php

namespace app\models;

use yii\base\Model;
use yii\base\InvalidArgumentException;

/**
 * Formularz resetowania hasła
 */
class ResetPasswordForm extends Model
{
    public $password;
    public $confirmPassword;

    /**
     * @var User
     */
    private $_user;

    /**
     * Tworzy formularz z tokenem resetowania hasła
     *
     * @param string $token
     * @param array $config
     * @throws InvalidArgumentException jeśli token jest pusty lub nie jest prawidłowy
     */
    public function __construct($token, $config = [])
    {
        if (empty($token) || !is_string($token)) {
            throw new InvalidArgumentException('Token resetowania hasła nie może być pusty.');
        }
        $this->_user = User::findByPasswordResetToken($token);
        if (!$this->_user) {
            throw new InvalidArgumentException('Nieprawidłowy token resetowania hasła.');
        }
        parent::__construct($config);
    }

    public function rules()
    {
        return [
            [['password', 'confirmPassword'], 'required'],
            ['password', 'string', 'min' => 6],
            ['confirmPassword', 'compare', 'compareAttribute' => 'password', 'message' => 'Hasła muszą być identyczne'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'password' => 'Nowe hasło',
            'confirmPassword' => 'Potwierdź hasło',
        ];
    }

    /**
     * Resetuje hasło
     *
     * @return bool jeśli hasło zostało zresetowane
     */
    public function resetPassword()
    {
        if (!$this->validate()) {
            return false;
        }
        
        $user = $this->_user;
        $user->setPassword($this->password);
        $user->removePasswordResetToken();
        $user->generateAuthKey();

        return $user->save(false);
    }
}