<?php
/**
 * Контроллер для работы с задачами
 * 
 * Управляет созданием, редактированием, удалением и отображением задач
 */
class TaskController {
    private $task;
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        $this->task = new Task($db);
        
        // Установка ID пользователя из сессии
        if(isset($_SESSION['user_id'])) {
            $this->task->user_id = $_SESSION['user_id'];
        }
    }

/**
 * Отображение главной страницы задач
 * 
 * Загружает задачи, типы задач, единицы измерения длительности и активные задачи
 */
public function index() {
    // Загружаем данные для всех вкладок
    
    // Данные для вкладки списка задач
    $activeTasks = $this->task->readActive();
    
    // Данные для вкладки создания задачи и профиля
    $taskTypes = $this->task->getTaskTypes();
    $durationUnits = $this->task->getDurationUnits();
    $taskPriorities = $this->task->getTaskPriorities();

    // Параметры для календаря
    $month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
    $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
    
    // Проверка корректности месяца
    if ($month < 1 || $month > 12) {
        $month = date('m');
    }
    
    // Определение первого дня месяца
    $firstDay = mktime(0, 0, 0, $month, 1, $year);
    
    // Определение количества дней в месяце
    $daysInMonth = date('t', $firstDay);
    
    // Определение дня недели первого дня месяца (0 - воскресенье, 1 - понедельник и т.д.)
    $dayOfWeek = date('w', $firstDay);
    // Преобразование для отображения с понедельника (1 - понедельник, 7 - воскресенье)
    $dayOfWeek = $dayOfWeek == 0 ? 7 : $dayOfWeek;
    
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
    
    // Получаем дату первого отображаемого дня календаря (последний день предыдущего месяца)
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
    $monthTasks = $this->task->readByDateRange($firstVisibleDay, $lastVisibleDay);
    
    // Формирование календарной сетки
    $calendar = [];
    $week = [];
    
    // Добавление дней предыдущего месяца
    $prevMonthDays = $dayOfWeek - 1;
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

    // Загрузка представления
    include_once 'views/tasks/index.php';
}
    
    /**
     * Отображение формы создания новой задачи
     * 
     * Загружает типы задач, единицы измерения длительности и приоритеты задач
     */
    public function create() {
        $taskTypes = $this->task->getTaskTypes();
        $durationUnits = $this->task->getDurationUnits();
        $taskPriorities = $this->task->getTaskPriorities();
        
        // Загрузка представления
        include_once 'views/tasks/create.php';
    }
    
    /**
     * Создание новой задачи
     * 
     * Проверяет данные формы и сохраняет новую задачу в базе данных
     */
    public function store() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->task->title = htmlspecialchars(strip_tags($_POST['title']));
            $this->task->type_id = htmlspecialchars(strip_tags($_POST['type_id']));
            $this->task->location = htmlspecialchars(strip_tags($_POST['location']));
            
            $date = $_POST['date'];
            $time = $_POST['time'];
            $this->task->scheduled_date = date('Y-m-d H:i:s', strtotime("$date $time"));
            
            $this->task->duration = htmlspecialchars(strip_tags($_POST['duration']));
            $this->task->duration_unit_id = htmlspecialchars(strip_tags($_POST['duration_unit_id']));
            $this->task->comments = htmlspecialchars(strip_tags($_POST['comments']));

            $this->task->priority_id = !empty($_POST['priority_id']) ? 
                htmlspecialchars(strip_tags($_POST['priority_id'])) : null;

            $this->task->status_id = 1; // По умолчанию - активная задача
            $this->task->user_id = $_SESSION['user_id']; // ID авторизованного пользователя
                        
            $this->task->notification_time = isset($_POST['notification_time']) && $_POST['notification_time'] !== '' ? 
                (int)$_POST['notification_time'] : null;


            // Проверяем, является ли запрос AJAX
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            if($this->task->create()) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Задача успешно создана',
                        'task_id' => $this->task->id,
                        'redirect_to' => isset($_POST['tab_source']) && $_POST['tab_source'] === 'main_page' 
                            ? 'index.php?tab=create' 
                            : 'index.php?action=index&notification=task_created'
                    ]);
                    exit;
                } else {
                    $_SESSION['success'] = "Задача успешно создана";
                    
                    // Определяем, откуда пришел запрос и куда перенаправлять
                    $redirect_to = isset($_POST['tab_source']) && $_POST['tab_source'] === 'main_page' 
                        ? 'index.php?tab=create' // Перенаправление на главную с активной вкладкой создания
                        : 'index.php?action=index&notification=task_created'; // Стандартное перенаправление
                    
                    header("Location: $redirect_to");
                    exit;
                }
            } else {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Ошибка при создании задачи'
                    ]);
                    exit;
                } else {
                    $_SESSION['error'] = "Ошибка при создании задачи";
                    
                    $redirect_to = isset($_POST['tab_source']) && $_POST['tab_source'] === 'main_page'
                        ? 'index.php?tab=create' // Перенаправление на главную с активной вкладкой создания
                        : 'index.php?action=create'; // Перенаправление на страницу создания
                        
                    header("Location: $redirect_to");
                    exit;
                }
            }
        } else {
            header('Location: index.php?action=create');
            exit;
        }
    }
    
    /* Отображение задачи по ID
     * 
     * Загружает данные задачи и отображает их в представлении
     */
    public function show($id) {
        $this->task->id = $id;
        $task_data = $this->task->readOne();
        
        if($task_data) {
            // Загрузка представления
            include_once 'views/tasks/show.php';
        } else {
            echo "Задача не найдена.";
        }
    }
    
    /* Редактирование задачи
     * 
     * Загружает данные задачи для редактирования и отображает форму
     */
    public function edit($id) {
        $this->task->id = $id;
        $task_data = $this->task->readOne();
        
        if($task_data) {
            $taskTypes = $this->task->getTaskTypes();
            $durationUnits = $this->task->getDurationUnits();
            $taskStatuses = $this->task->getTaskStatuses();
            $taskPriorities = $this->task->getTaskPriorities();
            
            // Загрузка представления
            include_once 'views/tasks/edit.php';
        } else {
            echo "Задача не найдена.";
        }
    }
    
    /**
     * Обновление задачи
     * 
     * Проверяет данные формы и обновляет задачу в базе данных
     */
    public function update($id) {
        // Проверка на отправку формы
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->task->id = $id;
            $this->task->title = htmlspecialchars(strip_tags($_POST['title']));
            $this->task->type_id = htmlspecialchars(strip_tags($_POST['type_id']));
            $this->task->location = htmlspecialchars(strip_tags($_POST['location']));
            
            // Форматирование даты и времени
            $date = $_POST['date'];
            $time = $_POST['time'];
            $this->task->scheduled_date = date('Y-m-d H:i:s', strtotime("$date $time"));
            
            $this->task->duration = htmlspecialchars(strip_tags($_POST['duration']));
            $this->task->duration_unit_id = htmlspecialchars(strip_tags($_POST['duration_unit_id']));

            $this->task->priority_id = !empty($_POST['priority_id']) ? 
                htmlspecialchars(strip_tags($_POST['priority_id'])) : null;
                
            $this->task->comments = htmlspecialchars(strip_tags($_POST['comments']));
            $this->task->status_id = htmlspecialchars(strip_tags($_POST['status_id']));
            
            $this->task->notification_time = isset($_POST['notification_time']) && $_POST['notification_time'] !== '' ? 
                (int)$_POST['notification_time'] : null;

            // Проверяем, является ли запрос AJAX
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            // Обновление задачи
            if($this->task->update()) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Задача успешно обновлена',
                        'task_id' => $id,
                        'redirect_to' => isset($_POST['return_url']) && !empty($_POST['return_url']) 
                            ? $_POST['return_url'] // Вернуться на страницу, указанную в форме
                            : "index.php?action=show&id=$id&notification=task_updated" // Стандартное перенаправление
                    ]);
                    exit;
                } else {
                    $_SESSION['success'] = "Задача успешно обновлена";
                    
                    // Определяем, куда перенаправлять
                    $redirect_to = isset($_POST['return_url']) && !empty($_POST['return_url']) 
                        ? $_POST['return_url'] // Вернуться на страницу, указанную в форме
                        : "index.php?action=show&id=$id&notification=task_updated"; // Стандартное перенаправление
                    
                    header("Location: $redirect_to");
                    exit;
                }
            } else {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Ошибка при обновлении задачи'
                    ]);
                    exit;
                } else {
                    $_SESSION['error'] = "Ошибка при обновлении задачи";
                    header("Location: index.php?action=edit&id=$id");
                    exit;
                }
            }
        } else {
            header("Location: index.php?action=edit&id=$id");
            exit;
        }
    }
    
    /**
     * Удаление задачи
     * 
     * Удаляет задачу из базы данных по ID
     */
    public function delete($id) {
        $this->task->id = $id;
        
        // Проверка на AJAX-запрос
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        if($this->task->delete()) {
            if($isAjax) {
                // Если AJAX-запрос, возвращаем JSON
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Задача успешно удалена'
                ]);
                exit;
            } else {
                // Если обычный запрос, перенаправляем на предыдущую страницу
                $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php?action=index';
                
                // Проверяем, что referer не ведет на ту же страницу удаления, чтобы избежать цикла
                if(strpos($referer, "action=delete") !== false || strpos($referer, "action=show&id=$id") !== false) {
                    header('Location: index.php?action=index&notification=task_deleted');
                } else {
                    header("Location: $referer&notification=task_deleted");
                }
            }
        } else {
            if($isAjax) {
                // Если AJAX-запрос, возвращаем JSON с ошибкой
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Ошибка при удалении задачи'
                ]);
                exit;
            } else {
                $_SESSION['error'] = "Ошибка при удалении задачи";
                header('Location: index.php?action=index');
            }
        }
        exit;
    }
    
    /* Просмотр активных задач
     * 
     * Загружает активные задачи и отображает их в представлении
     */
    public function active() {
        $tasks = $this->task->readActive();
        
        $task = $this->task;
        // Загрузка представления
        include_once 'views/tasks/list.php';
    }
    
    /* Просмотр просроченных задач
     * 
     * Загружает просроченные задачи и отображает их в представлении
     */
    public function overdue() {
        $tasks = $this->task->readOverdue();
        
        $task = $this->task;

        // Загрузка представления
        include_once 'views/tasks/list.php';
    }
    
    /* Просмотр выполненных задач
     * 
     * Загружает выполненные задачи и отображает их в представлении
     */
    public function completed() {
        $tasks = $this->task->readCompleted();
        
        $task = $this->task;

        // Загрузка представления
        include_once 'views/tasks/list.php';
    }
    
    /* Фильтрация задач по дате
     * 
     * Загружает задачи по указанной дате и отображает их в представлении
     */
    public function byDate() {
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        
        // Определяем контекст страницы, с которой был запрос
        $contextStatus = null;
        
        // Если в URL есть параметр status_context, используем его
        if (isset($_GET['status_context']) && $_GET['status_context'] !== '') {
            $contextStatus = (int)$_GET['status_context'];
        }
        // Иначе пытаемся определить по текущему действию
        else if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'active':
                    $contextStatus = 1; // Активные задачи
                    break;
                case 'overdue':
                    $contextStatus = 2; // Просроченные задачи
                    break;
                case 'completed':
                    $contextStatus = 3; // Выполненные задачи
                    break;
            }
        }
        
        // Получаем задачи с учетом контекста
        $tasks = $this->task->readByDate($date, $contextStatus);
        
        // Сохраняем информацию о контексте для использования в представлении
        $currentStatus = $contextStatus;

        $task = $this->task;
        
        // Загрузка представления
        include_once 'views/tasks/list.php';
    }

    /* Поиск задач
     * 
     * Загружает задачи по указанному ключевому слову и отображает их в представлении
     */
    public function search() {
        error_log("TaskController::search() - Начало метода");
        
        // Добавляем переменную для заголовка страницы
        $pageTitle = 'Результаты поиска';
        
        if (isset($_GET['keyword'])) {
            $keyword = trim($_GET['keyword']);
            
            // Проверка на пустой запрос после удаления пробелов
            if (empty($keyword)) {
                error_log("TaskController::search() - Пустой поисковый запрос");
                header('Location: index.php?action=index');
                exit;
            }
            
            error_log("TaskController::search() - Поиск по запросу: '" . $keyword . "'");
            
            // Сохраняем запрос для отображения в форме
            $searchKeyword = $keyword;
            
            try {
                // Получаем результаты поиска
                $tasks = $this->task->search($keyword);
                
                if ($tasks === false) {
                    error_log("TaskController::search() - Поиск вернул false");
                    throw new Exception("Ошибка выполнения поиска");
                }
                
                error_log("TaskController::search() - Поиск выполнен, найдено результатов: " . $tasks->rowCount());
                
                $task = $this->task;

                // Загрузка представления со списком найденных задач
                include_once 'views/tasks/list.php';
            } catch (Exception $e) {
                error_log("TaskController::search() - Исключение: " . $e->getMessage());
                $_SESSION['error'] = "Ошибка при выполнении поиска: " . $e->getMessage();
                header('Location: index.php?action=index');
                exit;
            }
        } else {
            error_log("TaskController::search() - Параметр keyword отсутствует");
            $_SESSION['error'] = "Введите поисковый запрос";
            header('Location: index.php?action=index');
            exit;
        }
    }

    /**
     * Комбинированный поиск задач по ключевому слову, дате, статусу и приоритету
     * 
     * Обрабатывает поиск с учетом всех параметров фильтрации
     */
    public function combinedSearch() {
        // Получаем параметры фильтрации
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : null;
        $date = isset($_GET['date']) ? trim($_GET['date']) : null;
        $statusId = isset($_GET['status_id']) && !empty($_GET['status_id']) ? (int)$_GET['status_id'] : null;
        $priorityId = isset($_GET['priority_id']) && !empty($_GET['priority_id']) ? (int)$_GET['priority_id'] : null;
        
        // Проверяем наличие хотя бы одного параметра фильтрации
        if (empty($keyword) && empty($date) && empty($statusId) && empty($priorityId)) {
            exit;
        }
        
        $taskListTitle = 'Результаты поиска';
        
        $titleParts = [];
        
        if (!empty($keyword)) {
            $titleParts[] = 'по запросу "' . htmlspecialchars($keyword) . '"';
        }
        
        if (!empty($date)) {
            $titleParts[] = 'на ' . date('d.m.Y', strtotime($date));
        }
        
        $task = $this->task;
        
        if (!empty($statusId)) {
            $statusName = ''; 
            $taskStatuses = $task->getTaskStatuses();
            foreach ($taskStatuses as $row) {
                if ($row['id'] == $statusId) {
                    $statusName = $row['name'];
                    break;
                }
            }
            $titleParts[] = 'статус: ' . $statusName;
        }
        
        if (!empty($priorityId)) {
            $priorityName = '';
            $taskPriorities = $task->getTaskPriorities();
            foreach ($taskPriorities as $row) {
                if ($row['id'] == $priorityId) {
                    $priorityName = $row['name'];
                    break;
                }
            }
            $titleParts[] = 'приоритет: ' . $priorityName;
        }
        
        if (!empty($titleParts)) {
            $taskListTitle = 'Результаты поиска: ' . implode(', ', $titleParts);
        }
        
        // Выполняем поиск с учетом всех параметров
        $tasks = $this->task->combinedSearch($keyword, $date, $statusId, $priorityId);
        
        // Для использования в представлении - сохраняем текущие параметры фильтрации
        $searchKeyword = $keyword;
        $searchDate = $date;
        $currentStatusId = $statusId;
        $currentPriorityId = $priorityId;
        
        // Загружаем представление со списком найденных задач
        include_once 'views/tasks/list.php';
    }
}