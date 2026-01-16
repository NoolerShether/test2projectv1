<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Регистрация нового пользователя БЕЗ номера телефона
     */
    public function register($server_id, $username, $password, $email = null) {
        try {
            // Проверка существования пользователя
            if ($this->userExists($username, $server_id)) {
                return ['success' => false, 'message' => 'Пользователь с таким ником уже существует на этом сервере'];
            }
            
            // Валидация пароля
            if (strlen($password) < 6) {
                return ['success' => false, 'message' => 'Пароль должен содержать минимум 6 символов'];
            }
            
            // Хеширование пароля
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            // ВАЖНО: НЕ добавляем phone_number и phone_balance
            // Они останутся NULL и 0.00 по умолчанию из структуры таблицы
            $sql = "INSERT INTO users (server_id, username, email, password_hash) 
                    VALUES (:server_id, :username, :email, :password_hash)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':server_id' => $server_id,
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $password_hash
            ]);
            
            return [
                'success' => true, 
                'message' => 'Регистрация успешна!',
                'user_id' => $this->db->lastInsertId()
            ];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Ошибка при регистрации: ' . $e->getMessage()];
        }
    }
    
    /**
     * Авторизация пользователя
     */
    public function login($username, $password, $server_id) {
        try {
            $sql = "SELECT * FROM users WHERE username = :username AND server_id = :server_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':username' => $username, ':server_id' => $server_id]);
            
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Проверка блокировки
                if ($user['is_blocked']) {
                    return ['success' => false, 'message' => 'Ваш аккаунт заблокирован'];
                }
                
                // Обновление времени последнего входа
                $update_sql = "UPDATE users SET last_login = NOW() WHERE id = :id";
                $update_stmt = $this->db->prepare($update_sql);
                $update_stmt->execute([':id' => $user['id']]);
                
                // Создание сессии
                $session_token = $this->createSession($user['id']);
                
                return [
                    'success' => true,
                    'message' => 'Вход выполнен успешно',
                    'user' => $this->sanitizeUserData($user),
                    'session_token' => $session_token
                ];
            } else {
                return ['success' => false, 'message' => 'Неверный логин или пароль'];
            }
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Ошибка при входе: ' . $e->getMessage()];
        }
    }
    
    /**
     * Проверка существования пользователя
     */
    private function userExists($username, $server_id) {
        $sql = "SELECT COUNT(*) FROM users WHERE username = :username AND server_id = :server_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':username' => $username, ':server_id' => $server_id]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Создание сессии
     */
    private function createSession($user_id) {
        // Удаление старых сессий пользователя
        $delete_sql = "DELETE FROM sessions WHERE user_id = :user_id";
        $delete_stmt = $this->db->prepare($delete_sql);
        $delete_stmt->execute([':user_id' => $user_id]);
        
        // Создание новой сессии
        $session_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $sql = "INSERT INTO sessions (user_id, session_token, ip_address, user_agent, expires_at) 
                VALUES (:user_id, :session_token, :ip_address, :user_agent, :expires_at)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':session_token' => $session_token,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ':expires_at' => $expires_at
        ]);
        
        return $session_token;
    }
    
    /**
     * Проверка сессии
     */
    public function validateSession($session_token) {
        try {
            $sql = "SELECT s.*, u.* FROM sessions s 
                    JOIN users u ON s.user_id = u.id 
                    WHERE s.session_token = :token AND s.expires_at > NOW()";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $session_token]);
            
            $result = $stmt->fetch();
            
            if ($result) {
                return [
                    'success' => true,
                    'user' => $this->sanitizeUserData($result)
                ];
            }
            
            return ['success' => false, 'message' => 'Сессия истекла'];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Ошибка проверки сессии'];
        }
    }
    
    /**
     * Выход из системы
     */
    public function logout($session_token) {
        try {
            $sql = "DELETE FROM sessions WHERE session_token = :token";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $session_token]);
            
            return ['success' => true, 'message' => 'Выход выполнен'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Ошибка при выходе'];
        }
    }
    
    /**
     * Получение данных пользователя по ID
     */
    public function getUserById($user_id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $user_id]);
        return $this->sanitizeUserData($stmt->fetch());
    }
    
    /**
     * Обновление баланса
     */
    public function updateBalance($user_id, $amount, $type = 'cash') {
        $column = $type === 'donate' ? 'donate_currency' : ($type === 'bank' ? 'bank_balance' : 'cash_balance');
        
        $sql = "UPDATE users SET $column = $column + :amount WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':amount' => $amount, ':id' => $user_id]);
    }
    
    /**
     * Очистка данных пользователя от чувствительной информации
     */
    private function sanitizeUserData($user) {
        if (!$user) return null;
        
        unset($user['password_hash']);
        return $user;
    }
    
    /**
     * Восстановление пароля - отправка email с токеном
     */
    public function resetPassword($username, $server_id, $email) {
        try {
            // Проверка существования пользователя
            $sql = "SELECT id, email, username FROM users WHERE username = :username AND server_id = :server_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':username' => $username, ':server_id' => $server_id]);
            
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Пользователь с таким никнеймом не найден на данном сервере'];
            }
            
            // Проверка что email указан при регистрации
            if (!$user['email']) {
                return ['success' => false, 'message' => 'К этому аккаунту не привязан email. Обратитесь в поддержку.'];
            }
            
            // Проверка совпадения email
            if ($user['email'] !== $email) {
                return ['success' => false, 'message' => 'Указанный email не совпадает с email, привязанным к аккаунту'];
            }
            
            // Генерация токена восстановления
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Удаление старых токенов этого пользователя
            $delete_sql = "DELETE FROM password_reset_tokens WHERE user_id = :user_id";
            $delete_stmt = $this->db->prepare($delete_sql);
            $delete_stmt->execute([':user_id' => $user['id']]);
            
            // Создание нового токена
            $insert_sql = "INSERT INTO password_reset_tokens (user_id, token, expires_at) 
                          VALUES (:user_id, :token, :expires_at)";
            $insert_stmt = $this->db->prepare($insert_sql);
            $insert_stmt->execute([
                ':user_id' => $user['id'],
                ':token' => $token,
                ':expires_at' => $expires_at
            ]);
            
            // ВРЕМЕННО: Показываем ссылку вместо отправки email
            // Это для тестирования без настройки SMTP
            require_once __DIR__ . '/../config/email.php';
            $reset_link = SITE_URL . '/reset-password.php?token=' . $token;
            
            // Попытка отправки email (может не работать если SMTP не настроен)
            try {
                $emailSender = new EmailSender();
                $subject = 'Восстановление пароля - ' . SITE_NAME;
                $body = $this->getPasswordResetEmailTemplate($user['username'], $reset_link);
                $email_sent = $emailSender->send($email, $subject, $body);
            } catch (Exception $e) {
                $email_sent = false;
            }
            
            // Возвращаем успех в любом случае, но показываем ссылку
            if ($email_sent) {
                return [
                    'success' => true, 
                    'message' => 'Инструкции по восстановлению пароля отправлены на ваш email'
                ];
            } else {
                // Если email не отправлен - показываем ссылку прямо в сообщении
                return [
                    'success' => true, 
                    'message' => 'Email не настроен. Используйте эту ссылку для восстановления:',
                    'reset_link' => $reset_link
                ];
            }
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Ошибка при восстановлении пароля: ' . $e->getMessage()];
        }
    }
    
    /**
     * Подтверждение токена и генерация нового пароля
     */
    public function confirmPasswordReset($token) {
        try {
            // Проверка токена
            $sql = "SELECT prt.*, u.id as user_id, u.username, u.email 
                    FROM password_reset_tokens prt
                    JOIN users u ON prt.user_id = u.id
                    WHERE prt.token = :token 
                      AND prt.expires_at > NOW() 
                      AND prt.used = FALSE";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $token]);
            
            $result = $stmt->fetch();
            
            if (!$result) {
                return ['success' => false, 'message' => 'Недействительный или истекший токен восстановления'];
            }
            
            // Генерация случайного пароля
            $new_password = $this->generateRandomPassword();
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            // Обновление пароля пользователя
            $update_sql = "UPDATE users SET password_hash = :password_hash WHERE id = :id";
            $update_stmt = $this->db->prepare($update_sql);
            $update_stmt->execute([
                ':password_hash' => $password_hash,
                ':id' => $result['user_id']
            ]);
            
            // Отметка токена как использованного
            $mark_used_sql = "UPDATE password_reset_tokens SET used = TRUE WHERE id = :id";
            $mark_used_stmt = $this->db->prepare($mark_used_sql);
            $mark_used_stmt->execute([':id' => $result['id']]);
            
            // Отправка нового пароля на email
            require_once __DIR__ . '/../config/email.php';
            $emailSender = new EmailSender();
            
            $subject = 'Ваш новый пароль - ' . SITE_NAME;
            $body = $this->getNewPasswordEmailTemplate($result['username'], $new_password);
            
            $emailSender->send($result['email'], $subject, $body);
            
            return [
                'success' => true,
                'message' => 'Пароль успешно изменен! Новый пароль отправлен на ваш email.',
                'new_password' => $new_password // Для отображения на странице
            ];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Ошибка при смене пароля'];
        }
    }
    
    /**
     * Генерация случайного пароля
     */
    private function generateRandomPassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $chars_length = strlen($chars);
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $chars_length - 1)];
        }
        
        return $password;
    }
    
    /**
     * Шаблон email для восстановления пароля
     */
    private function getPasswordResetEmailTemplate($username, $reset_link) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #FF5722; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                .button { display: inline-block; padding: 12px 30px; background: #FF5722; color: white; 
                         text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; color: #777; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>ZVEZDA RP</h1>
                </div>
                <div class='content'>
                    <h2>Восстановление пароля</h2>
                    <p>Здравствуйте, <strong>{$username}</strong>!</p>
                    <p>Вы запросили восстановление пароля для вашего аккаунта на сервере Zvezda RP.</p>
                    <p>Для создания нового пароля нажмите на кнопку ниже:</p>
                    <p style='text-align: center;'>
                        <a href='{$reset_link}' class='button'>Восстановить пароль</a>
                    </p>
                    <p>Или скопируйте ссылку в браузер:</p>
                    <p style='word-break: break-all; background: #fff; padding: 10px; border: 1px solid #ddd;'>
                        {$reset_link}
                    </p>
                    <p><strong>Важно:</strong> Ссылка действительна в течение 1 часа.</p>
                    <p>Если вы не запрашивали восстановление пароля, просто проигнорируйте это письмо.</p>
                </div>
                <div class='footer'>
                    <p>© 2026 Zvezda RP. Все права защищены.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Шаблон email с новым паролем
     */
    private function getNewPasswordEmailTemplate($username, $new_password) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #00C853; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                .password-box { background: #fff; padding: 20px; border: 2px solid #00C853; 
                               border-radius: 5px; text-align: center; font-size: 24px; 
                               font-weight: bold; letter-spacing: 2px; margin: 20px 0; }
                .footer { text-align: center; color: #777; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✓ Пароль изменен</h1>
                </div>
                <div class='content'>
                    <h2>Ваш новый пароль</h2>
                    <p>Здравствуйте, <strong>{$username}</strong>!</p>
                    <p>Ваш пароль был успешно изменен. Ваш новый пароль:</p>
                    <div class='password-box'>{$new_password}</div>
                    <p><strong>Важные рекомендации:</strong></p>
                    <ul>
                        <li>Сохраните этот пароль в надежном месте</li>
                        <li>Рекомендуем изменить пароль после первого входа</li>
                        <li>Не сообщайте пароль никому, даже администрации</li>
                    </ul>
                    <p>Теперь вы можете войти на сайт используя этот пароль.</p>
                </div>
                <div class='footer'>
                    <p>© 2026 Zvezda RP. Все права защищены.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>
