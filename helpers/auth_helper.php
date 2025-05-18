<?php
/**
 * Вспомогательный класс для работы с авторизацией
 * 
 * Предоставляет функции для проверки авторизации, генерации токенов и защиты от CSRF
 */
class AuthHelper {
    /**
     * Проверяет, авторизован ли пользователь
     * 
     * @return bool Результат проверки
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Проверяет время последней активности пользователя и обновляет его
     * 
     * @param int $timeout Время бездействия в секундах
     * @return bool Результат проверки
     */
    public static function checkSessionTimeout($timeout = 3600) {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        $currentTime = time();
        
        // Если last_activity не установлено, устанавливаем сейчас
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = $currentTime;
            return true;
        }
        
        // Проверяем timeout
        if (($currentTime - $_SESSION['last_activity']) > $timeout) {
            // Сессия устарела, выходим из системы
            self::logout();
            return false;
        }
        
        // Обновляем время последней активности
        $_SESSION['last_activity'] = $currentTime;
        return true;
    }
    
    /**
     * Выход из системы
     */
    public static function logout() {
        // Удаляем все данные сессии
        session_unset();
        session_destroy();
    }
    
    /**
     * Генерирует CSRF-токен
     * 
     * @return string Сгенерированный токен
     */
    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Проверяет CSRF-токен
     * 
     * @param string $token Токен для проверки
     * @return bool Результат проверки
     */
    public static function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Генерирует CSRF-поле для формы
     * 
     * @return string HTML-код поля с токеном
     */
    public static function csrfField() {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
    
    /**
     * Проверяет права на выполнение операции с задачей
     * 
     * @param int $taskUserId ID пользователя-владельца задачи
     * @return bool Результат проверки
     */
    public static function canModifyTask($taskUserId) {
        return self::isLoggedIn() && $_SESSION['user_id'] == $taskUserId;
    }
}