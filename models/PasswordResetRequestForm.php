<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * Formularz żądania resetowania hasła
 */
class PasswordResetRequestForm extends Model
{
    public $email;

    public function rules()
    {
        return [
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'validateEmailExists'], // Używamy własnej walidacji zamiast exist
        ];
    }

    public function attributeLabels()
    {
        return [
            'email' => 'Adres e-mail',
        ];
    }

    /**
     * Własna walidacja istnienia emaila
     */
    public function validateEmailExists($attribute, $params)
    {
        if (!$this->hasErrors($attribute)) {
            $user = User::findOne([
                'email' => $this->email,
                'status' => User::STATUS_ACTIVE,
            ]);
            
            if (!$user) {
                // Debug - sprawdź co dokładnie się dzieje
                $anyUser = User::findOne(['email' => $this->email]);
                if ($anyUser) {
                    // Użytkownik istnieje ale ma zły status
                    Yii::warning("User exists but has status: {$anyUser->status}, expected: " . User::STATUS_ACTIVE, __METHOD__);
                    $this->addError($attribute, 'Konto użytkownika jest nieaktywne. Skontaktuj się z administratorem.');
                } else {
                    // Użytkownik w ogóle nie istnieje
                    $this->addError($attribute, 'Nie ma użytkownika z takim adresem email.');
                }
            }
        }
    }

    /**
     * Wysyła email z linkiem do resetowania hasła
     *
     * @return bool czy email został wysłany
     */
    public function sendEmail()
    {
        /* @var $user User */
        $user = User::findOne([
            'status' => User::STATUS_ACTIVE,
            'email' => $this->email,
        ]);

        if (!$user) {
            return false;
        }

        if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
            $user->generatePasswordResetToken();
            if (!$user->save()) {
                return false;
            }
        }

        return Yii::$app
            ->mailer
            ->compose(
                ['html' => 'passwordResetToken-html', 'text' => 'passwordResetToken-text'],
                ['user' => $user]
            )
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' - Support'])
            ->setTo($this->email)
            ->setSubject('Reset hasła dla ' . Yii::$app->name)
            ->send();
    }
}