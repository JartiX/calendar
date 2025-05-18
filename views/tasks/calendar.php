<?php
/**
 * Представление календаря
 * 
 * Отображает календарь с задачами на месяц
 */

// Устанавливаем флаг для подключения скрипта календаря
$includeCalendarScript = true;

// Вспомогательные функции для календаря
require_once 'helpers/date_helper.php';

/**
 * Функция для сокращения текста
 * 
 * @param string $text Исходный текст
 * @param int $length Максимальная длина
 * @return string Сокращенный текст
 */
function shortenText($text, $length = 20) {
    return (strlen($text) > $length) ? substr($text, 0, $length) . '...' : $text;
}

/**
 * Функция для определения цвета статуса
 * 
 * @param array $task Данные задачи
 * @return string Цвет в формате HEX
 */
function getStatusColor($task) {
    switch($task['status_id'] ?? null) {
        case 1: // Активная
            return '#007bff'; // Синий
        case 2: // Просроченная
            return '#dc3545'; // Красный
        case 3: // Выполненная
            return '#28a745'; // Зеленый
        default:
            return '#6c757d'; // Серый (если статус не определен)
    }
}
?>

<div class="calendar">
    <div class="calendar-header d-flex justify-content-between align-items-center mb-3">
        <div>
            <button type="button" class="btn btn-outline-primary" id="prevMonthBtn" data-month="<?php echo $prevMonth; ?>" data-year="<?php echo $prevYear; ?>">
                <i class="fas fa-chevron-left"></i> Предыдущий
            </button>
        </div>
        <h3 id="calendarTitle"><?php echo DateHelper::getMonthName($month) . ' ' . $year; ?></h3>
        <div>
            <button type="button" class="btn btn-outline-primary" id="nextMonthBtn" data-month="<?php echo $nextMonth; ?>" data-year="<?php echo $nextYear; ?>">
                Следующий <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-bordered calendar-table mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Пн</th>
                        <th>Вт</th>
                        <th>Ср</th>
                        <th>Чт</th>
                        <th>Пт</th>
                        <th class="weekend">Сб</th>
                        <th class="weekend">Вс</th>
                    </tr>
                </thead>
                <tbody id="calendarBody">
                    <?php foreach($calendar as $week): ?>
                        <tr>
                            <?php foreach($week as $day): ?>
                                <td class="<?php echo $day['class']; ?> <?php echo date('Y-m-d') == $day['date'] ? 'today' : ''; ?>"
                                    onclick="handleDayClick(event, '<?php echo $day['date']; ?>')">
                                    <div class="day-number">
                                        <?php echo $day['day']; ?>
                                        <?php if(isset($day['tasks']) && count($day['tasks']) > 0): ?>
                                            <span class="task-count" title="<?php echo count($day['tasks']); ?> задач(и)">
                                                <?php echo count($day['tasks']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="add-task-icon" title="Добавить задачу на этот день" onclick="addTaskForDay(event, '<?php echo $day['date']; ?>')">
                                            <i class="fas fa-plus-circle"></i>
                                        </span>
                                    </div>
                                    
                                    <?php if(isset($day['tasks']) && !empty($day['tasks'])): ?>
                                        <div class="tasks-container">
                                            <?php 
                                            $maxVisibleTasks = 3; // Максимальное количество задач, которые показываем без прокрутки
                                            $visibleTasks = array_slice($day['tasks'], 0, $maxVisibleTasks);
                                            $hiddenTasksCount = count($day['tasks']) - $maxVisibleTasks;
                                            
                                            foreach($visibleTasks as $task): 
                                                $statusColor = getStatusColor($task);
                                            ?>
                                                <div class="task-item-calendar" 
                                                    onclick="viewTask(event, <?php echo $task['id']; ?>)"
                                                    style="border-left: 4px solid <?php echo $statusColor; ?>">
                                                    <div class="task-time">
                                                        <?php echo date('H:i', strtotime($task['scheduled_date'])); ?>
                                                    </div>
                                                    <div class="task-title" title="<?php echo htmlspecialchars($task['title']); ?>">
                                                        <?php echo shortenText($task['title'], 20); ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            
                                            <?php if($hiddenTasksCount > 0): ?>
                                                <div class="more-tasks" 
                                                    onclick="showDayTasks(event, '<?php echo $day['date']; ?>')">
                                                    <i class="fas fa-ellipsis-h"></i> Ещё <?php echo $hiddenTasksCount; ?> задач...
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно для просмотра задач на день -->
<div class="modal fade" id="dayTasksModal" tabindex="-1" aria-labelledby="dayTasksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dayTasksModalLabel">Задачи на <span id="modalDate"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="modalTasksTable">
                        <thead>
                            <tr>
                                <th>Время</th>
                                <th>Тема</th>
                                <th>Тип</th>
                                <th>Статус</th>
                                <th>Приоритет</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody id="modalTasksList">
                            <!-- Задачи будут добавлены динамически -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Легенда статусов -->
                <div class="mt-3">
                    <h6>Условные обозначения кнопок:</h6>
                    <div class="d-flex flex-wrap">
                        <div class="me-3 mb-2">
                            <button class="btn btn-sm btn-outline-primary" disabled>
                                <i class="fas fa-play"></i>
                            </button>
                            - Отметить как активную
                        </div>
                        <div class="me-3 mb-2">
                            <button class="btn btn-sm btn-outline-success" disabled>
                                <i class="fas fa-check"></i>
                            </button>
                            - Отметить как выполненную
                        </div>
                        <div class="me-3 mb-2">
                            <button class="btn btn-sm btn-outline-danger" disabled>
                                <i class="fas fa-clock"></i>
                            </button>
                            - Отметить как просроченную
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="addTaskModalBtn">
                    <i class="fas fa-plus"></i> Добавить задачу на этот день
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для подтверждения действий -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">Подтверждение</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body" id="confirmationModalBody">
                Вы уверены, что хотите выполнить это действие?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="confirmationCancelBtn">Отмена</button>
                <button type="button" class="btn btn-primary" id="confirmationConfirmBtn">Подтвердить</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Глобальный объект для хранения задач
    window.calendarData = {
        tasks: {}
    };

    <?php
    // Заполняем объект данными задач
    foreach($calendar as $week) {
        foreach($week as $day) {
            if(isset($day['tasks']) && !empty($day['tasks'])) {
                echo "window.calendarData.tasks['" . $day['date'] . "'] = " . json_encode($day['tasks']) . ";\n";
            }
        }
    }
    ?>
</script>

<!-- Легенда для цветов календаря -->
<div class="container mt-3">
    <div class="card">
        <div class="card-body">
            <h5>Условные обозначения:</h5>
            <div class="d-flex flex-wrap">
                <div class="me-3 mb-2">
                    <span class="badge" style="background-color: #007bff;">&#8205;</span> Активные задачи
                </div>
                <div class="me-3 mb-2">
                    <span class="badge" style="background-color: #dc3545;">&#8205;</span> Просроченные задачи
                </div>
                <div class="me-3 mb-2">
                    <span class="badge" style="background-color: #28a745;">&#8205;</span> Выполненные задачи
                </div>
            </div>
        </div>
    </div>
</div>