<?php
session_start();
require_once __DIR__ . '/includes/User.php';

// Проверка авторизации
$isLoggedIn = false;
$currentUser = null;

if (isset($_SESSION['session_token'])) {
    $user = new User();
    $session = $user->validateSession($_SESSION['session_token']);
    
    if ($session['success']) {
        $isLoggedIn = true;
        $currentUser = $session['user'];
    } else {
        session_destroy();
    }
}

// Если пользователь авторизован, перенаправляем в личный кабинет
if ($isLoggedIn && basename($_SERVER['PHP_SELF']) == 'index.php') {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zvezda RP - Будь на высоте вместе с нами</title>
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
                    <li><a href="#news">Новости</a></li>
                    <li><a href="#shop">Магазин</a></li>
                    <li><a href="#" onclick="alert('Форум в разработке')">Форум</a></li>
                    <li><a href="#" class="btn-login" onclick="openModal('login')">Личный кабинет</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Главная страница -->
    <section id="home-section" class="page-section">
        <!-- Главный экран -->
        <section class="hero">
            <div class="container">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1>ZVEZDA<br><span>RP</span></h1>
                        <p>Будь на высоте вместе с нами, присоединяйся ✨</p>
                        <div class="hero-buttons">
                            <button class="btn-primary" onclick="openModal('login')">
                                Начать играть ▶
                            </button>
                        </div>
                    </div>
                    <div class="hero-image">
                        <svg width="600" height="500" viewBox="0 0 600 500">
                            <defs>
                                <linearGradient id="heroGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#1565C0;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#FF5722;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <rect width="600" height="500" rx="30" fill="url(#heroGrad)" opacity="0.1"/>
                            <circle cx="300" cy="200" r="80" fill="url(#heroGrad)" opacity="0.3"/>
                            <path d="M300,100 L320,160 L380,170 L330,210 L345,270 L300,240 L255,270 L270,210 L220,170 L280,160 Z" fill="#FFC107" opacity="0.8"/>
                            <text x="300" y="380" font-family="Exo 2" font-size="72" font-weight="900" text-anchor="middle" fill="#1565C0">21 000</text>
                            <text x="300" y="420" font-family="Montserrat" font-size="24" text-anchor="middle" fill="#757575">игроков онлайн</text>
                        </svg>
                        <div class="stats-card">
                            <h3>15 247</h3>
                            <p>игроков онлайн</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Секция "Начать играть" -->
        <section class="start-section">
            <div class="container">
                <div class="start-content">
                    <div class="start-text">
                        <h2>НАЧИНАЙ ИГРАТЬ</h2>
                        <h3>В ПАРУ КЛИКОВ</h3>
                        <p>Скачайте наш собственный лаунчер, выберите ZVEZDA Roleplay, введите ник и нажмите кнопку Играть.</p>
                        <button class="btn-primary">Скачать ZVEZDA Launcher ⬇</button>
                    </div>
                    <div class="start-image">
                        <div class="launcher-preview">
                            <svg width="100%" height="300" viewBox="0 0 500 300">
                                <rect width="500" height="50" fill="#FF5722" rx="10"/>
                                <text x="20" y="35" font-family="Exo 2" font-size="24" font-weight="700" fill="white">ZVEZDA LAUNCHER</text>
                                <rect x="20" y="80" width="460" height="60" fill="#F0F4F8" rx="10"/>
                                <text x="40" y="118" font-family="Montserrat" font-size="18" fill="#757575">ZVEZDA Roleplay</text>
                                <rect x="20" y="160" width="460" height="50" fill="#F0F4F8" rx="10"/>
                                <text x="40" y="193" font-family="Montserrat" font-size="16" fill="#757575">Введите ваш никнейм...</text>
                                <rect x="150" y="230" width="200" height="50" fill="#FF5722" rx="25"/>
                                <text x="250" y="262" font-family="Montserrat" font-size="18" font-weight="700" fill="white" text-anchor="middle">ИГРАТЬ</text>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Новости (загружаются из БД) -->
        <section id="news" class="news-section">
            <div class="container">
                <div class="section-header">
                    <h2>НОВОСТИ</h2>
                    <p>ПРОЕКТА</p>
                </div>
                <div class="news-grid" id="newsContainer">
                    <!-- Новости загружаются через PHP -->
                </div>
            </div>
        </section>

        <!-- Социальные сети -->
        <section class="social-section">
            <div class="container">
                <div class="section-header">
                    <h2>НАШИ</h2>
                    <p>СОЦИАЛЬНЫЕ СЕТИ</p>
                </div>
                <div class="social-icons">
                    <div class="social-icon instagram">📷</div>
                    <div class="social-icon vk">В</div>
                    <div class="social-icon youtube">▶</div>
                    <div class="social-icon tiktok">♪</div>
                </div>
            </div>
        </section>

        <!-- Магазин -->
        <section id="shop" class="shop-section">
            <div class="container">
                <div class="shop-banner">
                    <div class="shop-text">
                        <h2>СТАНЬ БОГАЧЕ</h2>
                        <p>Приобрести любое количество игровой валюты прямо сейчас 🤑</p>
                        <button class="btn-primary" onclick="openModal('login')">Приобрести</button>
                    </div>
                    <div class="shop-image">
                        <svg width="400" height="300" viewBox="0 0 400 300">
                            <rect x="50" y="50" width="300" height="200" fill="#212121" rx="20"/>
                            <circle cx="200" cy="120" r="40" fill="#FFC107"/>
                            <text x="200" y="135" font-family="Exo 2" font-size="42" font-weight="900" fill="#212121" text-anchor="middle">$</text>
                            <text x="200" y="200" font-family="Exo 2" font-size="32" font-weight="700" fill="white" text-anchor="middle">СЕЙФ</text>
                        </svg>
                    </div>
                </div>

                <div class="shop-items">
                    <div class="shop-item">
                        <div class="shop-item-icon">🎫</div>
                        <h3>ЭКСКЛЮЗИВНЫЙ</h3>
                        <p>НОМЕР</p>
                    </div>
                    <div class="shop-item">
                        <div class="shop-item-icon">🔒</div>
                        <h3>ТЮРЕМНАЯ</h3>
                        <p>НАКОЛКА</p>
                    </div>
                    <div class="shop-item">
                        <div class="shop-item-icon">💰</div>
                        <h3>ИГРОВАЯ</h3>
                        <p>ВАЛЮТА</p>
                    </div>
                    <div class="shop-item">
                        <div class="shop-item-icon">👤</div>
                        <h3>СМЕНА</h3>
                        <p>НИКНЕЙМА</p>
                    </div>
                </div>

                <div class="section-header" style="margin-top: 80px;">
                    <h2>БОГАТЕЙ</h2>
                    <p>СО СКИДКОЙ</p>
                </div>

                <div class="packages-grid" id="packagesContainer">
                    <!-- Пакеты загружаются через PHP -->
                </div>
            </div>
        </section>
    </section>

    <!-- Модальное окно входа -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('login')">×</button>
            <div class="modal-header">
                <h2>ЛИЧНЫЙ КАБИНЕТ</h2>
            </div>
            <form id="loginForm">
                <button type="button" class="btn-social btn-vk">
                    <span>В</span> Войти через ВКонтакте
                </button>
                <div class="modal-divider">или</div>
                <div class="form-group">
                    <label>Выбрать сервер</label>
                    <select name="server_id" required>
                        <option value="1">Silver</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" name="username" placeholder="Логин" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Пароль" required>
                </div>
                <button type="submit" class="btn-submit">Войти</button>
                <div class="modal-footer">
                    <a href="#" onclick="openModal('forgot'); closeModal('login')">Забыли пароль?</a>
                </div>
                <div class="modal-footer" style="margin-top: 10px;">
                    Нет аккаунта? <a href="#" onclick="openModal('register'); closeModal('login')">Зарегистрироваться</a>
                </div>
            </form>
            <div id="loginMessage" class="message"></div>
        </div>
    </div>

    <!-- Модальное окно регистрации -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('register')">×</button>
            <div class="modal-header">
                <h2>РЕГИСТРАЦИЯ</h2>
            </div>
            <form id="registerForm">
                <div style="text-align: center; margin-bottom: 20px;">
                    <p style="color: var(--text-gray); margin-bottom: 15px; font-size: 16px;">Зарегистрироваться через</p>
                    <button type="button" class="btn-vk-icon">
                        <span style="font-size: 24px;">В</span>
                    </button>
                </div>
                <div class="modal-divider">или</div>
                <div class="form-group">
                    <label>Выбрать сервер</label>
                    <select name="server_id" required>
                        <option value="1">Silver</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" name="username" placeholder="Никнейм" required pattern="[a-zA-Z0-9_]{3,20}" title="От 3 до 20 символов (латиница, цифры, подчеркивание)">
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email (необязательно)">
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Пароль" required minlength="6">
                </div>
                <div class="form-group">
                    <input type="password" name="password_confirm" placeholder="Повторите пароль" required minlength="6">
                </div>
                <button type="submit" class="btn-submit">Зарегистрироваться</button>
                <div class="modal-footer">
                    Уже есть аккаунт? <a href="#" onclick="openModal('login'); closeModal('register')">Войти</a>
                </div>
            </form>
            <div id="registerMessage" class="message"></div>
        </div>
    </div>

    <!-- Модальное окно восстановления пароля -->
    <div id="forgotModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('forgot')">×</button>
            <div class="modal-header">
                <h2>ВОССТАНОВЛЕНИЕ ПАРОЛЯ</h2>
                <p style="color: var(--text-gray); font-size: 14px; margin-top: 10px;">Введите данные для восстановления доступа</p>
            </div>
            <form id="forgotForm">
                <div class="form-group">
                    <label>Выбрать сервер</label>
                    <select name="server_id" required>
                        <option value="1">Silver</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" name="username" placeholder="Ваш никнейм" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email для восстановления" required>
                </div>
                <button type="submit" class="btn-submit">Восстановить пароль</button>
                <div class="modal-footer">
                    Вспомнили пароль? <a href="#" onclick="openModal('login'); closeModal('forgot')">Войти</a>
                </div>
            </form>
            <div id="forgotMessage" class="message"></div>
        </div>
    </div>

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
</body>
</html>
