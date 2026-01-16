<?php
session_start();
require_once __DIR__ . '/includes/User.php';

$token = $_GET['token'] ?? '';
$message = '';
$success = false;
$new_password = '';

if ($token) {
    $user = new User();
    $result = $user->confirmPasswordReset($token);
    
    $success = $result['success'];
    $message = $result['message'];
    
    if ($success && isset($result['new_password'])) {
        $new_password = $result['new_password'];
    }
} else {
    $message = 'Недействительная ссылка восстановления пароля';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Восстановление пароля - Zvezda RP</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Exo+2:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .reset-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .reset-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        
        .reset-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        
        .reset-icon.success {
            background: #D4EDDA;
            color: #155724;
        }
        
        .reset-icon.error {
            background: #F8D7DA;
            color: #721C24;
        }
        
        .reset-card h1 {
            font-family: 'Exo 2', sans-serif;
            font-size: 32px;
            margin-bottom: 20px;
            color: #212121;
        }
        
        .reset-card p {
            font-size: 16px;
            color: #757575;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .password-display {
            background: #F5F5F5;
            border: 2px solid #00C853;
            border-radius: 10px;
            padding: 20px;
            margin: 30px 0;
            font-family: 'Courier New', monospace;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #212121;
            word-break: break-all;
            position: relative;
        }
        
        .copy-btn {
            background: #FF5722;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin: 10px 5px;
            transition: all 0.3s;
        }
        
        .copy-btn:hover {
            background: #E64A19;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 87, 34, 0.3);
        }
        
        .back-btn {
            background: #1565C0;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            display: inline-block;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: #0D47A1;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(21, 101, 192, 0.3);
        }
        
        .instructions {
            background: #FFF3CD;
            border: 1px solid #FFE082;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            text-align: left;
        }
        
        .instructions h3 {
            color: #856404;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .instructions ul {
            color: #856404;
            margin: 0;
            padding-left: 20px;
        }
        
        .instructions li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <?php if ($success): ?>
                <div class="reset-icon success">✓</div>
                <h1>Пароль изменен!</h1>
                <p><?php echo htmlspecialchars($message); ?></p>
                
                <?php if ($new_password): ?>
                    <div class="password-display" id="newPassword">
                        <?php echo htmlspecialchars($new_password); ?>
                    </div>
                    
                    <button class="copy-btn" onclick="copyPassword()">
                        📋 Скопировать пароль
                    </button>
                    
                    <div class="instructions">
                        <h3>⚠️ Важно:</h3>
                        <ul>
                            <li>Сохраните этот пароль в надежном месте</li>
                            <li>Новый пароль также отправлен на ваш email</li>
                            <li>Рекомендуем изменить пароль после входа</li>
                            <li>Не сообщайте пароль никому</li>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <a href="index.php" class="back-btn">🏠 Вернуться на главную</a>
                
            <?php else: ?>
                <div class="reset-icon error">✗</div>
                <h1>Ошибка</h1>
                <p><?php echo htmlspecialchars($message); ?></p>
                
                <div class="instructions">
                    <h3>Возможные причины:</h3>
                    <ul>
                        <li>Ссылка восстановления истекла (действительна 1 час)</li>
                        <li>Ссылка уже была использована</li>
                        <li>Ссылка повреждена или неверная</li>
                    </ul>
                    <p style="margin-top: 15px; color: #856404;">
                        Попробуйте запросить восстановление пароля заново.
                    </p>
                </div>
                
                <a href="index.php" class="back-btn">🏠 Вернуться на главную</a>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function copyPassword() {
            const passwordText = document.getElementById('newPassword').textContent.trim();
            
            // Копирование в буфер обмена
            navigator.clipboard.writeText(passwordText).then(function() {
                // Изменение текста кнопки
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = '✓ Скопировано!';
                btn.style.background = '#00C853';
                
                // Возврат текста через 2 секунды
                setTimeout(function() {
                    btn.textContent = originalText;
                    btn.style.background = '#FF5722';
                }, 2000);
            }).catch(function(err) {
                alert('Не удалось скопировать пароль. Пожалуйста, скопируйте его вручную.');
            });
        }
    </script>
</body>
</html>
