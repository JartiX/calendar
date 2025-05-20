<?php
/**
 * Скрипт для отправки уведомлений в Telegram
 * Запускается через cron каждые несколько минут
 */

require_once 'config/settings.php';
require_once 'config/database.php';
require_once 'models/task.php';
require_once 'models/telegram_user.php';
require_once 'models/notification.php';
require_once 'config/tg_credentials.php';

$database = new Database();
$db = $database->getConnection();

$task = new Task($db);
$notification = new Notification($db);

$upcomingTasks = $task->getUpcomingTasksForNotification();

if ($upcomingTasks && $upcomingTasks->rowCount() > 0) {
    error_log("Найдено " . $upcomingTasks->rowCount() . " задач для уведомления");
    while ($row = $upcomingTasks->fetch(PDO::FETCH_ASSOC)) {
        // Отправка уведомления
        $chatId = $row['telegram_chat_id'];
        
        error_log("Подготовка уведомления для задачи с ID: " . $row['id'] . " в чат ID: " . $chatId);
    
        $taskTime = date('H:i', strtotime($row['scheduled_date']));
        $minutesToStart = $row['notification_time'];
        
        $text = "🔔 <b>Напоминание о задаче</b>\n\n";
        $text .= "📌 <b>{$row['title']}</b>\n";
        
        if (!empty($row['type_name'])) {
            $text .= "Тип: {$row['type_name']}\n";
        }
        
        if (!empty($row['location'])) {
            $text .= "Место: {$row['location']}\n";
        }
        
        $text .= "Время: {$taskTime}\n";
        
        if ($minutesToStart == 60) {
            $text .= "До начала: 1 час";
        } else if ($minutesToStart < 60) {
            $text .= "До начала: {$minutesToStart} мин.";
        } else {
            $hours = floor($minutesToStart / 60);
            $minutes = $minutesToStart % 60;
            $text .= "До начала: {$hours} ч. {$minutes} мин.";
        }
        
        error_log("Отправка сообщения: " . $text);
        
        // Отправка сообщения в Telegram
        if (sendTelegramMessage($chatId, $text)) {
            $notification->task_id = $row['id'];
            $notification->notification_type = 'telegram';
            $notification->sent_at = date('Y-m-d H:i:s');
            
            if ($notification->create()) {
                error_log("Уведомление создано для задачи ID " . $row['id']);
            } else {
                error_log("Ошибка при создании уведомления для задачи ID " . $row['id']);
            }
            
            error_log("Уведомление отправлено для задачи ID {$row['id']} в чат ID {$chatId}");
        } else {
            error_log("Ошибка при отправке уведомления задачи ID {$row['id']} в чат ID {$chatId}");
        }
    }
} else {
    error_log("Нет задач для уведомления");
    error_log("SQL запрос: " . $task->getLastQuery());
}

/**
 * Отправляет сообщение в Telegram используя cURL вместо file_get_contents
 *
 * @param int $chatId ID чата
 * @param string $text Текст сообщения
 * @return bool Результат отправки
 */
function sendTelegramMessage($chatId, $text) {
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
    
    error_log("Отправка по URL: " . $url);
    error_log("С данными: " . json_encode($data));
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // SSL опции для решения проблем с сертификатами
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($error) {
        error_log("cURL Error: " . $error);
        curl_close($ch);
        return false;
    }
    
    error_log("Код ответа Telegram API: " . $httpCode);
    error_log("Ответ Telegram API: " . $response);
    
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    return !empty($result['ok']) && $result['ok'] === true;
}
?>