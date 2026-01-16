-- ================================================
-- ZVEZDA RP - БАЗА ДАННЫХ
-- ВАЖНО: phone_number по умолчанию NULL
-- ВАЖНО: phone_balance по умолчанию 0.00
-- ================================================

-- Создание базы данных
CREATE DATABASE IF NOT EXISTS zvezda_rp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE zvezda_rp;

-- Таблица серверов
CREATE TABLE IF NOT EXISTS servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('online', 'offline', 'maintenance') DEFAULT 'online',
    players_online INT DEFAULT 0,
    max_players INT DEFAULT 1000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка сервера Silver
INSERT INTO servers (name, status, players_online, max_players) 
VALUES ('Silver', 'online', 247, 1000)
ON DUPLICATE KEY UPDATE name=name;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    password_hash VARCHAR(255) NOT NULL,
    level INT DEFAULT 1,
    experience INT DEFAULT 0,
    play_time INT DEFAULT 0, -- в минутах
    cash_balance DECIMAL(15,2) DEFAULT 0.00,
    bank_balance DECIMAL(15,2) DEFAULT 0.00,
    donate_currency DECIMAL(15,2) DEFAULT 0.00,
    
    -- ВАЖНО: phone_number NULL - НЕ генерируется при регистрации!
    phone_number VARCHAR(20) DEFAULT NULL,
    
    -- ВАЖНО: phone_balance 0 - НЕ выдается при регистрации!
    phone_balance DECIMAL(10,2) DEFAULT 0.00,
    
    warnings INT DEFAULT 0,
    is_blocked BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_username_server (username, server_id),
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица сессий
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(64) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица транзакций
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('purchase', 'transfer', 'deposit', 'withdrawal') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency_type ENUM('cash', 'bank', 'donate') NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица новостей
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    content TEXT,
    image_url VARCHAR(255),
    category VARCHAR(50),
    author_id INT,
    views INT DEFAULT 0,
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка примеров новостей
INSERT INTO news (title, description, category, published_at) VALUES
('Операция «Новый Год»', 'Путь к торжеству в этом году лежит через новую квестовую линию...', 'event', '2025-12-28 10:00:00'),
('Адский парк', 'Хэллоуинское обновление уже здесь! Новые мини-игры и сезонный пропуск...', 'update', '2025-10-31 12:00:00'),
('Пацанский движ', 'Там, где раньше царили законы улиц, теперь приходит новый риск...', 'update', '2025-10-05 14:00:00'),
('Осенний пропуск', 'Этот большой пропуск лишь разогрел перед началом покладки рук...', 'season', '2025-09-18 16:00:00');

-- Таблица донат-пакетов
CREATE TABLE IF NOT EXISTS donate_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    title VARCHAR(100) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency_amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка донат-пакетов
INSERT INTO donate_packages (name, title, amount, currency_amount, sort_order) VALUES
('starter', 'СТАРТ ДЛЯ НАЧАЛА', 100000, 100000, 1),
('medium', 'СОЛИДНЕНЬКО', 250000, 250000, 2),
('large', 'САМОЕ ТО', 550000, 550000, 3),
('premium', 'ВОРОВСКОЕ', 1390000, 1390000, 4),
('gold', 'ЗОЛОТЫЕ ЗАПАСЫ', 7500000, 7500000, 5);

-- Индексы для оптимизации
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_sessions_token ON sessions(session_token);
CREATE INDEX idx_sessions_expires ON sessions(expires_at);
CREATE INDEX idx_transactions_user ON transactions(user_id);
CREATE INDEX idx_news_published ON news(published_at);

-- ================================================
-- ПРОВЕРКА СТРУКТУРЫ
-- ================================================

-- Проверка что phone_number может быть NULL
SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'zvezda_rp' 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME IN ('phone_number', 'phone_balance');

-- Должно быть:
-- phone_number    | YES | NULL
-- phone_balance   | NO  | 0.00
-- Таблица для токенов восстановления пароля
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
