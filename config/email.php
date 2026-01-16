<?php
/**
 * Конфигурация Email (SMTP)
 * Для отправки писем восстановления пароля
 */

// SMTP настройки для Gmail
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'teamgleb.14@gmail.com');        // ✅ ТВОЙ email
define('SMTP_PASSWORD', 'eglhdunpqmdcdlmc');     // ✅ Вставь пароль приложения
define('SMTP_FROM_EMAIL', 'teamgleb.14@gmail.com');      // ✅ ТАКОЙ ЖЕ как USERNAME!
define('SMTP_FROM_NAME', 'Zvezda RP');

// Настройки сайта
define('SITE_URL', 'https://test2projectv1.vercel.app/');   // ✅ URL твоего сайта
define('SITE_NAME', 'Zvezda RP');

// Класс для отправки email через SMTP
class EmailSender {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        $this->smtp_host = SMTP_HOST;
        $this->smtp_port = SMTP_PORT;
        $this->smtp_username = SMTP_USERNAME;
        $this->smtp_password = SMTP_PASSWORD;
        $this->from_email = SMTP_FROM_EMAIL;
        $this->from_name = SMTP_FROM_NAME;
    }
    
    /**
     * Отправка email
     */
    public function send($to_email, $subject, $body) {
        // Проверка наличия PHPMailer
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return $this->sendWithPHPMailer($to_email, $subject, $body);
        } else {
            // Fallback на встроенную функцию mail()
            return $this->sendWithMailFunction($to_email, $subject, $body);
        }
    }
    
    /**
     * Отправка через PHPMailer
     */
    private function sendWithPHPMailer($to_email, $subject, $body) {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Настройки SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            $mail->CharSet = 'UTF-8';
            
            // Отключаем проверку SSL сертификата (для локального тестирования)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Отправитель и получатель
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to_email);
            
            // Содержимое письма
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Отправка через встроенную функцию mail()
     */
    private function sendWithMailFunction($to_email, $subject, $body) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: {$this->from_name} <{$this->from_email}>" . "\r\n";
        
        return mail($to_email, $subject, $body, $headers);
    }
}
?>
