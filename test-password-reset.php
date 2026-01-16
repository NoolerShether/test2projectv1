<?php
/**
 * ДИАГНОСТИКА СИСТЕМЫ ВОССТАНОВЛЕНИЯ ПАРОЛЯ
 * Откройте этот файл в браузере: https://zvezda-rp/test-password-reset.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Диагностика системы восстановления пароля</h1>";
echo "<hr>";

$errors = [];
$warnings = [];
$success = [];

// ========================================
// ТЕСТ 1: Проверка файлов
// ========================================
echo "<h2>📁 Тест 1: Проверка файлов</h2>";

$files = [
    'config/database.php' => 'Конфигурация БД',
    'config/email.php' => 'Конфигурация Email',
    'includes/User.php' => 'Класс User',
    'includes/PasswordReset.php' => 'Класс PasswordReset',
    'ajax/forgot-password.php' => 'AJAX обработчик',
    'assets/js/main.js' => 'JavaScript'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ <strong>$description</strong>: $file<br>";
        $success[] = $file;
    } else {
        echo "❌ <strong>$description</strong>: $file <span style='color:red;'>НЕ НАЙДЕН!</span><br>";
        $errors[] = "$file не найден";
    }
}

echo "<hr>";

// ========================================
// ТЕСТ 2: Подключение config/database.php
// ========================================
echo "<h2>🗄️ Тест 2: Подключение к БД</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    echo "✅ config/database.php подключен<br>";
    
    $db = Database::getInstance()->getConnection();
    echo "✅ Подключение к БД успешно<br>";
    
    // Проверка таблицы users
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Таблица 'users' существует<br>";
    } else {
        echo "❌ Таблица 'users' <span style='color:red;'>НЕ НАЙДЕНА!</span><br>";
        $errors[] = "Таблица users не найдена";
    }
    
    // Проверка таблицы password_reset_tokens
    $stmt = $db->query("SHOW TABLES LIKE 'password_reset_tokens'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Таблица 'password_reset_tokens' существует<br>";
    } else {
        echo "❌ Таблица 'password_reset_tokens' <span style='color:red;'>НЕ НАЙДЕНА!</span><br>";
        $errors[] = "Таблица password_reset_tokens не найдена";
        echo "<div style='background:#fff3cd;padding:10px;margin:10px 0;border-left:4px solid #ffc107;'>";
        echo "<strong>Решение:</strong> Выполните SQL из файла password_reset.sql в phpMyAdmin";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "❌ <span style='color:red;'>Ошибка БД: " . $e->getMessage() . "</span><br>";
    $errors[] = "Ошибка подключения к БД: " . $e->getMessage();
}

echo "<hr>";

// ========================================
// ТЕСТ 3: Подключение config/email.php
// ========================================
echo "<h2>📧 Тест 3: Конфигурация Email</h2>";

try {
    require_once __DIR__ . '/config/email.php';
    echo "✅ config/email.php подключен<br>";
    
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;margin:10px 0;'>";
    echo "<tr><th>Параметр</th><th>Значение</th><th>Статус</th></tr>";
    
    // SMTP_HOST
    echo "<tr><td>SMTP_HOST</td><td>" . SMTP_HOST . "</td>";
    echo (SMTP_HOST === 'smtp.gmail.com') ? "<td style='color:green;'>✅ OK</td>" : "<td style='color:orange;'>⚠️ Проверь</td>";
    echo "</tr>";
    
    // SMTP_PORT
    echo "<tr><td>SMTP_PORT</td><td>" . SMTP_PORT . "</td>";
    echo (SMTP_PORT == 587) ? "<td style='color:green;'>✅ OK</td>" : "<td style='color:orange;'>⚠️ Проверь</td>";
    echo "</tr>";
    
    // SMTP_USERNAME
    echo "<tr><td>SMTP_USERNAME</td><td>" . SMTP_USERNAME . "</td>";
    if (SMTP_USERNAME === 'YOUR_EMAIL@gmail.com' || SMTP_USERNAME === 'teamgleb.14@gmail.com') {
        if (SMTP_USERNAME === 'YOUR_EMAIL@gmail.com') {
            echo "<td style='color:red;'>❌ НЕ НАСТРОЕН</td>";
            $errors[] = "SMTP_USERNAME не настроен";
        } else {
            echo "<td style='color:green;'>✅ OK</td>";
        }
    } else {
        echo "<td style='color:green;'>✅ OK</td>";
    }
    echo "</tr>";
    
    // SMTP_PASSWORD
    echo "<tr><td>SMTP_PASSWORD</td><td>" . str_repeat('*', strlen(SMTP_PASSWORD)) . " (" . strlen(SMTP_PASSWORD) . " символов)</td>";
    if (SMTP_PASSWORD === 'YOUR_APP_PASSWORD' || SMTP_PASSWORD === 'ТВОЙ_ПАРОЛЬ_БЕЗ_ПРОБЕЛОВ') {
        echo "<td style='color:red;'>❌ НЕ НАСТРОЕН</td>";
        $errors[] = "SMTP_PASSWORD не настроен";
    } elseif (strlen(SMTP_PASSWORD) == 16) {
        echo "<td style='color:green;'>✅ OK (16 символов)</td>";
    } else {
        echo "<td style='color:orange;'>⚠️ Должно быть 16 символов</td>";
        $warnings[] = "SMTP_PASSWORD должен быть 16 символов";
    }
    echo "</tr>";
    
    // SMTP_FROM_EMAIL
    echo "<tr><td>SMTP_FROM_EMAIL</td><td>" . SMTP_FROM_EMAIL . "</td>";
    if (SMTP_FROM_EMAIL === SMTP_USERNAME) {
        echo "<td style='color:green;'>✅ Совпадает с USERNAME</td>";
    } else {
        echo "<td style='color:red;'>❌ НЕ СОВПАДАЕТ!</td>";
        $errors[] = "SMTP_FROM_EMAIL должен совпадать с SMTP_USERNAME";
    }
    echo "</tr>";
    
    // SITE_URL
    echo "<tr><td>SITE_URL</td><td>" . SITE_URL . "</td>";
    if (SITE_URL === 'https://zvezda-rp') {
        echo "<td style='color:green;'>✅ OK</td>";
    } else {
        echo "<td style='color:orange;'>⚠️ Проверь</td>";
        $warnings[] = "SITE_URL может быть неправильным";
    }
    echo "</tr>";
    
    echo "</table>";
    
    // Проверка класса EmailSender
    if (class_exists('EmailSender')) {
        echo "✅ Класс EmailSender существует<br>";
    } else {
        echo "❌ Класс EmailSender <span style='color:red;'>НЕ НАЙДЕН!</span><br>";
        $errors[] = "Класс EmailSender не найден";
    }
    
} catch (Exception $e) {
    echo "❌ <span style='color:red;'>Ошибка: " . $e->getMessage() . "</span><br>";
    $errors[] = "Ошибка config/email.php: " . $e->getMessage();
}

echo "<hr>";

// ========================================
// ТЕСТ 4: Класс PasswordReset
// ========================================
echo "<h2>🔐 Тест 4: Класс PasswordReset</h2>";

try {
    if (file_exists(__DIR__ . '/includes/PasswordReset.php')) {
        require_once __DIR__ . '/includes/PasswordReset.php';
        echo "✅ PasswordReset.php подключен<br>";
        
        if (class_exists('PasswordReset')) {
            echo "✅ Класс PasswordReset существует<br>";
            
            // Проверка методов
            $methods = get_class_methods('PasswordReset');
            if (in_array('requestReset', $methods)) {
                echo "✅ Метод requestReset существует<br>";
            } else {
                echo "❌ Метод requestReset <span style='color:red;'>НЕ НАЙДЕН!</span><br>";
                $errors[] = "Метод requestReset не найден";
            }
        } else {
            echo "❌ Класс PasswordReset <span style='color:red;'>НЕ НАЙДЕН!</span><br>";
            $errors[] = "Класс PasswordReset не найден";
        }
    } else {
        echo "❌ Файл PasswordReset.php <span style='color:red;'>НЕ НАЙДЕН!</span><br>";
        $errors[] = "Файл PasswordReset.php не найден";
    }
} catch (Exception $e) {
    echo "❌ <span style='color:red;'>Ошибка: " . $e->getMessage() . "</span><br>";
    $errors[] = "Ошибка PasswordReset: " . $e->getMessage();
}

echo "<hr>";

// ========================================
// ТЕСТ 5: Проверка PHPMailer
// ========================================
echo "<h2>📬 Тест 5: PHPMailer</h2>";

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "✅ vendor/autoload.php найден<br>";
    require_once __DIR__ . '/vendor/autoload.php';
    
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "✅ PHPMailer установлен<br>";
        $version = PHPMailer\PHPMailer\PHPMailer::VERSION;
        echo "✅ Версия PHPMailer: <strong>$version</strong><br>";
    } else {
        echo "⚠️ PHPMailer не установлен (будет работать без email)<br>";
        $warnings[] = "PHPMailer не установлен - пароль будет показываться на экране";
    }
} else {
    echo "⚠️ vendor/autoload.php не найден (будет работать без email)<br>";
    $warnings[] = "PHPMailer не установлен - пароль будет показываться на экране";
}

echo "<hr>";

// ========================================
// ТЕСТ 6: Проверка тестового пользователя
// ========================================
echo "<h2>👤 Тест 6: Тестовый пользователь</h2>";

try {
    if (isset($db)) {
        $stmt = $db->prepare("SELECT username, email FROM users WHERE username = 'TestUser'");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user) {
            echo "✅ Пользователь TestUser найден<br>";
            echo "Username: <strong>" . htmlspecialchars($user['username']) . "</strong><br>";
            echo "Email: <strong>" . htmlspecialchars($user['email']) . "</strong><br>";
            
            if (empty($user['email'])) {
                echo "⚠️ Email не привязан к аккаунту<br>";
                $warnings[] = "Email не привязан к TestUser";
            }
        } else {
            echo "⚠️ Пользователь TestUser не найден (создайте для теста)<br>";
            $warnings[] = "Тестовый пользователь не создан";
        }
    }
} catch (Exception $e) {
    echo "❌ <span style='color:red;'>Ошибка: " . $e->getMessage() . "</span><br>";
}

echo "<hr>";

// ========================================
// ИТОГОВЫЙ ОТЧЕТ
// ========================================
echo "<h2>📊 Итоговый отчет</h2>";

if (empty($errors)) {
    echo "<div style='background:#d4edda;padding:15px;border-left:4px solid #28a745;margin:10px 0;'>";
    echo "<h3 style='color:#155724;margin:0 0 10px 0;'>✅ ВСЕ ТЕСТЫ ПРОЙДЕНЫ!</h3>";
    echo "Система восстановления пароля готова к использованию.";
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da;padding:15px;border-left:4px solid #dc3545;margin:10px 0;'>";
    echo "<h3 style='color:#721c24;margin:0 0 10px 0;'>❌ НАЙДЕНЫ ОШИБКИ:</h3>";
    echo "<ol style='margin:5px 0;padding-left:20px;'>";
    foreach ($errors as $error) {
        echo "<li style='color:#721c24;'>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ol>";
    echo "</div>";
}

if (!empty($warnings)) {
    echo "<div style='background:#fff3cd;padding:15px;border-left:4px solid #ffc107;margin:10px 0;'>";
    echo "<h3 style='color:#856404;margin:0 0 10px 0;'>⚠️ ПРЕДУПРЕЖДЕНИЯ:</h3>";
    echo "<ol style='margin:5px 0;padding-left:20px;'>";
    foreach ($warnings as $warning) {
        echo "<li style='color:#856404;'>" . htmlspecialchars($warning) . "</li>";
    }
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>🔧 Что делать дальше?</h3>";

if (!empty($errors)) {
    echo "<ol>";
    
    if (in_array("Таблица password_reset_tokens не найдена", $errors)) {
        echo "<li><strong>Создать таблицу:</strong> Выполните SQL из файла <code>password_reset.sql</code> в phpMyAdmin</li>";
    }
    
    if (in_array("Файл PasswordReset.php не найден", $errors)) {
        echo "<li><strong>Скопировать файл:</strong> <code>includes/PasswordReset.php</code> из архива</li>";
    }
    
    if (strpos(implode(',', $errors), 'SMTP') !== false) {
        echo "<li><strong>Настроить Email:</strong> Откройте <code>config/email.php</code> и укажите правильные данные</li>";
    }
    
    echo "</ol>";
} else {
    echo "<p style='color:green;'>✅ Всё готово! Попробуйте восстановить пароль на сайте.</p>";
}

echo "<hr>";
echo "<p><a href='https://zvezda-rp' style='display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>🏠 Вернуться на главную</a></p>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 1000px;
        margin: 20px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h1 {
        color: #333;
        border-bottom: 3px solid #007bff;
        padding-bottom: 10px;
    }
    h2 {
        color: #555;
        margin-top: 30px;
    }
    hr {
        border: none;
        border-top: 1px solid #ddd;
        margin: 20px 0;
    }
    table {
        width: 100%;
        background: white;
    }
    th {
        background: #007bff;
        color: white;
        text-align: left;
    }
</style>
