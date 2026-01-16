<?php
/**
 * AJAX обработчик восстановления пароля
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Подключаем необходимые файлы
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/PasswordReset.php';
    
    // Проверка метода запроса
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Недопустимый метод запроса'
        ]);
        exit;
    }
    
    // Получаем данные
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $server_id = $_POST['server_id'] ?? 1;
    
    // Валидация: проверка заполнения
    if (empty($username) || empty($email)) {
        echo json_encode([
            'success' => false,
            'message' => 'Заполните все поля'
        ]);
        exit;
    }
    
    // Валидация: проверка email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Неверный формат email'
        ]);
        exit;
    }
    
    // Подключаемся к БД
    $db = Database::getInstance()->getConnection();
    
    // Создаем объект восстановления пароля
    $passwordReset = new PasswordReset($db);
    
    // Выполняем восстановление
    $result = $passwordReset->requestReset($username, $email, $server_id);
    
    // Возвращаем результат
    echo json_encode($result);
    
} catch (Exception $e) {
    // Логируем ошибку
    error_log("Forgot Password Error: " . $e->getMessage());
    
    // Возвращаем ошибку пользователю
    echo json_encode([
        'success' => false,
        'message' => 'Произошла ошибка. Попробуйте позже.'
    ]);
}
?>
