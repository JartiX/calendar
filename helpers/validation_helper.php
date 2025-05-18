<?php
/**
 * Вспомогательный класс для валидации данных
 * 
 * Предоставляет функции для проверки и очистки входных данных
 */
class ValidationHelper {
    /**
     * Проверяет, что все обязательные поля заполнены
     * 
     * @param array $data Массив данных ($_POST или $_GET)
     * @param array $required Массив обязательных полей
     * @return bool Результат проверки
     */
    public static function validateRequired($data, $required) {
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Проверяет, что строка является действительным email
     * 
     * @param string $email Email для проверки
     * @return bool Результат проверки
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Проверяет, что строка является числом
     * 
     * @param string $value Значение для проверки
     * @return bool Результат проверки
     */
    public static function validateNumeric($value) {
        return is_numeric($value);
    }
    
    /**
     * Проверяет, что строка является действительной датой
     * 
     * @param string $date Дата для проверки
     * @param string $format Формат даты
     * @return bool Результат проверки
     */
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Проверяет, что строка является действительным временем
     * 
     * @param string $time Время для проверки
     * @return bool Результат проверки
     */
    public static function validateTime($time) {
        return preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])$/', $time) === 1;
    }
    
    /**
     * Проверяет, что строка не превышает максимальную длину
     * 
     * @param string $value Значение для проверки
     * @param int $maxLength Максимальная длина
     * @return bool Результат проверки
     */
    public static function validateMaxLength($value, $maxLength) {
        return mb_strlen($value, 'UTF-8') <= $maxLength;
    }
    
    /**
     * Проверяет, что строка имеет минимальную длину
     * 
     * @param string $value Значение для проверки
     * @param int $minLength Минимальная длина
     * @return bool Результат проверки
     */
    public static function validateMinLength($value, $minLength) {
        return mb_strlen($value, 'UTF-8') >= $minLength;
    }
    
    /**
     * Проверяет, что два пароля совпадают
     * 
     * @param string $password Пароль
     * @param string $confirmPassword Подтверждение пароля
     * @return bool Результат проверки
     */
    public static function validatePasswordsMatch($password, $confirmPassword) {
        return $password === $confirmPassword;
    }
    
    /**
     * Очищает строку от потенциально опасных символов
     * 
     * @param string $value Значение для очистки
     * @return string Очищенное значение
     */
    public static function sanitizeString($value) {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Преобразует значение в целое число
     * 
     * @param mixed $value Значение для преобразования
     * @return int Целое число
     */
    public static function sanitizeInt($value) {
        return (int)$value;
    }
    
    /**
     * Преобразует значение в число с плавающей точкой
     * 
     * @param mixed $value Значение для преобразования
     * @return float Число с плавающей точкой
     */
    public static function sanitizeFloat($value) {
        return (float)$value;
    }
    
    /**
     * Возвращает значение или значение по умолчанию, если оно не существует
     * 
     * @param array $array Массив данных
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed Значение или значение по умолчанию
     */
    public static function getValue($array, $key, $default = '') {
        return isset($array[$key]) ? $array[$key] : $default;
    }
}
