<?php
/**
 * Prosty skrypt do naprawy problemu z logowaniem
 * Uruchom: php fix-login.php nazwa_uzytkownika
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/web.php';

if (!isset($argv[1])) {
    echo "Użycie: php fix-login.php nazwa_uzytkownika [nowe_haslo]\n";
    echo "Przykład: php fix-login.php admin admin123\n";
    exit(1);
}

$username = $argv[1];
$newPassword = $argv[2] ?? 'admin123';

// Inicjalizuj Yii
$config = require __DIR__ . '/config/web.php';
new yii\web\Application($config);

echo "Naprawianie użytkownika: {$username}\n";

// Znajdź użytkownika bez ograniczeń statusu
$user = \app\models\User::find()->where(['username' => $username])->one();

if (!$user) {
    echo "❌ Nie znaleziono użytkownika: {$username}\n";
    
    // Lista wszystkich użytkowników
    $allUsers = \app\models\User::find()->select(['username', 'email', 'status'])->asArray()->all();
    echo "\nDostępni użytkownicy:\n";
    foreach ($allUsers as $u) {
        echo "- {$u['username']} (email: {$u['email']}, status: {$u['status']})\n";
    }
    exit(1);
}

echo "✅ Znaleziono użytkownika ID: {$user->id}\n";
echo "   Username: {$user->username}\n";
echo "   Email: {$user->email}\n";
echo "   Status: {$user->status}\n";
echo "   Auth key: " . ($user->auth_key ? 'OK' : 'BRAK') . "\n";

// Napraw użytkownika
$user->status = 1; // STATUS_ACTIVE
$user->setPassword($newPassword);
$user->generateAuthKey();

if ($user->save(false)) {
    echo "✅ Użytkownik naprawiony!\n";
    echo "   Nowe hasło: {$newPassword}\n";
    echo "   Status: 1 (aktywny)\n";
    echo "   Auth key: wygenerowany\n";
    echo "\nMożesz się teraz zalogować używając:\n";
    echo "   Username: {$username}\n";
    echo "   Password: {$newPassword}\n";
} else {
    echo "❌ Błąd podczas zapisywania:\n";
    foreach ($user->errors as $attr => $errors) {
        echo "   {$attr}: " . implode(', ', $errors) . "\n";
    }
}

// Wyczyść cache sesji
echo "\nCzyszczenie cache...\n";
if (file_exists(__DIR__ . '/runtime/cache')) {
    $files = glob(__DIR__ . '/runtime/cache/*');
    foreach ($files as $file) {
        if (is_file($file)) unlink($file);
    }
    echo "✅ Cache wyczyszczony\n";
}

echo "\nSpróbuj się teraz zalogować!\n";