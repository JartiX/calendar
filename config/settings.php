<?php
/**
 * Глобальные настройки приложения
 */

// Настройки окружения
define('APP_ENV', 'development'); // development или production
define('DEBUG_MODE', APP_ENV === 'development');

// Настройки безопасности
define('SESSION_TIMEOUT', 3600); // Время жизни сессии в секундах (1 час)
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_NAME', 'task_calendar_session');

// Настройки приложения
define('APP_NAME', 'Мой календарь задач');
define('APP_VERSION', '1.0.0');
define('DEFAULT_TIMEZONE', 'Asia/Irkutsk');

// Настройки путей
define('BASE_PATH', realpath(dirname(__FILE__) . '/..'));
define('VIEWS_PATH', BASE_PATH . '/views');
define('ASSETS_PATH', BASE_PATH . '/assets');

// Настройки журналирования
define('LOG_ERRORS', true);
define('ERROR_LOG_FILE', BASE_PATH . '/logs/error.log');

if (LOG_ERRORS) {
    ini_set('log_errors', 1);
    ini_set('error_log', ERROR_LOG_FILE);
    
    // Создаем директорию для логов, если её нет
    if (!file_exists(dirname(ERROR_LOG_FILE))) {
        mkdir(dirname(ERROR_LOG_FILE), 0755, true);
    }
}

// Инициализация системных настроек
date_default_timezone_set(DEFAULT_TIMEZONE);

// Функция для отображения ошибок только в режиме разработки
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// Настройка журналирования
if (LOG_ERRORS) {
    ini_set('log_errors', 1);
    ini_set('error_log', ERROR_LOG_FILE);
}

// Настройка обработчика ошибок
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $errorType = 'Неизвестная ошибка';
    switch ($errno) {
        case E_ERROR:
            $errorType = 'Фатальная ошибка';
            break;
        case E_WARNING:
            $errorType = 'Предупреждение';
            break;
        case E_PARSE:
            $errorType = 'Ошибка синтаксического анализа';
            break;
        case E_NOTICE:
            $errorType = 'Уведомление';
            break;
        case E_CORE_ERROR:
            $errorType = 'Фатальная ошибка ядра';
            break;
        case E_CORE_WARNING:
            $errorType = 'Предупреждение ядра';
            break;
        case E_COMPILE_ERROR:
            $errorType = 'Фатальная ошибка компиляции';
            break;
        case E_USER_ERROR:
            $errorType = 'Пользовательская ошибка';
            break;
        case E_USER_WARNING:
            $errorType = 'Пользовательское предупреждение';
            break;
        case E_USER_NOTICE:
            $errorType = 'Пользовательское уведомление';
            break;
    }
    
    $errorMessage = "[$errorType] $errstr в файле $errfile на строке $errline";
    
    // Записываем в журнал
    error_log($errorMessage);
    
    // В режиме разработки показываем ошибку
    if (DEBUG_MODE) {
        echo "<div style='background-color: #ffcccc; padding: 10px; margin: 10px; border: 1px solid #ff0000;'>";
        echo "<h3>Ошибка PHP:</h3>";
        echo "<p><strong>$errorType:</strong> $errstr</p>";
        echo "<p><strong>Файл:</strong> $errfile</p>";
        echo "<p><strong>Строка:</strong> $errline</p>";
        echo "</div>";
    }
    
    return true;
}

set_error_handler('customErrorHandler');

// Настройка сессии
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (APP_ENV === 'production') {
    ini_set('session.cookie_secure', 1);
}
session_name(SESSION_NAME);