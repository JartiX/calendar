<?php
/**
 * Модель для работы с задачами
 * 
 * Предоставляет методы для создания, чтения, обновления и удаления задач,
 * а также для получения связанных данных (типы, статусы, единицы длительности и т.д.)
 */
class Task {
    // Подключение к базе данных и название таблицы
    private $conn;
    private $table_name = "tasks";

    // Кэш для хранения справочников
    private static $cache = [
        'types' => null,
        'statuses' => null,
        'duration_units' => null,
        'priorities' => null
    ];

    // Свойства объекта
    public $id;
    public $title;
    public $type_id;
    public $location;
    public $scheduled_date;
    public $duration;
    public $duration_unit_id;
    public $comments;
    public $status_id;
    public $created_at;
    public $updated_at;
    public $user_id;
    public $priority_id;

    public $notification_time;

    /**
     * Конструктор класса
     * 
     * @param PDO $db Соединение с базой данных
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Создает новую задачу
     * 
     * @return bool Результат выполнения операции
     */
    public function create() {
        // Проверяем, что все обязательные поля заполнены
        if (empty($this->title) || empty($this->scheduled_date) || empty($this->user_id)) {
            return false;
        }

        // SQL-запрос для вставки
        $query = "INSERT INTO " . $this->table_name . " 
                SET title = :title, 
                    type_id = :type_id, 
                    location = :location, 
                    scheduled_date = :scheduled_date, 
                    duration = :duration, 
                    duration_unit_id = :duration_unit_id, 
                    comments = :comments, 
                    status_id = :status_id,
                    priority_id = :priority_id,
                    notification_time = :notification_time,
                    user_id = :user_id";

        try {
            // Подготовка запроса
            $stmt = $this->conn->prepare($query);

            // Очистка данных
            $this->title = htmlspecialchars(strip_tags($this->title));
            $this->location = htmlspecialchars(strip_tags($this->location ?? ''));
            $this->comments = htmlspecialchars(strip_tags($this->comments ?? ''));

            // Привязка значений
            $stmt->bindParam(":title", $this->title);
            $stmt->bindParam(":type_id", $this->type_id);
            $stmt->bindParam(":location", $this->location);
            $stmt->bindParam(":scheduled_date", $this->scheduled_date);
            $stmt->bindParam(":duration", $this->duration);
            $stmt->bindParam(":duration_unit_id", $this->duration_unit_id);
            $stmt->bindParam(":comments", $this->comments);
            $stmt->bindParam(":status_id", $this->status_id);
            $stmt->bindParam(":user_id", $this->user_id);

            if (empty($this->priority_id)) {
                $stmt->bindValue(":priority_id", null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(":priority_id", $this->priority_id);
            }

            if (empty($this->notification_time)) {
                $stmt->bindValue(":notification_time", null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(":notification_time", $this->notification_time);
            }

            // Выполнение запроса
            if($stmt->execute()) {
                // Получаем ID вставленной записи
                $this->id = $this->conn->lastInsertId();
                return true;
            }
        } catch (PDOException $e) {
            // Записываем ошибку в лог
            error_log("Ошибка создания задачи: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Получает задачу по ID
     * 
     * @return array|null Данные задачи или null, если задача не найдена
     */
    public function readOne() {
        // Проверка ID
        if (empty($this->id) || !is_numeric($this->id)) {
            return null;
        }

        // SQL-запрос для получения одной записи
        $query = "SELECT t.*, 
                     tt.name as type_name, 
                     ts.name as status_name, 
                     du.name as duration_unit_name, 
                     tp.name as priority_name, 
                     tp.color as priority_color
                FROM " . $this->table_name . " t
                LEFT JOIN task_types tt ON t.type_id = tt.id
                LEFT JOIN task_statuses ts ON t.status_id = ts.id
                LEFT JOIN duration_units du ON t.duration_unit_id = du.id
                LEFT JOIN task_priorities tp ON t.priority_id = tp.id
                WHERE t.id = :id LIMIT 1";

        try {
            // Подготовка запроса
            $stmt = $this->conn->prepare($query);

            // Привязка ID задачи
            $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

            // Выполнение запроса
            $stmt->execute();

            // Получение записи
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Если задача найдена, заполняем свойства объекта
            if($row) {
                $this->title = $row['title'];
                $this->type_id = $row['type_id'];
                $this->location = $row['location'];
                $this->scheduled_date = $row['scheduled_date'];
                $this->duration = $row['duration'];
                $this->duration_unit_id = $row['duration_unit_id'];
                $this->comments = $row['comments'];
                $this->status_id = $row['status_id'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                $this->priority_id = $row['priority_id'];
                $this->user_id = $row['user_id'];
                
                return $row;
            }
        } catch (PDOException $e) {
            // Записываем ошибку в лог
            error_log("Ошибка получения задачи: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Обновляет задачу
     * 
     * @return bool Результат выполнения операции
     */
    public function update() {
        // Проверка ID
        if (empty($this->id) || !is_numeric($this->id)) {
            return false;
        }

        // SQL-запрос для обновления записи
        $query = "UPDATE " . $this->table_name . "
                SET title = :title,
                    type_id = :type_id,
                    location = :location,
                    scheduled_date = :scheduled_date,
                    duration = :duration,
                    duration_unit_id = :duration_unit_id,
                    comments = :comments,
                    priority_id = :priority_id,
                    notification_time = :notification_time,
                    status_id = :status_id
                WHERE id = :id AND user_id = :user_id";

        try {
            // Подготовка запроса
            $stmt = $this->conn->prepare($query);

            // Очистка данных
            $this->title = htmlspecialchars(strip_tags($this->title));
            $this->location = htmlspecialchars(strip_tags($this->location ?? ''));
            $this->comments = htmlspecialchars(strip_tags($this->comments ?? ''));

            // Привязка значений
            $stmt->bindParam(":title", $this->title);
            $stmt->bindParam(":type_id", $this->type_id);
            $stmt->bindParam(":location", $this->location);
            $stmt->bindParam(":scheduled_date", $this->scheduled_date);
            $stmt->bindParam(":duration", $this->duration);
            $stmt->bindParam(":duration_unit_id", $this->duration_unit_id);
            $stmt->bindParam(":comments", $this->comments);
            $stmt->bindParam(":status_id", $this->status_id);
            $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);

            if (empty($this->priority_id)) {
                $stmt->bindValue(":priority_id", null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(":priority_id", $this->priority_id);
            }

            if (empty($this->notification_time)) {
                $stmt->bindValue(":notification_time", null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(":notification_time", $this->notification_time);
            }
            
            // Выполнение запроса
            if($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            // Записываем ошибку в лог
            error_log("Ошибка обновления задачи: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Удаляет задачу
     * 
     * @return bool Результат выполнения операции
     */
    public function delete() {
        // Проверка ID
        if (empty($this->id) || !is_numeric($this->id)) {
            return false;
        }

        // SQL-запрос для удаления записи
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";

        try {
            // Подготовка запроса
            $stmt = $this->conn->prepare($query);

            // Привязка ID записи для удаления
            $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);

            // Выполнение запроса
            if($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            // Записываем ошибку в лог
            error_log("Ошибка удаления задачи: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Получает активные задачи
     * 
     * @return PDOStatement Результат запроса
     */
    public function readActive() {
        return $this->readByStatus(1);
    }

    /**
     * Получает просроченные задачи
     * 
     * @return PDOStatement Результат запроса
     */
    public function readOverdue() {
        return $this->readByStatus(2);
    }

    /**
     * Получает выполненные задачи
     * 
     * @return PDOStatement Результат запроса
     */
    public function readCompleted() {
        return $this->readByStatus(3);
    }
    
    /**
     * Получает задачи по статусу
     * 
     * @param int $status_id ID статуса
     * @return PDOStatement Результат запроса
     */
    public function readByStatus($status_id) {
        $query = "SELECT t.*, 
                     tt.name as type_name, 
                     ts.name as status_name, 
                     du.name as duration_unit_name,
                     tp.name as priority_name, 
                     tp.color as priority_color
                FROM " . $this->table_name . " t
                LEFT JOIN task_types tt ON t.type_id = tt.id
                LEFT JOIN task_statuses ts ON t.status_id = ts.id
                LEFT JOIN duration_units du ON t.duration_unit_id = du.id
                LEFT JOIN task_priorities tp ON t.priority_id = tp.id
                WHERE t.status_id = :status_id AND t.user_id = :user_id
                ORDER BY t.scheduled_date ASC";

        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":status_id", $status_id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            // Записываем ошибку в лог
            error_log("Ошибка получения задач по статусу: " . $e->getMessage());
            
            // Возвращаем пустой результат
            return $this->conn->query("SELECT 1 WHERE 0");
        }
    }
    
    /**
     * Получает задачи на конкретную дату с учетом статуса
     * 
     * @param string $date Дата в формате YYYY-MM-DD
     * @param int|null $status_id ID статуса или null для всех статусов
     * @return PDOStatement Результат запроса
     */
    public function readByDate($date, $status_id = null) {
        // Проверка формата даты
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            error_log("Некорректный формат даты: " . $date);
            return $this->conn->query("SELECT 1 WHERE 0");
        }

        // Базовый запрос на выборку задач по дате
        $query = "SELECT t.*, 
                     tt.name as type_name, 
                     ts.name as status_name, 
                     du.name as duration_unit_name,
                     tp.name as priority_name, 
                     tp.color as priority_color
                FROM " . $this->table_name . " t
                LEFT JOIN task_types tt ON t.type_id = tt.id
                LEFT JOIN task_statuses ts ON t.status_id = ts.id
                LEFT JOIN duration_units du ON t.duration_unit_id = du.id
                LEFT JOIN task_priorities tp ON t.priority_id = tp.id
                WHERE DATE(t.scheduled_date) = :date";
        
        // Если указан статус, добавляем условие для фильтрации по статусу
        if ($status_id !== null) {
            $query .= " AND t.status_id = :status_id";
        }
        
        // Добавляем условие для фильтрации по пользователю
        $query .= " AND t.user_id = :user_id";
        
        // Сортировка
        $query .= " ORDER BY t.scheduled_date ASC";
        
        try {
            // Подготовка запроса
            $stmt = $this->conn->prepare($query);
            
            // Привязка параметров
            $stmt->bindParam(":date", $date);
            if ($status_id !== null) {
                $stmt->bindParam(":status_id", $status_id, PDO::PARAM_INT);
            }
            $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
            
            // Выполнение запроса
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            // Записываем ошибку в лог
            error_log("Ошибка получения задач по дате: " . $e->getMessage());
            
            // Возвращаем пустой результат
            return $this->conn->query("SELECT 1 WHERE 0");
        }
    }
    
    /**
     * Получает типы задач
     * 
     * @return PDOStatement Результат запроса
     */
    public function getTaskTypes() {
        // Используем кэш, если доступен
        if (self::$cache['types'] !== null) {
            return self::$cache['types'];
        }
        
        $query = "SELECT * FROM task_types ORDER BY name";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            // Сохраняем в кэш
            self::$cache['types'] = $stmt;
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Ошибка получения типов задач: " . $e->getMessage());
            return $this->conn->query("SELECT 1 WHERE 0");
        }
    }
    
    /**
     * Получает единицы длительности
     * 
     * @return PDOStatement Результат запроса
     */
    public function getDurationUnits() {
        // Используем кэш, если доступен
        if (self::$cache['duration_units'] !== null) {
            return self::$cache['duration_units'];
        }
        
        $query = "SELECT * FROM duration_units ORDER BY name";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            // Сохраняем в кэш
            self::$cache['duration_units'] = $stmt;
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Ошибка получения единиц длительности: " . $e->getMessage());
            return $this->conn->query("SELECT 1 WHERE 0");
        }
    }

    /**
     * Получает статусы задач
     * 
     * @return array Массив статусов
     */
    public function getTaskStatuses() {
        // Используем кэш, если доступен
        if (self::$cache['statuses'] !== null) {
            return self::$cache['statuses'];
        }
        
        $query = "SELECT * FROM task_statuses ORDER BY name";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            // Получаем все статусы в массив
            $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Сохраняем в кэш
            self::$cache['statuses'] = $statuses;
            
            return $statuses;
        } catch (PDOException $e) {
            error_log("Ошибка получения статусов задач: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получает приоритеты задач
     * 
     * @return array Массив приоритетов
     */
    public function getTaskPriorities() {
        // Используем кэш, если доступен
        if (self::$cache['priorities'] !== null) {
            return self::$cache['priorities'];
        }
        
        $query = "SELECT * FROM task_priorities ORDER BY id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            // Получаем все приоритеты в массив
            $priorities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Сохраняем в кэш
            self::$cache['priorities'] = $priorities;
            
            return $priorities;
        } catch (PDOException $e) {
            error_log("Ошибка получения приоритетов задач: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Обновляет только статус задачи
     * 
     * @return bool Результат операции
     */
    public function updateStatus() {
        // Проверка ID и статуса
        if (empty($this->id) || !is_numeric($this->id) || empty($this->status_id) || !is_numeric($this->status_id)) {
            return false;
        }

        // Запрос на обновление статуса задачи
        $query = "UPDATE " . $this->table_name . "
                SET status_id = :status_id
                WHERE id = :id AND user_id = :user_id";
        
        try {
            // Подготовка запроса
            $stmt = $this->conn->prepare($query);
            
            // Привязка значений
            $stmt->bindParam(":status_id", $this->status_id, PDO::PARAM_INT);
            $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
            
            // Выполнение запроса
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Ошибка обновления статуса задачи: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Обновляет статус просроченных задач
     * 
     * @return bool Результат операции
     */
    public function updateOverdueTasks() {
        // Проверка user_id
        if (empty($this->user_id) || !is_numeric($this->user_id)) {
            return false;
        }

        // Запрос на обновление статуса просроченных задач для текущего пользователя
        $query = "UPDATE " . $this->table_name . "
                SET status_id = 2 
                WHERE status_id = 1 
                AND scheduled_date < NOW()
                AND user_id = :user_id";
        
        try {
            // Подготовка запроса
            $stmt = $this->conn->prepare($query);
            
            // Привязка user_id
            $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
            
            // Выполнение запроса
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Ошибка обновления просроченных задач: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получает количество просроченных задач
     * 
     * @return int Количество просроченных задач
     */
    public function getOverdueTasksCount() {
        // Запрос на подсчет просроченных задач для текущего пользователя
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                WHERE status_id = 2 AND user_id = :user_id";
        
        try {
            // Подготовка запроса
            $stmt = $this->conn->prepare($query);
            
            // Привязка user_id
            $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
            
            // Выполнение запроса
            $stmt->execute();
            
            // Получение результата
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)$row['count'];
        } catch (PDOException $e) {
            error_log("Ошибка получения количества просроченных задач: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Получает задачи в указанном диапазоне дат
     * 
     * @param string $startDate Начальная дата в формате YYYY-MM-DD
     * @param string $endDate Конечная дата в формате YYYY-MM-DD
     * @return array Массив задач
     */
    public function readByDateRange($startDate, $endDate) {
        // Проверка формата дат
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            error_log("Некорректный формат даты: {$startDate} - {$endDate}");
            return [];
        }

        $query = "SELECT t.*, tt.name as type_name, ts.name as status_name, du.name as duration_unit_name,
                    tp.name as priority_name, tp.color as priority_color
                FROM " . $this->table_name . " t
                LEFT JOIN task_types tt ON t.type_id = tt.id
                LEFT JOIN task_statuses ts ON t.status_id = ts.id
                LEFT JOIN duration_units du ON t.duration_unit_id = du.id
                LEFT JOIN task_priorities tp ON t.priority_id = tp.id
                WHERE DATE(t.scheduled_date) BETWEEN :start_date AND :end_date
                    AND t.user_id = :user_id
                ORDER BY t.scheduled_date ASC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":start_date", $startDate);
            $stmt->bindParam(":end_date", $endDate);
            $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $tasks = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tasks[] = $row;
            }
            
            return $tasks;
        } catch (PDOException $e) {
            error_log("Ошибка получения задач по диапазону дат: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Поиск задач по ключевому слову
     * 
     * @param string $keyword Ключевое слово для поиска
     * @return PDOStatement|false Результат запроса или false в случае ошибки
     */
    public function search($keyword) {
        try {
            error_log("Task::search() - Начало метода с ключевым словом: '" . $keyword . "'");
            
            // Проверка на пустой запрос
            if (empty(trim($keyword))) {
                error_log("Task::search() - Пустой поисковый запрос");
                return false;
            }
            
            // Проверка user_id
            if (empty($this->user_id)) {
                error_log("Task::search() - Не установлен user_id");
                return false;
            }
            
            // Очистка ключевого слова
            $keyword = htmlspecialchars(strip_tags($keyword));
            $searchPattern = "%{$keyword}%";
            
            error_log("Task::search() - Поисковый паттерн: '" . $searchPattern . "', user_id: " . $this->user_id);
            
            $query = "SELECT t.*, 
                        tt.name as type_name, 
                        ts.name as status_name, 
                        du.name as duration_unit_name,
                        tp.name as priority_name, 
                        tp.color as priority_color
                    FROM " . $this->table_name . " t
                    LEFT JOIN task_types tt ON t.type_id = tt.id
                    LEFT JOIN task_statuses ts ON t.status_id = ts.id
                    LEFT JOIN duration_units du ON t.duration_unit_id = du.id
                    LEFT JOIN task_priorities tp ON t.priority_id = tp.id
                    WHERE (t.title LIKE :keyword_title 
                        OR t.location LIKE :keyword_location 
                        OR t.comments LIKE :keyword_comments)
                        AND t.user_id = :user_id
                    ORDER BY t.scheduled_date ASC";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindValue(":keyword_title", $searchPattern, PDO::PARAM_STR);
            $stmt->bindValue(":keyword_location", $searchPattern, PDO::PARAM_STR);
            $stmt->bindValue(":keyword_comments", $searchPattern, PDO::PARAM_STR);
            $stmt->bindValue(":user_id", $this->user_id, PDO::PARAM_INT);
        
            error_log("Task::search() - Выполнение SQL с параметрами: keyword=" . $searchPattern . ", user_id=" . $this->user_id);
            
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                error_log("Task::search() - Ошибка выполнения запроса: " . implode(", ", $errorInfo));
                return false;
            }
            
            error_log("Task::search() - Запрос выполнен успешно, найдено результатов: " . $stmt->rowCount());
            return $stmt;
            
        } catch (PDOException $e) {
            error_log("Task::search() - Исключение PDO: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Task::search() - Общее исключение: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Комбинированный поиск задач по ключевому слову, дате, статусу и приоритету
     * 
     * @param string|null $keyword Ключевое слово для поиска
     * @param string|null $date Дата в формате YYYY-MM-DD
     * @param int|null $statusId ID статуса задач
     * @param int|null $priorityId ID приоритета задач
     * @return PDOStatement|false Результат запроса
     */
    public function combinedSearch($keyword = null, $date = null, $statusId = null, $priorityId = null) {
        try {
            error_log("Task::combinedSearch() - Начало метода с параметрами: keyword='{$keyword}', date='{$date}', statusId='{$statusId}', priorityId='{$priorityId}'");
            
            // Проверка и восстановление user_id из сессии, если не установлен
            if (empty($this->user_id)) {
                error_log("Task::combinedSearch() - Не установлен user_id, пытаемся восстановить из сессии");
                
                // Убедимся, что сессия запущена
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                if (isset($_SESSION['user_id'])) {
                    $this->user_id = $_SESSION['user_id'];
                    error_log("Task::combinedSearch() - user_id восстановлен из сессии: " . $this->user_id);
                } else {
                    error_log("Task::combinedSearch() - Невозможно восстановить user_id из сессии");
                    return false;
                }
            }
            
            // Создаем базовый запрос
            $query = "SELECT t.*, 
                        tt.name as type_name, 
                        ts.name as status_name, 
                        du.name as duration_unit_name,
                        tp.name as priority_name, 
                        tp.color as priority_color
                    FROM " . $this->table_name . " t
                    LEFT JOIN task_types tt ON t.type_id = tt.id
                    LEFT JOIN task_statuses ts ON t.status_id = ts.id
                    LEFT JOIN duration_units du ON t.duration_unit_id = du.id
                    LEFT JOIN task_priorities tp ON t.priority_id = tp.id
                    WHERE t.user_id = :user_id";
            
            // Добавляем условия в зависимости от параметров
            $params = [];
            $params[':user_id'] = $this->user_id;
            
            // Если указано ключевое слово, добавляем условие для поиска
            if (!empty($keyword)) {
                $keyword = htmlspecialchars(strip_tags($keyword));
                $searchPattern = "%{$keyword}%";
                $query .= " AND (t.title LIKE :keyword_title 
                            OR t.location LIKE :keyword_location 
                            OR t.comments LIKE :keyword_comments)";
                $params[':keyword_title'] = $searchPattern;
                $params[':keyword_location'] = $searchPattern;
                $params[':keyword_comments'] = $searchPattern;
            }
            
            // Если указана дата, добавляем условие для фильтрации по дате
            if (!empty($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $query .= " AND DATE(t.scheduled_date) = :date";
                $params[':date'] = $date;
            }
            
            // Если указан статус, добавляем условие для фильтрации по статусу
            if (!empty($statusId) && is_numeric($statusId)) {
                $query .= " AND t.status_id = :status_id";
                $params[':status_id'] = $statusId;
            }
            
            // Если указан приоритет, добавляем условие для фильтрации по приоритету
            if (!empty($priorityId) && is_numeric($priorityId)) {
                $query .= " AND t.priority_id = :priority_id";
                $params[':priority_id'] = $priorityId;
            }
            
            // Добавляем сортировку
            $query .= " ORDER BY t.scheduled_date ASC";
            
            $stmt = $this->conn->prepare($query);
            
            // Привязываем параметры
            foreach ($params as $param => $value) {
                if ($param === ':user_id' || 
                    ($param === ':status_id' && !empty($statusId)) || 
                    ($param === ':priority_id' && !empty($priorityId))) {
                    $stmt->bindValue($param, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($param, $value, PDO::PARAM_STR);
                }
            }
            
            error_log("Task::combinedSearch() - Выполнение SQL-запроса: " . $query);
            error_log("Task::combinedSearch() - Параметры: " . json_encode($params));
            
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                error_log("Task::combinedSearch() - Ошибка выполнения запроса: " . implode(", ", $errorInfo));
                return false;
            }
            
            error_log("Task::combinedSearch() - Запрос выполнен успешно, найдено результатов: " . $stmt->rowCount());
            return $stmt;
            
        } catch (PDOException $e) {
            error_log("Task::combinedSearch() - Исключение PDO: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Task::combinedSearch() - Общее исключение: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Подсчитывает количество задач пользователя по статусу
     * 
     * @param int $status_id ID статуса
     * @return int Количество задач
     */
    public function countByStatus($status_id) {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                    WHERE user_id = :user_id AND status_id = :status_id";
                    
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
            $stmt->bindParam(':status_id', $status_id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)$row['count'];
        } catch (PDOException $e) {
            error_log("Ошибка при подсчете задач по статусу: " . $e->getMessage());
            return 0;
        }
    }

    private function setLastQuery($query) {
        $this->lastQuery = $query;
    }

    public function getLastQuery() {
        return $this->lastQuery;
    }

    /**
     * Получает задачи, которые нужно уведомить в Telegram
     * 
     * @return PDOStatement|false Результат запроса
     */
    public function getUpcomingTasksForNotification() {
        $now = date('Y-m-d H:i:s');

        error_log("Текущее время проверки уведомления: " . $now);

        $query = "SELECT t.*, 
                tt.name as type_name, 
                ts.name as status_name, 
                tu.telegram_chat_id
            FROM " . $this->table_name . " t
            JOIN telegram_users tu ON t.user_id = tu.user_id
            LEFT JOIN task_types tt ON t.type_id = tt.id
            LEFT JOIN task_statuses ts ON t.status_id = ts.id
            WHERE t.status_id = 1 
                AND t.notification_time IS NOT NULL
                AND tu.is_active = 1
                AND now() BETWEEN DATE_SUB(t.scheduled_date, INTERVAL t.notification_time MINUTE) AND t.scheduled_date
                AND NOT EXISTS (
                    SELECT 1 FROM notifications n 
                    WHERE n.task_id = t.id AND n.notification_type = 'telegram'
                )";
                
        $this->setLastQuery($query);

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            error_log("Найдено " . $stmt->rowCount() . " задач для уведомлений");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $taskTime = strtotime($row['scheduled_date']);
                $notificationTime = date('Y-m-d H:i:s', strtotime("-{$row['notification_time']} minutes", $taskTime));

                error_log("ID задачи: " . $row['id'] . 
                        ", Заголовок: " . $row['title'] . 
                        ", Время: " . $row['scheduled_date'] . 
                        ", Время уведомления: " . $notificationTime);
            }
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Ошибка получения задач для уведомлений: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Сбрасывает кэш справочников
     */
    public static function resetCache() {
        self::$cache = [
            'types' => null,
            'statuses' => null,
            'duration_units' => null,
            'priorities' => null
        ];
    }
}