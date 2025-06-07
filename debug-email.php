<?php
/**
 * Prosty skrypt do sprawdzenia email w bazie danych
 * Uruchom: php simple-debug.php email@uzytkownika.com
 */

if (!isset($argv[1])) {
    echo "Użycie: php simple-debug.php email@uzytkownika.com\n";
    exit(1);
}

$email = trim($argv[1]);

// Wczytaj konfigurację bazy danych
$dbConfig = require __DIR__ . '/config/db.php';

// Utwórz połączenie PDO
try {
    $dsn = $dbConfig['dsn'];
    $username = $dbConfig['username'];
    $password = $dbConfig['password'];
    
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 Debugowanie email: {$email}\n\n";
    
    // Test 1: Wszystcy użytkownicy
    echo "=== TEST 1: Wszyscy użytkownicy ===\n";
    $stmt = $pdo->prepare("SELECT id, username, email, status, CHAR_LENGTH(email) as email_len FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $match = ($user['email'] === $email) ? " ✅ MATCH!" : "";
        echo sprintf("ID: %d | User: %s | Email: '%s' (%d chars) | Status: %d%s\n", 
            $user['id'], $user['username'], $user['email'], $user['email_len'], $user['status'], $match);
    }
    
    // Test 2: Szukaj konkretnego emaila
    echo "\n=== TEST 2: Szukanie konkretnego emaila ===\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ Znaleziono użytkownika:\n";
        echo "   ID: " . $user['id'] . "\n";
        echo "   Username: " . $user['username'] . "\n";
        echo "   Email: '" . $user['email'] . "'\n";
        echo "   Status: " . $user['status'] . "\n";
        
        if ($user['status'] == 1) {
            echo "   ✅ Status jest prawidłowy (aktywny)\n";
        } else {
            echo "   ❌ Status nieprawidłowy (oczekiwano: 1, jest: " . $user['status'] . ")\n";
        }
    } else {
        echo "❌ Nie znaleziono użytkownika\n";
    }
    
    // Test 3: Sprawdź case-insensitive
    echo "\n=== TEST 3: Ignoruj wielkość liter ===\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(?)");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ Znaleziono ignorując wielkość liter:\n";
        echo "   Email w bazie: '" . $user['email'] . "'\n";
        echo "   Podany email:  '" . $email . "'\n";
        if ($user['email'] !== $email) {
            echo "   ⚠️  Różnica w wielkości liter!\n";
        }
    } else {
        echo "❌ Nie znaleziono nawet ignorując wielkość liter\n";
    }
    
    // Test 4: Sprawdź białe znaki
    echo "\n=== TEST 4: Sprawdzenie białych znaków ===\n";
    echo "Długość podanego email: " . strlen($email) . "\n";
    echo "Email w hex: " . bin2hex($email) . "\n";
    
    $stmt = $pdo->prepare("SELECT email, HEX(email) as email_hex FROM users WHERE email LIKE ?");
    $stmt->execute(['%' . trim($email) . '%']);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($users) {
        echo "Podobne emaile w bazie:\n";
        foreach ($users as $user) {
            echo "   Email: '" . $user['email'] . "' | Hex: " . $user['email_hex'] . "\n";
        }
    }
    
    // Test 5: Sprawdź tylko aktywnych
    echo "\n=== TEST 5: Tylko aktywni użytkownicy ===\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 1");
    $stmt->execute([$email]);
    $activeUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($activeUser) {
        echo "✅ Znaleziono aktywnego użytkownika\n";
    } else {
        echo "❌ Brak aktywnego użytkownika z tym emailem\n";
        
        // Sprawdź czy istnieje ale nieaktywny
        $stmt = $pdo->prepare("SELECT status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userStatus = $stmt->fetchColumn();
        
        if ($userStatus !== false) {
            echo "   ⚠️  Użytkownik istnieje ale ma status: $userStatus (powinien być: 1)\n";
        }
    }
    
    // PODSUMOWANIE
    echo "\n=== PODSUMOWANIE I ROZWIĄZANIE ===\n";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $foundUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$foundUser) {
        echo "❌ Email nie istnieje w bazie danych\n";
        echo "💡 Sprawdź czy email jest dokładnie taki jak w bazie\n";
        echo "💡 Sprawdź czy nie ma literówek\n";
    } elseif ($foundUser['status'] != 1) {
        echo "⚠️  Email istnieje ale użytkownik ma nieprawidłowy status: " . $foundUser['status'] . "\n";
        echo "💡 ROZWIĄZANIE: Uruchom to zapytanie SQL:\n";
        echo "   UPDATE users SET status = 1 WHERE email = '{$email}';\n";
    } else {
        echo "✅ Email istnieje i użytkownik jest aktywny\n";
        echo "🐛 Problem może być w kodzie PasswordResetRequestForm\n";
        echo "💡 Sprawdź czy model używa prawidłowych stałych statusu\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Błąd połączenia z bazą danych: " . $e->getMessage() . "\n";
    echo "💡 Sprawdź konfigurację w config/db.php\n";
}