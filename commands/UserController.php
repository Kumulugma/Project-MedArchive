<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\User;

class UserController extends Controller
{
    public function actionCreateAdmin($username = 'admin', $password = 'admin123', $email = 'admin@medarchive.local')
    {
        $user = new User();
        $user->username = $username;
        $user->email = $email;
        $user->setPassword($password);
        $user->status = User::STATUS_ACTIVE;
        
        if ($user->save()) {
            echo "Użytkownik admin został utworzony pomyślnie.\n";
            echo "Login: {$username}\n";
            echo "Hasło: {$password}\n";
            echo "Email: {$email}\n";
            return ExitCode::OK;
        } else {
            echo "Błąd podczas tworzenia użytkownika:\n";
            foreach ($user->errors as $attribute => $errors) {
                echo "{$attribute}: " . implode(', ', $errors) . "\n";
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}