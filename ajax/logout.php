<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/User.php';

$user = new User();

if (isset($_SESSION['session_token'])) {
    $user->logout($_SESSION['session_token']);
}

session_destroy();

echo json_encode([
    'success' => true,
    'message' => 'Выход выполнен',
    'redirect' => 'index.php'
]);
?>
