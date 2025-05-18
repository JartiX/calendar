<?php
/**
 * Обработчик AJAX-запроса для получения данных календаря
 */
require_once '../config/database.php';
require_once '../models/task.php';
require_once '../helpers/auth_helper.php';
require_once '../config/settings.php';

// Настраиваем имя сессии перед ее запуском
session_name(SESSION_NAME);

// Начинаем сессию для доступа к данным пользователя
session_start();

// Проверка авторизации
if (!AuthHelper::isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Пользователь не авторизован'
    ]);
    exit;
}

// Получаем параметры месяца и года
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Создаем подключение к базе данных
$database = new Database();
$db = $database->getConnection();

// Инициализируем модель задач
$task = new Task($db);
$task->user_id = $_SESSION['user_id'];

// Вычисление предыдущего и следующего месяца/года для навигации
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Название месяца для отображения
$monthNames = [
    1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
    5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
    9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
];
$monthName = $monthNames[$month];

// Определение первого дня месяца
$firstDay = mktime(0, 0, 0, $month, 1, $year);

// Определение количества дней в месяце
$daysInMonth = date('t', $firstDay);

// Определение дня недели первого дня месяца
$dayOfWeek = date('w', $firstDay);
$dayOfWeek = $dayOfWeek == 0 ? 7 : $dayOfWeek;

// Получаем дату первого отображаемого дня календаря
$firstVisibleDay = null;
$prevMonthDays = $dayOfWeek - 1;
if ($prevMonthDays > 0) {
    $prevMonthLastDay = date('t', mktime(0, 0, 0, $prevMonth, 1, $prevYear));
    $firstVisibleDay = date('Y-m-d', mktime(0, 0, 0, $prevMonth, $prevMonthLastDay - $prevMonthDays + 1, $prevYear));
} else {
    $firstVisibleDay = date('Y-m-d', $firstDay);
}

// Получаем дату последнего отображаемого дня календаря
$totalDaysDisplayed = $prevMonthDays + $daysInMonth;
$remainingDays = $totalDaysDisplayed % 7 == 0 ? 0 : 7 - ($totalDaysDisplayed % 7);
$lastVisibleDay = date('Y-m-d', mktime(0, 0, 0, $nextMonth, $remainingDays, $nextYear));

// Получение задач для всех отображаемых дней календаря
$monthTasks = $task->readByDateRange($firstVisibleDay, $lastVisibleDay);

// Формирование календарной сетки
$calendar = [];
$week = [];

// Добавление дней предыдущего месяца
if ($prevMonthDays > 0) {
    $prevMonthLastDay = date('t', mktime(0, 0, 0, $prevMonth, 1, $prevYear));
    for ($i = $prevMonthDays - 1; $i >= 0; $i--) {
        $day = $prevMonthLastDay - $i;
        $date = date('Y-m-d', mktime(0, 0, 0, $prevMonth, $day, $prevYear));
        $dayData = [
            'day' => $day,
            'class' => 'other-month',
            'date' => $date,
            'tasks' => []
        ];
        
        // Добавляем задачи для текущего дня
        if ($monthTasks) {
            foreach ($monthTasks as $task) {
                $taskDate = date('Y-m-d', strtotime($task['scheduled_date']));
                if ($taskDate == $date) {
                    $dayData['tasks'][] = $task;
                }
            }
        }
        
        $week[] = $dayData;
    }
}

// Добавление дней текущего месяца
for ($day = 1; $day <= $daysInMonth; $day++) {
    $date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
    $class = date('Y-m-d') == $date ? 'today' : '';
    $dayData = [
        'day' => $day,
        'class' => $class,
        'date' => $date,
        'tasks' => []
    ];
    
    // Добавление задач для текущего дня
    if ($monthTasks) {
        foreach ($monthTasks as $task) {
            $taskDate = date('Y-m-d', strtotime($task['scheduled_date']));
            if ($taskDate == $date) {
                $dayData['tasks'][] = $task;
            }
        }
    }

    $week[] = $dayData;
    
    // Если достигли воскресенья или конца месяца, добавляем неделю в календарь
    if (count($week) == 7 || $day == $daysInMonth) {
        // Если последняя неделя месяца не полная, добавляем дни следующего месяца
        if (count($week) < 7) {
            $nextMonthDay = 1;
            while (count($week) < 7) {
                $date = date('Y-m-d', mktime(0, 0, 0, $nextMonth, $nextMonthDay, $nextYear));
                $dayData = [
                    'day' => $nextMonthDay,
                    'class' => 'other-month',
                    'date' => $date,
                    'tasks' => []
                ];
                
                // Добавляем задачи для этого дня
                if ($monthTasks) {
                    foreach ($monthTasks as $task) {
                        $taskDate = date('Y-m-d', strtotime($task['scheduled_date']));
                        if ($taskDate == $date) {
                            $dayData['tasks'][] = $task;
                        }
                    }
                }
                
                $week[] = $dayData;
                $nextMonthDay++;
            }
        }
        
        $calendar[] = $week;
        $week = [];
    }
}

// Подготовка данных для ответа
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'calendar' => $calendar,
    'monthName' => $monthName,
    'month' => $month,
    'year' => $year,
    'prevMonth' => $prevMonth,
    'prevYear' => $prevYear,
    'nextMonth' => $nextMonth,
    'nextYear' => $nextYear
]);