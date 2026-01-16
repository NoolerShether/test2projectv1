<?php
session_start();
require_once __DIR__ . '/includes/User.php';

// Проверка авторизации
if (!isset($_SESSION['session_token'])) {
    header('Location: index.php');
    exit;
}

$user = new User();
$session = $user->validateSession($_SESSION['session_token']);

if (!$session['success']) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$currentUser = $session['user'];

// Расчет времени до следующего уровня
$exp_needed = ($currentUser['level'] * 100) - $currentUser['experience'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Zvezda RP</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Exo+2:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Декоративные элементы фона -->
    <div class="background-decoration">
        <div class="floating-circle circle-1"></div>
        <div class="floating-circle circle-2"></div>
        <div class="floating-circle circle-3"></div>
    </div>

    <!-- Хедер -->
    <header id="header">
        <div class="container">
            <nav>
                <a href="index.php" class="logo">
                    <div class="logo-icon"></div>
                    <span>ZVEZDA RP</span>
                </a>
                <ul class="nav-menu">
                    <li><a href="index.php">Главная</a></li>
                    <li><a href="index.php#news">Новости</a></li>
                    <li><a href="index.php#shop">Магазин</a></li>
                    <li><a href="#" onclick="alert('Форум в разработке')">Форум</a></li>
                    <li><a href="#" class="btn-login" onclick="logout()">Выход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Личный кабинет -->
    <section class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div class="user-avatar"><?php echo strtoupper(substr($currentUser['username'], 0, 2)); ?></div>
                <div class="user-info">
                    <h2>Привет, <?php echo htmlspecialchars($currentUser['username']); ?></h2>
                    <p>Добро пожаловать в личный кабинет</p>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-icon purple">⭐</div>
                    <div class="stat-value"><?php echo $currentUser['level']; ?> уровень</div>
                    <div class="stat-label">До нового уровня <?php echo $exp_needed; ?> exp</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon red">🎮</div>
                    <div class="stat-value"><?php echo floor($currentUser['play_time'] / 60); ?>ч.</div>
                    <div class="stat-label">Наиграно всего</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">💰</div>
                    <div class="stat-value">₽ <?php echo number_format($currentUser['cash_balance'] + $currentUser['bank_balance'], 0, ',', ' '); ?></div>
                    <div class="stat-label">Сумма ваших накоплений, с учетом имущества</div>
                </div>
            </div>

            <div class="finance-cards">
                <div class="finance-card">
                    <h3>💵 Количество донат-валюты</h3>
                    <div class="balance-amount">₽ <?php echo number_format($currentUser['donate_currency'], 0, ',', ' '); ?></div>
                    <div class="balance-label">Количество донат-валюты</div>
                    <button class="btn-topup" onclick="window.location.href='index.php#shop'">Пополнить счет</button>
                </div>
                
                <div class="finance-card">
                    <h3>💰 Наличные деньги</h3>
                    <div class="balance-amount">₽ <?php echo number_format($currentUser['cash_balance'], 0, ',', ' '); ?></div>
                    <div class="balance-label">Наличные деньги</div>
                </div>
            </div>

            <div class="finance-card">
                <h3>💳 Деньги в банке</h3>
                <div class="balance-amount">₽ <?php echo number_format($currentUser['bank_balance'], 0, ',', ' '); ?></div>
                <div class="balance-label">Деньги в банке</div>
            </div>

            <div class="finance-card" style="margin-top: 30px;">
                <h3>⚙️ МОИ НАСТРОЙКИ</h3>
                <div class="form-group">
                    <label>Имя</label>
                    <input type="text" value="<?php echo htmlspecialchars($currentUser['username']); ?>" readonly>
                </div>
                <?php if ($currentUser['email']): ?>
                <div class="form-group">
                    <label>Email</label>
                    <input type="text" value="<?php echo htmlspecialchars($currentUser['email']); ?>" readonly>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="text" value="<?php echo $currentUser['phone_number'] ? htmlspecialchars($currentUser['phone_number']) : 'Нет'; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Баланс телефона</label>
                    <input type="text" value="<?php echo $currentUser['phone_number'] ? number_format($currentUser['phone_balance'], 0, ',', ' ') . ' ₽' : '0 ₽'; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Предупреждения</label>
                    <input type="text" value="<?php echo $currentUser['warnings']; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Блокировка</label>
                    <input type="text" value="<?php echo $currentUser['is_blocked'] ? 'Да' : 'Нет'; ?>" readonly>
                </div>
                <?php if ($currentUser['last_login']): ?>
                <div class="form-group">
                    <label>Последний вход</label>
                    <input type="text" value="<?php echo date('d.m.Y H:i', strtotime($currentUser['last_login'])); ?>" readonly>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Дата регистрации</label>
                    <input type="text" value="<?php echo date('d.m.Y', strtotime($currentUser['created_at'])); ?>" readonly>
                </div>
            </div>
        </div>
    </section>

    <!-- Футер -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">ZVEZDA GAMES</div>
                <div class="footer-links">
                    <a href="#">Пользовательское соглашение</a>
                    <a href="#">Политика конфиденциальности</a>
                    <a href="#">Поддержка в чате</a>
                    <a href="#">mail@zvezda.games</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>ZVEZDA GAMES © 2026</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        function logout() {
            if (confirm('Вы уверены, что хотите выйти?')) {
                fetch('ajax/logout.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    }
                });
            }
        }
    </script>
</body>
</html>
