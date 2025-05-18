<?php
/**
 * Вспомогательный класс для работы с датами
 * 
 * Предоставляет функции для форматирования и обработки дат
 */
class DateHelper {
    /**
     * Форматирует дату для отображения пользователю
     * 
     * @param string $date Дата в формате MySQL (Y-m-d H:i:s)
     * @param string $format Формат вывода
     * @return string Отформатированная дата
     */
    public static function formatDate($date, $format = 'd.m.Y H:i') {
        if (!$date) {
            return '';
        }
        
        $timestamp = strtotime($date);
        return date($format, $timestamp);
    }
    
    /**
     * Возвращает дату в формате для ввода в поле input[type="date"]
     * 
     * @param string $date Дата в формате MySQL
     * @return string Отформатированная дата (Y-m-d)
     */
    public static function formatDateForInput($date) {
        if (!$date) {
            return date('Y-m-d');
        }
        
        $timestamp = strtotime($date);
        return date('Y-m-d', $timestamp);
    }
    
    /**
     * Возвращает время в формате для ввода в поле input[type="time"]
     * 
     * @param string $date Дата в формате MySQL
     * @return string Отформатированное время (H:i)
     */
    public static function formatTimeForInput($date) {
        if (!$date) {
            return date('H:i');
        }
        
        $timestamp = strtotime($date);
        return date('H:i', $timestamp);
    }
    
    /**
     * Проверяет, просрочена ли задача
     * 
     * @param string $scheduledDate Запланированная дата задачи
     * @return bool Результат проверки
     */
    public static function isOverdue($scheduledDate) {
        return strtotime($scheduledDate) < time();
    }
    
    /**
     * Возвращает название месяца по его номеру
     * 
     * @param int $month Номер месяца
     * @return string Название месяца
     */
    public static function getMonthName($month) {
        $months = [
            1 => 'Январь',
            2 => 'Февраль',
            3 => 'Март',
            4 => 'Апрель',
            5 => 'Май',
            6 => 'Июнь',
            7 => 'Июль',
            8 => 'Август',
            9 => 'Сентябрь',
            10 => 'Октябрь',
            11 => 'Ноябрь',
            12 => 'Декабрь'
        ];
        
        return isset($months[$month]) ? $months[$month] : '';
    }
    
    /**
     * Возвращает название дня недели по его номеру
     * 
     * @param int $dayOfWeek Номер дня недели (1-7, где 1 - понедельник)
     * @return string Название дня недели
     */
    public static function getDayOfWeekName($dayOfWeek) {
        $days = [
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            7 => 'Воскресенье'
        ];
        
        return isset($days[$dayOfWeek]) ? $days[$dayOfWeek] : '';
    }
    
    /**
     * Преобразует номер дня недели из формата PHP (0-6, где 0 - воскресенье) 
     * в формат понедельник-воскресенье (1-7, где 1 - понедельник)
     * 
     * @param int $phpDayOfWeek Номер дня недели в формате PHP
     * @return int Преобразованный номер дня недели
     */
    public static function convertPhpDayOfWeek($phpDayOfWeek) {
        return $phpDayOfWeek == 0 ? 7 : $phpDayOfWeek;
    }
    
    /**
     * Вычисляет количество дней в месяце
     * 
     * @param int $month Номер месяца
     * @param int $year Год
     * @return int Количество дней
     */
    public static function daysInMonth($month, $year) {
        return date('t', mktime(0, 0, 0, $month, 1, $year));
    }
}