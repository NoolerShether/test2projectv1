<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/User.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Недопустимый метод запроса']);
    exit;
}

// Получение данных
$server_id = $_POST['server_id'] ?? 1;
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$email = trim($_POST['email'] ?? null);

// Валидация
if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Заполните все обязательные поля']);
    exit;
}

if ($password !== $password_confirm) {
    echo json_encode(['success' => false, 'message' => 'Пароли не совпадают']);
    exit;
}

// Валидация никнейма
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Никнейм должен содержать от 3 до 20 символов (латиница, цифры, подчеркивание)']);
    exit;
}

// Валидация email если указан
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Неверный формат email']);
    exit;
}

// Регистрация
$user = new User();
$result = $user->register($server_id, $username, $password, $email);

if ($result['success']) {
    // Автоматический вход после регистрации
    $login_result = $user->login($username, $password, $server_id);
    
    if ($login_result['success']) {
        $_SESSION['user_id'] = $login_result['user']['id'];
        $_SESSION['session_token'] = $login_result['session_token'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Регистрация успешна! Перенаправление...',
            'redirect' => 'dashboard.php'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Регистрация успешна! Войдите в систему.',
            'redirect' => 'index.php'
        ]);
    }
} else {
    echo json_encode($result);
}
?>
