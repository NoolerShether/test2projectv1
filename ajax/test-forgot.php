<?php
/**
 * Простой тест AJAX обработчика восстановления пароля
 */

// Включаем все ошибки
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "TEST START<br>";

// Проверка метода
echo "Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";

// Проверка POST данных
echo "POST data: <pre>";
print_r($_POST);
echo "</pre>";

// Попытка подключить файлы
echo "Trying to load User.php...<br>";
try {
    require_once __DIR__ . '/../includes/User.php';
    echo "✓ User.php loaded<br>";
} catch (Exception $e) {
    echo "✗ Error loading User.php: " . $e->getMessage() . "<br>";
    exit;
}

// Попытка подключить database.php
echo "Trying to load database.php...<br>";
try {
    require_once __DIR__ . '/../config/database.php';
    echo "✓ database.php loaded<br>";
} catch (Exception $e) {
    echo "✗ Error loading database.php: " . $e->getMessage() . "<br>";
    exit;
}

// Попытка подключить email.php
echo "Trying to load email.php...<br>";
try {
    require_once __DIR__ . '/../config/email.php';
    echo "✓ email.php loaded<br>";
} catch (Exception $e) {
    echo "✗ Error loading email.php: " . $e->getMessage() . "<br>";
    exit;
}

// Проверка подключения к БД
echo "Testing database connection...<br>";
try {
    $db = Database::getInstance()->getConnection();
    echo "✓ Database connected<br>";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
    exit;
}

// Если всё ОК
echo "<br><strong>✓ ALL TESTS PASSED!</strong>";
?>
