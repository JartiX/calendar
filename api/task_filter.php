<?php
/**
 * Обработчик AJAX-запроса для фильтрации задач по дате
 * 
 * Принимает параметры date и опционально status_context для фильтрации
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

// Устанавливаем заголовок для возврата JSON
header('Content-Type: application/json');

// Проверка авторизации
if (!AuthHelper::isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Пользователь не авторизован'
    ]);
    exit;
}

// Проверка параметров
if (!isset($_GET['date']) || !ValidationHelper::validateDate($_GET['date'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Не указана корректная дата для фильтрации'
    ]);
    exit;
}

// Подключение к базе данных
$database = new Database();
$db = $database->getConnection();

// Инициализация модели задач
$task = new Task($db);
$task->user_id = $_SESSION['user_id'];

// Получаем дату и статус для фильтрации
$date = ValidationHelper::sanitizeString($_GET['date']);
$statusContext = isset($_GET['status_context']) && $_GET['status_context'] !== '' 
    ? ValidationHelper::sanitizeInt($_GET['status_context']) 
    : null;

// Записываем в журнал
error_log("Фильтрация задач по дате: $date, статус: " . ($statusContext !== null ? $statusContext : 'все'));

// Вызываем метод для получения задач по дате с учетом статуса
$stmt = $task->readByDate($date, $statusContext);

// Формируем массив задач
$tasks = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tasks[] = $row;
}

// Записываем в журнал
error_log("Найдено " . count($tasks) . " задач согласно фильтру");

// Возвращаем результат
echo json_encode([
    'success' => true,
    'date' => $date,
    'status_context' => $statusContext,
    'tasks' => $tasks
]);
?>