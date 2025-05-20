-- Таблица для типов задач
CREATE TABLE IF NOT EXISTS task_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Таблица для статусов задач
CREATE TABLE IF NOT EXISTS task_statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Таблица для хранения единиц длительности
CREATE TABLE IF NOT EXISTS duration_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL UNIQUE
);

-- Таблица приоритетов задач
CREATE TABLE IF NOT EXISTS task_priorities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(20) NOT NULL
);

-- Таблица для пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Основная таблица для задач
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    type_id INT NOT NULL,
    location VARCHAR(255),
    scheduled_date DATETIME NOT NULL,
    duration INT,
    duration_unit_id INT,
    comments TEXT,
    status_id INT NOT NULL DEFAULT 1,
    priority_id INT,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notification_time INT DEFAULT NULL COMMENT 'Минут до задачи для отправки уведомления',
    FOREIGN KEY (type_id) REFERENCES task_types(id),
    FOREIGN KEY (status_id) REFERENCES task_statuses(id),
    FOREIGN KEY (duration_unit_id) REFERENCES duration_units(id),
    FOREIGN KEY (priority_id) REFERENCES task_priorities(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Индексы для оптимизации запросов
ALTER TABLE tasks ADD INDEX idx_scheduled_date (scheduled_date);
ALTER TABLE tasks ADD INDEX idx_status_id (status_id);
ALTER TABLE tasks ADD INDEX idx_type_id (type_id);
ALTER TABLE tasks ADD INDEX idx_user_id (user_id);

-- Заполнение таблиц
INSERT INTO task_types (name) VALUES 
    ('Встреча'), 
    ('Звонок'), 
    ('Совещание'), 
    ('Дело');

INSERT INTO task_statuses (name) VALUES 
    ('Активная'), 
    ('Просроченная'), 
    ('Выполненная');

INSERT INTO duration_units (name) VALUES 
    ('минут'), 
    ('часов'), 
    ('дней');

INSERT INTO task_priorities (name, color) VALUES 
    ('Низкий', '#28a745'),
    ('Средний', '#ffc107'),
    ('Высокий', '#dc3545');

-- Таблица для хранения телеграм данных пользователей
CREATE TABLE IF NOT EXISTS telegram_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    telegram_chat_id VARCHAR(255) NOT NULL,
    telegram_username VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, telegram_chat_id)
);

-- Таблица для отслеживания отправки уведомлений
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    UNIQUE KEY (task_id, notification_type)
);

CREATE TABLE IF NOT EXISTS telegram_connection_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    connection_code VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (connection_code)
);