<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/User.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Недопустимый метод запроса']);
    exit;
}

// Получение данных
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$server_id = $_POST['server_id'] ?? 1;

// Валидация
if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
    exit;
}

// Авторизация
$user = new User();
$result = $user->login($username, $password, $server_id);

if ($result['success']) {
    $_SESSION['user_id'] = $result['user']['id'];
    $_SESSION['session_token'] = $result['session_token'];
    $_SESSION['username'] = $result['user']['username'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Вход выполнен успешно!',
        'redirect' => 'dashboard.php'
    ]);
} else {
    echo json_encode($result);
}
?>
