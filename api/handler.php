<?php
/**
 * Обработчик AJAX-запросов для управления задачами
 * 
 * Обрабатывает операции изменения статуса и проверки просроченных задач
 */
require_once '../config/database.php';
require_once '../models/task.php';
require_once '../helpers/auth_helper.php';
require_once '../helpers/validation_helper.php';
require_once '../config/settings.php'; // Добавлено для загрузки настроек сессии

// Настраиваем имя сессии перед ее запуском
session_name(SESSION_NAME);

// Начинаем сессию для доступа к данным пользователя
session_start();

// Проверка запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $task = new Task($db);
    
    // Установка ID пользователя из сессии для безопасности
    if(isset($_SESSION['user_id'])) {
        $task->user_id = $_SESSION['user_id'];
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Пользователь не авторизован'
        ]);
        exit;
    }
    
    // Обработка различных типов запросов
    switch ($_POST['action']) {
        case 'check_overdue':
            // Метод для проверки просроченных задач
            $result = $task->updateOverdueTasks();
            
            // Возвращаем результат в формате JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, // Всегда успешно, даже если нет обновления
                'message' => $result ? 'Статусы задач успешно обновлены' : 'Нет задач для обновления',
                'overdue_count' => $task->getOverdueTasksCount(),
                'user_id' => $_SESSION['user_id'] // Добавляем для отладки
            ]);
            break;
        
        case 'change_status':
            // Валидация входных данных
            if (!ValidationHelper::validateRequired($_POST, ['task_id', 'status_id'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Не указаны необходимые параметры'
                ]);
                exit;
            }
            
            $task->id = ValidationHelper::sanitizeInt($_POST['task_id']);
            $statusId = ValidationHelper::sanitizeInt($_POST['status_id']);
            
            // Проверка существования статуса
            if (!in_array($statusId, [1, 2, 3])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Недопустимый статус задачи'
                ]);
                exit;
            }
            
            $taskData = $task->readOne();
            
            // Проверка, что задача существует и принадлежит текущему пользователю
            if ($taskData && $taskData['user_id'] == $_SESSION['user_id']) {
                // Устанавливаем новый статус
                $task->status_id = $statusId;
                
                // Записываем в журнал
                error_log("Изменение статуса задачи ID: " . $task->id . " на: " . $task->status_id . " пользователем: " . $_SESSION['user_id']);
                
                $result = $task->updateStatus();
                
                $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : '';

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $result,
                    'message' => $result ? 'Статус задачи успешно обновлен' : 'Ошибка при обновлении статуса',
                    'redirect_to' => $redirect_to
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'У вас нет прав для изменения этой задачи или задача не найдена'
                ]);
            }
            break;
            
        case 'mark_as_completed':
            if (isset($_POST['task_id'])) {
                $_POST['status_id'] = 3; // Статус "Выполнено"
                $_POST['action'] = 'change_status';
                
                require __FILE__;
                exit;
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Не указан ID задачи'
                ]);
            }
            break;
            
        default:
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Неизвестное действие'
            ]);
            break;
    }
} else {
    // Если запрос не POST или не содержит параметр action
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Неверный запрос'
    ]);
}
?>