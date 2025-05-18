<?php
// Загружаем настройки, если они еще не загружены
if (!defined('SESSION_NAME')) {
    require_once __DIR__ . '/config/settings.php';
}

// Настройка сессии
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (APP_ENV === 'production') {
    ini_set('session.cookie_secure', 1);
}

// Устанавливаем имя сессии
session_name(SESSION_NAME);

// Проверяем, не запущена ли сессия
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>