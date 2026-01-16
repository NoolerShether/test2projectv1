<?php
/**
 * Класс для восстановления пароля
 */

class PasswordReset {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Шаг 1: Запрос восстановления пароля
     * 
     * @param string $username - Никнейм пользователя
     * @param string $email - Email пользователя
     * @param int $server_id - ID сервера
     * @return array - Результат операции
     */
    public function requestReset($username, $email, $server_id = 1) {
        try {
            // 1. Ищем пользователя
            $sql = "SELECT id, email, username FROM users 
                    WHERE username = :username AND server_id = :server_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':server_id' => $server_id
            ]);
            
            $user = $stmt->fetch();
            
            // Проверка: пользователь существует?
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Пользователь с таким никнеймом не найден на данном сервере'
                ];
            }
            
            // Проверка: email привязан?
            if (!$user['email']) {
                return [
                    'success' => false,
                    'message' => 'К этому аккаунту не привязан email. Обратитесь в поддержку.'
                ];
            }
            
            // Проверка: email совпадает?
            if ($user['email'] !== $email) {
                return [
                    'success' => false,
                    'message' => 'Указанный email не совпадает с email аккаунта'
                ];
            }
            
            // 2. Генерируем новый пароль
            $newPassword = $this->generatePassword();
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            
            // 3. Генерируем токен
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // 4. Удаляем старые токены пользователя
            $deleteSql = "DELETE FROM password_reset_tokens WHERE user_id = :user_id";
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteStmt->execute([':user_id' => $user['id']]);
            
            // 5. Сохраняем новый токен
            $insertSql = "INSERT INTO password_reset_tokens 
                         (user_id, token, new_password, expires_at) 
                         VALUES (:user_id, :token, :new_password, :expires_at)";
            $insertStmt = $this->db->prepare($insertSql);
            $insertStmt->execute([
                ':user_id' => $user['id'],
                ':token' => $token,
                ':new_password' => $passwordHash,
                ':expires_at' => $expiresAt
            ]);
            
            // 6. Отправляем email
            require_once __DIR__ . '/../config/email.php';
            $emailSender = new EmailSender();
            
            $subject = 'Восстановление пароля - ' . SITE_NAME;
            $body = $this->getEmailTemplate($user['username'], $newPassword);
            
            $emailSent = $emailSender->send($email, $subject, $body);
            
            if ($emailSent) {
                return [
                    'success' => true,
                    'message' => 'Новый пароль отправлен на ваш email!',
                    'password_preview' => substr($newPassword, 0, 4) . '...'
                ];
            } else {
                // Если email не отправлен - показываем пароль
                return [
                    'success' => true,
                    'message' => 'Ваш новый пароль:',
                    'new_password' => $newPassword,
                    'note' => 'Email не настроен. Сохраните этот пароль!'
                ];
            }
            
        } catch (PDOException $e) {
            error_log("Password Reset Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка сервера. Попробуйте позже.'
            ];
        }
    }
    
    /**
     * Генерация случайного пароля
     * 
     * @param int $length - Длина пароля
     * @return string - Сгенерированный пароль
     */
    private function generatePassword($length = 12) {
        // Символы для пароля
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*';
        
        $allChars = $lowercase . $uppercase . $numbers . $symbols;
        
        $password = '';
        
        // Обязательно добавляем по одному символу каждого типа
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        // Заполняем остальное случайными символами
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Перемешиваем символы
        $password = str_shuffle($password);
        
        return $password;
    }
    
    /**
     * Шаблон email с новым паролем
     */
    private function getEmailTemplate($username, $newPassword) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: white;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                }
                .content {
                    padding: 40px 30px;
                }
                .password-box {
                    background: #f8f9fa;
                    border: 2px solid #00C853;
                    border-radius: 10px;
                    padding: 20px;
                    margin: 30px 0;
                    text-align: center;
                }
                .password {
                    font-family: 'Courier New', monospace;
                    font-size: 28px;
                    font-weight: bold;
                    color: #00C853;
                    letter-spacing: 2px;
                    word-break: break-all;
                }
                .warning {
                    background: #fff3cd;
                    border-left: 4px solid #ffc107;
                    padding: 15px;
                    margin: 20px 0;
                }
                .warning h3 {
                    margin: 0 0 10px 0;
                    color: #856404;
                }
                .warning ul {
                    margin: 0;
                    padding-left: 20px;
                    color: #856404;
                }
                .footer {
                    background: #f8f9fa;
                    padding: 20px;
                    text-align: center;
                    color: #777;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Восстановление пароля</h1>
                </div>
                <div class='content'>
                    <p>Здравствуйте, <strong>{$username}</strong>!</p>
                    <p>Вы запросили восстановление пароля для вашего аккаунта.</p>
                    <p>Ваш новый пароль:</p>
                    
                    <div class='password-box'>
                        <div class='password'>{$newPassword}</div>
                    </div>
                    
                    <div class='warning'>
                        <h3>⚠️ Важные рекомендации:</h3>
                        <ul>
                            <li>Сохраните этот пароль в надежном месте</li>
                            <li>Рекомендуем изменить пароль после входа</li>
                            <li>Не сообщайте пароль никому, даже администрации</li>
                            <li>Это письмо было отправлено автоматически</li>
                        </ul>
                    </div>
                    
                    <p>Теперь вы можете войти на сайт используя этот пароль.</p>
                    <p>Если вы не запрашивали восстановление пароля, срочно обратитесь в поддержку!</p>
                </div>
                <div class='footer'>
                    <p>© 2026 " . SITE_NAME . ". Все права защищены.</p>
                    <p>Это автоматическое письмо. Не отвечайте на него.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>
